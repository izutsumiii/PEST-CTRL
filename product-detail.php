<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$productId = intval($_GET['id']);
$product = getProductById($productId);
$images = getProductImages($productId);
$reviews = getProductReviews($productId);

if (!$product) {
    echo "<p>Product not found.</p>";
    require_once 'includes/footer.php';
    exit();
}
?>

<h1><?php echo $product['name']; ?></h1>

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

<?php if (isLoggedIn()): ?>
    <div class="add-review">
        <h3>Add Your Review</h3>
        <form method="POST" action="">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <label for="rating">Rating:</label>
            <select id="rating" name="rating" required>
                <option value="1">1 Star</option>
                <option value="2">2 Stars</option>
                <option value="3">3 Stars</option>
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
<?php else: ?>
    <p><a href="login.php">Login</a> to leave a review.</p>
<?php endif; ?>

<style>

/* Product Detail Page Styles */

/* General Styling */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #f8f9fa;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Product Title */
h1 {
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
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Product Images */
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

/* Product Info */
.product-info {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.product-info p {
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.price {
    font-size: 2rem !important;
    font-weight: bold;
    color: #e74c3c;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stock {
    color: #27ae60;
    font-weight: 600;
    padding: 8px 15px;
    background-color: #d5f4e6;
    border-radius: 25px;
    display: inline-block;
    width: fit-content;
}

.seller {
    color: #7f8c8d;
    font-style: italic;
}

.rating {
    color: #f39c12;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rating::before {
    content: "⭐";
    font-size: 1.2rem;
}

/* Form Styling */
form {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 10px;
    border: 2px solid #e9ecef;
    margin-top: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

input[type="number"],
input[type="text"],
textarea,
select {
    width: 100%;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    margin-bottom: 15px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

input[type="number"]:focus,
input[type="text"]:focus,
textarea:focus,
select:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 10px rgba(52, 152, 219, 0.3);
}

textarea {
    height: 120px;
    resize: vertical;
}

button {
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

button:hover {
    background: linear-gradient(135deg, #2980b9, #21618c);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
}

button:active {
    transform: translateY(0);
}

/* Out of Stock */
.product-info p:last-child {
    color: #e74c3c;
    font-weight: bold;
    background-color: #fadbd8;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    font-size: 1.2rem;
}

/* Description Section */
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

.product-description p {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #555;
}

/* Reviews Section */
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
    display: flex;
    align-items: center;
    gap: 10px;
}

.review h4::before {
    content: "👤";
    font-size: 1rem;
}

.review p:first-of-type {
    color: #f39c12;
    font-weight: 600;
    margin-bottom: 10px;
}

.review p:first-of-type::before {
    content: "⭐ ";
}

.review p:nth-of-type(2) {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 10px;
    color: #555;
}

.review small {
    color: #7f8c8d;
    font-style: italic;
}

/* Add Review Section */
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

/* Success/Error Messages */
.add-review p {
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    font-weight: 600;
}

.add-review p:contains("successfully") {
    background-color: #d5f4e6;
    color: #27ae60;
    border: 2px solid #27ae60;
}

.add-review p:contains("Error") {
    background-color: #fadbd8;
    color: #e74c3c;
    border: 2px solid #e74c3c;
}

/* Login Link */
body > p:last-child {
    text-align: center;
    font-size: 1.1rem;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

body > p:last-child a {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

body > p:last-child a:hover {
    color: #2980b9;
    text-decoration: underline;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    h1 {
        font-size: 2rem;
        margin-bottom: 20px;
    }
    
    .product-detail {
        grid-template-columns: 1fr;
        gap: 25px;
        padding: 20px;
    }
    
    .price {
        font-size: 1.8rem !important;
    }
    
    .product-images img {
        max-height: 300px;
    }
    
    button {
        padding: 12px 25px;
        font-size: 1rem;
    }
    
    .product-description,
    .product-reviews,
    .add-review {
        padding: 20px;
    }
    
    .product-description h2,
    .product-reviews h2 {
        font-size: 1.5rem;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 1.7rem;
    }
    
    .product-detail,
    .product-description,
    .product-reviews,
    .add-review {
        padding: 15px;
    }
    
    .price {
        font-size: 1.5rem !important;
    }
    
    .review {
        padding: 15px;
    }
    
    button {
        padding: 10px 20px;
        font-size: 0.95rem;
    }
}

/* Animation for page load */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-detail,
.product-description,
.product-reviews,
.add-review {
    animation: fadeInUp 0.6s ease-out;
}


</style>


<?php require_once 'includes/footer.php'; ?>