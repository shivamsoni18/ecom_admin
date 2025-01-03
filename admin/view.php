<?php
session_start();
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');
require_once(__DIR__ . '/../models/Order.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if id is provided
if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product = new Product();
$product->id = $_GET['id'];

if (!$product->readOne()) {
    $_SESSION['error'] = "Product not found";
    header('Location: products.php');
    exit();
}

// Get product images
$product_images = $product->getImages();

// Get category name
$category = new Category();
$category->id = $product->category_id;
$category->read($category->id);

// Get product statistics
$order = new Order();
$stats = [
    'total_sales' => 0,  // Placeholder until Order class is updated
    'total_revenue' => 0,  // Placeholder until Order class is updated
    'last_sold' => null,  // Placeholder until Order class is updated
    'in_orders' => 0  // Placeholder until Order class is updated
];

// If the methods exist, use them
if (method_exists($order, 'getProductSales')) {
    $stats['total_sales'] = $order->getProductSales($product->id);
}
if (method_exists($order, 'getProductRevenue')) {
    $stats['total_revenue'] = $order->getProductRevenue($product->id);
}
if (method_exists($order, 'getLastSoldDate')) {
    $stats['last_sold'] = $order->getLastSoldDate($product->id);
}
if (method_exists($order, 'getProductOrderCount')) {
    $stats['in_orders'] = $order->getProductOrderCount($product->id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - <?php echo htmlspecialchars($product->name); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .product-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 15px;
        }
        .thumbnail {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin: 5px;
            cursor: pointer;
        }
        .stat-card {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .status-active { background-color: #28a745; color: white; }
        .status-inactive { background-color: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Product Details</h2>
                    <div>
                        <a href="edit.php?id=<?php echo $product->id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Product
                        </a>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Product Images -->
                            <div class="col-md-6">
                                <?php if (!empty($product_images)): ?>
                                    <div id="productCarousel" class="carousel slide" data-ride="carousel">
                                        <div class="carousel-inner">
                                            <?php foreach ($product_images as $index => $image): ?>
                                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                    <img src="<?php echo '../' . htmlspecialchars($image['image_path']); ?>" 
                                                         class="d-block w-100 product-image" 
                                                         alt="Product Image">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if (count($product_images) > 1): ?>
                                            <a class="carousel-control-prev" href="#productCarousel" role="button" data-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            </a>
                                            <a class="carousel-control-next" href="#productCarousel" role="button" data-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-wrap mt-3">
                                        <?php foreach ($product_images as $index => $image): ?>
                                            <img src="<?php echo '../' . htmlspecialchars($image['image_path']); ?>" 
                                                 class="thumbnail" 
                                                 data-target="#productCarousel" 
                                                 data-slide-to="<?php echo $index; ?>"
                                                 alt="Thumbnail">
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-4">
                                        <i class="fas fa-image fa-4x text-muted"></i>
                                        <p class="mt-2">No images available</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Product Information -->
                            <div class="col-md-6">
                                <h3><?php echo htmlspecialchars($product->name); ?></h3>
                                <p class="text-muted">SKU: <?php echo htmlspecialchars($product->sku); ?></p>
                                
                                <div class="mb-3">
                                    <span class="status-badge status-<?php echo $product->status === 'active' ? 'active' : 'inactive'; ?>">
                                        <?php echo ucfirst($product->status); ?>
                                    </span>
                                </div>

                                <h4 class="mb-3">₹<?php echo number_format($product->price, 2); ?></h4>
                                
                                <div class="mb-3">
                                    <strong>Category:</strong> 
                                    <?php echo htmlspecialchars($category->name); ?>
                                </div>

                                <div class="mb-3">
                                    <strong>Stock:</strong> 
                                    <?php echo $product->stock; ?> units
                                </div>

                                <div class="mb-4">
                                    <strong>Description:</strong>
                                    <p><?php echo nl2br(htmlspecialchars($product->description)); ?></p>
                                </div>

                                <!-- Product Statistics -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="stat-card">
                                            <h6>Total Sales</h6>
                                            <h3><?php echo $stats['total_sales']; ?> units</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stat-card">
                                            <h6>Revenue</h6>
                                            <h3>₹<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stat-card">
                                            <h6>In Orders</h6>
                                            <h3><?php echo $stats['in_orders']; ?> orders</h3>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="stat-card">
                                            <h6>Last Sold</h6>
                                            <h3><?php echo $stats['last_sold'] ? date('M j, Y', strtotime($stats['last_sold'])) : 'Never'; ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
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