<?php 
require_once 'includes/header.php';
require_once 'config/database.php';
// this is index.php
// Get featured products
$featuredProducts = getFeaturedProducts(8);
?>

<section class="featured-products">
    <div class="container">
        <h2 style="margin-top: 60px; margin-bottom: 40px; color: #F9F9F9; text-transform: uppercase;">Featured Products</h2>
    <div class="products-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card">
                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
                <p class="rating">
                    Rating: <?php echo number_format($product['rating'], 1); ?> 
                    (<?php echo $product['review_count']; ?> reviews)
                </p>
                <div class="product-actions">
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                       class="view-details-link">View Details</a>
                    <button onclick="addToCart(<?php echo $product['id']; ?>, 1)" 
                            class="btn btn-cart" 
                            data-product-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button onclick="buyNow(<?php echo $product['id']; ?>, 1)" 
                            class="btn btn-buy"
                            data-buy-product-id="<?php echo $product['id']; ?>">
                        Buy Now
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    </div>
</section>

<section class="categories">
    <div class="container">
        <h2>Shop by Category</h2>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="categories-list">
        <?php foreach ($categories as $category): ?>
            <a href="products.php?category=<?php echo $category['id']; ?>" 
               class="category-link">
                <?php echo htmlspecialchars($category['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    </div>
</section>

<!-- Cart notification -->
<div id="cart-notification" class="cart-notification" style="display: none;">
    <span id="notification-message"></span>
</div>

<!-- Buy Now notification -->
<div id="buy-now-notification" class="buy-now-notification" style="display: none;">
    <span id="buy-now-message"></span>
</div>

<script>
// Add to cart function (unchanged)
function addToCart(productId, quantity = 1) {
    console.log('Adding to cart:', productId, quantity); // Debug log
    
    // Show loading state
    const button = document.querySelector(`button[data-product-id="${productId}"]`);
    if (!button) {
        console.error('Cart button not found for product:', productId);
        return;
    }
    
    const originalText = button.textContent;
    button.textContent = 'Adding...';
    button.disabled = true;
    
    // Make AJAX request to add item to cart
    fetch('ajax/cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => {
        console.log('Cart response status:', response.status); // Debug log
        return response.json();
    })
    .then(data => {
        console.log('Cart response data:', data); // Debug log
        
        if (data.success) {
            // Show success notification
            showNotification('Product added to cart!', 'success');
            
            // Update cart count if you have a cart counter in header
            if (typeof updateCartCount === 'function') {
                updateCartCount(data.cartCount);
            }
            
            // Temporarily change button text
            button.textContent = '✓ Added';
            setTimeout(() => {
                button.textContent = originalText;
                button.disabled = false;
            }, 2000);
        } else {
            // Show error notification
            showNotification(data.message || 'Error adding to cart', 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Cart Error:', error);
        showNotification('Error adding to cart', 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Improved Buy Now function with better error handling
function buyNow(productId, quantity = 1) {
    console.log('Buy Now clicked:', productId, quantity); // Debug log
    
    // Validate inputs
    if (!productId || productId <= 0) {
        console.error('Invalid product ID:', productId);
        showBuyNowNotification('Invalid product selected', 'error');
        return;
    }
    
    if (!quantity || quantity <= 0) {
        console.error('Invalid quantity:', quantity);
        showBuyNowNotification('Invalid quantity specified', 'error');
        return;
    }
    
    // Show loading state
    const button = document.querySelector(`button[data-buy-product-id="${productId}"]`);
    if (!button) {
        console.error('Buy Now button not found for product:', productId);
        showBuyNowNotification('Button not found', 'error');
        return;
    }
    
    const originalText = button.textContent;
    button.textContent = 'Processing...';
    button.disabled = true;
    
    console.log('Sending buy now request...'); // Debug log
    
    // Make AJAX request to buy now handler
    fetch('ajax/buy-now.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: parseInt(productId),
            quantity: parseInt(quantity)
        })
    })
    .then(response => {
        console.log('Buy Now response status:', response.status); // Debug log
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Raw response:', text); // Debug log
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e, 'Raw text:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Buy Now response data:', data); // Debug log
        
        if (data.success) {
            // Show buy now notification
            showBuyNowNotification('Redirecting to checkout...', 'success');
            
            // Short delay before redirect for user feedback
            setTimeout(() => {
                console.log('Redirecting to:', data.redirect_url); // Debug log
                window.location.href = data.redirect_url;
            }, 1500);
        } else {
            // Show error notification
            const errorMessage = data.message || 'Error processing buy now request';
            console.error('Buy Now failed:', errorMessage);
            showBuyNowNotification(errorMessage, 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Buy Now Error:', error);
        showBuyNowNotification('Error processing request: ' + error.message, 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Show cart notification function
function showNotification(message, type = 'success') {
    const notification = document.getElementById('cart-notification');
    const messageElement = document.getElementById('notification-message');
    
    if (!notification || !messageElement) {
        console.error('Cart notification elements not found');
        return;
    }
    
    messageElement.textContent = message;
    notification.className = 'cart-notification ' + type;
    notification.style.display = 'block';
    
    // Hide after 3 seconds
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}

// Show buy now notification function
function showBuyNowNotification(message, type = 'success') {
    const notification = document.getElementById('buy-now-notification');
    const messageElement = document.getElementById('buy-now-message');
    
    if (!notification || !messageElement) {
        console.error('Buy Now notification elements not found');
        return;
    }
    
    messageElement.textContent = message;
    notification.className = 'buy-now-notification ' + type;
    notification.style.display = 'block';
    
    // Hide after 4 seconds (unless it's a success redirect)
    if (type !== 'success') {
        setTimeout(() => {
            notification.style.display = 'none';
        }, 4000);
    }
}

// Update cart count in header (if you have a cart counter)
function updateCartCount(count) {
    const cartCounter = document.querySelector('.cart-count');
    if (cartCounter) {
        cartCounter.textContent = count;
        
        // Add a little animation to draw attention
        cartCounter.style.transform = 'scale(1.2)';
        setTimeout(() => {
            cartCounter.style.transform = 'scale(1)';
        }, 200);
    }
}

// Function to get current cart count (useful for page load)
function loadCartCount() {
    fetch('ajax/cart-handler.php?action=get_count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.count);
        }
    })
    .catch(error => {
        console.error('Error loading cart count:', error);
    });
}

// Load cart count when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...'); // Debug log
    loadCartCount();
    
    // Test if buy now buttons exist
    const buyNowButtons = document.querySelectorAll('[data-buy-product-id]');
    console.log('Found buy now buttons:', buyNowButtons.length); // Debug log
});
</script>

<style>
/* Body background */
body {
    background: linear-gradient(135deg, #1a0a2e 0%, #16213e 100%);
    min-height: 100vh;
    color: #F9F9F9;
}

/* Text color overrides for better contrast */
h1, h2, h3, h4, h5, h6 {
    color: #F9F9F9;
}

p, span, div {
    color: #F9F9F9;
}

/* Product grid styles */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    background: #f8f9fa;
    color: #333;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.product-card img {
    width: 100%;
    max-width: 200px;
    height: 200px;
    object-fit: cover;
    margin-bottom: 10px;
}

.product-name {
    margin: 10px 0;
    font-size: 1.3em;
    font-weight: bold;
    color: #130325;
}

.price {
    font-weight: bold;
    color: #130325;
    font-size: 1.1em;
    margin: 8px 0;
}

.rating {
    color: #666;
    font-size: 0.9em;
    letter-spacing: -2px;
}

/* Product action buttons */
.product-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.3s, transform 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.view-details-link {
    color: #130325;
    text-decoration: underline;
    font-size: 0.9em;
    text-align: center;
    margin-bottom: 10px;
    transition: color 0.2s;
}

.view-details-link:hover {
    color: #FFD736;
}

.btn-cart {
    background-color: #FFD736;
    color: #130325;
    font-weight: 600;
}

.btn-cart:hover:not(:disabled) {
    background-color: #e6c230;
}

.btn-cart:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-buy {
    background-color: #130325;
    color: #F9F9F9;
    font-weight: bold;
}

.btn-buy:hover:not(:disabled) {
    background-color: rgba(19, 3, 37, 0.8);
}

.btn-buy:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Categories section */
.categories {
    margin: 40px 0;
}

.categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
}

.category-link {
    padding: 10px 20px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 20px;
    text-decoration: none;
    color: #333;
    transition: background-color 0.3s, transform 0.2s;
}

.category-link:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
}

/* Notification styles */
.cart-notification, .buy-now-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 4px;
    z-index: 1000;
    animation: slideIn 0.3s ease;
    max-width: 300px;
    word-wrap: break-word;
}

.buy-now-notification {
    top: 80px; /* Position below cart notification */
}

.cart-notification.success, .buy-now-notification.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.cart-notification.error, .buy-now-notification.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive design */
@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .product-actions {
        font-size: 12px;
    }
    
    .btn {
        padding: 6px 12px;
        font-size: 12px;
    }
    
    .cart-notification, .buy-now-notification {
        right: 10px;
        top: 10px;
        max-width: calc(100vw - 40px);
    }
    
    .buy-now-notification {
        top: 70px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>