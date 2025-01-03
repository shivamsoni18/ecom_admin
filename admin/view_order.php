<?php
session_start();
require_once(__DIR__ . '/../models/Order.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Order ID is required";
    header('Location: orders.php');
    exit();
}

$order = new Order();
$orderDetails = $order->getOrderById($_GET['id']);

if (!$orderDetails) {
    $_SESSION['error'] = "Order not found";
    header('Location: orders.php');
    exit();
}

// Get order items
$orderItems = $order->getOrderItems($_GET['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { padding-top: 70px; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order #<?php echo str_pad($orderDetails['id'], 8, '0', STR_PAD_LEFT); ?></h5>
                        <a href="orders.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Order Information</h6>
                                <p><strong>Order Date:</strong> <?php echo date('Y-m-d H:i', strtotime($orderDetails['created_at'])); ?></p>
                                <p><strong>Order Number:</strong> #<?php echo str_pad($orderDetails['id'], 8, '0', STR_PAD_LEFT); ?></p>
                                <?php if (!empty($orderDetails['transaction_id'])): ?>
                                    <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($orderDetails['transaction_id']); ?></p>
                                <?php endif; ?>
                                <p><strong>Status:</strong> 
                                    <span class="badge badge-<?php 
                                        echo $orderDetails['status'] === 'completed' ? 'success' : 
                                            ($orderDetails['status'] === 'cancelled' ? 'danger' : 
                                            ($orderDetails['status'] === 'processing' ? 'info' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($orderDetails['status']); ?>
                                    </span>
                                </p>
                                <p><strong>Payment Method:</strong> <?php echo ucfirst($orderDetails['payment_method']); ?></p>
                                <p><strong>Payment Status:</strong> 
                                    <span class="badge badge-<?php 
                                        echo $orderDetails['payment_status'] === 'paid' ? 'success' : 
                                            ($orderDetails['payment_status'] === 'failed' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($orderDetails['payment_status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Customer Information</h6>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($orderDetails['customer_name'] ?? 'Guest'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($orderDetails['customer_email'] ?? 'N/A'); ?></p>
                                <p><strong>Shipping Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($orderDetails['shipping_address'])); ?>
                                </p>
                            </div>
                        </div>

                        <h6 class="text-muted mb-4">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $subtotal = 0;
                                    while ($item = $orderItems->fetch(PDO::FETCH_ASSOC)): 
                                        $itemTotal = $item['price'] * $item['quantity'];
                                        $subtotal += $itemTotal;
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo '../uploads/' . $item['image']; ?>" 
                                                             alt="Product Image" 
                                                             class="mr-2"
                                                             style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                                        <?php if (!empty($item['sku'])): ?>
                                                            <br><small class="text-muted">SKU: <?php echo $item['sku']; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-right">₹<?php echo number_format($itemTotal, 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-right"><strong>Total:</strong></td>
                                        <td class="text-right"><strong>₹<?php echo number_format($orderDetails['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
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