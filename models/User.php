<?php
require_once(__DIR__ . '/../config/database.php');

class User {
    private $conn;
    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $status;
    public $phone;
    public $address;
    public $avatar;
    public $created_at;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Get all users with filters
    public function getAllUsers($role = '', $status = '', $search = '') {
        try {
            $conditions = [];
            $params = [];
            
            $query = "SELECT u.*, 
                             COUNT(DISTINCT o.id) as order_count,
                             COALESCE(SUM(o.total_amount), 0) as total_spent
                      FROM users u
                      LEFT JOIN orders o ON u.id = o.user_id";

            // Add role filter
            if (!empty($role)) {
                $conditions[] = "u.role = :role";
                $params[':role'] = $role;
            }

            // Add status filter
            if (!empty($status)) {
                $conditions[] = "u.status = :status";
                $params[':status'] = $status;
            }

            // Add search filter
            if (!empty($search)) {
                $conditions[] = "(u.name LIKE :search OR u.email LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            // Add WHERE clause if there are conditions
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            // Add GROUP BY and ORDER BY
            $query .= " GROUP BY u.id ORDER BY u.created_at DESC";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error getting users: " . $e->getMessage());
            return false;
        }
    }

    // Create new user
    public function create() {
        try {
            // Begin transaction
            $this->conn->beginTransaction();

            // Check if email exists
            if ($this->emailExists($this->email)) {
                error_log("Email already exists: " . $this->email);
                return false;
            }

            $query = "INSERT INTO users (name, email, password, role, status, phone, address, avatar) 
                     VALUES (:name, :email, :password, :role, :status, :phone, :address, :avatar)";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            $params = [
                ':name' => $this->name,
                ':email' => $this->email,
                ':password' => $this->password,
                ':role' => $this->role,
                ':status' => $this->status ?? 'active',
                ':phone' => $this->phone,
                ':address' => $this->address,
                ':avatar' => $this->avatar
            ];

            // Debug log
            error_log("Creating user with params: " . print_r($params, true));

            // Execute the query
            $result = $stmt->execute($params);

            if ($result) {
                $this->id = $this->conn->lastInsertId();
                $this->conn->commit();
                error_log("User created successfully with ID: " . $this->id);
                return true;
            } else {
                error_log("Failed to create user. PDO Error: " . print_r($stmt->errorInfo(), true));
                $this->conn->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            error_log("Database error in create user: " . $e->getMessage());
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        } catch (Exception $e) {
            error_log("General error in create user: " . $e->getMessage());
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    // Update existing user
    public function update() {
        try {
            $sql = "UPDATE users 
                    SET name = :name,
                        username = :username,
                        email = :email,
                        role = :role,
                        status = :status,
                        phone = :phone,
                        address = :address";
            
            // Add password to update if it's set
            if (isset($this->password)) {
                $sql .= ", password = :password";
            }
            
            // Add avatar to update if it's set
            if (isset($this->avatar)) {
                $sql .= ", avatar = :avatar";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            
            $params = [
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
                'role' => $this->role,
                'status' => $this->status,
                'phone' => $this->phone,
                'address' => $this->address,
                'id' => $this->id
            ];
            
            // Add password to params if it's set
            if (isset($this->password)) {
                $params['password'] = $this->password;
            }
            
            // Add avatar to params if it's set
            if (isset($this->avatar)) {
                $params['avatar'] = $this->avatar;
            }
            
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }

    // Read one user
    public function readOne() {
        try {
            $sql = "SELECT id, name, username, email, role, status, phone, address, avatar, created_at 
                    FROM users 
                    WHERE id = :id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['id' => $this->id]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                // Bind the data to object properties
                $this->name = $row['name'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                $this->status = $row['status'];
                $this->phone = $row['phone'] ?? '';
                $this->address = $row['address'] ?? '';
                $this->avatar = $row['avatar'];
                $this->created_at = $row['created_at'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Error reading user: " . $e->getMessage());
        }
    }

    // Check if email exists
    public function emailExists($email, $exclude_id = null) {
        try {
            $query = "SELECT id FROM users WHERE email = :email";
            if ($exclude_id) {
                $query .= " AND id != :exclude_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id);
            }
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error checking email: " . $e->getMessage());
            return false;
        }
    }

    // Update user status
    public function updateStatus($user_id, $status) {
        try {
            $query = "UPDATE users SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $user_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating user status: " . $e->getMessage());
            return false;
        }
    }

    public function login($email, $password) {
        try {
            $query = "SELECT * FROM users WHERE email = :email AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':email' => $email]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                return true;
            }
            
            return false;
            
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id) {
        try {
            $query = "SELECT u.*, 
                        COUNT(DISTINCT o.id) as order_count,
                        COALESCE(SUM(o.total_amount), 0) as total_spent
                     FROM users u
                     LEFT JOIN orders o ON u.id = o.user_id
                     WHERE u.id = :id
                     GROUP BY u.id";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log("Error getting user details: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser($data) {
        $fields = [];
        $values = [];
        
        foreach (['name', 'email', 'phone', 'avatar', 'password'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        // Add ID to values array
        $values[] = $data['id'];
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($values);
        } catch(PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function verifyPassword($userId, $password) {
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['password']) {
            // For passwords hashed with MD5 (legacy)
            if (strlen($user['password']) === 32) {
                return md5($password) === $user['password'];
            }
            // For passwords hashed with password_hash (newer method)
            return password_verify($password, $user['password']);
        }
        return false;
    }

    public function updatePassword($userId, $newPassword) {
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$hashedPassword, $userId]);
        } catch(PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function createUser($data) {
        $query = "INSERT INTO users (name, username, email, password, phone, role, status, avatar) 
                  VALUES (:name, :username, :email, :password, :phone, :role, :status, :avatar)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(":name", $data['name']);
            $stmt->bindParam(":username", $data['username']);
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":password", $data['password']);
            $stmt->bindParam(":phone", $data['phone']);
            $stmt->bindParam(":role", $data['role']);
            $stmt->bindParam(":status", $data['status']);
            $stmt->bindParam(":avatar", $data['avatar']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
            
        } catch(PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function usernameExists($username) {
        $query = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    /**
     * Get total number of users
     * @return int
     */
    public function getUserCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM users";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting user count: " . $e->getMessage());
            return 0;
        }
    }

    public function deleteUser($user_id) {
        try {
            $sql = "DELETE FROM users WHERE id = :user_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute(['user_id' => $user_id]);
        } catch (PDOException $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }

    public function addUser($data) {
        try {
            $sql = "INSERT INTO users (name, username, email, password, role, status, created_at) 
                    VALUES (:name, :username, :email, :password, :role, :status, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                'name' => $data['name'],
                'username' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'status' => $data['status']
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error adding user: " . $e->getMessage());
        }
    }
} 