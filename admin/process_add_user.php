<?php
session_start();
require_once(__DIR__ . '/../models/User.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user = new User();
        
        // Collect form data
        $userData = [
            'name' => $_POST['name'],
            'username' => $_POST['name'], // Using name as username
            'email' => $_POST['email'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => $_POST['role'] === 'admin' ? 'admin' : 'user',
            'status' => 'active'  // Default status for new users
        ];

        // Check if email already exists
        if ($user->emailExists($userData['email'])) {
            $_SESSION['error'] = "Email already exists";
            header('Location: users.php');
            exit();
        }

        // Add the user
        if ($user->addUser($userData)) {
            $_SESSION['success'] = "User added successfully";
        } else {
            $_SESSION['error'] = "Failed to add user";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

header('Location: users.php');
exit(); 