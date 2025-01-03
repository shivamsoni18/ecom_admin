<?php
session_start();
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $product = new Product();
        
        // Collect form data
        $productData = [
            'name' => $_POST['name'],
            'sku' => $_POST['sku'],
            'description' => $_POST['description'],
            'category_id' => $_POST['category_id'],
            'price' => $_POST['price'],
            'stock' => $_POST['stock'],
            'status' => $_POST['status']
        ];

        // Insert product and get the new product ID
        $product_id = $product->addProduct($productData);

        if ($product_id) {
            // Handle image uploads if any
            if (!empty($_FILES['images']['name'][0])) {  // Fixed line
                $product->uploadProductImages($product_id, $_FILES['images']);
            }
            
            $_SESSION['success'] = "Product added successfully";
            header('Location: products.php');
            exit();
        } else {
            throw new Exception("Failed to add product");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

$category = new Category();
$categories = $category->getAllCategories();

// Set page title
$page_title = 'Add New Product';

// Additional CSS for image preview
$additional_css = <<<EOT
<style>
    .image-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }
    .preview-container {
        width: 150px;
        height: 150px;
        position: relative;
        border-radius: 8px;
        overflow: hidden;
    }
    .preview-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .preview-container .primary-badge {
        position: absolute;
        top: 5px;
        left: 5px;
        background-color: rgba(40, 167, 69, 0.9);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    .preview-container .remove-image {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: rgba(220, 53, 69, 0.9);
        color: white;
        border: none;
        border-radius: 4px;
        padding: 3px 8px;
        cursor: pointer;
        font-size: 12px;
    }
    .form-group label {
        font-weight: 500;
        color: #495057;
    }
    .required-field::after {
        content: "*";
        color: #dc3545;
        margin-left: 4px;
    }
    .help-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 4px;
    }
</style>
EOT;

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
            <h4 class="mb-0">Add New Product</h4>
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
                            <label for="name" class="required-field">Product Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="name" 
                                   name="name" 
                                   required>
                            <small class="help-text">Enter a unique and descriptive name for the product</small>
                        </div>

                        <div class="form-group">
                            <label for="sku" class="required-field">SKU</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="sku" 
                                   name="sku" 
                                   required>
                            <small class="help-text">Stock Keeping Unit - Unique identifier for your product</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="5"></textarea>
                            <small class="help-text">Provide detailed information about the product</small>
                        </div>

                        <!-- Product Images -->
                        <div class="form-group">
                            <label for="images" class="required-field">Product Images</label>
                            <div class="custom-file">
                                <input type="file" 
                                       class="custom-file-input" 
                                       id="images" 
                                       name="images[]" 
                                       multiple 
                                       accept="image/*" 
                                       required>
                                <label class="custom-file-label" for="images">Choose files</label>
                            </div>
                            <small class="help-text">Upload one or more product images (JPG, PNG)</small>
                            <div id="imagePreview" class="image-preview"></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Product Details -->
                        <div class="form-group">
                            <label for="category_id" class="required-field">Category</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php while ($category = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="price" class="required-field">Price</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₹</span>
                                </div>
                                <input type="number" 
                                       class="form-control" 
                                       id="price" 
                                       name="price" 
                                       step="0.01" 
                                       required>
                            </div>
                            <small class="help-text">Set the selling price</small>
                        </div>

                        <div class="form-group">
                            <label for="stock" class="required-field">Stock</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="stock" 
                                   name="stock" 
                                   required>
                            <small class="help-text">Available quantity in stock</small>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <small class="help-text">Product visibility status</small>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Product
                    </button>
                    <button type="reset" class="btn btn-secondary ml-2">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
