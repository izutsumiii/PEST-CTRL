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

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(60, intval($_GET['per_page']))) : 24;

$filters = [
    'category' => $category,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'in_stock' => $inStock,
    'min_rating' => $minRating,
    'max_rating' => $maxRating,
    'min_reviews' => $minReviews,
    'sort' => $sort,
    'order' => $order,
    'page' => $page,
    'per_page' => $perPage
];

// Get products based on search and filters
// Use enhanced function if available, otherwise fallback to original
if (function_exists('searchProductsWithRatingEnhanced')) {
    $products = searchProductsWithRatingEnhanced($search, $filters);
    $totalCount = function_exists('countProductsWithRatingEnhanced') ? countProductsWithRatingEnhanced($search, $filters) : count(searchProductsWithRating($search, $filters));
} else {
    $allProducts = searchProductsWithRating($search, $filters);
    $totalCount = count($allProducts);
    // Fallback manual pagination if enhanced not available
    $offset = ($page - 1) * $perPage;
    $products = array_slice($allProducts, $offset, $perPage);
}
$totalPages = max(1, (int)ceil($totalCount / $perPage));

// Get categories for filter
$stmt = $pdo->prepare("SELECT * FROM categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Products grid and card styles copied from index.php */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.product-card {
    position: relative;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    background: #f8f9fa;
    color: #333;
    display: flex;
    flex-direction: column;
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

.product-checkbox {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.9);
    padding: 6px 10px;
    border-radius: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #e6e6e6;
    z-index: 2;
}

.product-checkbox:hover { background: #ffffff; border-color: #007bff; }
.product-checkbox input[type="checkbox"] { accent-color: #007bff; transform: scale(1.2); }
.product-checkbox label { margin: 0; color: #130325; font-size: 12px; font-weight: 600; }

.product-name { margin: 10px 0; font-size: 1.1em; font-weight: bold; color: #333; }
.price { font-weight: bold; color: #130325; font-size: 1.2em; margin: 10px 0; }
.rating { color: #130325; margin: 10px auto 4px auto; font-size: 1.2rem; letter-spacing: -2px; text-align: center; }
.rating-text { color: #130325; font-size: 0.85rem; display: block; text-align: left; margin-top: 4px; }
.product-card .stock { color: #28a745; font-size: 0.95rem; margin: 15px 0; font-weight: 600; padding: 5px 12px; background: rgba(40, 167, 69, 0.1); border-radius: 20px; display: inline-block; }

.product-actions { display: flex; flex-direction: column; gap: 10px; margin-top: auto; }
.btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.3s ease; text-decoration: none; display: inline-block; text-align: center; font-weight: 600; }
.btn-details { background-color: #130325; color: #F9F9F9; }
.btn-details:hover { background-color: rgba(19, 3, 37, 0.8); transform: translateY(-2px); text-decoration: none; }
.btn-cart { background-color: #007bff; color: #F9F9F9; }
.btn-cart:hover:not(:disabled) { background-color: #0056b3; transform: translateY(-2px); }
.btn-cart:disabled { background-color: #6c757d; color: #F9F9F9; opacity: 0.6; cursor: not-allowed; }
.btn-compare { background-color: #130325; color: #F9F9F9; font-weight: bold; }

/* Compare bar - hidden by default (match index.php behavior) */
.compare-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--primary-light);
    box-shadow: 0 -2px 10px var(--shadow-medium);
    z-index: 1000;
    padding: 15px;
    border-top: 2px solid #FFD736;
}

.compare-content { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
#compare-items { display: flex; gap: 10px; flex: 1; flex-wrap: wrap; min-width: 200px; }
.compare-item { display: flex; align-items: center; gap: 8px; background: var(--bg-secondary); padding: 8px 12px; border-radius: 20px; font-size: 14px; border: 1px solid var(--border-secondary); }
.compare-item img { width: 30px; height: 30px; object-fit: cover; border-radius: 4px; }
.compare-item span { max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.remove-compare { background: none; border: none; color: #dc3545; font-size: 18px; cursor: pointer; padding: 0; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background-color 0.2s; }
.remove-compare:hover { background: rgba(220, 53, 69, 0.1); }
.compare-actions { display: flex; gap: 10px; }
</style>

<div class="page-header">
    <h1>Pest Control Products</h1>
</div>

<!-- Compare Products Bar -->
<div id="compare-bar" class="compare-bar">
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
                                <label>Compare</label>
                            </div>
                            <?php 
                            if (!function_exists('resolveProductImageUrl')) {
                                function resolveProductImageUrl($url) {
                                    $url = trim((string)$url);
                                    if ($url === '') return '';
                                    if (preg_match('#^https?://#i', $url)) return $url;
                                    if (strpos($url, 'assets/') === 0) return str_replace(' ', '%20', $url);
                                    return 'assets/uploads/' . str_replace(' ', '%20', $url);
                                }
                            }
                            $listImg = resolveProductImageUrl($product['image_url'] ?? '');
                            ?>
                            <img loading="lazy" src="<?php echo htmlspecialchars($listImg ?: 'assets/images/placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price">₱<?php echo number_format($product['price'], 2); ?></p>
                            <div class="rating">
                                <?php
                                    $rating = $product['rating'] ?? 0;
                                    $fullStars = floor($rating);
                                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $fullStars) {
                                            echo '★';
                                        } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                            echo '☆';
                                        } else {
                                            echo '☆';
                                        }
                                    }
                                ?>
                                <span class="rating-text">(<?php echo number_format($product['rating'] ?? 0, 1); ?>) - <?php echo $product['review_count'] ?? 0; ?> reviews</span>
                            </div>
                            <div class="stock"><?php echo $product['stock_quantity']; ?> in stock</div>
                            <div class="product-actions">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-details">View Details</a>
                                <button onclick="addToCart(<?php echo $product['id']; ?>, 1)" class="btn btn-cart" data-product-id="<?php echo $product['id']; ?>">
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="filters">
    <h2>FILTERS</h2>
    <form method="GET">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        
<div class="filter-group">
    <h3>Category</h3>
    <div class="category-tree">
        <?php
        // Function to build category hierarchy
        function buildCategoryTree($categories) {
            $tree = [];
            $lookup = [];
            
            foreach ($categories as $category) {
                $lookup[$category['id']] = $category;
                $lookup[$category['id']]['children'] = [];
            }
            
            foreach ($categories as $category) {
                if ($category['parent_id']) {
                    $lookup[$category['parent_id']]['children'][] = &$lookup[$category['id']];
                } else {
                    $tree[] = &$lookup[$category['id']];
                }
            }
            
            return $tree;
        }
 // Get selected categories (handle both single and multiple)
$selectedCategories = [];
if (isset($_GET['categories']) && is_array($_GET['categories'])) {
    $selectedCategories = array_map('intval', $_GET['categories']);
} elseif (isset($_GET['category']) && $_GET['category'] > 0) {
    $selectedCategories = [intval($_GET['category'])];
}

// Function to get all child category IDs recursively
function getAllChildCategories($pdo, $parentIds) {
    if (empty($parentIds)) return [];
    
    $placeholders = str_repeat('?,', count($parentIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id IN ($placeholders)");
    $stmt->execute($parentIds);
    $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($children)) {
        return array_merge($children, getAllChildCategories($pdo, $children));
    }
    
    return [];
}

// Expand selected categories to include all children
if (!empty($selectedCategories)) {
    $allCategoryIds = array_merge($selectedCategories, getAllChildCategories($pdo, $selectedCategories));
    $filters['categories'] = array_unique($allCategoryIds);
} else {
    $filters['categories'] = [];
}       
        // Function to render category tree
        function renderCategoryTree($categories, $selectedCategories = [], $level = 0) {
            foreach ($categories as $category) {
                $hasChildren = !empty($category['children']);
                $isSelected = in_array($category['id'], $selectedCategories);
                $indent = $level * 20;
                
                echo '<div class="category-item" style="margin-left: ' . $indent . 'px;">';
                
                if ($hasChildren) {
                    echo '<span id="toggle-' . $category['id'] . '" class="category-toggle" onclick="toggleCategoryChildren(' . $category['id'] . ')">▶</span>';
                }
                
                echo '<label>';
                echo '<input type="checkbox" ';
                echo 'name="category_checkboxes[]" ';
                echo 'value="' . $category['id'] . '" ';
                echo 'onchange="handleCategorySelection(this)" ';
                if ($hasChildren) {
                    echo 'onclick="toggleCategoryChildren(' . $category['id'] . ')" ';
                }
                if ($isSelected) {
                    echo 'checked ';
                }
                if ($category['parent_id']) {
                    echo 'data-parent="' . $category['parent_id'] . '" ';
                }
                echo '>';
                $displayName = preg_replace('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}\s]+/u', '', (string)$category['name']);
                $displayName = html_entity_decode($displayName, ENT_QUOTES, 'UTF-8');
                echo htmlspecialchars($displayName);
                echo '</label>';
                echo '</div>';
                
                if ($hasChildren) {
                    echo '<div id="children-' . $category['id'] . '" class="category-children" style="display: ' . ($isSelected ? 'block' : 'none') . ';">';
                    renderCategoryTree($category['children'], $selectedCategories, $level + 1);
                    echo '</div>';
                }
            }
        }
        
        // Get selected categories from GET parameter
        $selectedCategories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
        
        // Build and render tree
        $categoryTree = buildCategoryTree($categories);
        renderCategoryTree($categoryTree, $selectedCategories);
        ?>
    </div>
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

 

<!-- Cart notification -->
<!-- Cart notification is handled by header.php -->

<!-- Buy Now notification -->
<div id="buy-now-notification" class="buy-now-notification">
    <span id="buy-now-message"></span>
</div>


<style>
    .category-tree {
    max-height: 300px;
    overflow-y: auto;
}

.category-item {
    margin: 5px 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.category-toggle {
    cursor: pointer;
    font-size: 12px;
    width: 20px;
    display: inline-block;
    color: var(--primary-light);
    user-select: none;
}

.category-children {
    margin-left: 10px;
}

.category-item label {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85rem;
    color: var(--primary-light);
}
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
    background: transparent; /* match main background, no container box */
    border: none;
    border-radius: 0;
    padding: 10px 0;
    margin: 20px 0;
    box-shadow: none;
    transition: none;
    position: sticky;
    top: 120px;
    max-height: calc(100vh - 160px);
    overflow-y: auto;
    color: #F9F9F9; /* white text */
}

.filters:hover { transform: none; box-shadow: none; }

.filters h2 {
    color: var(--accent-yellow);
    margin-bottom: 15px;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* removed emoji before filter title */
.filters h2::before { content: ""; }

.filter-group {
    margin-bottom: 16px;
    background: transparent;
    padding: 0;
    border: none;
}

.filter-group h3 {
    color: var(--accent-yellow);
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.filter-group select,
.filter-group input[type="number"],
.filter-group input[type="radio"],
.filter-group input[type="checkbox"] {
    background: #ffffff;
    border: 1px solid #ced4da;
    color: #130325;
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
.filter-group select { background: #ffffff; color: #130325; }
.filter-group select option { color: #130325; background: #ffffff; }

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
    display: inline-block;
    text-align: center;
    color: #dc3545;
    text-decoration: none;
    font-size: 0.8rem;
    margin-top: 8px;
    padding: 6px 12px;
    border: 1px solid #dc3545;
    border-radius: 4px;
    transition: all 0.2s;
}

.clear-filters:hover {
    background-color: #dc3545;
    color: #F9F9F9;
}

.filter-group:hover { background: transparent; transform: none; }

.filter-group h3 {
    color: var(--primary-light);
    margin-bottom: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group h3::before { content: ""; }

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
.rating-stars { letter-spacing: 0.5px; font-size: 1.1rem; }
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
    grid-template-columns: 300px 1fr; /* filters left, products right */
    align-items: start;
    gap: var(--spacing-2xl);
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 var(--spacing-xl);
}

.products-main {
    min-width: 0;
    grid-column: 2;
    grid-row: 1;
}

/* Products Grid - Match index.php exactly */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

/* Product Card - Match index.php exactly */
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

.product-card h3 {
    margin: 10px 0;
    font-size: 1.1em;
    color: #333;
}

.product-card .price {
    font-weight: bold;
    color: #130325;
    font-size: 1.2em;
    margin: 10px 0;
}

/* Use main CSS product card styles - remove custom overrides */

/* Rating Stars */
.rating {
    color: #130325;
    margin: 15px 0;
    font-size: 1.2rem;
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
    margin-top: auto;
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
    background-color: #007bff; /* blue */
    color: #F9F9F9;
}

.btn-cart:hover:not(:disabled) {
    background-color: #0056b3; /* darker blue */
}

.btn-cart:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-buy {
    background-color: #FFD736; /* yellow */
    color: #130325;
    font-weight: bold;
}

.btn-buy:hover:not(:disabled) {
    background-color: #e6c230; /* darker yellow */
}

.btn-buy:disabled {
    background-color: #6c757d;
    color: #F9F9F9;
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
    color: #ffffff; 
    font-size: 1rem; 
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
    background: rgba(0, 123, 255, 0.1);
    padding: 12px 16px;
    border-radius: 25px;
    font-size: 0.9rem;
    border: 2px solid #007bff;
    transition: all 0.3s ease;
    font-weight: 500;
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
    background-color: #130325;
    color: #F9F9F9;
    font-weight: bold;
    border: 2px solid #FFD736;
    box-shadow: 0 4px 15px rgba(19, 3, 37, 0.3);
}

.btn-compare:hover:not(:disabled) {
    background-color: rgba(19, 3, 37, 0.8);
    border-color: #e6c230;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(19, 3, 37, 0.5);
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

.buy-now-notification.success {
    background: linear-gradient(45deg, #d4edda, #c3e6cb);
    color: #155724;
    border-left: 5px solid #28a745;
}

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
    // Category hierarchy management
let selectedCategories = new Set();

function toggleCategoryChildren(categoryId) {
    const childrenDiv = document.getElementById(`children-${categoryId}`);
    const toggleIcon = document.getElementById(`toggle-${categoryId}`);
    
    if (childrenDiv) {
        if (childrenDiv.style.display === 'none' || childrenDiv.style.display === '') {
            childrenDiv.style.display = 'block';
            if (toggleIcon) toggleIcon.textContent = '▼';
        } else {
            childrenDiv.style.display = 'none';
            if (toggleIcon) toggleIcon.textContent = '▶';
        }
    }
}

function handleCategorySelection(checkbox) {
    const categoryId = checkbox.value;
    
    if (checkbox.checked) {
        selectedCategories.add(categoryId);
    } else {
        selectedCategories.delete(categoryId);
        // Also uncheck all children
        const children = document.querySelectorAll(`[data-parent="${categoryId}"]`);
        children.forEach(child => {
            child.checked = false;
            selectedCategories.delete(child.value);
        });
    }
}

// Modify form submission to include all selected categories
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('.filters form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            // Remove old category inputs
            const oldInputs = filterForm.querySelectorAll('input[name="categories[]"]');
            oldInputs.forEach(input => input.remove());
            
            // Add selected categories as array
            selectedCategories.forEach(catId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'categories[]';
                input.value = catId;
                filterForm.appendChild(input);
            });
        });
    }
});
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

// Use the addToCart function from assets/script.js

// Independent Buy Now function
function buyNow(productId, quantity = 1) {
    // Check if user is logged in
    <?php $isLoggedIn = isset($_SESSION['user_id']); ?>
    var isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;
    if (!isLoggedIn) {
        window.location.href = 'login.php';
        return;
    }
    
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
// showNotification function is handled by assets/script.js

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

</script>

<?php require_once 'includes/footer.php'; ?>