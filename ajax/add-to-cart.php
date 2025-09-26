<?php
// ajax/add-to-cart.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php'; // Include for consistency with other functions

// Set JSON header
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Check if user is logged in (if you're using user-based cart)
// Uncomment these lines if you want to require login
/*
if (!isLoggedIn()) {
    $response['message'] = 'Please log in to add items to cart';
    echo json_encode($response);
    exit;
}
*/

// Validate input
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

if ($productId <= 0) {
    $response['message'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

if ($quantity <= 0) {
    $quantity = 1;
}

try {
    // Check if product exists and is in stock
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, image_url FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        $response['message'] = 'Product not found or unavailable';
        echo json_encode($response);
        exit;
    }
    
    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        $response['message'] = "Only {$product['stock_quantity']} items available in stock";
        echo json_encode($response);
        exit;
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check current quantity in cart
    $currentQuantityInCart = isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId]['quantity'] : 0;
    $totalRequestedQuantity = $currentQuantityInCart + $quantity;
    
    // Validate total quantity against stock
    if ($totalRequestedQuantity > $product['stock_quantity']) {
        $availableToAdd = $product['stock_quantity'] - $currentQuantityInCart;
        
        if ($availableToAdd <= 0) {
            $response['message'] = 'Maximum available quantity already in cart';
            echo json_encode($response);
            exit;
        }
        
        $quantity = $availableToAdd;
        $totalRequestedQuantity = $product['stock_quantity'];
        $response['message'] = "Added maximum available quantity ({$quantity} items) to cart";
    } else {
        $response['message'] = $quantity > 1 ? "{$quantity} items added to cart" : 'Item added to cart';
    }
    
    // Add or update product in cart
    if (isset($_SESSION['cart'][$productId])) {
        // Update quantity if product already in cart
        $_SESSION['cart'][$productId]['quantity'] = $totalRequestedQuantity;
        
        // Update price in case it changed
        $_SESSION['cart'][$productId]['price'] = $product['price'];
    } else {
        // Add new product to cart
        $_SESSION['cart'][$productId] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image_url' => $product['image_url'],
            'added_at' => date('Y-m-d H:i:s')
        ];
    }
    
    // Calculate cart statistics
    $cartCount = 0;
    $cartTotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
        $cartTotal += $item['price'] * $item['quantity'];
    }
    
    $response['success'] = true;
    $response['cartCount'] = $cartCount;
    $response['cartTotal'] = number_format($cartTotal, 2);
    $response['itemCount'] = count($_SESSION['cart']); // Number of unique items
    
    // Add product info for frontend use
    $response['product'] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => number_format($product['price'], 2),
        'quantity_added' => $quantity
    ];
    
} catch (PDOException $e) {
    $response['message'] = 'Database error occurred';
    error_log('Add to cart DB error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error adding product to cart';
    error_log('Add to cart error: ' . $e->getMessage());
}

echo json_encode($response);
?>