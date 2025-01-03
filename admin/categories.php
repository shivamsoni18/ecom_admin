<?php
session_start();
require_once(__DIR__ . '/../models/Category.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$category = new Category();

// Handle category status update
if (isset($_POST['update_status']) && isset($_POST['category_id']) && isset($_POST['status'])) {
    if ($category->updateStatus($_POST['category_id'], $_POST['status'])) {
        $_SESSION['success'] = "Category status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update category status";
    }
    header('Location: categories.php');
    exit();
}

// Handle category deletion
if (isset($_POST['delete']) && isset($_POST['category_id'])) {
    if ($category->delete($_POST['category_id'])) {
        $_SESSION['success'] = "Category deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete category";
    }
    header('Location: categories.php');
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Get categories with filters
$categories = $category->getAllCategories($status, $search);

// Set page title
$page_title = 'Categories Management';

// Include header
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
            <h4 class="mb-0">Categories</h4>
            <a href="create_category.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Category
            </a>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form class="mb-4" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="search">Search</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search categories...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <a href="categories.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th width="80">Image</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th width="100">Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories && $categories->rowCount() > 0): ?>
                            <?php while ($row = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($row['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                 class="thumbnail-image">
                                        <?php else: ?>
                                            <div class="no-image-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    </td>
                                    <td class="description-cell">
                                        <?php echo htmlspecialchars($row['description'] ?? ''); ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit_category.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-primary" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-warning" 
                                                    data-toggle="modal" 
                                                    data-target="#updateStatus<?php echo $row['id']; ?>"
                                                    title="Update Status">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-danger" 
                                                    data-toggle="modal" 
                                                    data-target="#deleteModal<?php echo $row['id']; ?>"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="updateStatus<?php echo $row['id']; ?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="category_id" value="<?php echo $row['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Category Status</h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>Status</label>
                                                                <select name="status" class="form-control">
                                                                    <option value="active" <?php echo $row['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                                    <option value="inactive" <?php echo $row['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="category_id" value="<?php echo $row['id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Category</h5>
                                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete this category?</p>
                                                            <p class="text-danger">
                                                                <strong>Warning:</strong> This will also delete all products in this category!
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No categories found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Additional scripts
$additional_scripts = <<<EOT
<script>
    $(document).ready(function() {
        // Initialize DataTable
        $('.table').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search categories:",
                "lengthMenu": "Show _MENU_ categories per page"
            },
            "columnDefs": [
                { "orderable": false, "targets": [1, 5] }
            ]
        });
    });
</script>
EOT;

// Include footer
include 'includes/footer.php';
?> 