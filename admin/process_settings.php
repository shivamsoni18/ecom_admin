<?php
session_start();
require_once(__DIR__ . '/../models/Setting.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['section']) || !isset($data['data'])) {
        throw new Exception('Invalid data received');
    }

    $setting = new Setting();
    $result = $setting->updateSettings($data['section'], $data['data']);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Settings updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 