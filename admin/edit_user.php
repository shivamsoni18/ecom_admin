<?php
session_start();
require_once(__DIR__ . '/../models/User.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if id is provided
if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user = new User();
$user->id = $_GET['id'];

// Get user data
if (!$user->readOne()) {
    $_SESSION['error'] = "User not found";
    header('Location: users.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required_fields = ['name', 'email', 'role'];
    $errors = [];

    // Validate required fields
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . " is required";
        }
    }

    // Validate email format
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if email already exists (excluding current user)
    if (!empty($_POST['email']) && $user->emailExists($_POST['email'], $user->id)) {
        $errors[] = "Email already exists";
    }

    // Validate password if provided
    if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if (empty($errors)) {
        // Handle file upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['avatar']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = uniqid() . '.' . $filetype;
                $upload_path = '../uploads/avatars/' . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // Delete old avatar if exists
                    if ($user->avatar && file_exists('../' . $user->avatar)) {
                        unlink('../' . $user->avatar);
                    }
                    $user->avatar = 'uploads/avatars/' . $new_filename;
                }
            }
        }

        // Update user properties
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        if (!empty($_POST['password'])) {
            $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        $user->role = $_POST['role'];
        $user->status = $_POST['status'];
        $user->phone = $_POST['phone'] ?? '';
        $user->address = $_POST['address'] ?? '';

        if ($user->update()) {
            $_SESSION['success'] = "User updated successfully";
            header('Location: users.php');
            exit();
        } else {
            $errors[] = "Failed to update user";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            padding-top: 70px;
        }
        .avatar-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit User</h4>
                    <a href="users.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Users
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="name">Name *</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="name" 
                                           name="name" 
                                           value="<?php echo htmlspecialchars($user->name ?? ''); ?>" 
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user->email); ?>" 
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="password">Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password">
                                    <small class="form-text text-muted">
                                        Leave blank to keep current password. New password must be at least 6 characters long.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="role">Role *</label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="user" <?php echo ($user->role === 'user') ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo ($user->role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active" <?php echo ($user->status === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($user->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group text-center">
                                    <label>Avatar</label>
                                    <div class="d-flex justify-content-center">
                                        <img src="<?php echo $user->avatar ? '../' . $user->avatar : '../assets/images/default-avatar.png'; ?>" 
                                             class="avatar-preview" 
                                             id="avatarPreview" 
                                             alt="Avatar Preview">
                                    </div>
                                    <input type="file" 
                                           class="form-control-file" 
                                           id="avatar" 
                                           name="avatar" 
                                           accept="image/*"
                                           onchange="previewAvatar(this)">
                                    <small class="form-text text-muted">
                                        Supported formats: JPG, PNG, GIF
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?php echo htmlspecialchars($user->phone); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea class="form-control" 
                                              id="address" 
                                              name="address" 
                                              rows="3"><?php echo htmlspecialchars($user->address); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
                            </button>
                            <a href="users.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#avatarPreview').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html> 