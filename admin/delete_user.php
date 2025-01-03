<?php
session_start();
require_once(__DIR__ . '/../models/User.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if user_id is provided
if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
    $_SESSION['error'] = "Invalid user ID";
    header('Location: users.php');
    exit();
}

$user_id = (int)$_POST['user_id'];

// Prevent admin from deleting their own account
if ($user_id === $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account";
    header('Location: users.php');
    exit();
}

try {
    $user = new User();
    if ($user->deleteUser($user_id)) {
        $_SESSION['success'] = "User deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete user";
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header('Location: users.php');
exit(); 