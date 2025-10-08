<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$productId = intval($_GET['id']);

// OPTIMIZED: Single query to get all product data with seller info
$__debugMode = isset($_GET['debug']) && $_GET['debug'] == '1';
// Server-side request tracing to error log
$__reqStart = microtime(true);
error_log('[product-detail] START id=' . (isset($_GET['id']) ? (int)$_GET['id'] : 0));
register_shutdown_function(function() use ($__reqStart) {
    error_log('[product-detail] END after ' . number_format(microtime(true) - $__reqStart, 4) . 's');
});

// Ultra-light mode to bypass heavy rendering for diagnostics
if (isset($_GET['lite']) && $_GET['lite'] == '1') {
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$productId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['ok' => (bool)$p, 'product' => $p]);
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    exit;
}
if ($__debugMode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

if ($__debugMode) echo "<!-- DEBUG START " . date('Y-m-d H:i:s') . " -->\n";

$tProductStart = microtime(true);
$stmt = $pdo->prepare("
    SELECT 
        p.*, 
        u.username as seller_name,
        u.first_name as seller_first_name,
        u.last_name as seller_last_name,
        c.id as category_id,
        c.name as category_name,
        c.parent_id as category_parent_id
    FROM products p 
    JOIN users u ON p.seller_id = u.id 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if ($__debugMode) echo "<!-- product query: " . number_format(microtime(true) - $tProductStart, 4) . "s -->\n";

if (!$product) {
    echo "<p>Product not found.</p>";
    require_once 'includes/footer.php';
    exit();
}

// OPTIMIZED: Get all categories in one query to build breadcrumb efficiently
$allCategoriesStmt = $pdo->prepare("SELECT id, name, parent_id FROM categories");
$allCategoriesStmt->execute();
$allCategories = $allCategoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Build category lookup array for efficient traversal
$categoryLookup = [];
foreach ($allCategories as $cat) {
    $categoryLookup[$cat['id']] = $cat;
}

// Build category breadcrumb efficiently with cycle/overflow guards
$categoryPath = [];
$categoryBreadcrumb = [];

if (!empty($product['category_id'])) {
    $currentCatId = (int)$product['category_id'];
    $path = [];
    $visited = [];
    $maxDepth = 32; // hard cap to avoid infinite loops
    $depth = 0;
    
    while ($currentCatId && isset($categoryLookup[$currentCatId])) {
        if (isset($visited[$currentCatId])) { // cycle detected
            break;
        }
        $visited[$currentCatId] = true;
        $currentCat = $categoryLookup[$currentCatId];
        array_unshift($path, $currentCat['name']);
        array_unshift($categoryBreadcrumb, $currentCat);
        $currentCatId = (int)$currentCat['parent_id'];
        $depth++;
        if ($depth >= $maxDepth) { // depth cap reached
            break;
        }
    }
    $categoryPath = $path;
}

// OPTIMIZED: Get product images and reviews in parallel queries
$tImagesStart = microtime(true);
$imagesStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$reviewsStmt = $pdo->prepare("
    SELECT pr.*, u.username 
    FROM product_reviews pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.product_id = ? 
    ORDER BY pr.created_at DESC
    LIMIT 20
");

// Execute both queries
$imagesStmt->execute([$productId]);
$reviewsStmt->execute([$productId]);

$images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);
if ($__debugMode) echo "<!-- images query: " . number_format(microtime(true) - $tImagesStart, 4) . "s (" . count($images) . " rows) -->\n";

$tReviewsStart = microtime(true);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
if ($__debugMode) echo "<!-- reviews query: " . number_format(microtime(true) - $tReviewsStart, 4) . "s (" . count($reviews) . " rows) -->\n";

// Release session lock ASAP to avoid blocking concurrent AJAX requests
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
if ($__debugMode) echo "<!-- DEBUG END -->\n";

// Determine if current user can submit a review (must have a delivered order for this product and not reviewed yet)
$canReview = false;
$hasReviewed = false;
if (isLoggedIn()) {
    try {
        $userId = $_SESSION['user_id'];
        // Check if already reviewed
        $checkReviewed = $pdo->prepare("SELECT 1 FROM product_reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
        $checkReviewed->execute([$userId, $productId]);
        $hasReviewed = (bool)$checkReviewed->fetchColumn();

        if (!$hasReviewed) {
            // Check delivered purchase
            $checkDelivered = $pdo->prepare("SELECT 1
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'
                LIMIT 1");
            $checkDelivered->execute([$userId, $productId]);
            $canReview = (bool)$checkDelivered->fetchColumn();
        }
    } catch (Exception $e) {
        // Fallback to backend validation only
        $canReview = false;
    }
}

// Handle review submission
$reviewMessage = '';
if (isset($_POST['submit_review']) && isLoggedIn()) {
    $rating = intval($_POST['rating']);
    $reviewText = sanitizeInput($_POST['review_text']);
    
    if (addProductReview($productId, $rating, $reviewText)) {
        // Redirect to prevent resubmission on refresh
        header("Location: product-detail.php?id=" . $productId . "&review_success=1");
        exit();
    } else {
        $reviewMessage = "Error submitting review. You may have already reviewed this product or not purchased it.";
    }
}

// Check for success message
if (isset($_GET['review_success']) && $_GET['review_success'] == '1') {
    $reviewMessage = "Review submitted successfully!";
}

// Calculate seller display name
$sellerDisplayName = trim($product['seller_first_name'] . ' ' . $product['seller_last_name']) ?: $product['seller_name'];
?>

<style>
/* Force body and html to have full background coverage */
html, body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background: #130325 !important;
}

/* Main container styling */
main {
    background: transparent !important;
    margin: 0 !important;
    padding: 8px 0 100px 0 !important;
    min-height: calc(100vh - 80px) !important;
}

/* Product Detail Styles - Dark Theme */
.product-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin: 40px 0;
    background: var(--primary-dark);
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 4px 20px var(--shadow-light);
    border: 1px solid var(--accent-yellow);
}

.product-images {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.product-images img {
    width: 100%;
    max-width: 400px;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.product-info {
    background: var(--primary-dark);
    padding: 20px;
    border-radius: 8px;
    border: 1px solid var(--accent-yellow);
}

.product-info h1 {
    color: var(--primary-light);
    font-size: 1.8rem;
    margin-bottom: 15px;
}

.price {
    font-size: 2rem;
    font-weight: 700;
    color: #FFD736;
    margin-bottom: 10px;
}

.stock {
    color: #28a745;
    font-weight: 600;
    margin-bottom: 10px;
}

.seller {
    color: var(--primary-light);
    margin-bottom: 10px;
}

.category-info {
    color: var(--primary-light);
    font-size: 0.9rem;
    margin-bottom: 10px;
}

.rating {
    color: var(--primary-light);
    font-weight: 600;
    margin-bottom: 20px;
}

.quantity-form {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.quantity-form label {
    font-weight: 600;
    color: var(--primary-light);
}

.quantity-form input {
    width: 80px;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background: #ffffff;
    color: #130325;
}

.add-to-cart-btn {
    background: linear-gradient(135deg, #FFD736, #e6c230);
    color: #130325;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.add-to-cart-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 215, 54, 0.3);
}

.product-description {
    background: var(--primary-dark);
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 4px 20px var(--shadow-light);
    border: 1px solid var(--accent-yellow);
}

.product-description h2 {
    color: var(--primary-light);
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--accent-yellow);
    font-size: 1.2rem;
}

.description-content {
    color: var(--primary-light);
    line-height: 1.5;
    font-size: 0.5rem;
}

.description-content p {
    color: var(--primary-light);
    line-height: 1.5;
    font-size: 1rem;
    margin: 0;
}

.product-reviews {
    background: var(--primary-dark);
    padding: 25px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 4px 20px var(--shadow-light);
    border: 1px solid var(--accent-yellow);
}

.product-reviews h2 {
    color: var(--primary-light);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--accent-yellow);
    font-size: 1.2rem;
}

.review {
    border-bottom: 1px solid rgba(255, 215, 54, 0.2);
    padding: 15px 0;
}

.review:last-child {
    border-bottom: none;
}

.review h4 {
    color: var(--primary-light);
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.review p {
    margin-bottom: 5px;
    color: var(--primary-light);
    font-size: 1rem;
}

.review small {
    color: var(--primary-light);
    opacity: 0.8;
    font-size: 0.8rem;
}

.add-review {
    background: var(--primary-dark);
    padding: 25px;
    border-radius: 8px;
    margin: 20px 0;
    box-shadow: 0 4px 20px var(--shadow-light);
    border: 1px solid var(--accent-yellow);
}

.add-review h3 {
    color: var(--primary-light);
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--accent-yellow);
    font-size: 1.2rem;
}

.review-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.form-group label {
    font-weight: 600;
    color: var(--primary-light);
}

.form-group select,
.form-group textarea {
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 1rem;
    background: #ffffff;
    color: #130325;
}

.form-group textarea {
    height: 100px;
    resize: vertical;
}

.submit-review-btn {
    background: linear-gradient(135deg, #FFD736, #e6c230);
    color: #130325;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    align-self: flex-start;
}

.submit-review-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 215, 54, 0.3);
}

.message {
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.message.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.message.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

@media (max-width: 768px) {
    .product-detail {
        grid-template-columns: 1fr;
        gap: 20px;
        margin: 20px 0;
    }
    
    .quantity-form {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<main>
<div style="max-width: 1200px; margin: 0 auto; padding: 20px;">
<div class="product-detail">
    <div class="product-images">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $image): ?>
                <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
        <?php endif; ?>
    </div>
    
    <div class="product-info">
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
        <p class="stock"><?php echo $product['stock_quantity']; ?> in stock</p>
        <p class="seller">Sold by: <?php echo htmlspecialchars($sellerDisplayName); ?></p>
        
        <?php if (!empty($categoryPath)): ?>
            <p class="category-info">Category: <?php echo htmlspecialchars(implode(' › ', $categoryPath)); ?></p>
        <?php endif; ?>
        
        <p class="rating">Rating: <?php echo number_format($product['rating'], 1); ?> (<?php echo $product['review_count']; ?> reviews)</p>
        
        <?php if ($product['stock_quantity'] > 0): ?>
            <form method="POST" action="cart.php" class="quantity-form">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                <button type="submit" name="add_to_cart" class="add-to-cart-btn">Add to Cart</button>
            </form>
        <?php else: ?>
            <p style="color: #dc3545; font-weight: 600;">Out of Stock</p>
        <?php endif; ?>
    </div>
</div>

<div class="product-description">
    <h2>Description</h2>
    <div class="description-content">
        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
    </div>
</div>

<div class="product-reviews">
    <h2>Customer Reviews</h2>
    <?php if (empty($reviews)): ?>
        <p>No reviews yet.</p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review">
                <h4><?php echo htmlspecialchars($review['username']); ?></h4>
                <p>Rating: <?php echo $review['rating']; ?>/5</p>
                <p><?php echo htmlspecialchars($review['review_text']); ?></p>
                <p><small><?php echo date('F j, Y', strtotime($review['created_at'])); ?></small></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (isLoggedIn()): ?>
    <div class="add-review">
        <h3>Add Your Review</h3>
        
        <!-- Display any messages -->
        <?php if (!empty($reviewMessage)): ?>
            <div class="message <?php echo strpos($reviewMessage, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($reviewMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($hasReviewed): ?>
            <div class="message success">You've already reviewed this product.</div>
        <?php elseif (!$canReview): ?>
            <div class="message error">You can only review this product after your order is delivered.</div>
        <?php else: ?>
            <form method="POST" class="review-form">
                <div class="form-group">
                    <label for="rating">Rating:</label>
                    <select name="rating" id="rating" required>
                        <option value="">Select Rating</option>
                        <option value="5">5 - Excellent</option>
                        <option value="4">4 - Very Good</option>
                        <option value="3">3 - Good</option>
                        <option value="2">2 - Fair</option>
                        <option value="1">1 - Poor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Review:</label>
                    <textarea name="review_text" id="review_text" required 
                              placeholder="Share your experience with this product..."></textarea>
                </div>
                
                <button type="submit" name="submit_review" class="submit-review-btn">Submit Review</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>
</div>
</main>

<?php require_once 'includes/footer.php'; ?>
