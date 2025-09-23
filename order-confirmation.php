<?php
// Move all validation and redirects BEFORE any includes
session_start();
require_once 'config/database.php';

// Check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

requireLogin();

// Validate order ID parameter
if (!isset($_GET['id'])) {
    header("Location: user-dashboard.php");
    exit();
}

$orderId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

// Get order details
try {
    $stmt = $pdo->prepare("SELECT o.*, u.username, u.email, u.first_name, u.last_name, u.address, u.phone 
                          FROM orders o 
                          JOIN users u ON o.user_id = u.id 
                          WHERE o.id = ? AND o.user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: user-dashboard.php?error=order_not_found");
        exit();
    }

    // Get order items
    $stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url 
                          FROM order_items oi 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE oi.order_id = ?");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Order confirmation error: ' . $e->getMessage());
    header("Location: user-dashboard.php?error=database_error");
    exit();
}

// Now include header after all redirects are done
require_once 'includes/header.php';


// Get order items
$stmt = $pdo->prepare("SELECT oi.*, p.name, p.image_url 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate order total from items (for verification)
$calculatedTotal = 0;
foreach ($orderItems as $item) {
    $calculatedTotal += $item['price'] * $item['quantity'];
}
?>

<h1>Order Confirmation</h1>

<div class="order-confirmation">
    <div class="confirmation-header">
        <h2>Thank you for your order!</h2>
        <p>Your order has been received and is now being processed. Below are your order details.</p>
    </div>

    <div class="order-details">
        <h3>Order Information</h3>
        <div class="order-info-grid">
            <div class="info-item">
                <strong>Order Number:</strong> #<?php echo $order['id']; ?>
            </div>
            <div class="info-item">
                <strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?>
            </div>
            <div class="info-item">
                <strong>Order Status:</strong> <span class="status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
            </div>
            <div class="info-item">
                <strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
            </div>
            <div class="info-item">
                <strong>Payment Status:</strong> <?php echo ucfirst($order['payment_status']); ?>
            </div>
            <div class="info-item">
                <strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?>
            </div>
        </div>
    </div>

    <div class="customer-details">
        <h3>Customer Information</h3>
        <div class="customer-info-grid">
            <div class="info-item">
                <strong>Name:</strong> <?php echo $order['first_name'] . ' ' . $order['last_name']; ?>
            </div>
            <div class="info-item">
                <strong>Email:</strong> <?php echo $order['email']; ?>
            </div>
            <div class="info-item">
                <strong>Phone:</strong> <?php echo $order['phone'] ? $order['phone'] : 'N/A'; ?>
            </div>
            <div class="info-item">
                <strong>Shipping Address:</strong> <?php echo $order['shipping_address']; ?>
            </div>
        </div>
    </div>

    <div class="order-items">
        <h3>Order Items</h3>
        <table class="order-items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>" width="50">
                                <span><?php echo $item['name']; ?></span>
                            </div>
                        </td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" align="right"><strong>Subtotal:</strong></td>
                    <td>₱<?php echo number_format($calculatedTotal, 2); ?></td>
                </tr>
                <tr>
                    <td colspan="3" align="right"><strong>Total:</strong></td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="order-actions">
        <a href="user-dashboard.php" class="back-button">Back to Dashboard</a>
        <button onclick="window.print()" class="print-button">Print Confirmation</button>
        
        <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
            <a href="contact.php" class="contact-button">Contact Support</a>
        <?php endif; ?>
    </div>

    <div class="order-support">
        <h3>Need Help?</h3>
        <p>If you have any questions about your order, please contact our customer support team.</p>
        <p>Email: support@ecommerce.example.com | Phone: (555) 123-4567</p>
    </div>
</div>

<?php
// Send confirmation email if this is the first time viewing the confirmation
if (!isset($_SESSION['viewed_order_' . $orderId])) {
    $_SESSION['viewed_order_' . $orderId] = true;
    sendOrderConfirmationEmail($order, $orderItems);
}

require_once 'includes/footer.php';

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($order, $orderItems) {
    // Email content would be implemented here
    // This is a placeholder function
    $to = $order['email'];
    $subject = "Order Confirmation #" . $order['id'];
    $message = "Thank you for your order!\n\n";
    $message .= "Order Details:\n";
    $message .= "Order ID: #" . $order['id'] . "\n";
    $message .= "Order Date: " . date('F j, Y', strtotime($order['created_at'])) . "\n";
    $message .= "Total Amount: ₱" . number_format($order['total_amount'], 2) . "\n\n";
    
    $message .= "Items:\n";
    foreach ($orderItems as $item) {
        $message .= $item['name'] . " x " . $item['quantity'] . " - ₱" . number_format($item['price'] * $item['quantity'], 2) . "\n";
    }
    
    $message .= "\nShipping Address:\n" . $order['shipping_address'] . "\n\n";
    $message .= "Thank you for shopping with us!\n";
    
    $headers = "From: no-reply@ecommerce.example.com\r\n";
    $headers .= "Reply-To: support@ecommerce.example.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // In a real implementation, you would use mail() or a library like PHPMailer
    // mail($to, $subject, $message, $headers);
}
?>