<?php
/**
 * AJAX endpoint to check for order updates
 * Checks if any pending orders have been processed by sellers
 */

require_once '../config/database.php';

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

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'check_pending_orders':
            checkPendingOrderUpdates($pdo, $userId);
            break;
            
        case 'get_order_status':
            $orderId = $input['order_id'] ?? 0;
            getOrderStatus($pdo, $userId, $orderId);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("AJAX Check Updates Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Check if any of the user's pending orders have been updated
 */
function checkPendingOrderUpdates($pdo, $userId) {
    // Store current pending order IDs in session for comparison
    $sessionKey = 'pending_orders_' . $userId;
    
    // Get current pending orders
    $stmt = $pdo->prepare("
        SELECT id, status, UNIX_TIMESTAMP(updated_at) as updated_timestamp
        FROM orders 
        WHERE user_id = ? AND status IN ('pending', 'processing', 'shipped', 'delivered', 'cancelled')
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $currentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get previously stored order states
    $previousOrders = $_SESSION[$sessionKey] ?? [];
    
    $hasUpdates = false;
    $updatedOrders = [];
    
    foreach ($currentOrders as $order) {
        $orderId = $order['id'];
        $currentStatus = $order['status'];
        $currentTimestamp = $order['updated_timestamp'];
        
        // Check if this order's status has changed
        if (isset($previousOrders[$orderId])) {
            $previousStatus = $previousOrders[$orderId]['status'];
            $previousTimestamp = $previousOrders[$orderId]['timestamp'];
            
            if ($currentStatus !== $previousStatus || $currentTimestamp > $previousTimestamp) {
                $hasUpdates = true;
                $updatedOrders[] = [
                    'order_id' => $orderId,
                    'old_status' => $previousStatus,
                    'new_status' => $currentStatus,
                    'status_changed' => ($currentStatus !== $previousStatus)
                ];
            }
        } else {
            // New order found
            $hasUpdates = true;
            $updatedOrders[] = [
                'order_id' => $orderId,
                'old_status' => null,
                'new_status' => $currentStatus,
                'status_changed' => true
            ];
        }
    }
    
    // Update session with current order states
    $orderStates = [];
    foreach ($currentOrders as $order) {
        $orderStates[$order['id']] = [
            'status' => $order['status'],
            'timestamp' => $order['updated_timestamp']
        ];
    }
    $_SESSION[$sessionKey] = $orderStates;
    
    echo json_encode([
        'has_updates' => $hasUpdates,
        'updated_orders' => $updatedOrders,
        'total_orders' => count($currentOrders)
    ]);
}

/**
 * Get current status of a specific order
 */
function getOrderStatus($pdo, $userId, $orderId) {
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT id, status, created_at, updated_at,
               TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_since_created
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        return;
    }
    
    // Calculate cancellation status
    $canCancel = ($order['status'] === 'pending');
    $withinPriorityWindow = ($order['minutes_since_created'] < 5);
    
    echo json_encode([
        'order_id' => $order['id'],
        'status' => $order['status'],
        'created_at' => $order['created_at'],
        'updated_at' => $order['updated_at'],
        'minutes_since_created' => $order['minutes_since_created'],
        'can_cancel' => $canCancel,
        'within_priority_window' => $withinPriorityWindow,
        'cancellation_type' => $canCancel ? ($withinPriorityWindow ? 'priority' : 'extended') : 'none'
    ]);
}
?>