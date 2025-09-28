<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in BEFORE including header
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

require_once 'includes/header.php';

// Handle add to cart from product detail page
if (isset($_POST['add_to_cart'])) {
    $productId = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    
    $result = addToCart($productId, $quantity);
    if ($result['success']) {
        $successMessage = "Product added to cart!";
    } else {
        $errorMessage = $result['message'];
    }
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $productId = intval($_GET['remove']);
    if (removeFromCart($productId)) {
        $successMessage = "Item removed from cart!";
    } else {
        $errorMessage = "Error removing item from cart.";
    }
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    $hasErrors = false;
    $messages = [];
    
    foreach ($_POST['quantities'] as $productId => $quantity) {
        $result = updateCartQuantity(intval($productId), intval($quantity));
        if (!$result['success']) {
            $hasErrors = true;
            $messages[] = $result['message'];
        }
    }
    
    if (!$hasErrors) {
        $successMessage = "Cart updated successfully!";
    } else {
        $errorMessage = implode(', ', $messages);
    }
}

$cartItems = getCartItems();
$cartTotal = getCartTotal();
?>

<h1>Shopping Cart</h1>

<?php if (isset($successMessage)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
<?php endif; ?>

<?php if (isset($errorMessage)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
<?php endif; ?>

<?php if (empty($cartItems)): ?>
    <div class="empty-cart">
        <p>Your cart is empty.</p>
        <a href="products.php" class="btn btn-primary">Continue Shopping</a>
    </div>
<?php else: ?>
    <form method="POST" action="" id="cart-form">
        <div class="cart-table-container">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr data-product-id="<?php echo $item['product_id']; ?>">
                            <td class="product-info">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="product-image">
                                <div class="product-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="stock-info">Stock: <?php echo $item['stock_quantity']; ?> available</p>
                                </div>
                            </td>
                            <td class="price">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td class="quantity">
                                <div class="quantity-controls">
                                    <button type="button" class="qty-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, -1)">-</button>
                                    <input type="number" 
                                           name="quantities[<?php echo $item['product_id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock_quantity']; ?>"
                                           class="qty-input"
                                           onchange="calculateItemTotal(<?php echo $item['product_id']; ?>, <?php echo $item['price']; ?>)">
                                    <button type="button" class="qty-btn" onclick="updateQuantity(<?php echo $item['product_id']; ?>, 1)">+</button>
                                </div>
                            </td>
                            <td class="item-total">₱<span id="total-<?php echo $item['product_id']; ?>"><?php echo number_format($item['price'] * $item['quantity'], 2); ?></span></td>
                            <td class="actions">
                                <a href="cart.php?remove=<?php echo $item['product_id']; ?>" 
                                   class="btn btn-remove" 
                                   onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="cart-total">
                        <td colspan="3"><strong>Total</strong></td>
                        <td><strong>₱<span id="cart-total"><?php echo $cartTotal; ?></span></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="cart-actions">
            <button type="submit" name="update_cart" class="btn btn-update">Update Cart</button>
            <a href="products.php" class="btn btn-continue">Continue Shopping</a>
            <a href="checkout.php" class="btn btn-checkout">Proceed to Checkout</a>
        </div>
    </form>
<?php endif; ?>



<style>

/* Shopping Cart Styles */
body {
    background: #130325 !important;
    min-height: 100vh;
}

main {
    background: #130325;
    min-height: 100vh;
    padding: 20px;
}

.cart-container {
    max-width: 1200px;
    margin: 0 auto;
}

h1 {
    color: var(--primary-light);
    text-align: center;
    margin: 20px 0;
    font-size: 2rem;
    border-bottom: 3px solid var(--accent-yellow);
    padding-bottom: 10px;
}

/* Alert messages */
.alert {
    padding: 15px;
    margin: 20px 0;
    border-radius: 5px;
    font-weight: 500;
}

.alert-success {
    background: var(--primary-dark);
    color: var(--accent-yellow);
    border: 1px solid var(--accent-yellow);
}

.alert-error {
    background: var(--primary-dark);
    color: #dc3545;
    border: 1px solid #dc3545;
}

/* Empty cart */
.empty-cart {
    text-align: center;
    padding: 40px 20px;
    background: var(--primary-dark);
    border: 1px solid var(--accent-yellow);
    border-radius: 8px;
    margin: 20px 0;
}

.empty-cart p {
    font-size: 1.2rem;
    color: var(--primary-light);
    margin-bottom: 20px;
}

/* Cart table container */
.cart-table-container {
    overflow-x: auto;
    margin: 20px 0;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
    background: var(--primary-dark);
    border: 1px solid var(--accent-yellow);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    border-radius: 8px;
    overflow: hidden;
}

.cart-table th {
    background: var(--accent-yellow);
    color: var(--primary-dark);
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.cart-table td {
    padding: 15px;
    border-bottom: 1px solid rgba(255, 215, 54, 0.3);
    color: var(--primary-light);
}

.cart-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Product info cell */
.product-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 5px;
}

.product-details h4 {
    margin: 0 0 5px 0;
    color: var(--primary-light);
}

.stock-info {
    margin: 0;
    font-size: 0.9rem;
    color: var(--primary-light);
}

/* Price and totals */
.price, .item-total {
    font-weight: 600;
    color: var(--accent-yellow);
}

/* Quantity controls */
.quantity-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: 1px solid var(--accent-yellow);
    background: var(--primary-dark);
    color: var(--primary-light);
    cursor: pointer;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.qty-btn:hover {
    background: var(--accent-yellow);
    color: var(--primary-dark);
}

.qty-input {
    width: 60px;
    text-align: center;
    border: 1px solid var(--accent-yellow);
    background: var(--primary-dark);
    color: var(--primary-light);
    padding: 5px;
    border-radius: 4px;
}

/* Cart total row */
.cart-total {
    background: rgba(255, 215, 54, 0.1);
    color: var(--primary-light);
    font-weight: bold;
}

.cart-total td {
    border-bottom: none;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 1rem;
    text-align: center;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--accent-yellow);
    color: var(--primary-dark);
}

.btn-primary:hover {
    background: #e6c200;
}

.btn-remove {
    background: #dc3545;
    color: white;
    font-size: 0.9rem;
    padding: 8px 16px;
}

.btn-remove:hover {
    background: #c82333;
}

.btn-update {
    background: var(--accent-yellow);
    color: var(--primary-dark);
}

.btn-update:hover {
    background: #e6c200;
}

.btn-continue {
    background: rgba(255, 255, 255, 0.1);
    color: var(--primary-light);
    border: 1px solid var(--accent-yellow);
}

.btn-continue:hover {
    background: rgba(255, 255, 255, 0.2);
}

.btn-checkout {
    background: var(--accent-yellow);
    color: var(--primary-dark);
}

.btn-checkout:hover {
    background: #e6c200;
}

/* Cart actions */
.cart-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin: 30px 0;
    flex-wrap: wrap;
}

/* Responsive design */
@media (max-width: 768px) {
    .cart-table {
        font-size: 0.9rem;
    }
    
    .cart-table th,
    .cart-table td {
        padding: 10px 8px;
    }
    
    .product-info {
        flex-direction: column;
        text-align: center;
    }
    
    .product-image {
        width: 50px;
        height: 50px;
    }
    
    .cart-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 200px;
    }
    
    .qty-input {
        width: 50px;
    }
}


</style>

<script>
// Update quantity with plus/minus buttons
function updateQuantity(productId, change) {
    const input = document.querySelector(`input[name="quantities[${productId}]"]`);
    const currentValue = parseInt(input.value);
    const maxValue = parseInt(input.max);
    const minValue = parseInt(input.min);
    
    let newValue = currentValue + change;
    
    // Ensure value is within bounds
    if (newValue < minValue) newValue = minValue;
    if (newValue > maxValue) newValue = maxValue;
    
    input.value = newValue;
    
    // Calculate new item total
    const row = input.closest('tr');
    const priceText = row.querySelector('.price').textContent;
    const price = parseFloat(priceText.replace('₱', ''));
    
    calculateItemTotal(productId, price);
}

// Calculate item total when quantity changes
function calculateItemTotal(productId, price) {
    const input = document.querySelector(`input[name="quantities[${productId}]"]`);
    const quantity = parseInt(input.value);
    const total = price * quantity;
    
    document.getElementById(`total-${productId}`).textContent = total.toFixed(2);
    
    // Recalculate cart total
    calculateCartTotal();
}

// Calculate total cart amount
function calculateCartTotal() {
    let total = 0;
    const itemTotals = document.querySelectorAll('[id^="total-"]');
    
    itemTotals.forEach(function(element) {
        const value = parseFloat(element.textContent);
        total += value;
    });
    
    document.getElementById('cart-total').textContent = total.toFixed(2);
}

// Auto-save cart when quantities change (optional)
function autoSaveCart() {
    const form = document.getElementById('cart-form');
    const formData = new FormData(form);
    formData.append('update_cart', '1');
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            console.log('Cart auto-saved');
        }
    })
    .catch(error => {
        console.error('Auto-save failed:', error);
    });
}

// Optional: Auto-save cart after 2 seconds of inactivity
let autoSaveTimeout;
document.addEventListener('input', function(e) {
    if (e.target.matches('.qty-input')) {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(autoSaveCart, 2000);
    }
});
</script>

<?php  ?>