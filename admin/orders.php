<?php
session_start();
require_once(__DIR__ . '/../models/Order.php');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$order = new Order();
$orders = $order->getAllOrders();

// Set page title
$page_title = 'Orders Management';

// Additional CSS for orders page
$additional_css = <<<EOT
<style>
    .status-badge {
        padding: 0.5em 0.8em;
        font-weight: 500;
        text-transform: capitalize;
    }
    .order-details {
        font-size: 0.9rem;
    }
    .order-id {
        font-weight: 600;
        color: #4e73df;
    }
    .customer-info {
        color: #5a5c69;
    }
    .order-date {
        color: #858796;
    }
    .order-total {
        font-weight: 600;
        color: #2e59d9;
    }
    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .table td {
        vertical-align: middle;
    }
</style>
EOT;

include 'includes/header.php';
?>

<div class="container-fluid">
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
            <h4 class="mb-0">Orders Management</h4>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-primary">
                    <i class="fas fa-download"></i> Export Orders
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="ordersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $orders->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td>
                                    <span class="order-id">#<?php echo $order['id']; ?></span>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-date">
                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                        <br>
                                        <small><?php echo date('h:i A', strtotime($order['created_at'])); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="order-total">
                                        ₹<?php echo number_format($order['total_amount'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'shipped' => 'primary',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ][$order['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $order['payment_status'] === 'paid' ? 'success' : 'warning'; ?> status-badge">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-info btn-sm" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-primary btn-sm" 
                                                onclick="updateStatus(<?php echo $order['id']; ?>)"
                                                title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="updateStatusForm" action="update_order_status.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="orderIdInput">
                    <div class="form-group">
                        <label for="status">Order Status</label>
                        <select class="form-control" name="status" id="statusSelect">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Additional scripts
$additional_scripts = <<<EOT
<script>
    $(document).ready(function() {
        $('#ordersTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 25,
            "language": {
                "search": "Search orders:",
                "lengthMenu": "Show _MENU_ orders per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ orders",
                "infoEmpty": "Showing 0 to 0 of 0 orders",
                "zeroRecords": "No matching orders found"
            }
        });
    });

    function updateStatus(orderId) {
        $('#orderIdInput').val(orderId);
        $('#updateStatusModal').modal('show');
    }
</script>
EOT;

include 'includes/footer.php';
?> 