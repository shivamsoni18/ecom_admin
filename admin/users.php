<?php
session_start();
require_once(__DIR__ . '/../models/User.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user = new User();
$users = $user->getAllUsers();

// Set page title
$page_title = 'Users Management';

// Additional CSS for users page
$additional_css = <<<EOT
<style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-weight: 600;
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .user-details {
        line-height: 1.3;
    }
    .user-name {
        font-weight: 600;
        color: #4e73df;
    }
    .user-email {
        font-size: 0.875rem;
        color: #858796;
    }
    .role-badge {
        text-transform: capitalize;
        font-weight: 500;
    }
    .status-badge {
        padding: 0.4em 0.8em;
    }
    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .last-login {
        font-size: 0.875rem;
        color: #858796;
    }
</style>
EOT;

include 'includes/header.php';
?>

<div class="container-fluid">
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Users Management</h4>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-user-plus"></i> Add New User
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'info'; ?> role-badge">
                                        <?php echo $user['role']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?> status-badge">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="last-login">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" 
                                                class="btn btn-info btn-sm" 
                                                onclick="viewUser(<?php echo $user['id']; ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-primary btn-sm" 
                                                onclick="editUser(<?php echo $user['id']; ?>)"
                                                title="Edit User">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                            <button type="button" 
                                                    class="btn btn-warning btn-sm" 
                                                    onclick="toggleStatus(<?php echo $user['id']; ?>)"
                                                    title="Toggle Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm" 
                                                    onclick="confirmDelete(<?php echo $user['id']; ?>)"
                                                    title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="addUserForm" action="process_add_user.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select class="form-control" id="role" name="role">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <p class="text-danger">
                    <strong>Warning:</strong> This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteForm" action="delete_user.php" method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Additional scripts
$additional_scripts = <<<EOT
<script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            "order": [[3, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search users:",
                "lengthMenu": "Show _MENU_ users per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ users",
                "infoEmpty": "Showing 0 to 0 of 0 users",
                "zeroRecords": "No matching users found"
            },
            "columnDefs": [
                { "orderable": false, "targets": [4] }
            ]
        });
    });

    function viewUser(userId) {
        window.location.href = 'view_user.php?id=' + userId;
    }

    function editUser(userId) {
        window.location.href = 'edit_user.php?id=' + userId;
    }

    function toggleStatus(userId) {
        if (confirm('Are you sure you want to toggle this user\'s status?')) {
            $.ajax({
                url: 'ajax/toggle_user_status.php',
                type: 'POST',
                data: { user_id: userId },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Failed to toggle user status');
                    }
                },
                error: function() {
                    alert('Error communicating with server');
                }
            });
        }
    }

    function confirmDelete(userId) {
        $('#deleteUserId').val(userId);
        $('#deleteModal').modal('show');
    }
</script>
EOT;

include 'includes/footer.php';
?> 