<?php
require_once(__DIR__ . '/../config/database.php');

class Category {
    private $conn;
    public $id;
    public $name;
    public $slug;
    public $description;
    public $image;
    public $status;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Create new category
    public function create() {
        try {
            $this->conn->beginTransaction();

            if ($this->existsByName($this->name)) {
                return false;
            }

            $query = "INSERT INTO categories (name, slug, description, image, status) 
                     VALUES (:name, :slug, :description, :image, :status)";

            $stmt = $this->conn->prepare($query);

            // Clean and bind data
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->slug = $this->generateSlug($this->name);
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->status = htmlspecialchars(strip_tags($this->status));

            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':image', $this->image);
            $stmt->bindParam(':status', $this->status);

            if ($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                $this->conn->commit();
                return true;
            }

            $this->conn->rollBack();
            return false;
        } catch (PDOException $e) {
            error_log("Create category error: " . $e->getMessage());
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    // Read single category
    public function read($id) {
        try {
            $query = "SELECT * FROM categories WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->slug = $row['slug'];
                $this->description = $row['description'];
                $this->image = $row['image'];
                $this->status = $row['status'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Read category error: " . $e->getMessage());
            return false;
        }
    }

    // Update category
    public function update() {
        try {
            $this->conn->beginTransaction();

            // Check if name exists for other categories
            if ($this->existsByName($this->name, $this->id)) {
                return false;
            }

            $query = "UPDATE categories 
                     SET name = :name, 
                         slug = :slug,
                         description = :description,
                         status = :status";

            // Add image to update only if it's set
            if (!empty($this->image)) {
                $query .= ", image = :image";
            }

            $query .= " WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            // Clean and bind data
            $this->name = htmlspecialchars(strip_tags($this->name));
            $this->slug = $this->generateSlug($this->name);
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->status = htmlspecialchars(strip_tags($this->status));

            $stmt->bindParam(':name', $this->name);
            $stmt->bindParam(':slug', $this->slug);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':id', $this->id);

            if (!empty($this->image)) {
                $stmt->bindParam(':image', $this->image);
            }

            if ($stmt->execute()) {
                $this->conn->commit();
                return true;
            }

            $this->conn->rollBack();
            return false;
        } catch (PDOException $e) {
            error_log("Update category error: " . $e->getMessage());
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    // Delete category
    public function delete($id) {
        try {
            $this->conn->beginTransaction();

            // First, delete or update related products
            $query = "DELETE FROM products WHERE category_id = :category_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $id);
            $stmt->execute();

            // Then delete the category
            $query = "DELETE FROM categories WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                $this->conn->commit();
                return true;
            }

            $this->conn->rollBack();
            return false;
        } catch (PDOException $e) {
            error_log("Delete category error: " . $e->getMessage());
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    // Get all categories with optional filters
    public function getAllCategories($status = '', $search = '') {
        try {
            $conditions = [];
            $params = [];
            
            $query = "SELECT * FROM categories";

            if (!empty($status)) {
                $conditions[] = "status = :status";
                $params[':status'] = $status;
            }

            if (!empty($search)) {
                $conditions[] = "(name LIKE :search OR description LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY name ASC";

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Get categories error: " . $e->getMessage());
            return false;
        }
    }

    // Check if category exists by name
    public function existsByName($name, $exclude_id = null) {
        try {
            $query = "SELECT id FROM categories WHERE name = :name";
            if ($exclude_id) {
                $query .= " AND id != :exclude_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':name', $name);
            if ($exclude_id) {
                $stmt->bindParam(':exclude_id', $exclude_id);
            }
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Check category existence error: " . $e->getMessage());
            return false;
        }
    }

    // Generate slug from name
    public function generateSlug($name) {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        // Remove multiple hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        // Remove leading/trailing hyphens
        return trim($slug, '-');
    }

    // Update category status
    public function updateStatus($id, $status) {
        try {
            $query = "UPDATE categories SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Update category status error: " . $e->getMessage());
            return false;
        }
    }

    // Get product count for a category
    public function getProductCount($category_id) {
        try {
            $query = "SELECT COUNT(*) FROM products WHERE category_id = :category_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting product count: " . $e->getMessage());
            return 0;
        }
    }

    public function createCategory($data) {
        $query = "INSERT INTO categories 
                  (name, slug, description, status, image) 
                  VALUES 
                  (:name, :slug, :description, :status, :image)";
        
        try {
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            $stmt->bindParam(":name", $data['name']);
            $stmt->bindParam(":slug", $data['slug']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":status", $data['status']);
            $stmt->bindParam(":image", $data['image']);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
            
        } catch(PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCategoryById($id) {
        $query = "SELECT * FROM categories WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCategory($data) {
        $fields = [];
        $values = [];
        
        foreach (['name', 'slug', 'description', 'status', 'image'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        // Add ID to values array
        $values[] = $data['id'];
        
        $query = "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            return $stmt->execute($values);
        } catch(PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Get total number of categories
     * @return int
     */
    public function getCategoryCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM categories";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Error getting category count: " . $e->getMessage());
            return 0;
        }
    }
} 