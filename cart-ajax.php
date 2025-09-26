<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in for cart operations
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'add_to_cart':
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if ($productId > 0) {
            $result = addToCart($productId, $quantity);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        }
        break;
        
    case 'get_count':
        $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $result['count'] ? $result['count'] : 0;
        
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    case 'update_quantity':
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        
        if ($productId > 0) {
            $result = updateCartQuantity($productId, $quantity);
            
            // Get new cart total if update was successful
            if ($result['success']) {
                $stmt = $pdo->prepare("SELECT SUM(c.quantity * p.price) as total 
                                      FROM cart c 
                                      JOIN products p ON c.product_id = p.id 
                                      WHERE c.user_id = ?");
                $stmt->execute([$userId]);
                $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = $totalResult['total'] ? number_format($totalResult['total'], 2) : '0.00';
                
                $result['total'] = $total;
            }
            
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        }
        break;
        
    case 'remove_item':
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if ($productId > 0) {
            $result = updateCartQuantity($productId, 0); // Setting quantity to 0 removes the item
            
            // Get new cart total and count if removal was successful
            if ($result['success']) {
                $stmt = $pdo->prepare("SELECT SUM(c.quantity * p.price) as total 
                                      FROM cart c 
                                      JOIN products p ON c.product_id = p.id 
                                      WHERE c.user_id = ?");
                $stmt->execute([$userId]);
                $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = $totalResult['total'] ? number_format($totalResult['total'], 2) : '0.00';
                
                $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                $stmt->execute([$userId]);
                $countResult = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $countResult['count'] ? $countResult['count'] : 0;
                
                $result['total'] = $total;
                $result['count'] = $count;
            }
            
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        }
        break;
        
    case 'validate_cart':
        $result = validateCartForCheckout();
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>