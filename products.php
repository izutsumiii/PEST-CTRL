<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Get search query and filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 0;
$minRating = isset($_GET['min_rating']) ? floatval($_GET['min_rating']) : 0;
$maxRating = isset($_GET['max_rating']) ? floatval($_GET['max_rating']) : 5;
$minReviews = isset($_GET['min_reviews']) ? intval($_GET['min_reviews']) : 0;
$inStock = isset($_GET['in_stock']) ? true : false;
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'DESC';

$filters = [
    'category' => $category,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'in_stock' => $inStock,
    'min_rating' => $minRating,
    'max_rating' => $maxRating,
    'min_reviews' => $minReviews,
    'sort' => $sort,
    'order' => $order
];

// Get products based on search and filters
// Use enhanced function if available, otherwise fallback to original
if (function_exists('searchProductsWithRatingEnhanced')) {
    $products = searchProductsWithRatingEnhanced($search, $filters);
} else {
    $products = searchProductsWithRating($search, $filters);
}

// Get categories for filter
$stmt = $pdo->prepare("SELECT * FROM categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Compare Bar Styles */
.compare-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--primary-light);
    box-shadow: 0 -2px 10px var(--shadow-medium);
    z-index: 1000;
    padding: 15px;
    border-top: 2px solid var(--accent-yellow);
}

.compare-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.compare-content h4 {
    margin: 0;
    color: var(--primary-dark);
    font-size: 16px;
}

#compare-items {
    display: flex;
    gap: 10px;
    flex: 1;
    flex-wrap: wrap;
    min-width: 200px;
}

.compare-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--bg-secondary);
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    border: 1px solid var(--border-secondary);
}

.compare-item img {
    width: 30px;
    height: 30px;
    object-fit: cover;
    border-radius: 4px;
}

.compare-item span {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.remove-compare {
    background: none;
    border: none;
    color: #dc3545;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.remove-compare:hover {
    background: rgba(220, 53, 69, 0.1);
}

.compare-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-compare {
    background-color: #130325;
    color: #F9F9F9;
    font-weight: bold;
    border: 2px solid #FFD736;
}

.btn-compare:hover:not(:disabled) {
    background-color: rgba(19, 3, 37, 0.8);
    border-color: #e6c230;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(19, 3, 37, 0.3);
}

.btn-compare:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    border-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-clear {
    background: #dc3545;
    color: #F9F9F9;
}

.btn-clear:hover {
    background: #c82333;
}

/* Product card checkbox styling */
.product-checkbox {
    position: absolute;
    top: 10px;
    right: 10px;
    background: white;
    padding: 5px;
    border-radius: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    z-index: 10;
}

.product-card {
    position: relative;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: white;
    transition: all 0.2s;
}

.product-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color: #007bff;
}

/* Responsive design */
@media (max-width: 768px) {
    .compare-content {
        flex-direction: column;
        gap: 10px;
        align-items: stretch;
    }
    
    .compare-actions {
        justify-content: center;
    }
    
    #compare-items {
        justify-content: center;
    }
}
</style>

<div class="page-header">
    <h1>Pest Control Products</h1>
</div>

<!-- Compare Products Bar -->
<div id="compare-bar" class="compare-bar" style="display: none;">
    <div class="compare-content">
        <h4>Compare Products (<span id="compare-count">0</span>/4)</h4>
        <div id="compare-items"></div>
        <div class="compare-actions">
            <button id="compare-btn" class="btn btn-compare" disabled>Compare Selected</button>
            <button id="clear-compare" class="btn btn-clear">Clear All</button>
        </div>
    </div>
</div>

<div class="products-layout">
    <div class="products-main">
        <div class="products-list">
            <?php if ($search !== ''): ?>
                <h2>Search Results (<?php echo count($products); ?> products found)</h2>
            <?php endif; ?>

            <div class="applied-filters">
                <?php if ($minRating > 0): ?>
                    <span class="filter-tag">Rating: <?php echo $minRating; ?>+ stars</span>
                <?php endif; ?>
                
                <?php if ($minReviews > 0): ?>
                    <span class="filter-tag">Reviews: <?php echo $minReviews; ?>+</span>
                <?php endif; ?>
                
                <?php if ($inStock): ?>
                    <span class="filter-tag">In Stock Only</span>
                <?php endif; ?>
            </div>
            <?php if (empty($products)): ?>
                <p>No products found matching your criteria.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-checkbox">
                                <input type="checkbox" 
                                       class="compare-checkbox" 
                                       data-product-id="<?php echo $product['id']; ?>"
                                       data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                       data-product-price="<?php echo $product['price']; ?>"
                                       data-product-image="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>"
                                       data-product-rating="<?php echo $product['rating'] ?? 0; ?>"
                                       data-product-reviews="<?php echo $product['review_count'] ?? 0; ?>"
                                       onchange="toggleCompare(this)">
                                <label for="compare-<?php echo $product['id']; ?>">Compare</label>
                            </div>
                            
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'default-image.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='default-image.jpg'">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
                            
                            <div class="rating">
                                <?php
                                $rating = $product['rating'] ?? 0;
                                $fullStars = floor($rating);
                                $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                
                                echo str_repeat('★', $fullStars);
                                echo $hasHalfStar ? '½' : '';
                                echo str_repeat('☆', $emptyStars);
                                ?>
                                <span class="rating-value">
                                    (<?php echo number_format($rating, 1); ?>) - <?php echo $product['review_count'] ?? 0; ?> reviews
                                </span>
                            </div>
                            
                            <p class="stock"><?php echo $product['stock_quantity'] ?? 0; ?> in stock</p>
                            
                            
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="view-details-link">View Product Details</a>
                                <button onclick="addToCart(<?php echo $product['id']; ?>, 1)" 
                                        class="btn btn-cart" 
                                        data-product-id="<?php echo $product['id']; ?>">
                                    Add to Cart
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
            <?php endif; ?>
            
            
        </div>
    </div>
    <?php if ($search !== ''): ?>
        <h2>Search Results (<?php echo count($products); ?> products found)</h2>
    <?php endif; ?>
    <div class="filters">
    <h2>Filters</h2>
    <form method="GET">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        
        <div class="filter-group">
            <h3>Category</h3>
            <select name="category">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <h3>Price Range</h3>
            <div class="price-range">
                <input type="number" name="min_price" placeholder="Min" value="<?php echo $minPrice; ?>" step="0.01" min="0">
                <span>to</span>
                <input type="number" name="max_price" placeholder="Max" value="<?php echo $maxPrice; ?>" step="0.01" min="0">
            </div>
        </div>
        
        <div class="filter-group">
            <h3>Rating</h3>
            <div class="rating-filter">
                <div class="rating-options">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <label>
                            <input type="radio" name="min_rating" value="<?php echo $i; ?>" 
                                <?php echo $minRating == $i ? 'checked' : ''; ?>>
                            <span class="rating-stars">
                                <?php echo str_repeat('<span class=\'star-filled\'>★</span>', $i)
                                    . str_repeat('<span class=\'star-empty\'>☆</span>', 5 - $i); ?>
                            </span>
                        </label><br>
                    <?php endfor; ?>
                    <label>
                        <input type="radio" name="min_rating" value="0" 
                            <?php echo $minRating == 0 ? 'checked' : ''; ?>>
                        Any Rating
                    </label>
                </div>
            </div>
        </div>
        
        <div class="filter-group">
            <h3>Number of Reviews</h3>
            <select name="min_reviews">
                <option value="0" <?php echo $minReviews == 0 ? 'selected' : ''; ?>>Any Number of Reviews</option>
                <option value="1" <?php echo $minReviews == 1 ? 'selected' : ''; ?>>1+ Reviews</option>
                <option value="5" <?php echo $minReviews == 5 ? 'selected' : ''; ?>>5+ Reviews</option>
                <option value="10" <?php echo $minReviews == 10 ? 'selected' : ''; ?>>10+ Reviews</option>
                <option value="20" <?php echo $minReviews == 20 ? 'selected' : ''; ?>>20+ Reviews</option>
                <option value="50" <?php echo $minReviews == 50 ? 'selected' : ''; ?>>50+ Reviews</option>
            </select>
        </div>
        
        <div class="filter-group">
            <h3>Availability</h3>
            <label>
                <input type="checkbox" name="in_stock" <?php echo $inStock ? 'checked' : ''; ?>>
                In Stock Only
            </label>
        </div>
        
        <div class="filter-group">
            <h3>Sort By</h3>
            <select name="sort">
                <option value="created_at" <?php echo $sort == 'created_at' ? 'selected' : ''; ?>>Newest</option>
                <option value="price" <?php echo $sort == 'price' ? 'selected' : ''; ?>>Price</option>
                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name</option>
                <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Rating</option>
                <option value="review_count" <?php echo $sort == 'review_count' ? 'selected' : ''; ?>>Most Reviews</option>
            </select>
            <select name="order">
                <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?>>Descending</option>
                <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?>>Ascending</option>
            </select>
        </div>
        
        <button type="submit">Apply Filters</button>
        <a href="products.php" class="clear-filters">Clear All Filters</a>
    </form>
</div>

<div class="products-list">
    
    
    <div class="applied-filters">
        <?php if ($minRating > 0): ?>
            <span class="filter-tag">Rating: <?php echo $minRating; ?>+ stars</span>
        <?php endif; ?>
        
        <?php if ($minReviews > 0): ?>
            <span class="filter-tag">Reviews: <?php echo $minReviews; ?>+ </span>
        <?php endif; ?>
        
        <?php if ($minPrice > 0 || $maxPrice > 0): ?>
            <span class="filter-tag">Price: 
                <?php if ($minPrice > 0) echo '₱' . number_format($minPrice, 2); ?>
                <?php if ($minPrice > 0 && $maxPrice > 0) echo ' - '; ?>
                <?php if ($maxPrice > 0) echo '₱' . number_format($maxPrice, 2); ?>
            </span>
        <?php endif; ?>
        
        <?php if ($inStock): ?>
            <span class="filter-tag">In Stock Only</span>
        <?php endif; ?>
    </div>
    
    <?php if (empty($products)): ?>
        <p>No products found matching your criteria.</p>
    <?php endif; ?>
</div>

<!-- Cart notification -->
<div id="cart-notification" class="cart-notification" style="display: none;">
    <span id="notification-message"></span>
</div>

<!-- Buy Now notification -->
<div id="buy-now-notification" class="buy-now-notification" style="display: none;">
    <span id="buy-now-message"></span>
</div>


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

/* Enhanced Products Page Styles */

/* Reset and Base Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

/* Remove conflicting body styles - use main CSS */

/* Page Header */
.page-header {
    text-align: center;
    margin-top: 60px;
    margin-bottom: 40px;
    background: none;
    color: var(--primary-dark);
}

.page-header h1 {
    color: #F9F9F9;
    margin: 0;
    font-weight: 800;
}

.page-header p {
    color: var(--primary-dark);
    font-size: 1.25rem;
    margin: 0;
}


/* Filters Section */
.filters {
    background: var(--primary-dark);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-secondary);
    border-radius: var(--radius-xl);
    padding: 15px;
    margin: 20px 0;
    box-shadow: 0 4px 20px var(--shadow-light);
    transition: all 0.3s ease;
    position: sticky;
    top: 120px;
    max-height: calc(100vh - 160px);
    overflow-y: auto;
    color: var(--primary-light);
}

.filters:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px var(--shadow-medium);
}

.filters h2 {
    color: var(--primary-light);
    margin-bottom: 15px;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filters h2::before {
    content: "🔍";
    font-size: 1.2em;
}

.filter-group {
    margin-bottom: 12px;
    background: rgba(249, 249, 249, 0.06);
    padding: 10px;
    border-radius: 4px;
    border: 1px solid var(--border-secondary);
    transition: all 0.3s ease;
}

.filter-group h3 {
    color: var(--primary-light);
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.filter-group select,
.filter-group input[type="number"],
.filter-group input[type="radio"],
.filter-group input[type="checkbox"] {
    background: rgba(249, 249, 249, 0.1);
    border: 1px solid var(--border-secondary);
    color: var(--primary-light);
    border-radius: 4px;
    padding: 6px 8px;
    font-size: 0.8rem;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 2px rgba(255, 215, 54, 0.25);
}

/* Make dropdowns dark text on light background for readability */
.filter-group select {
    background: var(--primary-light);
    color: var(--primary-dark);
}
.filter-group select option {
    color: var(--primary-dark);
    background: var(--primary-light);
}

.filter-group button[type="submit"] {
    background-color: #FFD736;
    color: #130325;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
    width: 100%;
    margin-top: 10px;
}

.filter-group button[type="submit"]:hover:not(:disabled) {
    background-color: #e6c230;
}

.clear-filters {
    display: block;
    text-align: center;
    color: #dc3545;
    text-decoration: none;
    font-size: 0.8rem;
    margin-top: 8px;
    padding: 8px 16px;
    border: 1px solid #dc3545;
    border-radius: 4px;
    transition: all 0.2s;
}

.clear-filters:hover {
    background-color: #dc3545;
    color: #F9F9F9;
}

.filter-group:hover {
    background: rgba(249, 249, 249, 0.12);
    border-color: #FFD736;
    transform: translateX(5px);
}

.filter-group h3 {
    color: var(--primary-light);
    margin-bottom: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group h3::before {
    content: "▶";
    color: var(--primary-light);
    font-size: 0.8em;
}

/* Form Controls */
.filter-group select,
.filter-group input[type="number"] {
    padding: 6px 8px;
    border: 1px solid #130325;
    border-radius: 4px;
    font-size: 0.8rem;
    background: white;
    transition: all 0.2s ease;
}

.filter-group select:focus,
.filter-group input[type="number"]:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 2px rgba(255, 215, 54, 0.25);
}

.filter-group select {
    min-width: 220px;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    appearance: none;
}

/* Price Range */
.price-range {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.price-range input {
    width: 120px;
    flex: 1;
    min-width: 100px;
    background: var(--primary-light);
    color: var(--primary-dark);
    border: 1px solid var(--border-secondary);
    border-radius: 4px;
    padding: 6px 8px;
    font-size: 0.8rem;
}

.price-range input::placeholder {
    color: var(--primary-dark);
    opacity: 0.7;
}

.price-range span {
    font-weight: 500;
    color: #6c757d;
    font-size: 1.1rem;
}

/* Rating Filter */
.rating-filter {
    background: rgba(249, 249, 249, 0.06);
    padding: 10px;
    border-radius: 4px;
    border: 1px solid var(--border-secondary);
}

.rating-options label {
    display: flex;
    align-items: center;
    margin: 6px 0;
    cursor: pointer;
    padding: 6px 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
    font-weight: 500;
    color: var(--primary-light);
    font-size: 0.8rem;
}

.rating-options label:hover {
    background: rgba(255, 215, 54, 0.1);
    transform: translateX(5px);
}

.rating-options input[type="radio"] {
    margin-right: 8px;
    accent-color: #FFD736;
}

/* Star colors in rating filter */
.rating-stars { letter-spacing: 2px; }
.rating-stars .star-filled { color: var(--accent-yellow); }
.rating-stars .star-empty { color: #ffffff; opacity: 0.8; }

/* Filters custom scrollbar */
.filters::-webkit-scrollbar {
    width: 10px;
}
.filters::-webkit-scrollbar-track {
    background: rgba(249, 249, 249, 0.08);
    border-radius: 10px;
}
.filters::-webkit-scrollbar-thumb {
    background: rgba(255, 215, 54, 0.6);
    border-radius: 10px;
    border: 2px solid rgba(249, 249, 249, 0.1);
}
.filters::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 215, 54, 0.8);
}

/* Filter Buttons */
.filters button {
    background: linear-gradient(135deg, var(--accent-yellow), #e6c230);
    color: var(--primary-dark);
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 15px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s;
    box-shadow: 0 4px 15px rgba(255, 215, 54, 0.3);
}

.filters button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 215, 54, 0.4);
    background: linear-gradient(135deg, #e6c230, var(--accent-yellow));
}

.clear-filters {
    color: #dc3545;
    text-decoration: none;
    padding: 8px 16px;
    border: 1px solid #dc3545;
    border-radius: 4px;
    font-weight: 600;
    transition: all 0.2s;
    display: inline-block;
}

.clear-filters:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
}

/* Products List Section */
.products-list h2 {
    color: #FFD736;
    margin: 40px 0 25px 0;
    font-size: 1.8rem;
    font-weight: 600;
    text-align: center;
    border-bottom: 3px solid var(--accent-yellow);
    padding-bottom: 15px;
}

/* Applied Filters */
.applied-filters {
    margin: 20px 0;
    text-align: center;
}

.filter-tag {
    background: linear-gradient(45deg, #48cae4, #0096c7);
    color: white;
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 0.9rem;
    margin: 5px 8px;
    display: inline-block;
    font-weight: 500;
    box-shadow: 0 4px 10px rgba(72, 202, 228, 0.3);
    transition: all 0.3s ease;
}

.filter-tag:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(72, 202, 228, 0.5);
}

/* Products Layout */
.products-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: var(--spacing-2xl);
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-xl);
}

.products-main {
    min-width: 0;
}

/* Products Grid - Match index.php exactly */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 12px;
    margin: 12px 0;
}

/* Product Card - Match index.php exactly */
.product-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 8px;
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
    height: 120px;
    object-fit: cover;
    margin-bottom: 6px;
}

.product-card .product-name {
    margin: 3px 0;
    font-size: 0.88em !important;
    font-weight: bold;
    color: #130325 !important;
}

.product-card .price {
    font-weight: bold;
    color: #130325 !important;
    font-size: 0.8em !important;
    margin: 2px 0;
}

/* Use main CSS product card styles - remove custom overrides */

/* Rating Stars */
.rating {
    color: #130325 !important;
    margin: 2px 0;
    font-size: 0.72em !important;
    letter-spacing: -2px;
}

.rating-text {
    color: #130325;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rating-value {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Stock Info */
.stock {
    color: #28a745;
    font-size: 0.95rem;
    margin: 15px 0;
    font-weight: 600;
    padding: 5px 12px;
    background: rgba(40, 167, 69, 0.1);
    border-radius: 20px;
    display: inline-block;
}

/* Product Description */
.description {
    color: #6c757d;
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 15px 0;
    min-height: 60px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Product Actions */
.product-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 20px;
}

/* Buttons */
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-details {
    background-color: #130325;
    color: #F9F9F9;
}

.btn-details:hover {
    background-color: rgba(19, 3, 37, 0.8);
}

.btn-cart {
    background-color: #FFD736;
    color: #130325;
}

.btn-cart:hover:not(:disabled) {
    background-color: #e6c230;
}

.btn-cart:disabled {
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
    opacity: 0.6;
    cursor: not-allowed;
}

/* Compare Checkbox */
.product-checkbox {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 8px 12px;
    border-radius: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    z-index: 10;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.product-checkbox:hover {
    background: rgba(255, 255, 255, 1);
    border-color: #007bff;
    transform: scale(1.05);
}

.product-checkbox input[type="checkbox"] {
    accent-color: #007bff;
    transform: scale(1.2);
}

/* Compare Bar */
.compare-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    padding: 20px;
    border-top: 3px solid #007bff;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.compare-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

.compare-content h4 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.1rem;
    font-weight: 600;
}

#compare-items {
    display: flex;
    gap: 15px;
    flex: 1;
    flex-wrap: wrap;
}

.compare-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(248, 249, 250, 0.9);
    padding: 12px 16px;
    border-radius: 25px;
    font-size: 0.9rem;
    border: 2px solid #dee2e6;
    transition: all 0.3s ease;
    font-weight: 500;
}

.compare-item:hover {
    background: rgba(0, 123, 255, 0.1);
    border-color: #007bff;
    transform: translateY(-2px);
}

.compare-item img {
    width: 35px;
    height: 35px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.compare-item span {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.remove-compare {
    background: rgba(220, 53, 69, 0.1);
    border: none;
    color: #dc3545;
    font-size: 16px;
    cursor: pointer;
    padding: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
    font-weight: bold;
}

.remove-compare:hover {
    background: #dc3545;
    color: white;
    transform: rotate(90deg);
}

.compare-actions {
    display: flex;
    gap: 15px;
}

.btn-compare {
    background-color: #FFD736;
    color: #130325;
    font-weight: bold;
    border: 2px solid #130325;
    box-shadow: 0 4px 15px rgba(255, 215, 54, 0.3);
}

.btn-compare:hover:not(:disabled) {
    background-color: #e6c230;
    border-color: #130325;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 215, 54, 0.5);
}

.btn-compare:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    border-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-clear {
    background: linear-gradient(45deg, #dc3545, #c82333);
    color: white;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.btn-clear:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.5);
}

/* Notifications */
.cart-notification,
.buy-now-notification {
    position: fixed;
    top: 30px;
    right: 30px;
    padding: 20px 25px;
    border-radius: 15px;
    z-index: 1001;
    font-weight: 600;
    font-size: 1rem;
    max-width: 300px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    animation: slideIn 0.3s ease-out;
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

.cart-notification.success,
.buy-now-notification.success {
    background: linear-gradient(45deg, #d4edda, #c3e6cb);
    color: #155724;
    border-left: 5px solid #28a745;
}

.cart-notification.error,
.buy-now-notification.error {
    background: linear-gradient(45deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border-left: 5px solid #dc3545;
}

/* Loading States */
.btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
}

.btn.loading {
    position: relative;
    color: transparent;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    top: 50%;
    left: 50%;
    margin-left: -10px;
    margin-top: -10px;
    border: 2px solid #ffffff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Responsive Design */
@media (max-width: 1200px) {
    .products-layout {
        grid-template-columns: 1fr 250px;
        gap: var(--spacing-lg);
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }
    
    h1 {
        font-size: 2.2rem;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    h1 {
        font-size: 1.8rem;
        margin: 20px 0;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .filters {
        padding: 20px;
        border-radius: 15px;
    }
    
    .filter-group {
        padding: 15px;
    }
    
    .compare-content {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .compare-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .product-actions {
        flex-direction: column;
    }
    
    .price-range {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group select {
        width: 100%;
        min-width: auto;
    }
    
    .filters button,
    .clear-filters {
        width: 100%;
        margin: 5px 0;
    }
    
    .btn {
        padding: 12px 16px;
        font-size: 0.95rem;
    }
    
    .compare-bar {
        padding: 15px;
    }
    
    #compare-items {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .products-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .product-card {
        padding: 20px;
    }
    
    .product-card img {
        height: 200px;
    }
    
    .cart-notification,
    .buy-now-notification {
        right: 15px;
        left: 15px;
        max-width: none;
    }
}

/* Remove conflicting dark mode styles - use main CSS */

/* High contrast mode */
@media (prefers-contrast: high) {
    .product-card {
        border: 3px solid #000;
    }
    
    .btn {
        border: 2px solid currentColor;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}



</style>


<script>
// Compare functionality - Fixed version
let compareProducts = [];
const maxCompare = 4;

function toggleCompare(checkbox) {
    const productId = checkbox.dataset.productId;
    const productName = checkbox.dataset.productName;
    const productPrice = checkbox.dataset.productPrice;
    const productImage = checkbox.dataset.productImage;
    const productRating = checkbox.dataset.productRating || '0';
    const productReviews = checkbox.dataset.productReviews || '0';
    
    if (checkbox.checked) {
        // Check if we're at the limit
        if (compareProducts.length >= maxCompare) {
            checkbox.checked = false;
            alert(`You can only compare up to ${maxCompare} products at a time.`);
            return;
        }
        
        // Check if product is already in compare list (shouldn't happen, but safety check)
        if (compareProducts.some(p => p.id === productId)) {
            checkbox.checked = false;
            return;
        }
        
        // Add product to compare list
        compareProducts.push({
            id: productId,
            name: productName,
            price: productPrice,
            image: productImage,
            rating: productRating,
            reviews: productReviews
        });
    } else {
        // Remove product from compare list
        compareProducts = compareProducts.filter(p => p.id !== productId);
    }
    
    updateCompareBar();
}

function updateCompareBar() {
    const compareBar = document.getElementById('compare-bar');
    const compareCount = document.getElementById('compare-count');
    const compareItems = document.getElementById('compare-items');
    const compareBtn = document.getElementById('compare-btn');
    
    if (!compareBar || !compareCount || !compareItems || !compareBtn) {
        console.error('Compare elements not found');
        return;
    }
    
    compareCount.textContent = compareProducts.length;
    
    if (compareProducts.length > 0) {
        compareBar.style.display = 'block';
        compareBtn.disabled = compareProducts.length < 2;
        
        compareItems.innerHTML = compareProducts.map(product => 
            `<div class="compare-item">
                <img src="${product.image || 'default-image.jpg'}" 
                     alt="${product.name}" 
                     onerror="this.src='default-image.jpg'">
                <span title="${product.name}">${product.name}</span>
                <button onclick="removeFromCompare('${product.id}')" 
                        class="remove-compare" 
                        title="Remove from comparison">×</button>
            </div>`
        ).join('');
    } else {
        compareBar.style.display = 'none';
    }
}

function removeFromCompare(productId) {
    // Remove from compare array
    compareProducts = compareProducts.filter(p => p.id !== productId);
    
    // Uncheck the corresponding checkbox
    const checkbox = document.querySelector(`input[data-product-id="${productId}"]`);
    if (checkbox) {
        checkbox.checked = false;
    }
    
    updateCompareBar();
}

function clearCompare() {
    compareProducts = [];
    
    // Uncheck all compare checkboxes
    document.querySelectorAll('.compare-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    updateCompareBar();
}

function compareSelected() {
    if (compareProducts.length < 2) {
        alert('Please select at least 2 products to compare.');
        return;
    }
    
    const productIds = compareProducts.map(p => p.id).join(',');
    window.location.href = `compare.php?products=${productIds}`;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Attach event listeners
    const compareBtn = document.getElementById('compare-btn');
    const clearCompareBtn = document.getElementById('clear-compare');
    
    if (compareBtn) {
        compareBtn.addEventListener('click', compareSelected);
    }
    
    if (clearCompareBtn) {
        clearCompareBtn.addEventListener('click', clearCompare);
    }
    
    // Load cart count when page loads
    loadCartCount();
});

// Add to cart function (from index.php)
function addToCart(productId, quantity = 1) {
    // Show loading state
    const button = document.querySelector(`button[data-product-id="${productId}"]`);
    if (!button) return;
    
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
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success notification
            showNotification('Product added to cart!', 'success');
            
            // Update cart count if you have a cart counter in header
            if (typeof updateCartCount === 'function' && data.cartCount) {
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
        console.error('Error:', error);
        showNotification('Error adding to cart', 'error');
        button.textContent = originalText;
        button.disabled = false;
    });
}

// Independent Buy Now function
function buyNow(productId, quantity = 1) {
    // Show loading state
    const button = document.querySelector(`button[data-buy-product-id="${productId}"]`);
    if (!button) return;
    
    const originalText = button.textContent;
    button.textContent = 'Processing...';
    button.disabled = true;
    
    // Make AJAX request to buy now handler
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
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show buy now notification
            showBuyNowNotification('Redirecting to checkout...', 'success');
            
            // Short delay before redirect for user feedback
            setTimeout(() => {
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            }, 1000);
        } else {
            // Show error notification
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

// Show cart notification function
function showNotification(message, type = 'success') {
    const notification = document.getElementById('cart-notification');
    const messageElement = document.getElementById('notification-message');
    
    if (!notification || !messageElement) return;
    
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
    
    if (!notification || !messageElement) return;
    
    messageElement.textContent = message;
    notification.className = 'buy-now-notification ' + type;
    notification.style.display = 'block';
    
    // Hide after 3 seconds (unless it's a success redirect)
    if (type !== 'success') {
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
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
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.count !== undefined) {
            updateCartCount(data.count);
        }
    })
    .catch(error => {
        console.error('Error loading cart count:', error);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>