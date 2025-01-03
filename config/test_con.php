<?php
require_once __DIR__ . '/../config/config.php';
require_once BASE_PATH . '/config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if($db) {
        echo "Database connection successful!";
    } else {
        echo "Database connection failed!";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>