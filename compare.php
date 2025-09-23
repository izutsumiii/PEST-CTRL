<?php
// Move all includes and header calls to the top, before any output
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get product IDs from URL parameter
$productIds = isset($_GET['products']) ? $_GET['products'] : '';
if (empty($productIds)) {
    header('Location: products.php');
    exit();
}

// Clean and validate product IDs
$productIdArray = array_filter(array_map('intval', explode(',', $productIds)));
if (empty($productIdArray) || count($productIdArray) < 2 || count($productIdArray) > 4) {
    header('Location: products.php?error=invalid_comparison');
    exit();
}

// Fetch products with their details, ratings, and reviews
$placeholders = str_repeat('?,', count($productIdArray) - 1) . '?';
$sql = "SELECT p.*, c.name as category_name,
               COALESCE(AVG(r.rating), 0) as rating,
               COUNT(r.id) as review_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.id IN ($placeholders)
        GROUP BY p.id
        ORDER BY FIELD(p.id, " . implode(',', $productIdArray) . ")";

$stmt = $pdo->prepare($sql);
$stmt->execute($productIdArray);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If we don't have enough products, redirect back
if (count($products) < 2) {
    header('Location: products.php?error=products_not_found');
    exit();
}

// Get recent reviews for each product separately
$recentReviews = [];
foreach ($productIdArray as $productId) {
    $reviewSql = "SELECT rating, comment, created_at, 
                         COALESCE(user_id, 'Anonymous') as reviewer
                  FROM reviews 
                  WHERE product_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT 3";
    $reviewStmt = $pdo->prepare($reviewSql);
    $reviewStmt->execute([$productId]);
    $recentReviews[$productId] = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product features/specifications for comparison
$featuresSql = "SELECT product_id, feature_name, feature_value 
                FROM product_features 
                WHERE product_id IN ($placeholders)
                ORDER BY product_id, feature_name";
$featuresStmt = $pdo->prepare($featuresSql);
$featuresStmt->execute($productIdArray);
$allFeatures = $featuresStmt->fetchAll(PDO::FETCH_ASSOC);

// Organize features by product
$productFeatures = [];
$allFeatureNames = [];
foreach ($allFeatures as $feature) {
    $productFeatures[$feature['product_id']][$feature['feature_name']] = $feature['feature_value'];
    $allFeatureNames[] = $feature['feature_name'];
}
$allFeatureNames = array_unique($allFeatureNames);
?>

<style>
.comparison-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.comparison-header {
    text-align: center;
    margin-bottom: 30px;
}

.comparison-header h1 {
    color: #333;
    margin-bottom: 10px;
}

.comparison-actions {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #1e7e34;
}

.comparison-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.comparison-table th,
.comparison-table td {
    padding: 15px;
    text-align: center;
    border-bottom: 1px solid #dee2e6;
    vertical-align: top;
}

.comparison-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
    position: sticky;
    top: 0;
    z-index: 10;
}

.comparison-table .feature-label {
    background: #e9ecef;
    font-weight: 600;
    text-align: left;
    color: #495057;
    width: 200px;
    min-width: 200px;
}

.product-column {
    width: calc((100% - 200px) / <?php echo count($products); ?>);
    min-width: 250px;
}

.product-image {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    margin: 0 auto 15px;
    display: block;
    border: 2px solid #dee2e6;
}

.product-name {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    line-height: 1.3;
}

.product-price {
    font-size: 24px;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 10px;
}

.rating-display {
    margin-bottom: 15px;
}

.stars {
    color: #ffc107;
    font-size: 18px;
    margin-bottom: 5px;
}

.rating-text {
    font-size: 14px;
    color: #666;
}

.stock-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 15px;
}

.in-stock {
    background: #d4edda;
    color: #155724;
}

.low-stock {
    background: #fff3cd;
    color: #856404;
}

.out-of-stock {
    background: #f8d7da;
    color: #721c24;
}

.product-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.product-actions .btn {
    font-size: 12px;
    padding: 8px 12px;
}

.comparison-value {
    font-weight: 500;
}

.best-value {
    background: #d1ecf1;
    font-weight: 600;
    color: #0c5460;
}

.feature-missing {
    color: #6c757d;
    font-style: italic;
}

.description-cell {
    max-width: 200px;
    text-align: left;
    font-size: 14px;
    line-height: 1.4;
}

.category-badge {
    background: #e9ecef;
    color: #495057;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.reviews-preview {
    text-align: left;
    font-size: 12px;
    max-height: 100px;
    overflow-y: auto;
}

.review-item {
    margin-bottom: 8px;
    padding: 6px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}

.review-rating {
    color: #ffc107;
    font-weight: 600;
}

.review-text {
    color: #666;
    margin: 2px 0;
}

.review-author {
    color: #888;
    font-size: 10px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .comparison-table {
        font-size: 14px;
    }
    
    .product-image {
        width: 100px;
        height: 100px;
    }
    
    .product-name {
        font-size: 16px;
    }
    
    .product-price {
        font-size: 20px;
    }
}

@media (max-width: 768px) {
    .comparison-container {
        padding: 10px;
    }
    
    .comparison-table {
        font-size: 12px;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 8px;
    }
    
    .product-image {
        width: 80px;
        height: 80px;
    }
    
    .product-name {
        font-size: 14px;
    }
    
    .product-price {
        font-size: 18px;
    }
    
    .feature-label {
        width: 120px;
        min-width: 120px;
    }
    
    .product-actions {
        gap: 4px;
    }
    
    .product-actions .btn {
        font-size: 10px;
        padding: 6px 8px;
    }
}

/* Sticky header for long comparisons */
@media (min-width: 769px) {
    .comparison-table-container {
        max-height: 80vh;
        overflow-y: auto;
    }
}

/* Print styles */
@media print {
    .comparison-actions {
        display: none;
    }
    
    .product-actions {
        display: none;
    }
    
    .comparison-table {
        box-shadow: none;
        border: 1px solid #000;
    }
}
</style>

<div class="comparison-container">
    <div class="comparison-header">
        <h1>Product Comparison</h1>
        <p>Compare <?php echo count($products); ?> products side by side</p>
    </div>

    <div class="comparison-actions">
        <a href="products.php" class="btn btn-secondary">← Back to Products</a>
        <!-- <button onclick="printComparison()" class="btn btn-primary">🖨️ Print Comparison</button>
        <button onclick="shareComparison()" class="btn btn-primary">📤 Share Comparison</button> -->
    </div>

    <div class="comparison-table-container">
        <table class="comparison-table">
            <thead>
                <tr>
                    <th class="feature-label">Product Details</th>
                    <?php foreach ($products as $product): ?>
                        <th class="product-column">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'default-image.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="product-image"
                                 onerror="this.src='default-image.jpg'">
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <!-- Product Names -->
                <tr>
                    <td class="feature-label">Product Name</td>
                    <?php foreach ($products as $product): ?>
                        <td>
                            <div class="product-name">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   style="text-decoration: none; color: inherit;">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Prices -->
                <tr>
                    <td class="feature-label">Price</td>
                    <?php 
                    $minPrice = min(array_column($products, 'price'));
                    foreach ($products as $product): 
                        $isBestPrice = ($product['price'] == $minPrice);
                    ?>
                        <td class="<?php echo $isBestPrice ? 'best-value' : ''; ?>">
                            <div class="product-price">
                                ₱<?php echo number_format($product['price'], 2); ?>
                                <?php if ($isBestPrice): ?>
                                    <small style="display: block; color: #28a745; font-size: 12px;">Best Price!</small>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Ratings -->
                <tr>
                    <td class="feature-label">Rating</td>
                    <?php 
                    $maxRating = max(array_column($products, 'rating'));
                    foreach ($products as $product): 
                        $rating = floatval($product['rating']);
                        $isBestRating = ($rating == $maxRating && $rating > 0);
                        $fullStars = floor($rating);
                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                    ?>
                        <td class="<?php echo $isBestRating ? 'best-value' : ''; ?>">
                            <div class="rating-display">
                                <div class="stars">
                                    <?php echo str_repeat('★', $fullStars); ?>
                                    <?php echo $hasHalfStar ? '☆' : ''; ?>
                                    <?php echo str_repeat('☆', $emptyStars); ?>
                                </div>
                                <div class="rating-text">
                                    <?php echo number_format($rating, 1); ?>/5.0
                                    <br><small>(<?php echo $product['review_count']; ?> reviews)</small>
                                    <?php if ($isBestRating && $rating > 0): ?>
                                        <br><small style="color: #28a745;">Highest Rated!</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Stock Status -->
                <tr>
                    <td class="feature-label">Availability</td>
                    <?php foreach ($products as $product): 
                        $stock = intval($product['stock_quantity'] ?? 0);
                        if ($stock > 20) {
                            $stockClass = 'in-stock';
                            $stockText = "In Stock ($stock)";
                        } elseif ($stock > 0) {
                            $stockClass = 'low-stock';
                            $stockText = "Low Stock ($stock)";
                        } else {
                            $stockClass = 'out-of-stock';
                            $stockText = "Out of Stock";
                        }
                    ?>
                        <td>
                            <span class="stock-status <?php echo $stockClass; ?>">
                                <?php echo $stockText; ?>
                            </span>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Category -->
                <tr>
                    <td class="feature-label">Category</td>
                    <?php foreach ($products as $product): ?>
                        <td>
                            <span class="category-badge">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </span>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Description -->
                <tr>
                    <td class="feature-label">Description</td>
                    <?php foreach ($products as $product): ?>
                        <td class="description-cell">
                            <?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 200)); ?>
                            <?php if (strlen($product['description'] ?? '') > 200): ?>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   style="color: #007bff; text-decoration: none;">...read more</a>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Product Features -->
                <?php if (!empty($allFeatureNames)): ?>
                    <?php foreach ($allFeatureNames as $featureName): ?>
                        <tr>
                            <td class="feature-label"><?php echo htmlspecialchars($featureName); ?></td>
                            <?php foreach ($products as $product): ?>
                                <td class="comparison-value">
                                    <?php if (isset($productFeatures[$product['id']][$featureName])): ?>
                                        <?php echo htmlspecialchars($productFeatures[$product['id']][$featureName]); ?>
                                    <?php else: ?>
                                        <span class="feature-missing">Not specified</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Recent Reviews -->
                <tr>
                    <td class="feature-label">Recent Reviews</td>
                    <?php foreach ($products as $product): ?>
                        <td>
                            <div class="reviews-preview">
                                <?php 
                                if (isset($recentReviews[$product['id']]) && !empty($recentReviews[$product['id']])) {
                                    foreach ($recentReviews[$product['id']] as $review) {
                                        $reviewRating = intval($review['rating']);
                                        $reviewComment = $review['comment'] ?? '';
                                        $reviewer = $review['reviewer'] ?? 'Anonymous';
                                        $reviewDate = isset($review['created_at']) ? date('M j', strtotime($review['created_at'])) : '';
                                        
                                        echo "<div class='review-item'>";
                                        echo "<div class='review-rating'>" . str_repeat('★', $reviewRating) . str_repeat('☆', 5 - $reviewRating) . "</div>";
                                        if (!empty($reviewComment)) {
                                            echo "<div class='review-text'>" . htmlspecialchars(substr($reviewComment, 0, 50)) . (strlen($reviewComment) > 50 ? '...' : '') . "</div>";
                                        }
                                        echo "<div class='review-author'>- " . htmlspecialchars($reviewer) . ($reviewDate ? " (" . $reviewDate . ")" : "") . "</div>";
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<span class='feature-missing'>No reviews yet</span>";
                                }
                                ?>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>

                <!-- Actions -->
                <tr>
                    <td class="feature-label">Actions</td>
                    <?php foreach ($products as $product): ?>
                        <td>
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary">View Details</a>
                                <button onclick="addToCart(<?php echo $product['id']; ?>, 1)" 
                                        class="btn btn-success"
                                        data-product-id="<?php echo $product['id']; ?>">
                                    Add to Cart
                                </button>
                                <button onclick="buyNow(<?php echo $product['id']; ?>, 1)" 
                                        class="btn btn-primary"
                                        data-buy-product-id="<?php echo $product['id']; ?>">
                                    Buy Now
                                </button>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Notifications -->
<div id="cart-notification" class="cart-notification" style="display: none;">
    <span id="notification-message"></span>
</div>

<div id="buy-now-notification" class="buy-now-notification" style="display: none;">
    <span id="buy-now-message"></span>
</div>

<script>
// Print comparison function
function printComparison() {
    window.print();
}

// Share comparison function
function shareComparison() {
    const url = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: 'Product Comparison',
            text: 'Check out this product comparison',
            url: url
        });
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(url).then(() => {
            alert('Comparison URL copied to clipboard!');
        }).catch(() => {
            prompt('Copy this URL to share:', url);
        });
    }
}

// Add to cart function (reused from products.php)
function addToCart(productId, quantity = 1) {
    const button = document.querySelector(`button[data-product-id="${productId}"]`);
    if (!button) return;
    
    const originalText = button.textContent;
    button.textContent = 'Adding...';
    button.disabled = true;
    
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!', 'success');
            button.textContent = '✓ Added';
            setTimeout(() => {
                button.textContent = originalText;
                button.disabled = false;
            }, 2000);
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding to cart', 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Buy Now function (reused from products.php)
function buyNow(productId, quantity = 1) {
    const button = document.querySelector(`button[data-buy-product-id="${productId}"]`);
    if (!button) return;
    
    const originalText = button.textContent;
    button.textContent = 'Processing...';
    button.disabled = true;
    
    fetch('ajax/buy-now.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showBuyNowNotification('Redirecting to checkout...', 'success');
            setTimeout(() => {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            }, 1000);
        } else {
            showBuyNowNotification(data.message || 'Error processing buy now', 'error');
            button.textContent = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showBuyNowNotification('Error processing buy now', 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Notification functions (reused from products.php)
function showNotification(message, type = 'success') {
    const notification = document.getElementById('cart-notification');
    const messageElement = document.getElementById('notification-message');
    
    if (!notification || !messageElement) return;
    
    messageElement.textContent = message;
    notification.className = 'cart-notification ' + type;
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}

function showBuyNowNotification(message, type = 'success') {
    const notification = document.getElementById('buy-now-notification');
    const messageElement = document.getElementById('buy-now-message');
    
    if (!notification || !messageElement) return;
    
    messageElement.textContent = message;
    notification.className = 'buy-now-notification ' + type;
    notification.style.display = 'block';
    
    if (type !== 'success') {
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }
}

// Smooth scrolling for better UX
document.addEventListener('DOMContentLoaded', function() {
    // Add smooth scrolling behavior
    document.documentElement.style.scrollBehavior = 'smooth';
    
    // Highlight best values on load
    setTimeout(() => {
        document.querySelectorAll('.best-value').forEach(cell => {
            cell.style.animation = 'highlight 2s ease-in-out';
        });
    }, 500);
});

// Add CSS animation for highlighting
const style = document.createElement('style');
style.textContent = `
    @keyframes highlight {
        0% { background-color: #d1ecf1; }
        50% { background-color: #b8daff; }
        100% { background-color: #d1ecf1; }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once 'includes/footer.php'; ?>