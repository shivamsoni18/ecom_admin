<?php
session_start();
require_once(__DIR__ . '/../models/Product.php');

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if product ID is provided
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    $_SESSION['error'] = "Invalid product ID";
    header('Location: products.php');
    exit();
}

try {
    $product = new Product();
    $product_id = (int)$_POST['product_id'];
    
    // Get product images before deletion
    $product_images = $product->getProductImages($product_id);
    
    // Delete product and its images from database
    if ($product->deleteProduct($product_id)) {
        // Delete physical image files
        foreach ($product_images as $image) {
            $file_path = __DIR__ . '/../' . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $_SESSION['success'] = "Product deleted successfully";
    } else {
        throw new Exception("Failed to delete product");
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: products.php');
exit(); 