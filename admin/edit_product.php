<?php
session_start();
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');

// Check admin access
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
$product_id = (int)$_GET['id'];

// Get existing product data
$product_data = $product->getProductById($product_id);
if (!$product_data) {
    $_SESSION['error'] = "Product not found";
    header('Location: products.php');
    exit();
}

$categories = $category->getAllCategories();
$product_images = $product->getProductImages($product_id);

// Set page title
$page_title = 'Edit Product: ' . htmlspecialchars($product_data['name']);

// Additional CSS for image handling
$additional_css = <<<EOT
<style>
    .image-preview {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .current-images {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    .image-container {
        position: relative;
        width: 150px;
    }
    .image-actions {
        position: absolute;
        top: 5px;
        right: 5px;
        display: flex;
        gap: 5px;
    }
    .image-actions .btn {
        padding: 3px 8px;
        font-size: 12px;
        opacity: 0.9;
    }
    .primary-badge {
        position: absolute;
        top: 5px;
        left: 5px;
        background-color: rgba(40, 167, 69, 0.9);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    #imagePreview {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }
    .preview-container {
        width: 150px;
        height: 150px;
        border-radius: 8px;
        overflow: hidden;
    }
    .preview-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>
EOT;

// Add form processing logic after validation checks
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Collect form data
        $updateData = [
            'id' => $product_id,
            'name' => $_POST['name'],
            'sku' => $_POST['sku'],
            'description' => $_POST['description'],
            'category_id' => $_POST['category_id'],
            'price' => $_POST['price'],
            'stock' => $_POST['stock'],
            'status' => $_POST['status']
        ];

        // Update product information
        if ($product->updateProduct($updateData)) {
            // Handle image uploads if any
            if (!empty($_FILES['images']['name'][0])) {
                $product->uploadProductImages($product_id, $_FILES['images']);
            }
            
            $_SESSION['success'] = "Product updated successfully";
            header('Location: products.php');
            exit();
        } else {
            $_SESSION['error'] = "Failed to update product";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
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
            <h4 class="mb-0">Edit Product</h4>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Products
            </a>
        </div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <!-- Basic Information -->
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   value="<?php echo htmlspecialchars($product_data['name']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="sku">SKU</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="sku" 
                                   name="sku" 
                                   value="<?php echo htmlspecialchars($product_data['sku']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="5"><?php echo htmlspecialchars($product_data['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Product Details -->
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <select class="form-control" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php while ($category = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category['id'] == $product_data['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="price">Price</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₹</span>
                                </div>
                                <input type="number" 
                                       class="form-control" 
                                       id="price" 
                                       name="price" 
                                       step="0.01" 
                                       value="<?php echo $product_data['price']; ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="stock" 
                                   name="stock" 
                                   value="<?php echo $product_data['stock']; ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo $product_data['status'] === 'active' ? 'selected' : ''; ?>>
                                    Active
                                </option>
                                <option value="inactive" <?php echo $product_data['status'] === 'inactive' ? 'selected' : ''; ?>>
                                    Inactive
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Current Images -->
                <?php if (!empty($product_images)): ?>
                    <div class="form-group">
                        <label>Current Images</label>
                        <div class="current-images">
                            <?php foreach ($product_images as $image): ?>
                                <div class="image-container">
                                    <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" 
                                         class="image-preview">
                                    <?php if ($image['is_primary']): ?>
                                        <span class="primary-badge">Primary</span>
                                    <?php endif; ?>
                                    <div class="image-actions">
                                        <?php if (!$image['is_primary']): ?>
                                            <button type="button" 
                                                    class="btn btn-success btn-sm set-primary-btn" 
                                                    data-image-id="<?php echo $image['id']; ?>">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm delete-image-btn" 
                                                data-image-id="<?php echo $image['id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- New Images -->
                <div class="form-group">
                    <label for="images">Add New Images</label>
                    <div class="custom-file">
                        <input type="file" 
                               class="custom-file-input" 
                               id="images" 
                               name="images[]" 
                               multiple 
                               accept="image/*">
                        <label class="custom-file-label" for="images">Choose files</label>
                    </div>
                    <div id="imagePreview"></div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// Additional scripts
$additional_scripts = <<<EOT
<script>
    // Image preview functionality
    document.getElementById('images').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        
        [...e.target.files].forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-container';
                div.innerHTML = `<img src="\${e.target.result}">`;
                preview.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    });

    // Update file input label
    $('.custom-file-input').on('change', function() {
        let fileCount = $(this)[0].files.length;
        let label = fileCount > 1 ? fileCount + ' files selected' : $(this)[0].files[0].name;
        $(this).next('.custom-file-label').html(label);
    });

    // Add event listeners using jQuery
    $(document).ready(function() {
        // Set primary image handler
        $('.set-primary-btn').on('click', function() {
            const imageId = $(this).data('image-id');
            console.log('Setting primary image:', imageId); // Debug log
            
            $.ajax({
                url: 'ajax/set_primary_image.php',
                type: 'POST',
                data: {
                    image_id: imageId,
                    product_id: {$product_id}
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Server response:', response); // Debug log
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message || 'Failed to set primary image');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax error:', error); // Debug log
                    alert('Error communicating with server: ' + error);
                }
            });
        });

        // Delete image handler
        $('.delete-image-btn').on('click', function() {
            const imageId = $(this).data('image-id');
            console.log('Deleting image:', imageId); // Debug log
            
            if (confirm('Are you sure you want to delete this image?')) {
                $.ajax({
                    url: 'ajax/delete_image.php',
                    type: 'POST',
                    data: {
                        image_id: imageId,
                        product_id: {$product_id}
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Server response:', response); // Debug log
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.message || 'Failed to delete image');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', error); // Debug log
                        alert('Error communicating with server: ' + error);
                    }
                });
            }
        });
    });
</script>
EOT;

include 'includes/footer.php';
?> 