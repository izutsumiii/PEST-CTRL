<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get and decode JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

// Validate required fields
if (!isset($data['product_id']) || !isset($data['quantity'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing product ID or quantity']);
    exit();
}

$productId = intval($data['product_id']);
$quantity = intval($data['quantity']);

// Validate inputs
if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

try {
    // Get product details from database
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, status, image_url FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unavailable']);
        exit();
    }

    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        $stockMessage = $product['stock_quantity'] == 0 ? 
            "Product is out of stock" : 
            "Only {$product['stock_quantity']} items available";
        echo json_encode(['success' => false, 'message' => $stockMessage]);
        exit();
    }

    // Calculate total
    $total = (float)$product['price'] * $quantity;

    // Store buy now item in session (completely independent of cart)
    $_SESSION['buy_now_item'] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'quantity' => $quantity,
        'total' => $total,
        'image_url' => $product['image_url'] ?? 'images/placeholder.jpg',
        'timestamp' => time() // For session expiry
    ];

    // Clear any previous buy now warnings
    unset($_SESSION['price_change_warning']);

    // Return success with redirect URL
    echo json_encode([
        'success' => true, 
        'message' => 'Product prepared for checkout',
        'redirect_url' => 'checkout.php?buy_now=1',
        'item_name' => $product['name'],
        'total' => number_format($total, 2)
    ]);

} catch (Exception $e) {
    error_log('Buy Now Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
?>