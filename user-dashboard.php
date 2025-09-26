<?php
date_default_timezone_set('Asia/Manila');
require_once 'includes/header.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Function to check if customer can cancel order (only if status is pending)
function canCustomerCancelOrder($order) {
    return $order['status'] === 'pending';
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ADD THIS MISSING FUNCTION
function getOrdersByStatus($status) {
    global $pdo, $userId;
    
    try {
        $stmt = $pdo->prepare("SELECT o.*, 
                              GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items
                              FROM orders o
                              LEFT JOIN order_items oi ON o.id = oi.order_id
                              LEFT JOIN products p ON oi.product_id = p.id
                              WHERE o.user_id = ? AND o.status = ?
                              GROUP BY o.id
                              ORDER BY o.created_at DESC");
        $stmt->execute([$userId, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching orders by status: " . $e->getMessage());
        return [];
    }
}

// Function to get user orders with delivery date
function getUserOrders() {
    global $pdo, $userId;
    
    $stmt = $pdo->prepare("SELECT o.*, 
                          GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items
                          FROM orders o
                          LEFT JOIN order_items oi ON o.id = oi.order_id
                          LEFT JOIN products p ON oi.product_id = p.id
                          WHERE o.user_id = ?
                          GROUP BY o.id
                          ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Updated function to get delivered orders with delivery date
function getDeliveredOrders() {
    global $pdo, $userId;
    
    $stmt = $pdo->prepare("SELECT o.id as order_id, 
                          o.created_at as order_date,
                          oi.product_id, 
                          oi.quantity, 
                          p.name as product_name, 
                          p.price,
                          (oi.quantity * oi.price) as item_total
                          FROM orders o
                          JOIN order_items oi ON o.id = oi.order_id
                          JOIN products p ON oi.product_id = p.id
                          WHERE o.user_id = ? AND o.status = 'delivered'
                          ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to update order status and set delivery date
function updateOrderStatus($orderId, $newStatus, $updatedBy = null, $notes = '') {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Update order status (delivery_date column not present in schema)
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        
        $result = $stmt->execute([$newStatus, $orderId]);
        
        if ($result) {
            // Log status change in history
            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $newStatus, $notes, $updatedBy]);
            
            $pdo->commit();
            return true;
        } else {
            $pdo->rollback();
            return false;
        }
    } catch (Exception $e) {
        $pdo->rollback();
        error_log("Order status update error: " . $e->getMessage());
        return false;
    }
}

// Function to get order status history
function getOrderStatusHistory($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT osh.*, u.username as updated_by_name
                          FROM order_status_history osh
                          LEFT JOIN users u ON osh.updated_by = u.id
                          WHERE osh.order_id = ?
                          ORDER BY osh.status_date ASC");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle order cancellation (FIXED VERSION)
if (isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id']);
    
    // Verify that this order belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Check if order is still pending
        if ($order['status'] === 'pending') {
            
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Update order status to cancelled
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                $result = $stmt->execute([$orderId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    // Restore product stock
                    $stmt = $pdo->prepare("SELECT oi.product_id, oi.quantity 
                                          FROM order_items oi 
                                          WHERE oi.order_id = ?");
                    $stmt->execute([$orderId]);
                    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Check if products table has a stock column
                    try {
                        $stmt = $pdo->prepare("DESCRIBE products");
                        $stmt->execute();
                        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $hasStockColumn = in_array('stock', $columns);
                        $hasQuantityColumn = in_array('quantity', $columns);
                        
                        $stockUpdateSuccess = true;
                        
                        if ($hasStockColumn) {
                            // Update using 'stock' column
                            foreach ($orderItems as $item) {
                                $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                                $stockResult = $stmt->execute([$item['quantity'], $item['product_id']]);
                                if (!$stockResult) {
                                    $stockUpdateSuccess = false;
                                    break;
                                }
                            }
                        } elseif ($hasQuantityColumn) {
                            // Update using 'quantity' column
                            foreach ($orderItems as $item) {
                                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                                $stockResult = $stmt->execute([$item['quantity'], $item['product_id']]);
                                if (!$stockResult) {
                                    $stockUpdateSuccess = false;
                                    break;
                                }
                            }
                        } else {
                            // No stock management column found - skip stock restoration but continue with cancellation
                            error_log("No stock/quantity column found in products table - skipping stock restoration");
                        }
                    } catch (Exception $e) {
                        error_log("Error checking products table structure: " . $e->getMessage());
                        // Continue without stock update rather than failing the entire cancellation
                        $stockUpdateSuccess = true;
                    }
                    
                    if ($stockUpdateSuccess) {
                        // Log the cancellation - Make this optional in case table doesn't exist
                        try {
                            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                                                  VALUES (?, 'cancelled', 'Order cancelled by customer', ?)");
                            $stmt->execute([$orderId, $userId]);
                        } catch (PDOException $e) {
                            // Log error but don't fail the cancellation
                            error_log("Order status history insert failed: " . $e->getMessage());
                        }
                        
                        $pdo->commit();
                        
                        $cancelMessage = "<div class='alert alert-success'>
                                            <strong>Order Cancelled Successfully!</strong><br>
                                            Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " has been cancelled and product stock has been restored.<br>
                                            If you made a payment, the refund will be processed within 3-5 business days.
                                          </div>";
                    } else {
                        $pdo->rollback();
                        $cancelMessage = "<div class='alert alert-error'>Error restoring product stock. Please contact support.</div>";
                    }
                } else {
                    $pdo->rollback();
                    $cancelMessage = "<div class='alert alert-error'>Error cancelling order. Please try again or contact support.</div>";
                }
            } catch (Exception $e) {
                $pdo->rollback();
                error_log("Order cancellation error: " . $e->getMessage());
                
                // More specific error message based on the exception
                if (strpos($e->getMessage(), 'order_status_history') !== false) {
                    $cancelMessage = "<div class='alert alert-error'>Order cancelled but history logging failed. Order status has been updated.</div>";
                } else {
                    $cancelMessage = "<div class='alert alert-error'>Database error occurred while cancelling the order: " . $e->getMessage() . "</div>";
                }
            }
        } else if ($order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'delivered') {
            $cancelMessage = "<div class='alert alert-error'>This order has already been processed and cannot be cancelled. Please contact customer support for assistance.</div>";
        } else if ($order['status'] === 'cancelled') {
            $cancelMessage = "<div class='alert alert-warning'>This order has already been cancelled.</div>";
        } else {
            $cancelMessage = "<div class='alert alert-error'>This order cannot be cancelled because it has status: " . $order['status'] . "</div>";
        }
    } else {
        $cancelMessage = "<div class='alert alert-error'>Order not found or you don't have permission to cancel this order.</div>";
    }
}


function checkDatabaseStructure($pdo) {
    try {
        // Check if order_status_history table exists
        $stmt = $pdo->prepare("DESCRIBE order_status_history");
        $stmt->execute();
        echo "<!-- order_status_history table exists -->";
    } catch (PDOException $e) {
        echo "<!-- order_status_history table missing: " . $e->getMessage() . " -->";
    }
    
    try {
        // Check orders table structure
        $stmt = $pdo->prepare("DESCRIBE orders");
        $result = $stmt->execute();
        if ($result) {
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<!-- Orders table columns: " . implode(', ', array_column($columns, 'Field')) . " -->";
        }
    } catch (PDOException $e) {
        echo "<!-- Error checking orders table: " . $e->getMessage() . " -->";
    }
}


// Get orders by status - NOW THESE FUNCTIONS WILL WORK
$pendingOrders = getOrdersByStatus('pending');
$processingOrders = getOrdersByStatus('processing');
$shippedOrders = getOrdersByStatus('shipped');
$cancelledOrders = getOrdersByStatus('cancelled');
$orders = getUserOrders();
$deliveredOrders = getDeliveredOrders();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user exists
if (!$user) {
    // User doesn't exist, redirect to login
    header('Location: login.php?error=user_not_found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard</title>

<style>
/* Dashboard Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-primary);
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    line-height: 1.6;
}

/* Alert Styles */
.alert {
    padding: 15px 20px;
    margin: 20px auto;
    max-width: 1200px;
    border-radius: 10px;
    font-size: 1rem;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border-left: 5px solid #28a745;
}

.alert-error {
    background: linear-gradient(135deg, #f8d7da, #f1b0b7);
    color: #721c24;
    border-left: 5px solid #dc3545;
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border-left: 5px solid #ffc107;
}

/* Main heading */
h1 {
    color: var(--primary-dark);
    text-align: center;
    margin: 30px 0;
    font-size: 2.5rem;
    font-weight: 600;
    text-shadow: 0 2px 4px var(--shadow-light);
}

/* Container for the entire dashboard */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    align-items: start;
}

/* Full width container for delivered products */
.dashboard-full-width {
    max-width: 1200px;
    margin: 30px auto 0;
    padding: 0 20px;
}

/* User info section */
.user-info {
    background: var(--gradient-primary);
    position: relative;
    overflow: hidden;
    padding: 25px;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    height: fit-content;
    max-height: 400px;
}

.user-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    pointer-events: none;
}

.user-info h2 {
    font-size: 1.6rem;
    font-weight: 600;
    margin-bottom: 20px;
}

.user-info p {
    font-size: 1rem;
    margin: 12px 0;
    padding: 6px 10px;
}

.user-info p strong {
    display: inline-block;
    width: 80px;
    font-weight: 600;
}

.user-info a {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 25px;
    background: var(--accent-yellow);
    color: var(--primary-dark);
    text-decoration: none;
    border-radius: var(--radius-full);
    font-weight: 600;
    transition: var(--transition-normal);
    border: 2px solid var(--accent-yellow);
    position: relative;
    z-index: 1;
}

.user-info a:hover {
    background: #e6c230;
    border-color: #e6c230;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px var(--shadow-medium);
}

/* Orders section */
.user-orders {
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.2);
    padding: 25px;
    border-radius: 15px;
    box-shadow: var(--shadow-soft);
    height: fit-content;
    max-height: 600px;
    overflow-y: auto;
}

.user-orders h2 {
    font-size: 1.6rem;
    margin-bottom: 20px;
}

.user-orders p {
    color: var(--text-muted);
    font-size: 1.1rem;
    text-align: center;
    margin-top: 40px;
    font-style: italic;
}

/* Order Card Styles */
.order-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.order-number {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2c3e50;
}

.order-date {
    color: #6c757d;
    font-size: 0.9rem;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.detail-item {
    text-align: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.detail-item:hover {
    background: #e9ecef;
}

.detail-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
}

.detail-value {
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
}

.order-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.order-status.pending {
    background: #fff3cd;
    color: #856404;
}

.order-status.processing {
    background: #d1ecf1;
    color: #0c5460;
}

.order-status.shipped {
    background: #d4edda;
    color: #155724;
}

.order-status.delivered {
    background: #d4edda;
    color: #155724;
}

.order-status.cancelled {
    background: #f8d7da;
    color: #721c24;
}

.order-items {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
    margin: 15px 0;
    font-size: 0.95rem;
    color: #495057;
}

.delivery-info {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    padding: 12px;
    border-radius: 8px;
    margin: 15px 0;
    color: #155724;
    font-weight: 500;
}

.order-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    text-align: center;
    display: inline-block;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
}

/* Status Grid Styles */
.order-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.status-container {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.status-container h3 {
    padding: 15px 20px;
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    border-bottom: 2px solid #f0f0f0;
}

.status-container.pending h3 {
    background: #fff3cd;
    color: #856404;
    border-bottom-color: #ffeaa7;
}

.status-container.processing h3 {
    background: #d1ecf1;
    color: #0c5460;
    border-bottom-color: #bee5eb;
}

.status-container.shipped h3 {
    background: #d4edda;
    color: #155724;
    border-bottom-color: #c3e6cb;
}

.status-container.cancelled h3 {
    background: #f8d7da;
    color: #721c24;
    border-bottom-color: #f1b0b7;
}

.status-items {
    max-height: 400px;
    overflow-y: auto;
    padding: 0;
}

.status-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.status-item:hover {
    background-color: #f8f9fa;
}

.status-item:last-child {
    border-bottom: none;
}

.status-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.order-id {
    font-weight: 600;
    color: #2c3e50;
}

.status-item-details {
    margin-bottom: 10px;
}

.order-items {
    font-size: 0.9rem;
    color: #444;
    margin-bottom: 8px;
}

.order-meta {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.order-meta span {
    font-size: 0.8rem;
    color: #666;
}

.status-item-total {
    font-weight: 600;
    color: #27ae60;
    font-size: 0.95rem;
}

.empty-status {
    padding: 30px 20px;
    text-align: center;
    color: #999;
    font-style: italic;
}

/* Delivered Products Section */
.delivered-products {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.delivered-products h2 {
    padding: 20px 25px;
    margin: 0;
    background: #e8f5e8;
    color: #155724;
    border-bottom: 2px solid #c3e6cb;
    font-size: 1.3rem;
}

.delivered-products-scroll {
    max-height: 500px;
    overflow-y: auto;
    padding: 0;
}

.delivered-product-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #f0f0f0;
    transition: background-color 0.2s ease;
}

.delivered-product-item:hover {
    background-color: #f8f9fa;
}

.delivered-product-item:last-child {
    border-bottom: none;
}

.product-info {
    flex: 1;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.product-details {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 8px;
}

.product-details span {
    font-size: 0.9rem;
    color: #666;
}

.delivery-date {
    color: #27ae60 !important;
    font-weight: 500;
}

.delivery-info {
    color: #27ae60;
    font-size: 0.9rem;
    margin-top: 5px;
}

.review-action {
    margin-left: 20px;
}

.btn-review {
    background: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.btn-review:hover {
    background: #0056b3;
    text-decoration: none;
    color: white;
}

.no-delivered-products {
    padding: 40px 20px;
    text-align: center;
    color: #666;
    font-style: italic;
}

.no-orders {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.no-orders p {
    font-size: 1.1rem;
    margin-bottom: 20px;
    font-style: italic;
}

/* Cancel Modal */
.cancel-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
}

.cancel-modal-content {
    background: white;
    margin: 10% auto;
    padding: 30px;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    position: relative;
    animation: modalSlideIn 0.3s ease;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.close-modal {
    position: absolute;
    right: 15px;
    top: 15px;
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #ccc;
    transition: all 0.2s ease;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.close-modal:hover {
    color: #e74c3c;
    background: #f8f9fa;
}

.cancel-modal-content h3 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #2c3e50;
}

.cancel-modal-content p {
    margin-bottom: 15px;
    color: #495057;
}

.cancel-modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 25px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .dashboard-container {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 15px;
    }
    
    .dashboard-full-width {
        padding: 0 15px;
    }
    
    .order-status-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .delivered-product-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .review-action {
        margin-left: 0;
        width: 100%;
    }
    
    .btn-review {
        width: 100%;
        text-align: center;
    }
    
    .product-details {
        flex-direction: column;
        gap: 5px;
    }
    
    .order-details {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .order-actions {
        flex-direction: column;
    }
    
    .cancel-modal-content {
        margin: 20% auto;
        padding: 25px;
    }
    
    .cancel-modal-buttons {
        flex-direction: column;
    }
}

/* Custom Scrollbar Styling */
.status-items::-webkit-scrollbar,
.delivered-products-scroll::-webkit-scrollbar,
.user-orders::-webkit-scrollbar {
    width: 6px;
}

.status-items::-webkit-scrollbar-track,
.delivered-products-scroll::-webkit-scrollbar-track,
.user-orders::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.status-items::-webkit-scrollbar-thumb,
.delivered-products-scroll::-webkit-scrollbar-thumb,
.user-orders::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.status-items::-webkit-scrollbar-thumb:hover,
.delivered-products-scroll::-webkit-scrollbar-thumb:hover,
.user-orders::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* CSS Variables */
:root {
    --gradient-primary: linear-gradient(135deg, rgba(19, 3, 37, 0.9) 0%, #130325  100%);
    --primary-dark: #130325;
    --primary-light: #ffffff;
    --accent-yellow: #ffd700;
    --radius-full: 25px;
    --shadow-soft: 0 10px 40px rgba(0,0,0,0.1);
    --shadow-medium: 0 5px 15px rgba(0,0,0,0.2);
    --transition-normal: all 0.3s ease;
    --text-muted: #6c757d;
}

/* Animation for fade in */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.user-info,
.user-orders,
.delivered-products {
    animation: fadeInUp 0.6s ease-out;
}

.user-orders {
    animation-delay: 0.2s;
}

.delivered-products {
    animation-delay: 0.4s;
}
</style>
</head>
<body>

<script>

    function debugCancelForm(orderId) {
    console.log('Cancel button clicked for order:', orderId);
    console.log('Form data will be:', {
        cancel_order: '1',
        order_id: orderId
    });
    
    // Show confirmation
    if (confirm('DEBUG: Are you sure you want to cancel order #' + orderId + '?')) {
        console.log('User confirmed cancellation');
        return true;
    } else {
        console.log('User cancelled the cancellation');
        return false;
    }
}
// Order Management JavaScript (FIXED VERSION)
class OrderManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Cancel order modal events - FIXED
        document.addEventListener('click', (e) => {
            // Handle cancel button clicks from both order history and status grid
            if (e.target.classList.contains('cancel-order-btn') || e.target.classList.contains('btn-cancel-modal')) {
                e.preventDefault();
                const orderId = e.target.dataset.orderId || e.target.getAttribute('data-order-id');
                const orderNumber = e.target.dataset.orderNumber || e.target.getAttribute('data-order-number');
                this.showCancelModal(orderId, orderNumber);
            }

            // Handle close modal clicks
            if (e.target.classList.contains('close-modal') || e.target === document.getElementById('cancelModal')) {
                this.closeCancelModal();
            }

            // Handle direct cancel form submissions from status grid
            if (e.target.type === 'submit' && e.target.textContent.includes('Cancel Order')) {
                const confirmCancel = confirm('Are you sure you want to cancel this order? This action cannot be undone.');
                if (!confirmCancel) {
                    e.preventDefault();
                }
            }
        });

        // Keyboard events
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeCancelModal();
            }
        });
    }

    showCancelModal(orderId, orderNumber) {
        const cancelOrderId = document.getElementById('cancelOrderId');
        const cancelOrderNumber = document.getElementById('cancelOrderNumber');
        const cancelModal = document.getElementById('cancelModal');
        
        if (cancelOrderId && cancelOrderNumber && cancelModal) {
            cancelOrderId.value = orderId;
            cancelOrderNumber.textContent = orderNumber;
            cancelModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    closeCancelModal() {
        const cancelModal = document.getElementById('cancelModal');
        if (cancelModal) {
            cancelModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new OrderManager();
});
</script>

<h1>My Dashboard</h1>

<!-- Display cancel message if exists -->
<?php if (isset($cancelMessage)): ?>
    <?php echo $cancelMessage; ?>
<?php endif; ?>

<div class="dashboard-container">
   <div class="user-info">
    <h2>Profile Information</h2>
    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
    <p><strong>Username: </strong> <?php echo htmlspecialchars($user['username']); ?></p>
    <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></p>
    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
    <a href="edit-profile.php" class="btn btn-primary">Edit Profile</a>
</div>

   <div class="user-orders">
    <h2>Order History</h2>
    
    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <p>You haven't placed any orders yet.</p>
            <a href="products.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                <div class="order-header">
                    <div class="order-number">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    <div class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                </div>
                
                <div class="order-body">
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value">$<?php echo number_format((float)$order['total_amount'], 2); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="order-status <?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Order Date</div>
                            <div class="detail-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        <?php if ($order['status'] === 'delivered' && !empty($order['delivery_date'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Delivery Date</div>
                                <div class="detail-value delivery-date">
                                    <?php echo date('M j, Y g:i A', strtotime($order['delivery_date'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="detail-item">
                                <div class="detail-label">Expected Delivery</div>
                                <div class="detail-value">
                                    <?php 
                                    $expectedDelivery = date('M j', strtotime($order['created_at'] . ' +5 days')) . '-' . 
                                                       date('j, Y', strtotime($order['created_at'] . ' +7 days'));
                                    echo $expectedDelivery; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="order-items">
                        <strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?>
                    </div>
                    
                    <?php if ($order['status'] === 'delivered' && !empty($order['delivery_date'])): ?>
                        <div class="delivery-info">
                            <strong>Delivered on <?php echo date('F j, Y \a\t g:i A', strtotime($order['delivery_date'])); ?></strong><br>
                            <small>Your order has been successfully delivered. You can now leave reviews for the products.</small>
                        </div>
                    <?php endif; ?>
                    
                    <div class="order-actions">
                        <a href="order-confirmation.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">View Details</a>
                        
                        <?php if ($order['status'] === 'delivered'): ?>
                            <span class="btn" style="background: #28a745; color: white; cursor: default;">Order Delivered</span>
                        <?php elseif (canCustomerCancelOrder($order)): ?>
                            <button type="button" 
                                    class="btn btn-danger btn-cancel-modal" 
                                    data-order-id="<?php echo $order['id']; ?>"
                                    data-order-number="#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>">
                                Cancel Order
                            </button>
                        <?php elseif ($order['status'] === 'cancelled'): ?>
                            <span class="btn" style="background: #6c757d; color: white; cursor: default;">Order Cancelled</span>
                        <?php else: ?>
                            <span class="btn" style="background: #17a2b8; color: white; cursor: default;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>

<!-- Order Status Grid and Delivered Products -->
<div class="dashboard-full-width">
    <!-- Order Status Grid -->
    <div class="order-status-grid">
        <!-- Pending Orders -->
        <div class="status-container pending">
            <h3>Pending Orders</h3>
            <div class="status-items">
                <?php if (empty($pendingOrders)): ?>
                    <div class="empty-status">No pending orders</div>
                <?php else: ?>
                    <?php foreach ($pendingOrders as $order): ?>
                        <div class="status-item">
                            <div class="status-item-header">
                                <span class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="status-item-details">
                                <div class="order-items"><?php echo htmlspecialchars($order['items']); ?></div>
                                <div class="order-meta">
                                    <span><strong>Status:</strong> Order received, awaiting confirmation</span>
                                    <span><strong>Estimated Processing:</strong> 1-2 business days</span>
                                </div>
                            </div>
                            <div class="status-item-total">Total: $<?php echo number_format((float)$order['total_amount'], 2); ?></div>
                            <?php if (canCustomerCancelOrder($order)): ?>
                                <form method="POST" action="" style="margin-top: 10px;" onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="cancel_order" value="1">
                                    <button type="submit" class="btn btn-danger" style="font-size: 0.8rem; padding: 8px 15px;">Cancel Order</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Processing Orders -->
        <div class="status-container processing">
            <h3>Processing Orders</h3>
            <div class="status-items">
                <?php if (empty($processingOrders)): ?>
                    <div class="empty-status">No processing orders</div>
                <?php else: ?>
                    <?php foreach ($processingOrders as $order): ?>
                        <div class="status-item">
                            <div class="status-item-header">
                                <span class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="status-item-details">
                                <div class="order-items"><?php echo htmlspecialchars($order['items']); ?></div>
                                <div class="order-meta">
                                    <span><strong>Status:</strong> Order confirmed and being prepared</span>
                                    <span><strong>Processing Stage:</strong> Item preparation in progress</span>
                                    <span><strong>Estimated Ship Date:</strong> <?php echo date('M j, Y', strtotime($order['created_at'] . ' +2 days')); ?></span>
                                </div>
                            </div>
                            <div class="status-item-total">Total: $<?php echo number_format((float)$order['total_amount'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipped Orders -->
        <div class="status-container shipped">
            <h3>Shipped Orders</h3>
            <div class="status-items">
                <?php if (empty($shippedOrders)): ?>
                    <div class="empty-status">No shipped orders</div>
                <?php else: ?>
                    <?php foreach ($shippedOrders as $order): ?>
                        <div class="status-item">
                            <div class="status-item-header">
                                <span class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="status-item-details">
                                <div class="order-items"><?php echo htmlspecialchars($order['items']); ?></div>
                                <div class="order-meta">
                                    <span><strong>Status:</strong> Package shipped and in transit</span>
                                    <?php if (!empty($order['tracking_number'])): ?>
                                        <span><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                    <?php endif; ?>
                                    <span><strong>Shipped Date:</strong> <?php echo date('M j, Y', strtotime($order['shipped_at'] ?? $order['updated_at'])); ?></span>
                                    <span><strong>Estimated Delivery:</strong> <?php echo date('M j, Y', strtotime($order['created_at'] . ' +5 days')); ?></span>
                                </div>
                            </div>
                            <div class="status-item-total">Total: $<?php echo number_format((float)$order['total_amount'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cancelled Orders -->
        <div class="status-container cancelled">
            <h3>Cancelled Orders</h3>
            <div class="status-items">
                <?php if (empty($cancelledOrders)): ?>
                    <div class="empty-status">No cancelled orders</div>
                <?php else: ?>
                    <?php foreach ($cancelledOrders as $order): ?>
                        <div class="status-item">
                            <div class="status-item-header">
                                <span class="order-id">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                <span class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="status-item-details">
                                <div class="order-items"><?php echo htmlspecialchars($order['items']); ?></div>
                                <div class="order-meta">
                                    <span><strong>Status:</strong> Order cancelled</span>
                                    <span><strong>Cancelled Date:</strong> <?php echo date('M j, Y', strtotime($order['cancelled_at'] ?? $order['updated_at'])); ?></span>
                                    <?php if (!empty($order['cancellation_reason'])): ?>
                                        <span><strong>Reason:</strong> <?php echo htmlspecialchars($order['cancellation_reason']); ?></span>
                                    <?php endif; ?>
                                    <span><strong>Refund Status:</strong> <?php echo !empty($order['refund_status']) ? ucfirst($order['refund_status']) : 'Processing'; ?></span>
                                </div>
                            </div>
                            <div class="status-item-total">Total: $<?php echo number_format((float)$order['total_amount'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delivered Products - Add Reviews Section -->
    <div class="delivered-products">
        <h2>Delivered Products - Add Reviews</h2>
        <div class="delivered-products-scroll">
            <?php if (empty($deliveredOrders)): ?>
                <div class="no-delivered-products">
                    <p>No delivered products yet. Complete an order to see products here for review.</p>
                </div>
            <?php else: ?>
                <?php foreach ($deliveredOrders as $deliveredItem): ?>
                    <div class="delivered-product-item">
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($deliveredItem['product_name']); ?></div>
                            <div class="product-details">
                                <span><strong>Quantity:</strong> <?php echo (int)$deliveredItem['quantity']; ?></span>
                                <span><strong>Price:</strong> $<?php echo number_format((float)$deliveredItem['price'], 2); ?></span>
                                <span><strong>Total:</strong> $<?php echo number_format((float)$deliveredItem['item_total'], 2); ?></span>
                                <span><strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($deliveredItem['order_date'])); ?></span>
                                <?php if (!empty($deliveredItem['delivery_date'])): ?>
                                    <span><strong>Delivered:</strong> 
                                        <span class="delivery-date">
                                            <?php echo date('M j, Y g:i A', strtotime($deliveredItem['delivery_date'])); ?>
                                        </span>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($deliveredItem['delivery_date'])): ?>
                                <?php
                                $deliveryTime = strtotime($deliveredItem['delivery_date']);
                                $currentTime = time();
                                $daysDiff = floor(($currentTime - $deliveryTime) / (60 * 60 * 24));
                                ?>
                                <div class="delivery-info">
                                    <strong>Delivered <?php echo $daysDiff > 0 ? $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '') . ' ago' : 'today'; ?></strong> - Ready for review
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="review-action">
                            <a href="product-detail.php?id=<?php echo $deliveredItem['product_id']; ?>" class="btn btn-review">Add Review</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div id="cancelModal" class="cancel-modal">
    <div class="cancel-modal-content">
        <button class="close-modal" type="button">&times;</button>
        <h3>Cancel Order Confirmation</h3>
        <p>Are you sure you want to cancel order <strong id="cancelOrderNumber">#000000</strong>?</p>
        
        <p style="color: #dc3545; font-weight: 600; margin: 20px 0;">
            This action cannot be undone. Once cancelled, you'll need to place a new order.
        </p>
        
        <form method="POST" action="" style="margin: 0;">
            <input type="hidden" name="order_id" id="cancelOrderId" value="">
            <input type="hidden" name="cancel_order" value="1">
            <div class="cancel-modal-buttons">
                <button type="button" class="btn btn-primary close-modal">Keep Order</button>
                <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
            </div>
        </form>
    </div>
</div>
