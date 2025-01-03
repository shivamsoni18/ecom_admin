<?php
session_start();
require_once(__DIR__ . '/../models/User.php');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Not authenticated");
    }

    // Validate required fields
    if (!isset($_POST['name']) || empty($_POST['name'])) {
        throw new Exception("Name is required");
    }
    if (!isset($_POST['email']) || empty($_POST['email'])) {
        throw new Exception("Email is required");
    }

    $user = new User();
    
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
            
            // Delete old avatar if exists
            $oldUser = $user->getUserById($_SESSION['user_id']);
            if ($oldUser && !empty($oldUser['avatar'])) {
                $oldAvatarPath = __DIR__ . '/../' . $oldUser['avatar'];
                if (file_exists($oldAvatarPath)) {
                    unlink($oldAvatarPath);
                }
            }
        }
    }
    
    // Prepare user data
    $userData = [
        'id' => $_SESSION['user_id'],
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'] ?? null
    ];
    
    // Add avatar path if uploaded
    if ($avatarPath) {
        $userData['avatar'] = $avatarPath;
    }
    
    // Handle password change
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        // Verify current password
        if (!$user->verifyPassword($_SESSION['user_id'], $_POST['current_password'])) {
            throw new Exception("Current password is incorrect");
        }
        
        // Update password separately
        if (!$user->updatePassword($_SESSION['user_id'], $_POST['new_password'])) {
            throw new Exception("Failed to update password");
        }
        
        // Remove password from main update data
        unset($userData['password']);
    }
    
    // Update user
    $result = $user->updateUser($userData);
    
    if ($result) {
        // Update session data
        $_SESSION['user_name'] = $userData['name'];
        
        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully"
        ]);
    } else {
        throw new Exception("Failed to update profile");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 