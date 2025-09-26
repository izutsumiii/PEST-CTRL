<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$productId = intval($_GET['id']);
$product = getProductById($productId);
$images = getProductImages($productId);
$reviews = getProductReviews($productId);

// Determine if the current user can review this product: must be logged in, have a delivered order for this product, and not already reviewed
$canReview = false;
if (isLoggedIn()) {
    $currentUserId = $_SESSION['user_id'] ?? null;
    if ($currentUserId) {
        // Check delivered purchase
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o 
                               JOIN order_items oi ON oi.order_id = o.id 
                               WHERE o.user_id = ? AND o.status = 'delivered' AND oi.product_id = ?");
        $stmt->execute([$currentUserId, $productId]);
        $hasDeliveredPurchase = $stmt->fetchColumn() > 0;

        // Check existing review
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$currentUserId, $productId]);
        $alreadyReviewed = $stmt->fetchColumn() > 0;

        $canReview = $hasDeliveredPurchase && !$alreadyReviewed;
    }
}

if (!$product) {
    echo "<p>Product not found.</p>";
    require_once 'includes/footer.php';
    exit();
}
?>

<main>

<div class="product-detail">
    <div class="product-images">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $image): ?>
                <img src="<?php echo $image['image_url']; ?>" alt="<?php echo $product['name']; ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>">
        <?php endif; ?>
    </div>
    
    <div class="product-info">
        <p class="price">₱<?php echo $product['price']; ?></p>
        <p class="stock"><?php echo $product['stock_quantity']; ?> in stock</p>
        <p class="seller">Sold by: <?php echo $product['seller_name']; ?></p>
        <p class="rating">Rating: <?php echo $product['rating']; ?> (<?php echo $product['review_count']; ?> reviews)</p>
        
        <?php if ($product['stock_quantity'] > 0): ?>
            <form method="POST" action="cart.php">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                <button type="submit" name="add_to_cart">Add to Cart</button>
            </form>
        <?php else: ?>
            <p>Out of Stock</p>
        <?php endif; ?>
    </div>
</div>

</main>

<div class="product-description">
    <h2>Description</h2>
    <p><?php echo $product['description']; ?></p>
</div>

<div class="product-reviews">
    <h2>Customer Reviews</h2>
    <?php if (empty($reviews)): ?>
        <p>No reviews yet.</p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <div class="review">
                <h4><?php echo $review['username']; ?></h4>
                <p>Rating: <?php echo $review['rating']; ?>/5</p>
                <p><?php echo $review['review_text']; ?></p>
                <p><small><?php echo date('F j, Y', strtotime($review['created_at'])); ?></small></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (isLoggedIn() && $canReview): ?>
    <div class="add-review">
        <h3>Add Your Review</h3>
        <form method="POST" action="">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <label for="rating">Rating:</label>
            <select id="rating" name="rating" required>
                <option value="1">1 Star</option>
                <option value="2">2 Stars</option>
                <option value="3">Stars</option>
                <option value="4">4 Stars</option>
                <option value="5">5 Stars</option>
            </select>
            <label for="review_text">Review:</label>
            <textarea id="review_text" name="review_text" required></textarea>
            <button type="submit" name="submit_review">Submit Review</button>
        </form>
        
        <?php
        if (isset($_POST['submit_review'])) {
            $rating = intval($_POST['rating']);
            $reviewText = sanitizeInput($_POST['review_text']);
            
            if (addProductReview($productId, $rating, $reviewText)) {
                echo "<p>Review submitted successfully!</p>";
                header("Refresh:0");
            } else {
                echo "<p>Error submitting review. You may have already reviewed this product or not purchased it.</p>";
            }
        }
        ?>
    </div>
<?php elseif (!isLoggedIn()): ?>
    <p><a href="login.php">Login</a> to leave a review.</p>
<?php else: ?>
    <p style="margin-top: 20px;">You can review this product after your order is delivered.<?php 
        if (isset($alreadyReviewed) && $alreadyReviewed) { echo ' You have already submitted a review.'; } 
    ?></p>
<?php endif; ?>

<style>
/* REMOVED THE PROBLEMATIC GLOBAL RESET - THIS WAS BREAKING THE HEADER */

/* Product Detail Page Styles - Only targeting specific elements */
.product-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Only target main content h1, not all h1 elements */
main h1 {
    font-size: 2.5rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 30px;
    text-align: center;
    border-bottom: 3px solid #3498db;
    padding-bottom: 15px;
}

/* Product Detail Layout */
.product-detail {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
    margin-top: 120px; /* Account for fixed header */
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-images {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.product-images img {
    width: 100%;
    height: auto;
    max-height: 400px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-images img:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.product-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.product-info p {
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.product-info .price {
    font-size: 2rem !important;
    font-weight: bold;
    color: #e74c3c;
}

.product-info .stock {
    color: #27ae60;
    font-weight: 600;
    padding: 8px 15px;
    background-color: #d5f4e6;
    border-radius: 25px;
    display: inline-block;
    width: fit-content;
}

.product-info .seller {
    color: #7f8c8d;
    font-style: italic;
}

.product-info .rating {
    color: #f39c12;
    font-weight: 600;
}

.product-info .rating::before {
    content: "⭐ ";
}

/* Form Styling - Only target forms within product sections */
.product-info form,
.add-review form {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    border: 2px solid #e9ecef;
    margin-top: 20px;
}

.product-info label,
.add-review label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.product-info input,
.product-info select,
.add-review input,
.add-review select,
.add-review textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    margin-bottom: 15px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.product-info input:focus,
.product-info select:focus,
.add-review input:focus,
.add-review select:focus,
.add-review textarea:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
}

.add-review textarea {
    height: 120px;
    resize: vertical;
}

.product-info button,
.add-review button {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.product-info button:hover,
.add-review button:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
}

.product-description {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.product-description h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.8rem;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.product-reviews {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.product-reviews h2 {
    color: #2c3e50;
    margin-bottom: 25px;
    font-size: 1.8rem;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

.review {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid #3498db;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.review:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.review h4 {
    color: #2c3e50;
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.review h4::before {
    content: "👤 ";
}

.add-review {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.add-review h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5rem;
    border-bottom: 2px solid #3498db;
    padding-bottom: 10px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .product-detail {
        grid-template-columns: 1fr;
        gap: 25px;
        padding: 20px;
        margin-top: 140px; /* More space for mobile header */
    }
    
    .product-info .price {
        font-size: 1.8rem !important;
    }
    
    .product-images img {
        max-height: 300px;
    }
    
    .product-info button,
    .add-review button {
        padding: 12px 25px;
        font-size: 1rem;
    }
}

</style>

<?php require_once 'includes/footer.php'; ?>