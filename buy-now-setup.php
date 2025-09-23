<?php
// buy-now-setup.php
session_start();
require_once 'config/database.php';

// Check if this is a POST request with the correct action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buy_now') {
    
    // Get and validate input
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    // Validate inputs
    if ($productId <= 0) {
        $_SESSION['error_message'] = 'Invalid product selected';
        header('Location: index.php');
        exit();
    }
    
    if ($quantity < 1 || $quantity > 100) {
        $_SESSION['error_message'] = 'Invalid quantity';
        header('Location: index.php');
        exit();
    }
    
    try {
        // Get product details from database
        $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, status, image_url FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if product exists
        if (!$product) {
            $_SESSION['error_message'] = 'Product not found';
            header('Location: index.php');
            exit();
        }
        
        // Check if product is active
        if ($product['status'] !== 'active') {
            $_SESSION['error_message'] = 'Product is not available';
            header('Location: index.php');
            exit();
        }
        
        // Check stock availability
        if ($product['stock_quantity'] < $quantity) {
            $_SESSION['error_message'] = 'Insufficient stock. Only ' . $product['stock_quantity'] . ' items available';
            header('Location: index.php');
            exit();
        }
        
        // Calculate total
        $total = $product['price'] * $quantity;
        
        // Set up buy now session item (matching your checkout.php structure)
        $_SESSION['buy_now_item'] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image_url' => $product['image_url'],
            'total' => $total,
            'timestamp' => time()
        ];
        
        // Clear any previous error messages
        unset($_SESSION['error_message']);
        
        // Redirect to checkout page with buy_now parameter
        header('Location: checkout.php?buy_now=true');
        exit();
        
    } catch (Exception $e) {
        error_log('Buy now setup error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Database error occurred. Please try again.';
        header('Location: index.php');
        exit();
    }
    
} else {
    // Invalid request - redirect to home
    header('Location: index.php');
    exit();
}
?>