<?php
session_start();
require_once(__DIR__ . '/../models/User.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user = new User();
$userData = $user->getUserById($_SESSION['user_id']);

if (!$userData) {
    $_SESSION['error'] = "User not found";
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .profile-image {
            max-width: 150px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h4>My Profile</h4>
                    </div>
                    <div class="card-body">
                        <form id="profileForm">
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="mb-3">
                                        <img src="<?php echo !empty($userData['avatar']) ? '../' . $userData['avatar'] : '../assets/images/default-avatar.png'; ?>" 
                                             alt="Profile Image" 
                                             class="profile-image img-thumbnail">
                                        <input type="file" 
                                               class="form-control mt-2" 
                                               id="avatar" 
                                               name="avatar" 
                                               accept="image/*">
                                        <small class="text-muted">Max file size: 2MB</small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="name" 
                                               name="name" 
                                               value="<?php echo htmlspecialchars($userData['name']); ?>" 
                                               required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($userData['email']); ?>" 
                                               required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="phone" 
                                               name="phone" 
                                               value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="mb-3">
                                <h5>Change Password</h5>
                                <small class="text-muted">Leave blank if you don't want to change the password</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="current_password" 
                                               name="current_password">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="new_password" 
                                               name="new_password">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('phone', document.getElementById('phone').value);
            
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            
            if (currentPassword || newPassword) {
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
            }
            
            const avatarFile = document.getElementById('avatar').files[0];
            if (avatarFile) {
                formData.append('avatar', avatarFile);
            }

            try {
                const response = await fetch('process_profile.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.status === 'success') {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error updating profile: ' + error.message);
            }
        });
    </script>
</body>
</html> 