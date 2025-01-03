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
$category = new Category();
$categories = $category->getAllCategories('active');
$error = '';
$message = '';

// Check if id is provided
if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product->id = $_GET['id'];
$product_exists = $product->readOne();

if (!$product_exists) {
    $_SESSION['error'] = "Product not found";
    header('Location: products.php');
    exit();
}

// Get product images
$product_images = $product->getImages();

// Handle image deletion
if (isset($_POST['delete_image']) && !empty($_POST['delete_image'])) {
    $image_id = $_POST['delete_image'];
    if ($product->deleteImage($image_id)) {
        $_SESSION['success'] = "Image deleted successfully";
        header("Location: edit.php?id=" . $product->id);
        exit();
    } else {
        $error = "Failed to delete image";
    }
}

// Handle setting primary image
if (isset($_POST['make_primary']) && !empty($_POST['make_primary'])) {
    $image_id = $_POST['make_primary'];
    if ($product->setPrimaryImage($image_id)) {
        $_SESSION['success'] = "Primary image updated successfully";
        header("Location: edit.php?id=" . $product->id);
        exit();
    } else {
        $error = "Failed to set primary image";
    }
}

// Display session messages
if (isset($_SESSION['success'])) {
    $message = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_image']) && !isset($_POST['make_primary'])) {
    // Handle file upload
    $uploaded_images = [];
    $target_dir = "../uploads/";
    
    if (isset($_FILES["images"]) && is_array($_FILES["images"]["name"])) {
        foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
            if ($_FILES["images"]["error"][$key] == 0) {
                $file_extension = strtolower(pathinfo($_FILES["images"]["name"][$key], PATHINFO_EXTENSION));
                $allowed_extensions = array("jpg", "jpeg", "png", "gif");
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $uploaded_images[] = [
                            'path' => str_replace("../", "", $target_file),
                            'is_primary' => empty($product_images) && empty($uploaded_images)
                        ];
                    }
                }
            }
        }
    }

    // Set product properties
    $product->name = $_POST['name'] ?? '';
    $product->description = $_POST['description'] ?? '';
    $product->price = $_POST['price'] ?? 0;
    $product->category_id = $_POST['category_id'] ?? null;
    $product->stock = $_POST['stock'] ?? 0;
    $product->sku = $_POST['sku'] ?? '';
    $product->status = $_POST['status'] ?? 'active';

    // Update the product
    if ($product->update()) {
        // Add new images
        if (!empty($uploaded_images)) {
            foreach ($uploaded_images as $image) {
                $product->addImage($image['path'], $image['is_primary']);
            }
        }
        
        $_SESSION['success'] = "Product updated successfully";
        header("Location: products.php");
        exit();
    } else {
        $error = "Unable to update product. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin: 5px;
        }
        .image-container {
            position: relative;
            display: inline-block;
        }
        .image-actions {
            position: absolute;
            top: 5px;
            right: 5px;
        }
        .primary-badge {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: rgba(40, 167, 69, 0.8);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Edit Product</h4>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Current Images Section -->
                        <?php if (!empty($product_images)): ?>
                            <div class="mb-4">
                                <h5>Current Images</h5>
                                <div class="d-flex flex-wrap">
                                    <?php foreach ($product_images as $image): ?>
                                        <div class="image-container">
                                            <img src="<?php echo '../' . htmlspecialchars($image['image_path']); ?>" 
                                                 class="product-image" 
                                                 alt="Product Image">
                                            
                                            <?php if ($image['is_primary']): ?>
                                                <span class="primary-badge">Primary</span>
                                            <?php endif; ?>
                                            
                                            <div class="image-actions">
                                                <?php if (!$image['is_primary']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="make_primary" value="<?php echo $image['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-success" title="Make Primary">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                                    <input type="hidden" name="delete_image" value="<?php echo $image['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Image">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Edit Form -->
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $product->id); ?>" 
                              method="POST" 
                              enctype="multipart/form-data">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Product Name *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="name" 
                                               name="name" 
                                               value="<?php echo htmlspecialchars($product->name); ?>" 
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label for="sku">SKU</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="sku" 
                                               name="sku" 
                                               value="<?php echo htmlspecialchars($product->sku); ?>">
                                    </div>

                                    <div class="form-group">
                                        <label for="price">Price *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">₹</span>
                                            </div>
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="price" 
                                                   name="price" 
                                                   value="<?php echo $product->price; ?>" 
                                                   step="0.01" 
                                                   required>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="stock">Stock</label>
                                        <input type="number" 
                                               class="form-control" 
                                               id="stock" 
                                               name="stock" 
                                               value="<?php echo $product->stock; ?>" 
                                               min="0">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="category_id">Category *</label>
                                        <select class="form-control" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php 
                                            if ($categories) {
                                                while ($category_row = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                                    <option value="<?php echo $category_row['id']; ?>" 
                                                            <?php echo $category_row['id'] == $product->category_id ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category_row['name']); ?>
                                                    </option>
                                                <?php endwhile;
                                            } ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="active" <?php echo $product->status === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $product->status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea class="form-control" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="4"><?php echo htmlspecialchars($product->description); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="images">Add New Images</label>
                                        <input type="file" 
                                               class="form-control-file" 
                                               id="images" 
                                               name="images[]" 
                                               multiple 
                                               accept="image/*">
                                        <small class="form-text text-muted">
                                            You can select multiple images. Supported formats: JPG, PNG, GIF
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Product
                                </button>
                                <a href="products.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 