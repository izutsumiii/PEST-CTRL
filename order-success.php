<?php
require_once 'includes/header.php';
require_once 'config/database.php';
//this is order-success.php

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($orderId <= 0) {
    header("Location: products.php");
    exit();
}

try {
    // Get order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header("Location: products.php");
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
    error_log('Order success page error: ' . $e->getMessage());
    header("Location: products.php");
    exit();
}
?>

<div class="order-success-container">
    <div class="success-header">
        <div class="success-icon">✓</div>
        <h1>Order Placed Successfully!</h1>
        <p>Thank you for your purchase. Your order has been received and is being processed.</p>
    </div>
    
    <div class="order-details">
        <h2>Order Details</h2>
        <div class="order-info">
            <div class="info-row">
                <span class="label">Order ID:</span>
                <span class="value">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Order Date:</span>
                <span class="value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
            </div>
            <?php 
            // Get customer info from session for guest orders or from user table for logged-in users
            $customerName = '';
            $customerEmail = '';
            $customerPhone = '';
            
            if ($order['user_id']) {
                // Logged-in user - get info from users table
                $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
                $stmt->execute([$order['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $customerName = trim($user['first_name'] . ' ' . $user['last_name']);
                    $customerEmail = $user['email'];
                    $customerPhone = $user['phone'];
                }
            } else {
                // Guest order - get info from session
                $guestInfo = $_SESSION['guest_order_' . $orderId] ?? null;
                if ($guestInfo) {
                    $customerName = $guestInfo['customer_name'];
                    $customerEmail = $guestInfo['customer_email'];
                    $customerPhone = $guestInfo['customer_phone'];
                }
            }
            ?>
            <div class="info-row">
                <span class="label">Customer Name:</span>
                <span class="value"><?php echo htmlspecialchars($customerName); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Email:</span>
                <span class="value"><?php echo htmlspecialchars($customerEmail); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value"><?php echo htmlspecialchars($customerPhone); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Shipping Address:</span>
                <span class="value"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Payment Method:</span>
                <span class="value"><?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?></span>
            </div>
            <div class="info-row">
                <span class="label">Order Status:</span>
                <span class="value status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="order-items">
        <h2>Items Ordered</h2>
        <div class="items-list">
            <?php foreach ($orderItems as $item): ?>
                <div class="order-item">
                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'images/placeholder.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         class="item-image">
                    <div class="item-details">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p>Price: ₱<?php echo number_format($item['price'], 2); ?></p>
                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                    </div>
                    <div class="item-total">
                        ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="order-total">
            <strong>Total Amount: ₱<?php echo number_format($order['total_amount'], 2); ?></strong>
        </div>
    </div>
    
    <div class="next-steps">
        <h2>What's Next?</h2>
        <div class="steps-list">
            <div class="step">
                <span class="step-number">1</span>
                <div class="step-content">
                    <h4>Order Confirmation</h4>
                    <p>You'll receive an order confirmation email at <?php echo htmlspecialchars($customerEmail); ?> shortly.</p>
                </div>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <div class="step-content">
                    <h4>Processing</h4>
                    <p>Your order is being processed. You'll be notified when it ships.</p>
                </div>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <div class="step-content">
                    <h4>Delivery</h4>
                    <p>Your order will be delivered to the provided shipping address within 5-7 business days.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="action-buttons">
        <a href="products.php" class="btn btn-primary">Continue Shopping</a>
        <!-- <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-secondary">View Order Details</a> -->
        <button onclick="window.print()" class="btn btn-outline">Print Order</button>
    </div>
</div>



<style>


/* Order Success Page Styles */
.order-success-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
    font-family: 'Arial', sans-serif;
    color: #333;
}

.success-header {
    text-align: center;
    background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
    border-radius: 12px;
    padding: 40px 20px;
    margin-bottom: 30px;
    border: 2px solid #4CAF50;
}

.success-icon {
    font-size: 4rem;
    color: #4CAF50;
    background: white;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.success-header h1 {
    color: #2e7d32;
    margin: 0 0 10px;
    font-size: 2.2rem;
    font-weight: bold;
}

.success-header p {
    color: #4a7c59;
    font-size: 1.1rem;
    margin: 0;
}

.order-details, .order-items, .next-steps {
    background: white;
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border: 1px solid #e0e0e0;
}

.order-details h2, .order-items h2, .next-steps h2 {
    color: #333;
    font-size: 1.5rem;
    margin: 0 0 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f0f0f0;
}

.order-info .info-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid #f5f5f5;
}

.order-info .info-row:last-child {
    border-bottom: none;
}

.info-row .label {
    font-weight: 600;
    color: #555;
    min-width: 150px;
}

.info-row .value {
    flex: 1;
    text-align: right;
    color: #333;
}

.status-pending {
    color: #ff9800;
    font-weight: bold;
}

.status-processing {
    color: #2196F3;
    font-weight: bold;
}

.status-completed {
    color: #4CAF50;
    font-weight: bold;
}

.status-cancelled {
    color: #f44336;
    font-weight: bold;
}

.items-list {
    margin-bottom: 20px;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 15px;
    background: #fafafa;
    transition: box-shadow 0.3s ease;
}

.order-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.item-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    margin-right: 15px;
    border: 1px solid #ddd;
}

.item-details {
    flex: 1;
}

.item-details h4 {
    margin: 0 0 8px;
    color: #333;
    font-size: 1.1rem;
}

.item-details p {
    margin: 4px 0;
    color: #666;
    font-size: 0.9rem;
}

.item-total {
    font-weight: bold;
    font-size: 1.1rem;
    color: #2e7d32;
    margin-left: 15px;
}

.order-total {
    text-align: right;
    padding: 20px 0 0;
    border-top: 2px solid #e0e0e0;
    font-size: 1.3rem;
    color: #2e7d32;
}

.steps-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.step {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    border-left: 4px solid #4CAF50;
}

.step-number {
    background: #4CAF50;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-right: 15px;
    flex-shrink: 0;
}

.step-content h4 {
    margin: 0 0 8px;
    color: #333;
    font-size: 1.1rem;
}

.step-content p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
    padding: 20px 0;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    text-align: center;
    cursor: pointer;
    border: none;
    font-size: 1rem;
    transition: all 0.3s ease;
    min-width: 150px;
}

.btn-primary {
    background: #4CAF50;
    color: white;
}

.btn-primary:hover {
    background: #45a049;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #2196F3;
    color: white;
}

.btn-secondary:hover {
    background: #1976D2;
    transform: translateY(-1px);
}

.btn-outline {
    background: transparent;
    color: #666;
    border: 2px solid #ddd;
}

.btn-outline:hover {
    background: #f5f5f5;
    border-color: #bbb;
}

/* Responsive Design */
@media (max-width: 768px) {
    .order-success-container {
        padding: 15px;
    }
    
    .success-header {
        padding: 30px 15px;
    }
    
    .success-header h1 {
        font-size: 1.8rem;
    }
    
    .success-icon {
        width: 60px;
        height: 60px;
        font-size: 2.5rem;
    }
    
    .order-details, .order-items, .next-steps {
        padding: 20px;
    }
    
    .info-row {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 5px;
    }
    
    .info-row .value {
        text-align: left !important;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }
    
    .item-image {
        margin: 0 0 10px 0;
    }
    
    .item-total {
        margin: 10px 0 0 0;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    .steps-list {
        gap: 15px;
    }
    
    .step {
        padding: 15px;
    }
}

@media (max-width: 480px) {
    .success-header h1 {
        font-size: 1.5rem;
    }
    
    .success-header p {
        font-size: 1rem;
    }
    
    .order-details h2, .order-items h2, .next-steps h2 {
        font-size: 1.3rem;
    }
}

</style>