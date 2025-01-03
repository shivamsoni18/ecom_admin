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
$categories = $category->getAllCategories('active'); // Get only active categories for the dropdown
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $required_fields = ['name', 'price', 'category_id', 'stock'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Validate numeric fields
        if (!is_numeric($_POST['price']) || $_POST['price'] <= 0) {
            throw new Exception("Invalid price");
        }
        
        if (!is_numeric($_POST['stock']) || $_POST['stock'] < 0) {
            throw new Exception("Invalid stock quantity");
        }

        $uploaded_images = [];
        $target_dir = "../uploads/";
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Handle image uploads
        if (isset($_FILES["images"]) && is_array($_FILES["images"]["name"])) {
            foreach ($_FILES["images"]["tmp_name"] as $key => $tmp_name) {
                if ($_FILES["images"]["error"][$key] == 0) {
                    $file_extension = strtolower(pathinfo($_FILES["images"]["name"][$key], PATHINFO_EXTENSION));
                    
                    if (!in_array($file_extension, ["jpg", "jpeg", "png", "gif"])) {
                        throw new Exception("Invalid file type: " . $_FILES["images"]["name"][$key]);
                    }
                    
                    $image_path = $target_dir . uniqid() . '.' . $file_extension;
                    
                    if (!move_uploaded_file($tmp_name, $image_path)) {
                        throw new Exception("Failed to upload: " . $_FILES["images"]["name"][$key]);
                    }
                    
                    $uploaded_images[] = [
                        'path' => str_replace("../", "", $image_path),
                        'is_primary' => ($key === 0)
                    ];
                }
            }
        }

        // Set product properties
        $product = new Product();
        $product->name = trim($_POST['name']);
        $product->description = trim($_POST['description'] ?? '');
        $product->price = floatval($_POST['price']);
        $product->category_id = intval($_POST['category_id']);
        $product->stock = intval($_POST['stock']);
        $product->sku = $_POST['sku'] ?? $product->generateSKU();
        $product->status = $_POST['status'] ?? 'active';

        // Create product
        if (!$product->create()) {
            throw new Exception("Failed to create product");
        }

        // Add images if any
        if (!empty($uploaded_images) && !$product->addImages($uploaded_images, $product->id)) {
            throw new Exception("Product created but failed to add images");
        }

        $_SESSION['success'] = "Product created successfully";
        header("Location: products.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Error creating product: " . $error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Create New Product</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="price">Price (₹)</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">₹</span>
                    </div>
                    <input type="number" 
                           class="form-control" 
                           id="price" 
                           name="price" 
                           step="1" 
                           min="1" 
                           required>
                    <small class="form-text text-muted">Enter price in Indian Rupees</small>
                </div>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select class="form-control" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php 
                    // Update the fetch loop since getAllCategories returns a PDO statement
                    if ($categories) {
                        while ($row = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                            <option value="<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile;
                    } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="images">Product Images</label>
                <input type="file" name="images[]" multiple accept="image/*">
            </div>

            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" 
                       class="form-control" 
                       id="stock" 
                       name="stock" 
                       value="0" 
                       required>
            </div>

            <div class="form-group">
                <label for="sku">SKU</label>
                <input type="text" 
                       class="form-control" 
                       id="sku" 
                       name="sku" 
                       value="<?php echo $product->generateSKU(); ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="draft">Draft</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Create Product</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 