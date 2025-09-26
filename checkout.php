<?php
// Move all processing logic to the top before any includes


require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle item removal via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_item') {
    header('Content-Type: application/json');
    
    $productId = (int)($_POST['product_id'] ?? 0);
    $checkoutType = $_POST['checkout_type'] ?? 'cart';
    
    if ($checkoutType === 'buy_now') {
        // For buy now, we can't remove items - redirect to previous page
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot remove items from Buy Now checkout',
            'redirect' => 'javascript:history.back()'
        ]);
        exit();
    }
    
    // Handle cart item removal
    try {
        if (isLoggedIn()) {
            // Remove from database cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $productId]);
            $affected = $stmt->rowCount();
        } else {
            // Remove from session cart
            $affected = 0;
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ($item['product_id'] == $productId) {
                        unset($_SESSION['cart'][$key]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                        $affected = 1;
                        break;
                    }
                }
            }
        }
        
        if ($affected > 0) {
            echo json_encode(['success' => true, 'message' => 'Item removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
        }
    } catch (Exception $e) {
        error_log('Remove item error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error removing item']);
    }
    exit();
}

// Check if user is logged in (optional - comment out if guest checkout allowed)
/*
if (!isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}
*/

// Get user information if logged in
$userData = null;
if (isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$checkoutItems = [];
$checkoutTotal = 0;
$checkoutType = 'cart'; // 'cart' or 'buy_now'

// Check if this is a "buy now" checkout
if (isset($_GET['buy_now']) && isset($_SESSION['buy_now_item'])) {
    $checkoutType = 'buy_now';
    $buyNowItem = $_SESSION['buy_now_item'];
    
    // Check if buy now item is still valid (not too old - 30 minutes)
    if ((time() - $buyNowItem['timestamp']) > 1800) {
        unset($_SESSION['buy_now_item']);
        header("Location: index.php?message=Session expired, please try again");
        exit();
    }
    
    // Validate product is still available and price hasn't changed significantly
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, status, image_url FROM products WHERE id = ?");
    $stmt->execute([$buyNowItem['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product || $product['status'] !== 'active') {
        unset($_SESSION['buy_now_item']);
        header("Location: index.php?message=Product no longer available");
        exit();
    }
    
    if ($product['stock_quantity'] < $buyNowItem['quantity']) {
        unset($_SESSION['buy_now_item']);
        $stockMessage = $product['stock_quantity'] == 0 ? "Product is out of stock" : "Only {$product['stock_quantity']} items available";
        header("Location: index.php?message=" . urlencode($stockMessage));
        exit();
    }
    
    // Update price if it changed (and update total)
    if (abs($product['price'] - $buyNowItem['price']) > 0.01) {
        $_SESSION['buy_now_item']['price'] = (float)$product['price'];
        $_SESSION['buy_now_item']['total'] = (float)$product['price'] * $buyNowItem['quantity'];
        $buyNowItem = $_SESSION['buy_now_item']; // Update local copy
        
        // Optional: Show price change warning
        $_SESSION['price_change_warning'] = "Price has been updated from $" . number_format($buyNowItem['price'], 2) . " to $" . number_format($product['price'], 2);
    }
    
    // Update image URL if changed
    if ($product['image_url'] !== $buyNowItem['image_url']) {
        $_SESSION['buy_now_item']['image_url'] = $product['image_url'] ?? 'images/placeholder.jpg';
        $buyNowItem = $_SESSION['buy_now_item'];
    }
    
    $checkoutItems[] = [
        'product_id' => $buyNowItem['id'],
        'name' => $buyNowItem['name'],
        'price' => $buyNowItem['price'],
        'quantity' => $buyNowItem['quantity'],
        'image_url' => $buyNowItem['image_url'],
        'total' => $buyNowItem['total']
    ];
    
    $checkoutTotal = $buyNowItem['total'];
    
} else {
    // Regular cart checkout
    $checkoutType = 'cart';
    
    // Get cart items (use session or database based on your implementation)
    if (isLoggedIn()) {
        $checkoutItems = getCartItems(); // Database cart
    } else {
        $checkoutItems = getSessionCartForDisplay(); // Session cart
    }
    
    if (empty($checkoutItems)) {
        header("Location: cart.php?message=Your cart is empty");
        exit();
    }
    
    // Validate cart
    if (isLoggedIn()) {
        $validation = validateCartForCheckout();
    } else {
        $validation = validateSessionCart();
    }
    
    if (!$validation['success']) {
        $_SESSION['checkout_errors'] = $validation['errors'] ?? [$validation['message']];
        header("Location: cart.php");
        exit();
    }
    
    // Calculate total
    foreach ($checkoutItems as $item) {
        $checkoutTotal += isset($item['total']) ? $item['total'] : ($item['price'] * $item['quantity']);
    }
}

// Pre-fill form data with user information or POST data
$formData = [
    'customer_name' => '',
    'customer_email' => '',
    'customer_phone' => '',
    'shipping_address' => '',
    'payment_method' => ''
];

// Handle form submission - BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $errors = [];
    
    // Validate form data
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';
    
    if (empty($customerName)) $errors[] = 'Name is required';
    if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    if (empty($customerPhone)) $errors[] = 'Phone number is required';
    if (empty($shippingAddress)) $errors[] = 'Shipping address is required';
    if (empty($paymentMethod)) $errors[] = 'Payment method is required';
    
    // Re-validate items before placing order
    if ($checkoutType === 'buy_now') {
        // Validate buy now item one more time
        if (!isset($_SESSION['buy_now_item'])) {
            $errors[] = 'Buy now session expired. Please try again.';
        } else {
            $stmt = $pdo->prepare("SELECT id, price, stock_quantity, status FROM products WHERE id = ?");
            $stmt->execute([$_SESSION['buy_now_item']['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product || $product['status'] !== 'active') {
                $errors[] = 'Product is no longer available';
            } elseif ($product['stock_quantity'] < $_SESSION['buy_now_item']['quantity']) {
                $errors[] = 'Insufficient stock available';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, shipping_address, payment_method, total_amount, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
            $stmt->execute([$userId, $shippingAddress, $paymentMethod, $checkoutTotal]);
            $orderId = $pdo->lastInsertId();
            
            // Store customer information for guest orders
            if (!isLoggedIn()) {
                $_SESSION['guest_order_' . $orderId] = [
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'order_id' => $orderId,
                    'order_type' => $checkoutType
                ];
            }
            
            // Add order items and update stock
            foreach ($checkoutItems as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            $pdo->commit();
            
            // Clear appropriate session data
            if ($checkoutType === 'buy_now') {
                // Only clear buy now session
                unset($_SESSION['buy_now_item']);
                unset($_SESSION['price_change_warning']);
            } else {
                // Clear cart session/database
                if (isLoggedIn()) {
                    // Clear database cart
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                } else {
                    // Clear session cart
                    $_SESSION['cart'] = [];
                }
            }
            
            // Redirect to success page
            header("Location: order-success.php?order_id=" . $orderId . "&type=" . $checkoutType);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = 'Error processing order. Please try again.';
            error_log('Checkout error: ' . $e->getMessage());
        }
    }
    
    // Store form data for re-display if there were errors
    $formData = [
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'shipping_address' => $shippingAddress,
        'payment_method' => $paymentMethod
    ];
} elseif ($userData) {
    // Auto-fill with user's profile data only if not a POST request
    $formData = [
        'customer_name' => trim(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? '')),
        'customer_email' => $userData['email'] ?? '',
        'customer_phone' => $userData['phone'] ?? '',
        'shipping_address' => $userData['address'] ?? '',
        'payment_method' => ''
    ];
}

// NOW include header after all processing is done
require_once 'includes/header.php';
?>

<div class="checkout-container">
    <h1>Checkout</h1>
    
    <?php if ($checkoutType === 'buy_now'): ?>
        <div class="checkout-type-info">
            <span class="badge badge-primary">Buy Now Checkout</span>
            <p class="checkout-note">You are purchasing this item directly without adding it to your cart.</p>
        </div>
    <?php endif; ?>
    
    <?php if (isLoggedIn() && $userData): ?>
        <div class="auto-fill-info">
            <div class="alert alert-info">
                <strong>Information Auto-filled:</strong> Your profile information has been automatically filled in the form below. 
                You can modify any field if needed.
                <a href="profile.php" class="update-profile-link">Update Profile</a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['price_change_warning'])): ?>
        <div class="alert alert-warning">
            <strong>Price Update:</strong> <?php echo htmlspecialchars($_SESSION['price_change_warning']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="checkout-content">
        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="order-items" id="order-items-container">
                <?php foreach ($checkoutItems as $item): ?>
                    <div class="order-item" data-product-id="<?php echo $item['product_id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'images/placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="item-image">
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Price: $<?php echo number_format($item['price'], 2); ?></p>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div class="item-total">
                            $<?php echo number_format(isset($item['total']) ? $item['total'] : ($item['price'] * $item['quantity']), 2); ?>
                        </div>
                        <?php if ($checkoutType === 'cart'): ?>
                            <button class="remove-item-btn" 
                                    data-product-id="<?php echo $item['product_id']; ?>"
                                    title="Remove item">
                                ×
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="order-total" id="order-total">
                <strong>Total: $<?php echo number_format($checkoutTotal, 2); ?></strong>
            </div>
            
            <?php if ($checkoutType === 'buy_now'): ?>
                <div class="buy-now-info">
                    <small class="text-muted">
                        <i>Note: This purchase will not affect your shopping cart.</i>
                    </small>
                </div>
            <?php endif; ?>
            
            <div id="empty-cart-message" style="display: none;" class="empty-cart-notice">
                <p>Your cart is empty. <a href="index.php">Continue shopping</a></p>
            </div>
        </div>
        
        <div class="checkout-form" id="checkout-form">
            <h2>Billing & Shipping Information</h2>
            <?php if (!isLoggedIn()): ?>
                <div class="guest-checkout-note">
                    <p><strong>Guest Checkout:</strong> Create an account to save your information for faster checkout next time.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="customer_name">Full Name *</label>
                    <input type="text" id="customer_name" name="customer_name" 
                           value="<?php echo htmlspecialchars($formData['customer_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_email">Email Address *</label>
                    <input type="email" id="customer_email" name="customer_email" 
                           value="<?php echo htmlspecialchars($formData['customer_email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_phone">Phone Number *</label>
                    <input type="tel" id="customer_phone" name="customer_phone" 
                           value="<?php echo htmlspecialchars($formData['customer_phone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="shipping_address">Shipping Address *</label>
                    <textarea id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($formData['shipping_address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="credit_card" <?php echo ($formData['payment_method'] === 'credit_card') ? 'selected' : ''; ?>>Credit Card</option>
                        <option value="debit_card" <?php echo ($formData['payment_method'] === 'debit_card') ? 'selected' : ''; ?>>Debit Card</option>
                        <option value="paypal" <?php echo ($formData['payment_method'] === 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                        <option value="cash_on_delivery" <?php echo ($formData['payment_method'] === 'cash_on_delivery') ? 'selected' : ''; ?>>Cash on Delivery</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <?php if ($checkoutType === 'buy_now'): ?>
                        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
                    <?php else: ?>
                        <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                    <?php endif; ?>
                    <button type="submit" name="place_order" class="btn btn-primary">Place Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Remove item functionality
document.addEventListener('DOMContentLoaded', function() {
    const removeButtons = document.querySelectorAll('.remove-item-btn');
    
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const orderItem = this.closest('.order-item');
            
            if (confirm('Are you sure you want to remove this item?')) {
                // Show loading state
                this.disabled = true;
                this.textContent = '...';
                
                // Send AJAX request
                const formData = new FormData();
                formData.append('action', 'remove_item');
                formData.append('product_id', productId);
                formData.append('checkout_type', '<?php echo $checkoutType; ?>');
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the item from DOM
                        orderItem.style.transition = 'opacity 0.3s ease';
                        orderItem.style.opacity = '0';
                        
                        setTimeout(() => {
                            orderItem.remove();
                            
                            // Check if any items left
                            const remainingItems = document.querySelectorAll('.order-item');
                            if (remainingItems.length === 0) {
                                // Show empty cart message and hide checkout form
                                document.getElementById('empty-cart-message').style.display = 'block';
                                document.getElementById('checkout-form').style.display = 'none';
                                document.getElementById('order-total').textContent = 'Total: $0.00';
                            } else {
                                // Recalculate total
                                updateOrderTotal();
                            }
                        }, 300);
                    } else {
                        alert(data.message || 'Error removing item');
                        // Reset button state
                        this.disabled = false;
                        this.textContent = '×';
                        
                        if (data.redirect) {
                            eval(data.redirect);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                    // Reset button state
                    this.disabled = false;
                    this.textContent = '×';
                });
            }
        });
    });
    
    function updateOrderTotal() {
        let total = 0;
        const itemTotals = document.querySelectorAll('.item-total');
        
        itemTotals.forEach(itemTotal => {
            const priceText = itemTotal.textContent.replace('$', '').replace(',', '');
            total += parseFloat(priceText) || 0;
        });
        
        document.getElementById('order-total').innerHTML = '<strong>Total: $' + total.toFixed(2) + '</strong>';
    }
});
</script>

<style>
.checkout-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.checkout-type-info {
    background-color: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px; 
    padding: 15px;
    margin-bottom: 20px;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.badge-primary {
    background-color: #007bff;
    color: white;
}

.checkout-note {
    margin: 10px 0 0 0;
    color: #666;
    font-size: 14px;
}

.alert {
    padding: 12px 16px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.alert-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.alert-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert ul {
    margin: 0;
    padding-left: 20px;
}

.checkout-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-top: 20px;
}

.order-summary {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.order-items {
    margin: 20px 0;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #dee2e6;
    position: relative;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 15px;
}

.item-details {
    flex-grow: 1;
}

.item-details h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.item-details p {
    margin: 2px 0;
    color: #666;
    font-size: 14px;
}

.item-total {
    font-weight: bold;
    font-size: 16px;
    margin-right: 10px;
}

.remove-item-btn {
    background-color: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    line-height: 1;
}

.remove-item-btn:hover {
    background-color: #c82333;
    transform: scale(1.1);
}

.remove-item-btn:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    transform: none;
}

.order-total {
    border-top: 2px solid #dee2e6;
    padding-top: 15px;
    font-size: 18px;
    text-align: right;
}

.buy-now-info {
    margin-top: 15px;
    text-align: center;
}

.empty-cart-notice {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}

.empty-cart-notice a {
    color: #007bff;
    text-decoration: none;
}

.empty-cart-notice a:hover {
    text-decoration: underline;
}

.text-muted {
    color: #6c757d;
}

.checkout-form {
    background-color: white;
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-actions {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    text-align: center;
    transition: background-color 0.3s;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    font-weight: 500;
}

.btn-primary:hover {
    background-color: #0056b3;
}

/* Responsive design */
@media (max-width: 768px) {
    .checkout-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .order-item {
        flex-direction: column;
        text-align: center;
        padding: 20px 15px;
    }
    
    .item-image {
        margin: 0 0 10px 0;
    }
    
    .item-total {
        margin-right: 0;
        margin-top: 10px;
    }
    
    .remove-item-btn {
        position: absolute;
        top: 10px;
        right: 10px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>