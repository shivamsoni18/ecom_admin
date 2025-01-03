<?php
session_start();
require_once(__DIR__ . '/../models/User.php');

try {
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        throw new Exception("Unauthorized access");
    }

    // Validate required fields
    if (!isset($_POST['name']) || empty($_POST['name'])) {
        throw new Exception("Name is required");
    }
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        throw new Exception("Email is required");
    }
    if (!isset($_POST['password']) || empty($_POST['password'])) {
        throw new Exception("Password is required");
    }
    if (!isset($_POST['role']) || empty($_POST['role'])) {
        throw new Exception("Role is required");
    }
    if (!isset($_POST['username']) || empty($_POST['username'])) {
        throw new Exception("Username is required");
    }

    $user = new User();
    
    // Check if email already exists
    if ($user->emailExists($_POST['email'])) {
        throw new Exception("Email already exists");
    }
    
    // Check if username already exists
    if ($user->usernameExists($_POST['username'])) {
        throw new Exception("Username already exists");
    }
    
    // Handle avatar upload
    $avatarPath = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/avatars/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Validate file size (2MB max)
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            throw new Exception("File size too large. Maximum size is 2MB.");
        }
        
        $fileName = time() . '_' . basename($_FILES['avatar']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
            $avatarPath = 'uploads/avatars/' . $fileName;
        }
    }
    
    // Prepare user data
    $userData = [
        'name' => $_POST['name'],
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'phone' => $_POST['phone'] ?? null,
        'role' => $_POST['role'],
        'status' => $_POST['status'] ?? 'active',
        'avatar' => $avatarPath
    ];
    
    // Create new user
    $result = $user->createUser($userData);
    
    if ($result) {
        echo json_encode([
            "status" => "success",
            "message" => "User created successfully",
            "data" => [
                "id" => $result,
                "name" => $userData['name'],
                "email" => $userData['email'],
                "role" => $userData['role'],
                "status" => $userData['status']
            ]
        ]);
    } else {
        throw new Exception("Failed to create user");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Failed to create user: " . $e->getMessage()
    ]);
}
?> 