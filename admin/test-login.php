<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once BASE_PATH . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>Database Connection Test:</h3>";
    if($db) {
        echo "✓ Database connected successfully<br>";
        
        // Check if users table exists
        $query = "SHOW TABLES LIKE 'users'";
        $result = $db->query($query);
        if($result->rowCount() > 0) {
            echo "✓ Users table exists<br>";
            
            // Check for admin user
            $query = "SELECT * FROM users WHERE username = 'admin'";
            $stmt = $db->query($query);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                echo "✓ Admin user found<br>";
                echo "User details:<br>";
                echo "ID: " . $user['id'] . "<br>";
                echo "Username: " . $user['username'] . "<br>";
                echo "Role: " . $user['role'] . "<br>";
                echo "Status: " . $user['status'] . "<br>";
            } else {
                echo "✗ Admin user not found<br>";
                
                // Create admin user
                echo "<h3>Creating admin user:</h3>";
                $password = password_hash("admin123", PASSWORD_DEFAULT);
                $query = "INSERT INTO users (username, password, email, role, status) 
                         VALUES ('admin', :password, 'admin@example.com', 'admin', 'active')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $password);
                if($stmt->execute()) {
                    echo "✓ Admin user created successfully<br>";
                    echo "Username: admin<br>";
                    echo "Password: admin123<br>";
                } else {
                    echo "✗ Failed to create admin user<br>";
                }
            }
        } else {
            echo "✗ Users table does not exist<br>";
            
            // Create users table
            echo "<h3>Creating users table:</h3>";
            $query = "CREATE TABLE users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                role ENUM('admin', 'user') DEFAULT 'user',
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if($db->exec($query)) {
                echo "✓ Users table created successfully<br>";
                
                // Create admin user
                $password = password_hash("admin123", PASSWORD_DEFAULT);
                $query = "INSERT INTO users (username, password, email, role, status) 
                         VALUES ('admin', :password, 'admin@example.com', 'admin', 'active')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':password', $password);
                if($stmt->execute()) {
                    echo "✓ Admin user created successfully<br>";
                    echo "Username: admin<br>";
                    echo "Password: admin123<br>";
                } else {
                    echo "✗ Failed to create admin user<br>";
                }
            } else {
                echo "✗ Failed to create users table<br>";
            }
        }
    } else {
        echo "✗ Database connection failed<br>";
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>