<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../../models/Product.php');

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $image_id = $_POST['image_id'] ?? null;
        $product_id = $_POST['product_id'] ?? null;
        
        if (!$image_id || !$product_id) {
            throw new Exception('Missing required parameters');
        }
        
        $product = new Product();
        if ($product->setPrimaryImage($image_id, $product_id)) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Failed to set primary image');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 