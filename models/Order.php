<?php
require_once(__DIR__ . '/../config/database.php');

class Order {
    protected $conn;
    public $id;
    public $user_id;
    public $total_amount;
    public $status;
    public $payment_status;
    public $created_at;

    public function __construct() {
        try {
            require_once(__DIR__ . '/../config/Database.php');
            $database = new Database();
            $this->conn = $database->getConnection();
            
            if (!$this->conn) {
                throw new Exception("Database connection failed");
            }
            
            // Set error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Add transaction_id column
            $this->addTransactionIdColumn();
            
        } catch(Exception $e) {
            error_log("Order Model Constructor Error: " . $e->getMessage());
            throw new Exception("Failed to initialize Order model");
        }
    }

    private function checkAndCreateTables() {
        try {
            // Check if orders table exists
            $tableExists = $this->conn->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
            
            if (!$tableExists) {
                // Create orders table
                $this->conn->exec("CREATE TABLE IF NOT EXISTS orders (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT,
                    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
                    shipping_address TEXT,
                    payment_method VARCHAR(50),
                    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Create order_items table
                $this->conn->exec("CREATE TABLE IF NOT EXISTS order_items (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    order_id INT,
                    product_id INT,
                    quantity INT NOT NULL DEFAULT 1,
                    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

                // Insert sample data
                $this->insertSampleData();
            }
        } catch(PDOException $e) {
            error_log("Table Creation Error: " . $e->getMessage());
            throw new Exception("Failed to create required tables");
        }
    }

    private function insertSampleData() {
        try {
            // Insert sample orders
            $sampleOrders = [
                [
                    'total_amount' => 1500.00,
                    'status' => 'pending',
                    'shipping_address' => '123 Test Street, Test City',
                    'payment_method' => 'cod'
                ],
                [
                    'total_amount' => 2500.00,
                    'status' => 'completed',
                    'shipping_address' => '456 Sample Road, Sample City',
                    'payment_method' => 'online'
                ]
            ];

            foreach ($sampleOrders as $order) {
                $stmt = $this->conn->prepare("
                    INSERT INTO orders (total_amount, status, shipping_address, payment_method) 
                    VALUES (:total_amount, :status, :shipping_address, :payment_method)
                ");
                $stmt->execute($order);
            }
        } catch(PDOException $e) {
            error_log("Sample Data Insertion Error: " . $e->getMessage());
        }
    }

    // Get all orders with filters
    public function getAllOrders($status = '', $date_from = '', $date_to = '') {
        try {
            $conditions = [];
            $params = [];
            
            $query = "SELECT o.*, u.name as customer_name 
                     FROM orders o 
                     LEFT JOIN users u ON o.user_id = u.id";

            // Add status filter
            if (!empty($status)) {
                $conditions[] = "o.status = :status";
                $params[':status'] = $status;
            }

            // Add date range filter
            if (!empty($date_from) && !empty($date_to)) {
                $conditions[] = "DATE(o.created_at) BETWEEN :date_from AND :date_to";
                $params[':date_from'] = $date_from;
                $params[':date_to'] = $date_to;
            }

            // Add WHERE clause if there are conditions
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            // Add order by
            $query .= " ORDER BY o.created_at DESC";

            $stmt = $this->conn->prepare($query);

            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error getting orders: " . $e->getMessage());
            return false;
        }
    }

    // Update order status
    public function updateStatus($order_id, $status) {
        try {
            $query = "UPDATE orders SET status = :status WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $order_id);
            
            if ($stmt->execute()) {
                // Add to order history
                $query = "INSERT INTO order_history (order_id, status, created_by) VALUES (:order_id, :status, :created_by)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':order_id', $order_id);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':created_by', $_SESSION['user_id']);
                $stmt->execute();
                
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error updating order status: " . $e->getMessage());
            return false;
        }
    }

    // Get order statistics for dashboard
    public function getStats() {
        try {
            $stats = [
                'total_orders' => 0,
                'total_revenue' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0
            ];

            $query = "SELECT 
                        COUNT(*) as total_orders,
                        COALESCE(SUM(total_amount), 0) as total_revenue,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders
                     FROM orders";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stats = $row;
            }

            return $stats;
        } catch (PDOException $e) {
            error_log("Error getting order stats: " . $e->getMessage());
            return $stats;
        }
    }

    // Get recent orders
    public function getRecentOrders($limit = 5) {
        try {
            // First, verify the tables exist
            $this->verifyAndCreateTables();

            // Simpler query without joins initially
            $query = "SELECT o.*, 
                        COALESCE((SELECT name FROM users WHERE id = o.user_id), 'Guest') as customer_name
                     FROM orders o 
                     ORDER BY o.created_at DESC 
                     LIMIT ?";

            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("Failed to prepare statement");
                throw new Exception("Query preparation failed");
            }

            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();

            error_log("Successfully fetched orders. Row count: " . $stmt->rowCount());
            
            return $stmt;

        } catch(PDOException $e) {
            error_log("PDO Error in getRecentOrders: " . $e->getMessage());
            error_log("SQL State: " . $e->errorInfo[0]);
            return null;
        } catch(Exception $e) {
            error_log("General Error in getRecentOrders: " . $e->getMessage());
            return null;
        }
    }

    private function verifyAndCreateTables() {
        try {
            // Check if orders table exists
            $result = $this->conn->query("SHOW TABLES LIKE 'orders'");
            if ($result->rowCount() === 0) {
                // Create orders table with transaction_id field
                $this->conn->exec("CREATE TABLE IF NOT EXISTS orders (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NULL,
                    transaction_id VARCHAR(100) UNIQUE,
                    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    status VARCHAR(20) DEFAULT 'pending',
                    shipping_address TEXT,
                    payment_method VARCHAR(50),
                    payment_status VARCHAR(20) DEFAULT 'pending',
                    customer_name VARCHAR(100),
                    customer_email VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            } else {
                // Add transaction_id column if it doesn't exist
                $result = $this->conn->query("SHOW COLUMNS FROM orders LIKE 'transaction_id'");
                if ($result->rowCount() === 0) {
                    $this->conn->exec("ALTER TABLE orders 
                        ADD COLUMN transaction_id VARCHAR(100) UNIQUE AFTER user_id,
                        ADD COLUMN customer_name VARCHAR(100) AFTER payment_status,
                        ADD COLUMN customer_email VARCHAR(100) AFTER customer_name");
                }
            }

            return true;
        } catch(PDOException $e) {
            error_log("Table verification error: " . $e->getMessage());
            return false;
        }
    }

    // Get user's order count
    public function getUserOrderCount($user_id) {
        try {
            $query = "SELECT COUNT(*) FROM orders WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting user order count: " . $e->getMessage());
            return 0;
        }
    }

    // Get user's total spent
    public function getUserTotalSpent($user_id) {
        try {
            $query = "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting user total spent: " . $e->getMessage());
            return 0;
        }
    }

    // Get user's last order
    public function getUserLastOrder($user_id) {
        try {
            $query = "SELECT created_at FROM orders WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting user last order: " . $e->getMessage());
            return null;
        }
    }

    // Get user's average order value
    public function getUserAverageOrderValue($user_id) {
        try {
            $query = "SELECT COALESCE(AVG(total_amount), 0) FROM orders WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting user average order value: " . $e->getMessage());
            return 0;
        }
    }

    // Get user's orders
    public function getUserOrders($user_id, $limit = null) {
        try {
            $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
            if ($limit) {
                $query .= " LIMIT :limit";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            if ($limit) {
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            }
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error getting user orders: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderCount($status = null) {
        try {
            $query = "SELECT COUNT(*) FROM orders";
            $params = [];
            
            if ($status) {
                $query .= " WHERE status = ?";
                $params[] = $status;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
        } catch(PDOException $e) {
            error_log("Error getting order count: " . $e->getMessage());
            return 0;
        }
    }

    public function createOrder($orderData, $orderItems) {
        try {
            // Insert order with transaction_id
            $query = "INSERT INTO orders (
                user_id, transaction_id, total_amount, shipping_address, 
                payment_method, payment_status, status, customer_name, customer_email
            ) VALUES (
                :user_id, :transaction_id, :total_amount, :shipping_address, 
                :payment_method, :payment_status, :status, :customer_name, :customer_email
            )";
                     
            $stmt = $this->conn->prepare($query);
            
            $stmt->execute([
                ':user_id' => $orderData['user_id'],
                ':transaction_id' => $orderData['transaction_id'],
                ':total_amount' => $orderData['total_amount'],
                ':shipping_address' => $orderData['shipping_address'],
                ':payment_method' => $orderData['payment_method'],
                ':payment_status' => $orderData['payment_status'],
                ':status' => $orderData['status'],
                ':customer_name' => $orderData['customer_name'],
                ':customer_email' => $orderData['customer_email']
            ]);
            
            $orderId = $this->conn->lastInsertId();
            
            // Insert order items
            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                     VALUES (:order_id, :product_id, :quantity, :price)";
                     
            $stmt = $this->conn->prepare($query);
            
            foreach ($orderItems as $item) {
                $stmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price']
                ]);
            }
            
            return $orderId;
            
        } catch(PDOException $e) {
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }

    public function addOrderItem($orderId, $productId, $quantity, $price) {
        try {
            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                     VALUES (?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$orderId, $productId, $quantity, $price]);
        } catch(PDOException $e) {
            error_log("Error adding order item: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderById($id) {
        try {
            $query = "SELECT o.*, u.name as customer_name, u.email as customer_email
                     FROM orders o
                     LEFT JOIN users u ON o.user_id = u.id
                     WHERE o.id = ?";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error getting order details: " . $e->getMessage());
            return null;
        }
    }

    public function getOrderItems($orderId) {
        try {
            $query = "SELECT oi.*, p.name as product_name, p.sku,
                            (SELECT image_path FROM product_images 
                             WHERE product_id = p.id AND is_primary = 1 
                             LIMIT 1) as primary_image
                     FROM order_items oi
                     LEFT JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = ?";
                     
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$orderId]);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Error getting order items: " . $e->getMessage());
            return null;
        }
    }

    public function updateOrder($orderId, $status, $paymentStatus) {
        try {
            $query = "UPDATE orders 
                     SET status = ?, payment_status = ?, updated_at = CURRENT_TIMESTAMP 
                     WHERE id = ?";
                     
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$status, $paymentStatus, $orderId]);
        } catch(PDOException $e) {
            error_log("Error updating order: " . $e->getMessage());
            return false;
        }
    }

    // Add this method to verify the database setup
    public function verifyDatabaseSetup() {
        try {
            // Check if orders table exists
            $tables = $this->conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            error_log("Existing tables: " . print_r($tables, true));
            
            if (!in_array('orders', $tables)) {
                error_log("Orders table does not exist");
                return false;
            }
            
            // Check table structure
            $columns = $this->conn->query("DESCRIBE orders")->fetchAll(PDO::FETCH_COLUMN);
            error_log("Orders table columns: " . print_r($columns, true));
            
            return true;
        } catch(Exception $e) {
            error_log("Database verification error: " . $e->getMessage());
            return false;
        }
    }

    // Add this method to initialize the database
    public function initializeDatabase() {
        try {
            // Create orders table if not exists
            $this->conn->exec("CREATE TABLE IF NOT EXISTS orders (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(20) DEFAULT 'pending',
                shipping_address TEXT,
                payment_method VARCHAR(50),
                payment_status VARCHAR(20) DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )");

            // Check if we have any orders
            $count = $this->conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
            
            if ($count == 0) {
                // Get a valid user ID from the users table
                $userId = $this->conn->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1")->fetchColumn();
                
                if ($userId) {
                    // Insert test orders with actual user ID
                    $stmt = $this->conn->prepare("
                        INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) 
                        VALUES 
                        (?, 1500.00, 'pending', '123 Test Street, Test City', 'cod'),
                        (?, 2500.00, 'completed', '456 Sample Road, Sample City', 'online')
                    ");
                    $stmt->execute([$userId, $userId]);
                    error_log("Test orders inserted with user_id: " . $userId);
                } else {
                    // Insert test orders without user ID (guest orders)
                    $this->conn->exec("
                        INSERT INTO orders (total_amount, status, shipping_address, payment_method) 
                        VALUES 
                        (1500.00, 'pending', '123 Test Street, Test City', 'cod'),
                        (2500.00, 'completed', '456 Sample Road, Sample City', 'online')
                    ");
                    error_log("Test orders inserted as guest orders");
                }
            }

            return true;
        } catch(Exception $e) {
            error_log("Database initialization error: " . $e->getMessage());
            return false;
        }
    }

    // Add method to check if transaction exists
    public function transactionExists($transaction_id) {
        try {
            $query = "SELECT COUNT(*) FROM orders WHERE transaction_id = :transaction_id";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':transaction_id' => $transaction_id]);
            return $stmt->fetchColumn() > 0;
        } catch(PDOException $e) {
            error_log("Error checking transaction: " . $e->getMessage());
            return false;
        }
    }

    // Add this method to add transaction_id column
    public function addTransactionIdColumn() {
        try {
            // Check if transaction_id column exists
            $result = $this->conn->query("SHOW COLUMNS FROM orders LIKE 'transaction_id'");
            
            if ($result->rowCount() === 0) {
                // Add transaction_id column if it doesn't exist
                $this->conn->exec("ALTER TABLE orders 
                    ADD COLUMN transaction_id VARCHAR(100) UNIQUE AFTER user_id");
                    
                error_log("Successfully added transaction_id column to orders table");
                return true;
            }
            
            error_log("transaction_id column already exists");
            return true;
            
        } catch(PDOException $e) {
            error_log("Error adding transaction_id column: " . $e->getMessage());
            return false;
        }
    }

    // Add getter method for database connection
    public function getConnection() {
        return $this->conn;
    }

    // Add method to start transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // Add method to commit transaction
    public function commitTransaction() {
        return $this->conn->commit();
    }

    // Add method to rollback transaction
    public function rollbackTransaction() {
        return $this->conn->rollBack();
    }
} 