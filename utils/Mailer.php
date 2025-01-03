<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class Mailer {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // Server settings
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com'; // Or your SMTP host
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'your-email@gmail.com'; // SMTP username
        $this->mailer->Password = 'your-app-specific-password'; // SMTP password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = 587;
        
        // Default settings
        $this->mailer->isHTML(true);
        $this->mailer->setFrom('noreply@yourstore.com', 'Your Store Name');
    }

    public function sendOrderConfirmation($orderData, $userDetails) {
        try {
            $this->mailer->addAddress($userDetails['email'], $userDetails['name']);
            $this->mailer->Subject = 'Order Confirmation - #' . str_pad($orderData['order_id'], 6, '0', STR_PAD_LEFT);

            // Generate email content
            $body = $this->getOrderEmailTemplate($orderData, $userDetails);
            $this->mailer->Body = $body;
            
            // Plain text version
            $this->mailer->AltBody = strip_tags(str_replace('<br>', "\n", $body));

            return $this->mailer->send();
        } catch (Exception $e) {
            Logger::log('email_error', [
                'error' => $e->getMessage(),
                'order_id' => $orderData['order_id']
            ]);
            return false;
        }
    }

    private function getOrderEmailTemplate($orderData, $userDetails) {
        $orderNumber = str_pad($orderData['order_id'], 6, '0', STR_PAD_LEFT);
        
        // Generate items table
        $itemsHtml = '';
        $total = 0;
        foreach ($orderData['items'] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $total += $subtotal;
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>₹{$item['price']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>₹{$subtotal}</td>
                </tr>";
        }

        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f8f9fa; padding: 20px; text-align: center;'>
                    <h1 style='color: #333;'>Order Confirmation</h1>
                    <p style='font-size: 18px;'>Thank you for your order!</p>
                </div>

                <div style='padding: 20px;'>
                    <h2>Order Details</h2>
                    <p><strong>Order Number:</strong> #{$orderNumber}</p>
                    <p><strong>Order Date:</strong> " . date('F j, Y') . "</p>
                    <p><strong>Tracking Number:</strong> {$orderData['tracking_number']}</p>
                    <p><strong>Estimated Delivery:</strong> {$orderData['estimated_delivery']}</p>

                    <h3>Items Ordered</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <thead>
                            <tr style='background-color: #f8f9fa;'>
                                <th style='padding: 10px; text-align: left;'>Item</th>
                                <th style='padding: 10px; text-align: left;'>Quantity</th>
                                <th style='padding: 10px; text-align: left;'>Price</th>
                                <th style='padding: 10px; text-align: left;'>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='3' style='padding: 10px; text-align: right;'><strong>Total:</strong></td>
                                <td style='padding: 10px;'><strong>₹{$total}</strong></td>
                            </tr>
                        </tfoot>
                    </table>

                    <h3>Shipping Address</h3>
                    <p>
                        {$orderData['shipping_address']['street']}<br>
                        {$orderData['shipping_address']['city']}<br>
                        {$orderData['shipping_address']['state']} - {$orderData['shipping_address']['pincode']}
                    </p>

                    <div style='margin-top: 30px;'>
                        <p>Track your order: <a href='https://yourstore.com/track/{$orderData['tracking_number']}'>Click here</a></p>
                    </div>
                </div>

                <div style='background-color: #f8f9fa; padding: 20px; text-align: center; margin-top: 20px;'>
                    <p>If you have any questions, please contact our customer service.</p>
                    <p>Email: support@yourstore.com | Phone: +91 1234567890</p>
                </div>
            </div>";
    }
} 