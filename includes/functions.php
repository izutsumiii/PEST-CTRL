<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Check if user is seller
function isSeller() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'seller';
}

// Redirect to login if not authenticated
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
}

// Redirect to admin dashboard if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

// Redirect to seller dashboard if not seller
function requireSeller() {
    requireLogin();
    if (!isSeller()) {
        header("Location: index.php");
        exit();
    }
}
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }
}
// Get featured products
function getFeaturedProducts($limit = 8) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY rating DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product by ID
function getProductById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT p.*, u.username as seller_name 
                          FROM products p 
                          JOIN users u ON p.seller_id = u.id 
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get product images
function getProductImages($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product reviews
function getProductReviews($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT pr.*, u.username 
                          FROM product_reviews pr 
                          JOIN users u ON pr.user_id = u.id 
                          WHERE product_id = ? 
                          ORDER BY created_at DESC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* -----------------------------
   CART FUNCTIONS WITH VALIDATION
------------------------------ */

// Add to cart with stock validation
function addToCart($productId, $quantity = 1) {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    // Check product stock
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return ["success" => false, "message" => "Product not found or not available."];
    }
    
    // Check if product already in cart
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingItem) {
        $newQuantity = $existingItem['quantity'] + $quantity;
        
        if ($newQuantity > $product['stock_quantity']) {
            $available = $product['stock_quantity'] - $existingItem['quantity'];
            return [
                "success" => false, 
                "message" => "Only $available more of this product available. You already have {$existingItem['quantity']} in your cart."
            ];
        }
        
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $result = $stmt->execute([$newQuantity, $userId, $productId]);
        
        return ["success" => $result, "message" => $result ? "Product added to cart!" : "Error updating cart."];
    } else {
        if ($quantity > $product['stock_quantity']) {
            return [
                "success" => false, 
                "message" => "Only {$product['stock_quantity']} of this product available."
            ];
        }
        
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $result = $stmt->execute([$userId, $productId, $quantity]);
        
        return ["success" => $result, "message" => $result ? "Product added to cart!" : "Error adding to cart."];
    }
}

// Update cart quantity with stock validation
function updateCartQuantity($productId, $quantity) {
    if (!isLoggedIn()) {
        return ["success" => false, "message" => "Not logged in."];
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        return ["success" => false, "message" => "Product not found or not available."];
    }
    
    if ($quantity > $product['stock_quantity']) {
        return [
            "success" => false, 
            "message" => "Only {$product['stock_quantity']} of this product available."
        ];
    }
    
    if ($quantity <= 0) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $result = $stmt->execute([$userId, $productId]);
        return ["success" => $result, "message" => $result ? "Item removed from cart." : "Error removing item."];
    } else {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $result = $stmt->execute([$quantity, $userId, $productId]);
        return ["success" => $result, "message" => $result ? "Cart updated!" : "Error updating cart."];
    }
}

// Get cart items with stock info
function getCartItems() {
    if (!isLoggedIn()) {
        return [];
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image_url, p.stock_quantity 
                          FROM cart c 
                          JOIN products p ON c.product_id = p.id 
                          WHERE c.user_id = ? AND p.status = 'active'");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Validate cart before checkout
function validateCartForCheckout() {
    if (!isLoggedIn()) {
        return ["success" => false, "message" => "Not logged in."];
    }
    
    $cartItems = getCartItems();
    $errors = [];
    
    foreach ($cartItems as $item) {
        if ($item['quantity'] > $item['stock_quantity']) {
            $errors[] = "Only {$item['stock_quantity']} of '{$item['name']}' available. You have {$item['quantity']} in your cart.";
        }
    }
    
    if (!empty($errors)) {
        return ["success" => false, "message" => implode("<br>", $errors)];
    }
    
    return ["success" => true, "message" => "Cart is valid for checkout."];
}

/* -----------------------------
   PRODUCT MANAGEMENT FUNCTIONS
------------------------------ */

// Add product (auto active)
function addProduct($name, $description, $price, $categoryId, $sellerId, $stockQuantity, $imageUrl) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, seller_id, stock_quantity, image_url, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    return $stmt->execute([$name, $description, $price, $categoryId, $sellerId, $stockQuantity, $imageUrl]);
}

// Update product
function updateProduct($productId, $name, $description, $price, $categoryId, $stockQuantity, $status) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock_quantity = ?, status = ? WHERE id = ?");
    return $stmt->execute([$name, $description, $price, $categoryId, $stockQuantity, $status, $productId]);
}

// Seller active products
function getSellerActiveProducts($sellerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.seller_id = ? AND p.status = 'active'
                          ORDER BY p.created_at DESC");
    $stmt->execute([$sellerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Seller inactive products
function getSellerInactiveProducts($sellerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.seller_id = ? AND p.status = 'inactive'
                          ORDER BY p.created_at DESC");
    $stmt->execute([$sellerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cart total
function getCartTotal() {
    $cartItems = getCartItems();
    $total = 0;
    
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

// Remove from cart
function removeFromCart($productId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    return $stmt->execute([$userId, $productId]);
}

// Process checkout
function processCheckout($shippingAddress, $paymentMethod) {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo->beginTransaction();
        
        $validation = validateCartForCheckout();
        if (!$validation['success']) {
            throw new Exception($validation['message']);
        }
        
        $cartItems = getCartItems();
        if (empty($cartItems)) {
            throw new Exception("Cart is empty");
        }
        
        $total = getCartTotal();
        
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $total, $shippingAddress, $paymentMethod]);
        $orderId = $pdo->lastInsertId();
        
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
            
            $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// User orders
if (!function_exists('getUserOrders')) {
    function getUserOrders() {
        if (!isLoggedIn()) {
            return [];
        }
        
        global $pdo;
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Order details
function getOrderDetails($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT o.*, oi.*, p.name, p.image_url 
                          FROM orders o 
                          JOIN order_items oi ON o.id = oi.order_id 
                          JOIN products p ON oi.product_id = p.id 
                          WHERE o.id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Search products
function searchProducts($query, $filters = []) {
    global $pdo;
    
    $sql = "SELECT * FROM products WHERE status = 'active' AND (name LIKE ? OR description LIKE ?)";
    $params = ["%$query%", "%$query%"];
    
    if (!empty($filters['category'])) {
        $sql .= " AND category_id = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['min_price'])) {
        $sql .= " AND price >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $sql .= " AND price <= ?";
        $params[] = $filters['max_price'];
    }
    
    if (isset($filters['in_stock']) && $filters['in_stock']) {
        $sql .= " AND stock_quantity > 0";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* -----------------------------
   RATING & REVIEW FILTER FUNCTIONS
------------------------------ */

// Get products filtered by rating
function getProductsByRating($minRating = 0, $maxRating = 5) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM products 
                          WHERE status = 'active' 
                          AND rating >= ? AND rating <= ?
                          ORDER BY rating DESC, review_count DESC");
    $stmt->execute([$minRating, $maxRating]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get products with minimum number of reviews
function getProductsByMinReviews($minReviews = 1) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM products 
                          WHERE status = 'active' 
                          AND review_count >= ?
                          ORDER BY rating DESC, review_count DESC");
    $stmt->execute([$minReviews]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getProducts() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY id DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Enhanced search function with rating filters
function searchProductsWithRating($query, $filters = []) {
    global $pdo;
    
    $sql = "SELECT * FROM products WHERE status = 'active'";
    $params = [];
    
    // Add search query
    if (!empty($query)) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $params[] = "%$query%";
        $params[] = "%$query%";
    }
    
    // Add filters
    if (!empty($filters['category'])) {
        $sql .= " AND category_id = ?";
        $params[] = $filters['category'];
    }
    
    if (!empty($filters['min_price'])) {
        $sql .= " AND price >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $sql .= " AND price <= ?";
        $params[] = $filters['max_price'];
    }
    
    if (isset($filters['in_stock']) && $filters['in_stock']) {
        $sql .= " AND stock_quantity > 0";
    }
    
    // Add rating filters
    if (!empty($filters['min_rating'])) {
        $sql .= " AND rating >= ?";
        $params[] = $filters['min_rating'];
    }
    
    if (!empty($filters['max_rating'])) {
        $sql .= " AND rating <= ?";
        $params[] = $filters['max_rating'];
    }
    
    if (!empty($filters['min_reviews'])) {
        $sql .= " AND review_count >= ?";
        $params[] = $filters['min_reviews'];
    }
    
    // Add sorting
    $sort = isset($filters['sort']) ? $filters['sort'] : 'created_at';
    $order = isset($filters['order']) ? $filters['order'] : 'DESC';
    
    $validSorts = ['name', 'price', 'rating', 'review_count', 'created_at'];
    $validOrders = ['ASC', 'DESC'];
    
    if (in_array($sort, $validSorts) && in_array($order, $validOrders)) {
        $sql .= " ORDER BY $sort $order";
    } else {
        $sql .= " ORDER BY created_at DESC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get products by seller with rating filters
function getSellerProductsWithRating($sellerId, $filters = []) {
    global $pdo;
    
    $sql = "SELECT * FROM products WHERE seller_id = ? AND status = 'active'";
    $params = [$sellerId];
    
    // Add rating filters
    if (!empty($filters['min_rating'])) {
        $sql .= " AND rating >= ?";
        $params[] = $filters['min_rating'];
    }
    
    if (!empty($filters['max_rating'])) {
        $sql .= " AND rating <= ?";
        $params[] = $filters['max_rating'];
    }
    
    if (!empty($filters['min_reviews'])) {
        $sql .= " AND review_count >= ?";
        $params[] = $filters['min_reviews'];
    }
    
    // Add sorting
    $sort = isset($filters['sort']) ? $filters['sort'] : 'created_at';
    $order = isset($filters['order']) ? $filters['order'] : 'DESC';
    
    $validSorts = ['name', 'price', 'rating', 'review_count', 'created_at'];
    $validOrders = ['ASC', 'DESC'];
    
    if (in_array($sort, $validSorts) && in_array($order, $validOrders)) {
        $sql .= " ORDER BY $sort $order";
    } else {
        $sql .= " ORDER BY created_at DESC";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get average rating distribution for a seller (for analytics)
function getSellerRatingDistribution($sellerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_products,
        AVG(rating) as avg_rating,
        SUM(review_count) as total_reviews,
        COUNT(CASE WHEN rating >= 4 THEN 1 END) as highly_rated,
        COUNT(CASE WHEN rating >= 3 AND rating < 4 THEN 1 END) as medium_rated,
        COUNT(CASE WHEN rating < 3 THEN 1 END) as low_rated
        FROM products 
        WHERE seller_id = ? AND status = 'active'");
    $stmt->execute([$sellerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* -----------------------------
   PRODUCT REVIEWS
------------------------------ */

// Add product review
function addProductReview($productId, $rating, $reviewText) {
    if (!isLoggedIn()) {
        return false;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT oi.* 
                          FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.id 
                          WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'");
    $stmt->execute([$userId, $productId]);
    $purchased = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$purchased) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM product_reviews WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $existingReview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingReview) {
        return false;
    }
    
    $stmt = $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, rating, review_text) 
                          VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$productId, $userId, $rating, $reviewText]);
    
    if ($result) {
        updateProductRating($productId);
        return true;
    }
    
    return false;
}

// Update product rating
function updateProductRating($productId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
                          FROM product_reviews 
                          WHERE product_id = ?");
    $stmt->execute([$productId]);
    $ratingData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("UPDATE products SET rating = ?, review_count = ? WHERE id = ?");
    $stmt->execute([$ratingData['avg_rating'], $ratingData['review_count'], $productId]);
}

// Add to includes/functions.php

// Get sales data by time period
function getSalesDataByPeriod($sellerId, $period = 'monthly') {
    global $pdo;
    
    switch ($period) {
        case 'weekly':
            $stmt = $pdo->prepare("SELECT 
                                YEARWEEK(o.created_at) as week,
                                CONCAT('Week ', WEEK(o.created_at), ' ', YEAR(o.created_at)) as label,
                                SUM(oi.quantity * oi.price) as revenue
                                FROM orders o
                                JOIN order_items oi ON o.id = oi.order_id
                                JOIN products p ON oi.product_id = p.id
                                WHERE p.seller_id = ? AND o.status = 'delivered'
                                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
                                GROUP BY YEARWEEK(o.created_at)
                                ORDER BY week DESC
                                LIMIT 8");
            break;
            
        case '6months':
            $stmt = $pdo->prepare("SELECT 
                                YEAR(o.created_at) as year,
                                MONTH(o.created_at) as month,
                                CONCAT(MONTHNAME(o.created_at), ' ', YEAR(o.created_at)) as label,
                                SUM(oi.quantity * oi.price) as revenue
                                FROM orders o
                                JOIN order_items oi ON o.id = oi.order_id
                                JOIN products p ON oi.product_id = p.id
                                WHERE p.seller_id = ? AND o.status = 'delivered'
                                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                GROUP BY YEAR(o.created_at), MONTH(o.created_at)
                                ORDER BY year DESC, month DESC
                                LIMIT 6");
            break;
            
        case 'yearly':
            $stmt = $pdo->prepare("SELECT 
                                YEAR(o.created_at) as year,
                                CONCAT('Year ', YEAR(o.created_at)) as label,
                                SUM(oi.quantity * oi.price) as revenue
                                FROM orders o
                                JOIN order_items oi ON o.id = oi.order_id
                                JOIN products p ON oi.product_id = p.id
                                WHERE p.seller_id = ? AND o.status = 'delivered'
                                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
                                GROUP BY YEAR(o.created_at)
                                ORDER BY year DESC
                                LIMIT 3");
            break;
            
        case 'monthly':
        default:
            $stmt = $pdo->prepare("SELECT 
                                YEAR(o.created_at) as year,
                                MONTH(o.created_at) as month,
                                CONCAT(MONTHNAME(o.created_at), ' ', YEAR(o.created_at)) as label,
                                SUM(oi.quantity * oi.price) as revenue
                                FROM orders o
                                JOIN order_items oi ON o.id = oi.order_id
                                JOIN products p ON oi.product_id = p.id
                                WHERE p.seller_id = ? AND o.status = 'delivered'
                                AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                GROUP BY YEAR(o.created_at), MONTH(o.created_at)
                                ORDER BY year DESC, month DESC
                                LIMIT 6");
            break;
    }
    
    $stmt->execute([$sellerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get total sales statistics
function getTotalSalesStats($sellerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT 
                          COUNT(DISTINCT o.id) as total_orders,
                          SUM(oi.quantity * oi.price) as total_revenue,
                          AVG(oi.quantity * oi.price) as avg_order_value,
                          COUNT(DISTINCT o.user_id) as total_customers
                          FROM orders o
                          JOIN order_items oi ON o.id = oi.order_id
                          JOIN products p ON oi.product_id = p.id
                          WHERE p.seller_id = ? AND o.status = 'delivered'");
    $stmt->execute([$sellerId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getSessionCart() {
    return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
}

// Get cart count from session
function getSessionCartCount() {
    $cart = getSessionCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

// Get cart total from session
function getSessionCartTotal() {
    $cart = getSessionCart();
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return number_format($total, 2);
}

// Update cart item quantity in session
function updateSessionCartQuantity($productId, $quantity) {
    if (!isset($_SESSION['cart'])) {
        return ['success' => false, 'message' => 'Cart is empty'];
    }
    
    if ($quantity <= 0) {
        // Remove item from cart
        unset($_SESSION['cart'][$productId]);
        return ['success' => true, 'message' => 'Item removed from cart'];
    }
    
    if (!isset($_SESSION['cart'][$productId])) {
        return ['success' => false, 'message' => 'Item not found in cart'];
    }
    
    // Check stock availability
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        if ($quantity > $product['stock_quantity']) {
            return ['success' => false, 'message' => 'Insufficient stock available'];
        }
        
        $_SESSION['cart'][$productId]['quantity'] = $quantity;
        return ['success' => true, 'message' => 'Cart updated successfully'];
        
    } catch (PDOException $e) {
        error_log('Update session cart error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Database error occurred'];
    }
}

// Remove item from session cart
function removeFromSessionCart($productId) {
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}

// Clear entire cart
function clearCart() {
    $_SESSION['cart'] = [];
    return true;
}

// Validate session cart against current stock and prices
function validateSessionCart() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return ['success' => true, 'message' => 'Cart is empty'];
    }
    
    global $pdo;
    $errors = [];
    $updates = [];
    
    try {
        foreach ($_SESSION['cart'] as $productId => $item) {
            $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, status FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $errors[] = "Product '{$item['name']}' is no longer available";
                unset($_SESSION['cart'][$productId]);
                continue;
            }
            
            if ($product['status'] !== 'active') {
                $errors[] = "Product '{$item['name']}' is no longer available";
                unset($_SESSION['cart'][$productId]);
                continue;
            }
            
            // Check if price changed
            if ($item['price'] != $product['price']) {
                $_SESSION['cart'][$productId]['price'] = $product['price'];
                $updates[] = "Price updated for '{$item['name']}'";
            }
            
            // Check stock availability
            if ($item['quantity'] > $product['stock_quantity']) {
                if ($product['stock_quantity'] > 0) {
                    $_SESSION['cart'][$productId]['quantity'] = $product['stock_quantity'];
                    $updates[] = "Quantity adjusted for '{$item['name']}' - only {$product['stock_quantity']} available";
                } else {
                    $errors[] = "Product '{$item['name']}' is out of stock";
                    unset($_SESSION['cart'][$productId]);
                }
            }
        }
        
        $messages = array_merge($errors, $updates);
        $hasErrors = !empty($errors);
        
        return [
            'success' => !$hasErrors,
            'message' => empty($messages) ? 'Cart is valid' : implode('; ', $messages),
            'errors' => $errors,
            'updates' => $updates
        ];
        
    } catch (PDOException $e) {
        error_log('Cart validation error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error validating cart'];
    }
}

// Convert session cart to display format (similar to database cart)
function getSessionCartForDisplay() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    $displayCart = [];
    foreach ($_SESSION['cart'] as $productId => $item) {
        $displayCart[] = [
            'product_id' => $productId,
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'image_url' => isset($item['image_url']) ? $item['image_url'] : '',
            'total' => $item['price'] * $item['quantity']
        ];
    }
    
    return $displayCart;
}

// Merge session cart with database cart (useful for login)
function mergeSessionCartWithDatabase() {
    if (!isLoggedIn() || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return;
    }
    
    global $pdo;
    $userId = $_SESSION['user_id'];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($_SESSION['cart'] as $productId => $sessionItem) {
            // Check if item already exists in database cart
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $dbItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($dbItem) {
                // Update quantity (combine session and database quantities)
                $newQuantity = $dbItem['quantity'] + $sessionItem['quantity'];
                
                // Check stock limit
                $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product && $newQuantity > $product['stock_quantity']) {
                    $newQuantity = $product['stock_quantity'];
                }
                
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$newQuantity, $userId, $productId]);
            } else {
                // Insert new item
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$userId, $productId, $sessionItem['quantity']]);
            }
        }
        
        $pdo->commit();
        
        // Clear session cart after merging
        $_SESSION['cart'] = [];
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log('Cart merge error: ' . $e->getMessage());
    }
}
?>
