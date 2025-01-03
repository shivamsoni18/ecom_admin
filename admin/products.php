<?php
session_start();
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$product = new Product();
$products = $product->getAllProducts();

// Set page title
$page_title = 'Products Management';

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Success/Error Messages -->
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
            <h4 class="mb-0">Products Management</h4>
            <a href="add_product.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="productsTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th width="80">Image</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th width="100">Price</th>
                            <th width="80">Stock</th>
                            <th width="100">Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td class="text-center">
                                    <?php 
                                    $displayImage = '';
                                    foreach ($product['images'] as $img) {
                                        if ($img['is_primary']) {
                                            $displayImage = $img['image_path'];
                                            break;
                                        }
                                    }
                                    if (empty($displayImage) && !empty($product['images'])) {
                                        $displayImage = $product['images'][0]['image_path'];
                                    }
                                    ?>
                                    <?php if (!empty($displayImage)): ?>
                                        <img src="../<?php echo htmlspecialchars($displayImage); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="thumbnail-image">
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>₹<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $product['stock'] <= 5 ? 'danger' : 'success'; ?> stock-badge">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $product['status'] === 'active' ? 'success' : 'secondary'; ?> status-badge">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-info" 
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-danger" 
                                                onclick="confirmDelete(<?php echo $product['id']; ?>)"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this product?</p>
                <p class="text-danger">
                    <strong>Warning:</strong> This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form id="deleteForm" action="delete_product.php" method="POST" style="display: inline;">
                    <input type="hidden" name="product_id" id="deleteProductId">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Additional scripts for this page
$additional_scripts = <<<EOT
<script>
    $(document).ready(function() {
        $('#productsTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search products:",
                "lengthMenu": "Show _MENU_ products per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ products",
                "infoEmpty": "Showing 0 to 0 of 0 products",
                "zeroRecords": "No matching products found"
            },
            "columnDefs": [
                { "orderable": false, "targets": [1, 8] }
            ]
        });
    });

    function confirmDelete(productId) {
        $('#deleteProductId').val(productId);
        $('#deleteModal').modal('show');
    }
</script>
EOT;

// Include footer
include 'includes/footer.php';
?>