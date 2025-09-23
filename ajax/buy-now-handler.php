<?php
// ajax/buy-now-handler.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['product_id']) || !isset($input['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$productId = (int)$input['product_id'];
$quantity = (int)$input['quantity'];

// Validate quantity
if ($quantity < 1 || $quantity > 100) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit();
}

try {
    // Get product details
    $stmt = $pdo->prepare("SELECT id, name, price, stock_quantity, status, image_url FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if product exists
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    
    // Check if product is active
    if ($product['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Product is not available']);
        exit();
    }
    
    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false, 
            'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' items available'
        ]);
        exit();
    }
    
    // Calculate total
    $total = $product['price'] * $quantity;
    
    // Set up buy now session item
    $_SESSION['buy_now_item'] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => $quantity,
        'image_url' => $product['image_url'],
        'total' => $total,
        'timestamp' => time()
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Buy now item prepared successfully',
        'product' => [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $total
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Buy now handler error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>