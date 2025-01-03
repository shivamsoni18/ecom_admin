<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recent_orders = $product->getRecentOrders(5);
            if ($recent_orders && $recent_orders->rowCount() > 0):
                while ($order = $recent_orders->fetch(PDO::FETCH_ASSOC)):
            ?>
            <tr>
                <td>#<?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                <td>₹<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                <td>
                    <span class="badge badge-<?php 
                        echo $order['status'] === 'completed' ? 'success' : 
                            ($order['status'] === 'pending' ? 'warning' : 
                            ($order['status'] === 'cancelled' ? 'danger' : 'secondary')); 
                    ?>">
                        <?php echo ucfirst($order['status'] ?? 'unknown'); ?>
                    </span>
                </td>
            </tr>
            <?php 
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="4" class="text-center">No recent orders</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>