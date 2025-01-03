<?php
session_start();
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');
require_once(__DIR__ . '/../models/Order.php');
require_once(__DIR__ . '/../models/User.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Initialize models
$product = new Product();
$category = new Category();
$order = new Order();
$user = new User();

// Get statistics
$total_products = $product->getProductCount();
$total_categories = $category->getCategoryCount();
$total_orders = $order->getOrderCount();
$total_users = $user->getUserCount();

// Set page title
$page_title = 'Dashboard';

// Additional CSS for dashboard
$additional_css = <<<EOT
<style>
    .stats-card {
        border-radius: 10px;
        border-left: 4px solid;
        transition: transform 0.2s;
        color: inherit;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        text-decoration: none;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .stats-card.products {
        border-left-color: #4e73df;
    }
    .stats-card.categories {
        border-left-color: #1cc88a;
    }
    .stats-card.orders {
        border-left-color: #36b9cc;
    }
    .stats-card.users {
        border-left-color: #f6c23e;
    }
    .stats-icon {
        font-size: 2rem;
        opacity: 0.3;
    }
    .stats-number {
        font-size: 1.8rem;
        font-weight: 600;
    }
    .stats-label {
        font-size: 0.9rem;
        color: #858796;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }
    .quick-actions .btn {
        padding: 1rem;
        text-align: left;
        margin-bottom: 1rem;
        border-radius: 10px;
        transition: all 0.3s;
    }
    .quick-actions .btn i {
        margin-right: 10px;
        font-size: 1.2rem;
    }
    .quick-actions .btn:hover {
        transform: translateX(5px);
    }
    .recent-activity {
        height: 400px;
        overflow-y: auto;
    }
    .activity-item {
        padding: 1rem;
        border-left: 3px solid #e3e6f0;
        margin-bottom: 1rem;
        background-color: #fff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s;
    }
    .activity-item:hover {
        border-left-color: #4e73df;
        background-color: #f8f9fc;
    }
    .welcome-section {
        background: linear-gradient(to right, #4e73df, #36b9cc);
        color: white;
        padding: 2rem;
        border-radius: 10px;
        margin-bottom: 2rem;
    }
</style>
EOT;

include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h2>Welcome, <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?>!</h2>
        <p class="mb-0">Here's what's happening in your store today.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="products.php" class="text-decoration-none">
                <div class="card stats-card products h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stats-label">Products</div>
                                <div class="stats-number"><?php echo number_format($total_products); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-box stats-icon text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="categories.php" class="text-decoration-none">
                <div class="card stats-card categories h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stats-label">Categories</div>
                                <div class="stats-number"><?php echo number_format($total_categories); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-folder stats-icon text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="orders.php" class="text-decoration-none">
                <div class="card stats-card orders h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stats-label">Orders</div>
                                <div class="stats-number"><?php echo number_format($total_orders); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart stats-icon text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <a href="users.php" class="text-decoration-none">
                <div class="card stats-card users h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="stats-label">Users</div>
                                <div class="stats-number"><?php echo number_format($total_users); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users stats-icon text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Quick Actions -->
        <div class="col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="add_product.php" class="btn btn-primary btn-block">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                        <a href="create_category.php" class="btn btn-success btn-block">
                            <i class="fas fa-folder-plus"></i> Create Category
                        </a>
                        <a href="orders.php" class="btn btn-info btn-block">
                            <i class="fas fa-list"></i> View Orders
                        </a>
                        <a href="users.php" class="btn btn-warning btn-block">
                            <i class="fas fa-user"></i> Manage Users
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-xl-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="recent-activity">
                        <!-- Add your recent activity items here -->
                        <div class="activity-item">
                            <h6 class="mb-1">New Order Received</h6>
                            <p class="mb-0 text-muted">Order #12345 - ₹1,299.00</p>
                            <small class="text-muted">5 minutes ago</small>
                        </div>
                        <div class="activity-item">
                            <h6 class="mb-1">Product Stock Updated</h6>
                            <p class="mb-0 text-muted">iPhone 13 Pro - Stock: 25</p>
                            <small class="text-muted">1 hour ago</small>
                        </div>
                        <div class="activity-item">
                            <h6 class="mb-1">New User Registration</h6>
                            <p class="mb-0 text-muted">john.doe@example.com</p>
                            <small class="text-muted">2 hours ago</small>
                        </div>
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
    $(document).ready(function() {
        // Add any dashboard-specific JavaScript here
        
        // Example: Animate statistics on page load
        $('.stats-number').each(function() {
            $(this).prop('Counter', 0).animate({
                Counter: $(this).text()
            }, {
                duration: 1000,
                easing: 'swing',
                step: function(now) {
                    $(this).text(Math.ceil(now).toLocaleString());
                }
            });
        });
    });
</script>
EOT;

include 'includes/footer.php';
?>