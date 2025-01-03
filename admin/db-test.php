<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test database connection
try {
    $conn = new PDO("mysql:host=localhost;dbname=ecommerce_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connected successfully<br>";

    // Check if users table exists
    $tables = $conn->query("SHOW TABLES LIKE 'users'")->rowCount();
    if($tables == 0) {
        echo "Creating users table...<br>";
        
        // Create users table
        $sql = "CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $conn->exec($sql);
        echo "✓ Users table created successfully<br>";
    } else {
        echo "✓ Users table exists<br>";
    }

    // Check if admin user exists
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $adminExists = $stmt->fetchColumn();

    if($adminExists == 0) {
        echo "Creating admin user...<br>";
        
        // Create admin user
        $password = password_hash("admin123", PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, email, role, status) 
                VALUES ('admin', :password, 'admin@example.com', 'admin', 'active')";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute(['password' => $password]);
        echo "✓ Admin user created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
    } else {
        echo "✓ Admin user exists<br>";
    }

    // Display all users
    echo "<h3>Current Users:</h3>";
    $users = $conn->query("SELECT id, username, email, role, status FROM users")->fetchAll();
    foreach($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Role: {$user['role']}, Status: {$user['status']}<br>";
    }

} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>