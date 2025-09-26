<?php
/**
 * AJAX endpoint for order status updates and grace period checking
 * This file should be placed in ajax/order_status.php
 */

require_once '../config/database.php';
require_once '../includes/order_helpers.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'check_grace_period':
            checkGracePeriod($pdo, $userId);
            break;
            
        case 'check_order_status':
            checkOrderStatus($pdo, $userId);
            break;
            
        case 'get_cancellable_orders':
            getCancellableOrders($pdo, $userId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("AJAX Order Status Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Check grace period for specific order
 */
function checkGracePeriod($pdo, $userId) {
    $orderId = intval($_GET['order_id'] ?? 0);
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    // Verify order belongs to user
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        return;
    }
    
    $withinGracePeriod = isWithinGracePeriod($order['created_at']);
    $remainingTime = $withinGracePeriod ? getRemainingGracePeriod($order['created_at']) : null;
    
    echo json_encode([
        'order_id' => $orderId,
        'status' => $order['status'],
        'within_grace_period' => $withinGracePeriod,
        'can_cancel' => $withinGracePeriod && $order['status'] === 'pending',
        'remaining_time' => $remainingTime,
        'formatted_time' => $withinGracePeriod ? getFormattedTimeRemaining($order['created_at']) : null
    ]);
}

/**
 * Check current status of an order
 */
function checkOrderStatus($pdo, $userId) {
    $orderId = intval($_GET['order_id'] ?? 0);
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    // Check if user is customer or seller for this order
    $stmt = $pdo->prepare("
        SELECT DISTINCT o.*, 
               CASE WHEN o.user_id = ? THEN 'customer' ELSE NULL END as customer_access,
               CASE WHEN p.seller_id = ? THEN 'seller' ELSE NULL END as seller_access
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ? AND (o.user_id = ? OR p.seller_id = ?)
    ");
    $stmt->execute([$userId, $userId, $orderId, $userId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        return;
    }
    
    $withinGracePeriod = isWithinGracePeriod($order['created_at']);
    $remainingTime = $withinGracePeriod ? getRemainingGracePeriod($order['created_at']) : null;
    
    $response = [
        'order_id' => $orderId,
        'status' => $order['status'],
        'within_grace_period' => $withinGracePeriod,
        'remaining_time' => $remainingTime,
        'access_level' => $order['customer_access'] ?: $order['seller_access'],
        'status_badge_html' => getOrderStatusBadge($order['status'])
    ];
    
    // Add specific permissions based on user role
    if ($order['customer_access']) {
        $response['can_cancel'] = $withinGracePeriod && $order['status'] === 'pending';
    }
    
    if ($order['seller_access']) {
        $canProcessResult = canSellerProcessOrder($orderId, $userId, $pdo);
        $response['can_process'] = $canProcessResult['can_process'];
        $response['process_message'] = $canProcessResult['message'];
    }
    
    echo json_encode($response);
}

/**
 * Get all orders that can be cancelled by the current user
 */
function getCancellableOrders($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT o.id, o.status, o.created_at, o.total_amount
        FROM orders o
        WHERE o.user_id = ? 
        AND o.status = 'pending'
       AND TIMESTAMPDIFF(SECOND, o.created_at, NOW()) < 300  ← This is correct (300 = 5 minutes)
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cancellableOrders = [];
    foreach ($orders as $order) {
        $remainingTime = getRemainingGracePeriod($order['created_at']);
        
        $cancellableOrders[] = [
            'order_id' => $order['id'],
            'order_number' => str_pad($order['id'], 6, '0', STR_PAD_LEFT),
            'status' => $order['status'],
            'total_amount' => number_format($order['total_amount'], 2),
            'created_at' => $order['created_at'],
            'remaining_time' => $remainingTime,
            'formatted_time' => getFormattedTimeRemaining($order['created_at']),
            'can_cancel' => true
        ];
    }
    
    echo json_encode([
        'cancellable_orders' => $cancellableOrders,
        'count' => count($cancellableOrders)
    ]);
}

/**
 * Batch check multiple orders for grace period status
 */
function batchCheckOrders($pdo, $userId) {
    $orderIds = $_GET['order_ids'] ?? '';
    $orderIdsArray = array_map('intval', explode(',', $orderIds));
    $orderIdsArray = array_filter($orderIdsArray); // Remove zeros
    
    if (empty($orderIdsArray)) {
        echo json_encode(['orders' => []]);
        return;
    }
    
    $placeholders = str_repeat('?,', count($orderIdsArray) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT o.id, o.status, o.created_at
        FROM orders o
        WHERE o.id IN ($placeholders) 
        AND (o.user_id = ? OR EXISTS (
            SELECT 1 FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = o.id AND p.seller_id = ?
        ))
    ");
    
    $params = array_merge($orderIdsArray, [$userId, $userId]);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($orders as $order) {
        $withinGracePeriod = isWithinGracePeriod($order['created_at']);
        $remainingTime = $withinGracePeriod ? getRemainingGracePeriod($order['created_at']) : null;
        
        $result[] = [
            'order_id' => $order['id'],
            'status' => $order['status'],
            'within_grace_period' => $withinGracePeriod,
            'remaining_time' => $remainingTime,
            'formatted_time' => $withinGracePeriod ? getFormattedTimeRemaining($order['created_at']) : null
        ];
    }
    
    echo json_encode(['orders' => $result]);
}
?>