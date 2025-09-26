<?php
// includes/buy-now-functions.php
// Helper functions for the independent Buy Now feature

/**
 * Clear expired buy now sessions
 * Call this periodically to clean up old sessions
 */
function clearExpiredBuyNowSessions() {
    if (isset($_SESSION['buy_now_item'])) {
        $expireTime = 1800; // 30 minutes
        if ((time() - $_SESSION['buy_now_item']['timestamp']) > $expireTime) {
            unset($_SESSION['buy_now_item']);
            return true; // Session was expired and cleared
        }
    }
    return false; // No expired session
}

/**
 * Get buy now item details for display
 * @return array|null
 */
function getBuyNowItem() {
    if (!isset($_SESSION['buy_now_item'])) {
        return null;
    }
    
    // Check if expired
    if (clearExpiredBuyNowSessions()) {
        return null;
    }
    
    return $_SESSION['buy_now_item'];
}

/**
 * Validate buy now item against current database state
 * @return array ['success' => bool, 'message' => string, 'updated_item' => array|null]
 */
function validateBuyNowItem($pdo) {
    global $pdo;
    
    if (!isset($_SESSION['buy_now_item'])) {
        return [
            'success' => false, 
            'message' => 'No buy now item in session',
            'updated_item' => null
        ];
    }
    
    $buyNowItem = $_SESSION['buy_now_item'];
    
    // Check if expired
    if (clearExpiredBuyNowSessions()) {
        return [
            'success' => false,
            'message' => 'Buy now session has expired',
            'updated_item' => null
        ];
    }
    
    try {
        // Validate against current database state
        $stmt = $pdo->prepare("
            SELECT id, name, price, stock_quantity, status, image_url 
            FROM products 
            WHERE id = ?
        ");
        $stmt->execute([$buyNowItem['id']]);
        $currentProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentProduct) {
            unset($_SESSION['buy_now_item']);
            return [
                'success' => false,
                'message' => 'Product no longer exists',
                'updated_item' => null
            ];
        }
        
        if ($currentProduct['status'] !== 'active') {
            unset($_SESSION['buy_now_item']);
            return [
                'success' => false,
                'message' => 'Product is no longer available',
                'updated_item' => null
            ];
        }
        
        if ($currentProduct['stock_quantity'] < $buyNowItem['quantity']) {
            unset($_SESSION['buy_now_item']);
            $stockMessage = $currentProduct['stock_quantity'] == 0 ? 
                'Product is out of stock' : 
                "Only {$currentProduct['stock_quantity']} items available";
            return [
                'success' => false,
                'message' => $stockMessage,
                'updated_item' => null
            ];
        }
        
        // Check if price has changed
        $priceChanged = abs($currentProduct['price'] - $buyNowItem['price']) > 0.01;
        $imageChanged = $currentProduct['image_url'] !== $buyNowItem['image_url'];
        
        if ($priceChanged || $imageChanged) {
            // Update session with current data
            $_SESSION['buy_now_item']['price'] = (float)$currentProduct['price'];
            $_SESSION['buy_now_item']['total'] = (float)$currentProduct['price'] * $buyNowItem['quantity'];
            $_SESSION['buy_now_item']['image_url'] = $currentProduct['image_url'] ?? 'images/placeholder.jpg';
            $_SESSION['buy_now_item']['name'] = $currentProduct['name']; // Update name too in case it changed
            
            $updateMessage = '';
            if ($priceChanged) {
                $updateMessage = "Price updated from ₱" . number_format($buyNowItem['price'], 2) . 
                               " to ₱" . number_format($currentProduct['price'], 2);
            }
            
            return [
                'success' => true,
                'message' => $updateMessage,
                'updated_item' => $_SESSION['buy_now_item'],
                'price_changed' => $priceChanged
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Buy now item is valid',
            'updated_item' => $buyNowItem,
            'price_changed' => false
        ];
        
    } catch (Exception $e) {
        error_log('Buy now validation error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error validating buy now item',
            'updated_item' => null
        ];
    }
}

/**
 * Check if current request is a buy now checkout
 * @return bool
 */
function isBuyNowCheckout() {
    return isset($_GET['buy_now']) && isset($_SESSION['buy_now_item']);
}

/**
 * Get time remaining for buy now session (in seconds)
 * @return int|null
 */
function getBuyNowTimeRemaining() {
    if (!isset($_SESSION['buy_now_item'])) {
        return null;
    }
    
    $expireTime = 1800; // 30 minutes
    $elapsed = time() - $_SESSION['buy_now_item']['timestamp'];
    $remaining = $expireTime - $elapsed;
    
    return max(0, $remaining);
}

/**
 * Format time remaining as human readable string
 * @param int $seconds
 * @return string
 */
function formatTimeRemaining($seconds) {
    if ($seconds <= 0) {
        return 'Expired';
    }
    
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    
    if ($minutes > 0) {
        return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . 
               ($remainingSeconds > 0 ? ' ' . $remainingSeconds . ' second' . ($remainingSeconds != 1 ? 's' : '') : '');
    } else {
        return $remainingSeconds . ' second' . ($remainingSeconds != 1 ? 's' : '');
    }
}

/**
 * Clear buy now session manually
 */
function clearBuyNowSession() {
    if (isset($_SESSION['buy_now_item'])) {
        unset($_SESSION['buy_now_item']);
        return true;
    }
    return false;
}

/**
 * Log buy now activity for debugging/analytics
 * @param string $action
 * @param array $data
 */
function logBuyNowActivity($action, $data = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? 'guest',
        'data' => $data
    ];
    
    // Log to file or database as needed
    error_log('Buy Now Activity: ' . json_encode($logData));
}

/**
 * Security function to prevent buy now session hijacking
 * @return bool
 */
function validateBuyNowSession() {
    if (!isset($_SESSION['buy_now_item'])) {
        return true; // No session to validate
    }
    
    // Add any security checks here, such as:
    // - IP address validation
    // - User agent validation
    // - Rate limiting checks
    
    // For now, just check session timeout
    return !clearExpiredBuyNowSessions();
}

/**
 * Get buy now statistics for admin dashboard
 * @param PDO $pdo
 * @return array
 */
function getBuyNowStatistics($pdo) {
    try {
        // This would require additional tracking in your database
        // For now, return basic structure
        return [
            'total_buy_now_orders' => 0,
            'buy_now_conversion_rate' => 0,
            'average_buy_now_value' => 0,
            'top_buy_now_products' => []
        ];
    } catch (Exception $e) {
        error_log('Buy now stats error: ' . $e->getMessage());
        return [];
    }
}
?>