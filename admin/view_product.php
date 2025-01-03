<?php
session_start();
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid product ID";
    header('Location: products.php');
    exit();
}

$product = new Product();
$category = new Category();

// Get product details
$product_id = (int)$_GET['id'];

// Get product images first
$product_images = $product->getProductImages($product_id);

// Get product details
$product_details = $product->getProductById($product_id);

if (!$product_details) {
    $_SESSION['error'] = "Product not found";
    header('Location: products.php');
    exit();
}

// Set page title
$page_title = 'View Product: ' . htmlspecialchars($product_details['name']);

// Additional CSS for image gallery
$additional_css = <<<EOT
<style>
    .product-gallery {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    .gallery-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    .gallery-image:hover {
        border-color: #007bff;
    }
    .gallery-image.active {
        border-color: #28a745;
    }
    .primary-image-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: rgba(40, 167, 69, 0.9);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
    }
    .description-box {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-top: 10px;
    }
</style>
EOT;

// Include header
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Product Details</h4>
            <div>
                <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Product
                </a>
                <a href="products.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Product Images -->
                <div class="col-md-5">
                    <div class="position-relative">
                        <?php 
                        $primary_image = '';
                        foreach ($product_images as $image) {
                            if ($image['is_primary']) {
                                $primary_image = $image['image_path'];
                                break;
                            }
                        }
                        if (empty($primary_image) && !empty($product_images)) {
                            $primary_image = $product_images[0]['image_path'];
                        }
                        ?>
                        <?php if (!empty($primary_image)): ?>
                            <img src="../<?php echo htmlspecialchars($primary_image); ?>" 
                                 alt="<?php echo htmlspecialchars($product_details['name']); ?>"
                                 class="img-fluid rounded" id="mainImage">
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product_images)): ?>
                        <div class="product-gallery">
                            <?php foreach ($product_images as $image): ?>
                                <div class="position-relative">
                                    <img src="../<?php echo htmlspecialchars($image['image_path']); ?>"
                                         class="gallery-image <?php echo $image['is_primary'] ? 'active' : ''; ?>"
                                         onclick="updateMainImage(this.src)"
                                         alt="Product image">
                                    <?php if ($image['is_primary']): ?>
                                        <span class="primary-image-badge">Primary</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Details -->
                <div class="col-md-7">
                    <h3 class="mb-4"><?php echo htmlspecialchars($product_details['name']); ?></h3>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-label">SKU</div>
                            <div class="detail-value"><?php echo htmlspecialchars($product_details['sku']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Category</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($product_details['category_name'] ?? 'Uncategorized'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-label">Price</div>
                            <div class="detail-value">
                                ₹<?php echo number_format($product_details['price'], 2); ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Stock</div>
                            <div class="detail-value">
                                <span class="badge badge-<?php echo $product_details['stock'] <= 5 ? 'danger' : 'success'; ?>">
                                    <?php echo $product_details['stock']; ?> units
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="badge badge-<?php echo $product_details['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($product_details['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Created Date</div>
                            <div class="detail-value">
                                <?php echo date('M j, Y', strtotime($product_details['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <div class="detail-label">Description</div>
                    <div class="description-box">
                        <?php echo nl2br(htmlspecialchars($product_details['description'] ?? 'No description available.')); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Additional scripts
$additional_scripts = <<<EOT
<script>
    function updateMainImage(src) {
        document.getElementById('mainImage').src = src;
        // Update active state of gallery images
        document.querySelectorAll('.gallery-image').forEach(img => {
            img.classList.remove('active');
            if (img.src === src) {
                img.classList.add('active');
            }
        });
    }
</script>
EOT;

// Include footer
include 'includes/footer.php';
?>