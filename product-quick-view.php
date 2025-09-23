<?php
// product-quick-view.php - Quick view modal content
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_GET['id'])) {
    exit('Product ID required');
}

$productId = intval($_GET['id']);
$product = getProductById($productId);
$images = getProductImages($productId);

if (!$product) {
    exit('Product not found');
}

// Get product specifications
$stmt = $pdo->prepare("
    SELECT spec_name, spec_value 
    FROM product_specifications 
    WHERE product_id = ? 
    ORDER BY spec_name
");
$stmt->execute([$productId]);
$specifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if product is in compare list
$isInCompare = isInCompareList($productId);
$compareCount = getCompareCount();
?>

<div class="quick-view-container">
    <div class="quick-view-content">
        <!-- Close button -->
        <button class="quick-view-close" onclick="closeQuickView()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="quick-view-body">
            <!-- Product Images -->
            <div class="quick-view-images">
                <?php if (!empty($images)): ?>
                    <div class="main-image">
                        <img src="<?php echo htmlspecialchars($images[0]['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             id="main-quick-image">
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="thumbnail-images">
                            <?php foreach ($images as $index => $image): ?>
                                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     onclick="changeMainImage(this.src, this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="main-image">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Information -->
            <div class="quick-view-info">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                
                <!-- Price and Rating -->
                <div class="price-rating">
                    <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
                    <div class="rating">
                        <?php
                        $rating = $product['rating'];
                        $fullStars = floor($rating);
                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        
                        echo '<span class="stars">';
                        echo str_repeat('★', $fullStars);
                        echo $hasHalfStar ? '½' : '';
                        echo str_repeat('☆', $emptyStars);
                        echo '</span>';
                        ?>
                        <span class="rating-value">(<?php echo number_format($rating, 1); ?>)</span>
                        <span class="review-count"><?php echo $product['review_count']; ?> reviews</span>
                    </div>
                </div>

                <!-- Product Meta Information -->
                <div class="product-meta">
                    <div class="meta-item">
                        <span class="label">Seller:</span>
                        <span class="value"><?php echo htmlspecialchars($product['seller_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="label">Stock:</span>
                        <span class="value <?php echo $product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock'; ?>">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <?php echo $product['stock_quantity']; ?> available
                            <?php else: ?>
                                Out of Stock
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="label">SKU:</span>
                        <span class="value"><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <!-- Product Description -->
                <div class="product-description">
                    <h4>Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <!-- Product Specifications -->
                <?php if (!empty($specifications)): ?>
                <div class="product-specifications">
                    <h4>Specifications</h4>
                    <div class="spec-table">
                        <?php foreach ($specifications as $spec): ?>
                            <div class="spec-row">
                                <div class="spec-name"><?php echo htmlspecialchars($spec['spec_name']); ?></div>
                                <div class="spec-value"><?php echo htmlspecialchars($spec['spec_value']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quantity and Actions -->
                <div class="product-actions">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-input">
                                <button type="button" class="qty-btn minus" onclick="updateQuantity(-1)">-</button>
                                <input type="number" id="quantity" name="quantity" value="1" 
                                       min="1" max="<?php echo $product['stock_quantity']; ?>">
                                <button type="button" class="qty-btn plus" onclick="updateQuantity(1)">+</button>
                            </div>
                        </div>

                        <div class="action-buttons">
                            <button class="btn btn-primary add-to-cart-btn" 
                                    onclick="addToCartFromQuickView(<?php echo $product['id']; ?>)">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            
                            <button class="btn btn-secondary compare-btn <?php echo $isInCompare ? 'active' : ''; ?>" 
                                    onclick="toggleCompare(<?php echo $product['id']; ?>, this)"
                                    <?php echo $compareCount >= 5 && !$isInCompare ? 'disabled title="Maximum 5 products can be compared"' : ''; ?>>
                                <i class="fas fa-balance-scale"></i>
                                <span class="compare-text">
                                    <?php echo $isInCompare ? 'Remove from Compare' : 'Add to Compare'; ?>
                                </span>
                            </button>
                            
                            <button class="btn btn-outline wishlist-btn" 
                                    onclick="toggleWishlist(<?php echo $product['id']; ?>, this)">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="out-of-stock-actions">
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-times"></i> Out of Stock
                            </button>
                            <button class="btn btn-outline notify-btn" onclick="notifyWhenAvailable(<?php echo $product['id']; ?>)">
                                <i class="fas fa-bell"></i> Notify When Available
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="quick-actions">
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-link">
                            <i class="fas fa-eye"></i> View Full Details
                        </a>
                        <button class="btn btn-link share-btn" onclick="shareProduct(<?php echo $product['id']; ?>)">
                            <i class="fas fa-share-alt"></i> Share
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quick-view-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.quick-view-content {
    background: white;
    border-radius: 12px;
    max-width: 1000px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.quick-view-close {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(0, 0, 0, 0.1);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: background 0.3s;
}

.quick-view-close:hover {
    background: rgba(0, 0, 0, 0.2);
}

.quick-view-body {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    padding: 30px;
}

.quick-view-images .main-image {
    width: 100%;
    height: 400px;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 15px;
}

.quick-view-images .main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail-images {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.thumbnail {
    width: 60px;
    height: 60px;
    border-radius: 6px;
    cursor: pointer;
    border: 2px solid transparent;
    object-fit: cover;
    transition: border-color 0.3s;
}

.thumbnail.active,
.thumbnail:hover {
    border-color: #007bff;
}

.quick-view-info h2 {
    font-size: 24px;
    margin-bottom: 15px;
    color: #333;
}

.price-rating {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
}

.price {
    font-size: 28px;
    font-weight: bold;
    color: #e74c3c;
}

.rating {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stars {
    color: #ffc107;
    font-size: 16px;
}

.rating-value {
    color: #666;
    font-size: 14px;
}

.review-count {
    color: #999;
    font-size: 14px;
}

.product-meta {
    margin-bottom: 20px;
}

.meta-item {
    display: flex;
    margin-bottom: 8px;
}

.meta-item .label {
    font-weight: 600;
    min-width: 80px;
    color: #666;
}

.meta-item .value {
    color: #333;
}

.in-stock {
    color: #28a745;
}

.out-stock {
    color: #dc3545;
}

.product-description,
.product-specifications {
    margin-bottom: 20px;
}

.product-description h4,
.product-specifications h4 {
    font-size: 16px;
    margin-bottom: 10px;
    color: #333;
}

.spec-table {
    border: 1px solid #eee;
    border-radius: 6px;
    overflow: hidden;
}

.spec-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

.spec-row:not(:last-child) {
    border-bottom: 1px solid #eee;
}

.spec-name,
.spec-value {
    padding: 8px 12px;
    font-size: 14px;
}

.spec-name {
    background: #f8f9fa;
    font-weight: 600;
    color: #666;
    border-right: 1px solid #eee;
}

.spec-value {
    color: #333;
}

.quantity-selector {
    margin-bottom: 20px;
}

.quantity-selector label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.quantity-input {
    display: flex;
    align-items: center;
    width: 120px;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
}

.qty-btn {
    width: 32px;
    height: 40px;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    font-size: 16px;
    color: #666;
    transition: background 0.3s;
}

.qty-btn:hover {
    background: #e9ecef;
}

.quantity-input input {
    flex: 1;
    height: 40px;
    border: none;
    text-align: center;
    font-size: 14px;
    background: white;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-secondary.active {
    background: #28a745;
}

.btn-outline {
    background: transparent;
    border: 1px solid #ddd;
    color: #666;
}

.btn-outline:hover {
    background: #f8f9fa;
}

.btn-link {
    background: transparent;
    color: #007bff;
    padding: 8px 0;
}

.btn-link:hover {
    text-decoration: underline;
}

.quick-actions {
    display: flex;
    gap: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.out-of-stock-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .quick-view-body {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 20px;
    }
    
    .quick-view-images .main-image {
        height: 250px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        justify-content: center;
    }
}
</style>

<script>
// Quick view functionality
function changeMainImage(src, thumbnail) {
    const mainImage = document.getElementById('main-quick-image');
    mainImage.src = src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    if (thumbnail) {
        thumbnail.classList.add('active');
    }
}

function updateQuantity(change) {
    const input = document.getElementById('quantity');
    let currentValue = parseInt(input.value);
    let newValue = currentValue + change;
    
    if (newValue < 1) newValue = 1;
    if (newValue > parseInt(input.max)) newValue = parseInt(input.max);
    
    input.value = newValue;
}

function closeQuickView() {
    const quickView = document.querySelector('.quick-view-container');
    if (quickView) {
        quickView.remove();
    }
}

function addToCartFromQuickView(productId) {
    const quantity = parseInt(document.getElementById('quantity').value);
    const button = event.target.closest('.add-to-cart-btn');
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    
    // Use session-based cart or AJAX depending on login status
    <?php if (isLoggedIn()): ?>
        // Database cart for logged-in users
        fetch('ajax/add-to-cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&quantity=${quantity}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Product added to cart!', 'success');
                updateCartCount();
            } else {
                showNotification(data.message || 'Error adding to cart', 'error');
            }
        })
        .catch(error => {
            showNotification('Error adding to cart', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
        });
    <?php else: ?>
        // Session cart for guest users
        addToSessionCart(productId, quantity);
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
        showNotification('Product added to cart!', 'success');
        updateCartCount();
    <?php endif; ?>
}

function toggleCompare(productId, button) {
    const isActive = button.classList.contains('active');
    const action = isActive ? 'remove' : 'add';
    
    fetch('ajax/compare.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${action}&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (action === 'add') {
                button.classList.add('active');
                button.querySelector('.compare-text').textContent = 'Remove from Compare';
            } else {
                button.classList.remove('active');
                button.querySelector('.compare-text').textContent = 'Add to Compare';
            }
            updateCompareCount(data.count);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Error updating compare list', 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating compare list', 'error');
    });
}

function toggleWishlist(productId, button) {
    <?php if (!isLoggedIn()): ?>
        showNotification('Please login to add items to wishlist', 'error');
        return;
    <?php endif; ?>
    
    const icon = button.querySelector('i');
    const isActive = icon.classList.contains('fas');
    
    fetch('ajax/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=${isActive ? 'remove' : 'add'}&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (isActive) {
                icon.className = 'far fa-heart';
            } else {
                icon.className = 'fas fa-heart';
            }
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Error updating wishlist', 'error');
        }
    })
    .catch(error => {
        showNotification('Error updating wishlist', 'error');
    });
}

function notifyWhenAvailable(productId) {
    <?php if (!isLoggedIn()): ?>
        showNotification('Please login to get notified', 'error');
        return;
    <?php endif; ?>
    
    fetch('ajax/notify-availability.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        showNotification(data.message, data.success ? 'success' : 'error');
    })
    .catch(error => {
        showNotification('Error setting up notification', 'error');
    });
}

function shareProduct(productId) {
    const url = `${window.location.origin}/product.php?id=${productId}`;
    
    if (navigator.share) {
        navigator.share({
            title: document.querySelector('.quick-view-info h2').textContent,
            url: url
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Product link copied to clipboard!', 'success');
        });
    }
}

// Close quick view when clicking outside
document.querySelector('.quick-view-container').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuickView();
    }
});

// Close quick view with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeQuickView();
    }
});
</script>