<?php
// create email-service.php (optional)
function sendOrderConfirmationEmail($order, $orderItems) {
    // Use PHPMailer or another library for better email handling
    $to = $order['email'];
    $subject = "Order Confirmation #" . $order['id'] . " - E-Commerce Store";
    
    // HTML email content
    $message = '<!DOCTYPE html>
    <html>
    <head>
        <title>Order Confirmation</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #f8f8f8; padding: 20px; text-align: center; }
            .order-details { margin: 20px 0; }
            .order-item { border-bottom: 1px solid #eee; padding: 10px 0; }
            .footer { margin-top: 20px; padding: 20px; background: #f8f8f8; text-align: center; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>Thank you for your order!</h1>
                <p>Your order has been received and is now being processed.</p>
            </div>
            
            <div class="order-details">
                <h2>Order Details</h2>
                <p><strong>Order ID:</strong> #' . $order['id'] . '</p>
                <p><strong>Order Date:</strong> ' . date('F j, Y', strtotime($order['created_at'])) . '</p>
                <p><strong>Total Amount:</strong> ₱' . number_format($order['total_amount'], 2) . '</p>
            </div>
            
            <h2>Order Items</h2>';
    
    foreach ($orderItems as $item) {
        $message .= '
            <div class="order-item">
                <p><strong>' . $item['name'] . '</strong></p>
                <p>Quantity: ' . $item['quantity'] . '</p>
                <p>Price: ₱' . number_format($item['price'], 2) . '</p>
                <p>Total: ₱' . number_format($item['price'] * $item['quantity'], 2) . '</p>
            </div>';
    }
    
    $message .= '
            <div class="shipping-details">
                <h2>Shipping Address</h2>
                <p>' . nl2br($order['shipping_address']) . '</p>
            </div>
            
            <div class="footer">
                <p>If you have any questions about your order, please contact our customer support team.</p>
                <p>Email: support@ecommerce.example.com | Phone: (555) 123-4567</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@ecommerce.example.com" . "\r\n";
    $headers .= "Reply-To: support@ecommerce.example.com" . "\r\n";
    
    // Send email
    return mail($to, $subject, $message, $headers);
}
?>