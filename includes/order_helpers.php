    <?php
/**
 * Order Helper Functions
 * Contains utility functions for order management, grace periods, and cancellations
 */

/**
 * Check if an order is within the grace period (10 minutes)
 * @param string $orderCreatedAt - The created_at timestamp of the order
 * @return bool - True if within grace period, false otherwise
 */
function isWithinGracePeriod($orderCreatedAt) {
    $orderTime = strtotime($orderCreatedAt);
    $currentTime = time();
    $timeDifference = $currentTime - $orderTime;
    $gracePeriodSeconds = 5 * 60; // Change from 10 * 60 to 5 * 60
    
    return $timeDifference < $gracePeriodSeconds;
}

function getRemainingGracePeriod($orderCreatedAt) {
    $orderTime = strtotime($orderCreatedAt);
    $currentTime = time();
    $timeDifference = $currentTime - $orderTime;
    $gracePeriodSeconds = 5 * 60; // Change from 10 * 60 to 5 * 60
    $remaining = $gracePeriodSeconds - $timeDifference;
    
    if ($remaining <= 0) return 0;
    
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    return [
        'minutes' => $minutes, 
        'seconds' => $seconds, 
        'total_seconds' => $remaining
    ];
}

/**
 * Check if a user can cancel a specific order
 * @param int $orderId - The order ID
 * @param int $userId - The user ID
 * @param PDO $pdo - Database connection
 * @return array - Result array with status and message
 */
function canUserCancelOrder($orderId, $userId, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [
                'can_cancel' => false,
                'reason' => 'ORDER_NOT_FOUND',
                'message' => 'Order not found or you do not have permission to cancel this order.'
            ];
        }
        
        if ($order['status'] !== 'pending') {
            return [
                'can_cancel' => false,
                'reason' => 'INVALID_STATUS',
                'message' => 'Only pending orders can be cancelled.'
            ];
        }
        
        if (!isWithinGracePeriod($order['created_at'])) {
            return [
                'can_cancel' => false,
                'reason' => 'GRACE_PERIOD_EXPIRED',
                'message' => 'The 10-minute cancellation window has expired.'
            ];
        }
        
        return [
            'can_cancel' => true,
            'reason' => 'SUCCESS',
            'message' => 'Order can be cancelled.',
            'order' => $order
        ];
        
    } catch (Exception $e) {
        error_log("Error checking order cancellation: " . $e->getMessage());
        return [
            'can_cancel' => false,
            'reason' => 'DATABASE_ERROR',
            'message' => 'An error occurred while checking order status.'
        ];
    }
}

/**
 * Cancel an order and restore stock
 * @param int $orderId - The order ID to cancel
 * @param int $userId - The user ID requesting the cancellation
 * @param PDO $pdo - Database connection
 * @return array - Result array with success status and message
 */
function cancelOrder($orderId, $userId, $pdo) {
    try {
        // First check if order can be cancelled
        $canCancelResult = canUserCancelOrder($orderId, $userId, $pdo);
        if (!$canCancelResult['can_cancel']) {
            return $canCancelResult;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Update order status to cancelled
        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $result = $stmt->execute([$orderId]);
        
        if (!$result) {
            $pdo->rollback();
            return [
                'success' => false,
                'reason' => 'UPDATE_FAILED',
                'message' => 'Failed to update order status.'
            ];
        }
        
        // Restore product stock
        $stmt = $pdo->prepare("SELECT oi.product_id, oi.quantity 
                              FROM order_items oi 
                              WHERE oi.order_id = ?");
        $stmt->execute([$orderId]);
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orderItems as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stockResult = $stmt->execute([$item['quantity'], $item['product_id']]);
            
            if (!$stockResult) {
                $pdo->rollback();
                return [
                    'success' => false,
                    'reason' => 'STOCK_RESTORE_FAILED',
                    'message' => 'Failed to restore product stock.'
                ];
            }
        }
        
        // Log the cancellation in order status history
        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                              VALUES (?, 'cancelled', 'Order cancelled by customer within grace period', ?)");
        $historyResult = $stmt->execute([$orderId, $userId]);
        
        if (!$historyResult) {
            // Don't rollback for history failure, but log the error
            error_log("Failed to log order cancellation history for order ID: $orderId");
        }
        
        // Commit transaction
        $pdo->commit();
        
        return [
            'success' => true,
            'reason' => 'SUCCESS',
            'message' => 'Order cancelled successfully. Product stock has been restored.',
            'order_id' => $orderId
        ];
        
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Error cancelling order: " . $e->getMessage());
        return [
            'success' => false,
            'reason' => 'EXCEPTION',
            'message' => 'An unexpected error occurred while cancelling the order.'
        ];
    }
}

/**
 * Check if a seller can process an order (outside grace period)
 * @param int $orderId - The order ID
 * @param int $sellerId - The seller ID
 * @param PDO $pdo - Database connection
 * @return array - Result array with can_process status and details
 */
function canSellerProcessOrder($orderId, $sellerId, $pdo) {
    try {
        // Verify that the order contains the seller's products
        $stmt = $pdo->prepare("SELECT o.* 
                              FROM orders o
                              JOIN order_items oi ON o.id = oi.order_id
                              JOIN products p ON oi.product_id = p.id
                              WHERE o.id = ? AND p.seller_id = ?
                              GROUP BY o.id");
        $stmt->execute([$orderId, $sellerId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return [
                'can_process' => false,
                'reason' => 'ORDER_NOT_FOUND',
                'message' => 'Order not found or you do not have permission to process this order.'
            ];
        }
        
        if ($order['status'] !== 'pending') {
            return [
                'can_process' => false,
                'reason' => 'INVALID_STATUS',
                'message' => 'Only pending orders can be processed.'
            ];
        }
        
        if (isWithinGracePeriod($order['created_at'])) {
            $remaining = getRemainingGracePeriod($order['created_at']);
            return [
                'can_process' => false,
                'reason' => 'GRACE_PERIOD_ACTIVE',
                'message' => "Customer has {$remaining['minutes']}m {$remaining['seconds']}s remaining to cancel.",
                'remaining_time' => $remaining
            ];
        }
        
        return [
            'can_process' => true,
            'reason' => 'SUCCESS',
            'message' => 'Order can be processed.',
            'order' => $order
        ];
        
    } catch (Exception $e) {
        error_log("Error checking order processing eligibility: " . $e->getMessage());
        return [
            'can_process' => false,
            'reason' => 'DATABASE_ERROR',
            'message' => 'An error occurred while checking order status.'
        ];
    }
}

/**
 * Get formatted time remaining for display
 * @param string $orderCreatedAt - The created_at timestamp of the order
 * @return string - Formatted time string or empty if expired
 */
function getFormattedTimeRemaining($orderCreatedAt) {
    $remaining = getRemainingGracePeriod($orderCreatedAt);
    
    if ($remaining === 0) {
        return '';
    }
    
    return sprintf('%dm %ds', $remaining['minutes'], $remaining['seconds']);
}

/**
 * Get order status badge HTML
 * @param string $status - The order status
 * @param array $options - Optional parameters for additional styling
 * @return string - HTML for status badge
 */
function getOrderStatusBadge($status, $options = []) {
    $statusColors = [
        'pending' => '#fff3cd; color: #856404;',
        'confirmed' => '#d1ecf1; color: #0c5460;',
        'processing' => '#cce5ff; color: #004085;',
        'shipped' => '#d4edda; color: #155724;',
        'delivered' => '#d4edda; color: #155724;',
        'cancelled' => '#f8d7da; color: #721c24;'
    ];
    
    $statusEmojis = [
        'pending' => '⏳',
        'confirmed' => '✅',
        'processing' => '🔄',
        'shipped' => '🚚',
        'delivered' => '📦',
        'cancelled' => '❌'
    ];
    
    $color = $statusColors[$status] ?? '#e2e3e5; color: #383d41;';
    $emoji = $statusEmojis[$status] ?? '';
    $displayStatus = ucfirst($status);
    
    $additionalClass = $options['class'] ?? '';
    
    return sprintf(
        '<span class="order-status %s %s" style="padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; text-transform: uppercase; background: %s">%s %s</span>',
        $status,
        $additionalClass,
        $color,
        $emoji,
        $displayStatus
    );
}

/**
 * Log order status change
 * @param int $orderId - The order ID
 * @param string $newStatus - The new status
 * @param string $notes - Additional notes
 * @param int $updatedBy - User ID who made the change
 * @param PDO $pdo - Database connection
 * @return bool - Success status
 */
function logOrderStatusChange($orderId, $newStatus, $notes, $updatedBy, $pdo) {
    try {
        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                              VALUES (?, ?, ?, ?)");
        return $stmt->execute([$orderId, $newStatus, $notes, $updatedBy]);
    } catch (Exception $e) {
        error_log("Error logging order status change: " . $e->getMessage());
        return false;
    }
}

/**
 * Get order status history
 * @param int $orderId - The order ID
 * @param PDO $pdo - Database connection
 * @return array - Array of status history records
 */
function getOrderStatusHistory($orderId, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT osh.*, u.username as updated_by_name 
            FROM order_status_history osh 
            LEFT JOIN users u ON osh.updated_by = u.id 
            WHERE osh.order_id = ? 
            ORDER BY osh.created_at DESC
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching order status history: " . $e->getMessage());
        return [];
    }
}

/**
 * Send order status notification email (if email functionality is implemented)
 * @param int $orderId - The order ID
 * @param string $newStatus - The new status
 * @param PDO $pdo - Database connection
 * @return bool - Success status
 */
function sendOrderStatusNotification($orderId, $newStatus, $pdo) {
    // This would integrate with your existing email system
    // Implementation depends on your email setup (PHPMailer, etc.)
    
    try {
        // Get order and customer details
        $stmt = $pdo->prepare("
            SELECT o.*, u.email, u.first_name, u.last_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order || !$order['email']) {
            return false; // No email to send to
        }
        
        // Here you would implement the email sending logic
        // For now, just log the action
        error_log("Order status notification would be sent to {$order['email']} for order #{$orderId} - Status: {$newStatus}");
        
        return true;
    } catch (Exception $e) {
        error_log("Error sending order status notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate order status transition
 * @param string $currentStatus - The current order status
 * @param string $newStatus - The proposed new status
 * @return array - Validation result
 */
function validateStatusTransition($currentStatus, $newStatus) {
    $validTransitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [], // Final state
        'cancelled' => [] // Final state
    ];
    
    if (!isset($validTransitions[$currentStatus])) {
        return [
            'valid' => false,
            'message' => 'Invalid current status.'
        ];
    }
    
    if (!in_array($newStatus, $validTransitions[$currentStatus])) {
        return [
            'valid' => false,
            'message' => "Cannot change status from {$currentStatus} to {$newStatus}."
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Status transition is valid.'
    ];
}
?>