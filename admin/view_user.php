<?php
session_start();
require_once(__DIR__ . '/../models/User.php');
require_once(__DIR__ . '/../models/Order.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if id is provided
if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user = new User();
$user->id = $_GET['id'];

// Get user data
if (!$user->readOne()) {
    $_SESSION['error'] = "User not found";
    header('Location: users.php');
    exit();
}

// Get user statistics
$order = new Order();
$stats = [
    'total_orders' => $order->getUserOrderCount($user->id),
    'total_spent' => $order->getUserTotalSpent($user->id),
    'last_order' => $order->getUserLastOrder($user->id),
    'avg_order_value' => $order->getUserAverageOrderValue($user->id)
];

// Get recent orders
$recent_orders = $order->getUserOrders($user->id, 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User - Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            padding-top: 70px;
        }
        .user-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .status-active { background-color: #28a745; color: white; }
        .status-inactive { background-color: #dc3545; color: white; }
        .role-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
        .role-admin { background-color: #007bff; color: white; }
        .role-customer { background-color: #6c757d; color: white; }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .stat-card i {
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">User Details</h4>
                    <div>
                        <a href="edit_user.php?id=<?php echo $user->id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit User
                        </a>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- User Profile -->
                        <div class="col-md-4">
                            <div class="text-center mb-4">
                                <img src="<?php echo $user->avatar ? '../' . $user->avatar : '../assets/images/default-avatar.png'; ?>" 
                                     class="user-avatar mb-3" 
                                     alt="User Avatar">
                                <h4><?php echo htmlspecialchars($user->name); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($user->email); ?></p>
                                <div class="mb-2">
                                    <span class="role-badge role-<?php echo $user->role; ?>">
                                        <?php echo ucfirst($user->role); ?>
                                    </span>
                                </div>
                                <span class="status-badge status-<?php echo $user->status; ?>">
                                    <?php echo ucfirst($user->status); ?>
                                </span>
                            </div>

                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">Contact Information</h5>
                                    <p class="mb-1">
                                        <i class="fas fa-phone text-muted mr-2"></i>
                                        <?php echo $user->phone ? htmlspecialchars($user->phone) : 'Not provided'; ?>
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-map-marker-alt text-muted mr-2"></i>
                                        <?php echo $user->address ? htmlspecialchars($user->address) : 'Not provided'; ?>
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-calendar text-muted mr-2"></i>
                                        Joined: <?php echo date('M j, Y', strtotime($user->created_at)); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- User Statistics and Orders -->
                        <div class="col-md-8">
                            <!-- Statistics -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="stat-card bg-primary text-white">
                                        <i class="fas fa-shopping-cart"></i>
                                        <h3><?php echo $stats['total_orders']; ?></h3>
                                        <p class="mb-0">Total Orders</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card bg-success text-white">
                                        <i class="fas fa-rupee-sign"></i>
                                        <h3>₹<?php echo number_format($stats['total_spent'], 2); ?></h3>
                                        <p class="mb-0">Total Spent</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card bg-info text-white">
                                        <i class="fas fa-shopping-basket"></i>
                                        <h3>₹<?php echo number_format($stats['avg_order_value'], 2); ?></h3>
                                        <p class="mb-0">Average Order Value</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card bg-warning text-dark">
                                        <i class="fas fa-clock"></i>
                                        <h3><?php echo $stats['last_order'] ? date('M j, Y', strtotime($stats['last_order'])) : 'Never'; ?></h3>
                                        <p class="mb-0">Last Order</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Orders -->
                            <div class="card mt-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent Orders</h5>
                                    <a href="orders.php?user_id=<?php echo $user->id; ?>" class="btn btn-sm btn-primary">
                                        View All Orders
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($order = $recent_orders->fetch(PDO::FETCH_ASSOC)): ?>
                                                    <tr>
                                                        <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                        <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                                        <td>
                                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                                <?php if ($recent_orders->rowCount() == 0): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No orders found</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
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