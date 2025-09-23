<?php
// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

require_once 'includes/header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

function sendOrderStatusUpdateEmail($customerEmail, $customerName, $orderId, $newStatus, $oldStatus, $orderItems, $totalAmount, $pdo) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jhongujol1299@gmail.com';
        $mail->Password = 'ljdo ohkv pehx idkv'; // App password
        $mail->SMTPSecure = "ssl";
        $mail->Port = 465;
        
        // Recipients
        $mail->setFrom('jhongujol1299@gmail.com', 'E-Commerce Store');
        $mail->addAddress($customerEmail, $customerName);
        
        // Status configurations
        $statusConfig = [
            'pending' => [
                'emoji' => '⏳',
                'title' => 'Order Received',
                'color' => '#ffc107',
                'message' => 'Your order has been received and is awaiting processing.',
                'next_step' => 'We\'ll start preparing your order soon.'
            ],
            'processing' => [
                'emoji' => '🔄',
                'title' => 'Order Confirmed & Processing',
                'color' => '#007bff',
                'message' => 'Great news! Your order has been confirmed and is now being prepared.',
                'next_step' => 'Your items are being carefully prepared for shipment.'
            ],
            'shipped' => [
                'emoji' => '🚚',
                'title' => 'Order Shipped',
                'color' => '#17a2b8',
                'message' => 'Your order is on its way!',
                'next_step' => 'You\'ll receive a tracking number shortly. Expected delivery: 3-5 business days.'
            ],
            'delivered' => [
                'emoji' => '✅',
                'title' => 'Order Delivered',
                'color' => '#28a745',
                'message' => 'Your order has been successfully delivered!',
                'next_step' => 'We hope you enjoy your purchase. Please consider leaving a review.'
            ],
            'cancelled' => [
                'emoji' => '❌',
                'title' => 'Order Cancelled',
                'color' => '#dc3545',
                'message' => 'Your order has been cancelled as requested.',
                'next_step' => 'If you have any questions, please contact our support team.'
            ]
        ];

        $config = $statusConfig[$newStatus] ?? [
            'emoji' => '📋',
            'title' => 'Order Status Updated',
            'color' => '#6c757d',
            'message' => 'Your order status has been updated.',
            'next_step' => 'We\'ll keep you informed of any further updates.'
        ];

        // Build items list for email
        $itemsList = '';
        foreach ($orderItems as $item) {
            $itemTotal = $item['quantity'] * $item['item_price'];
            $itemsList .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['product_name']) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>" . (int)$item['quantity'] . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format((float)$item['item_price'], 2) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format($itemTotal, 2) . "</td>
                </tr>";
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $config['emoji'] . ' Order Update - Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; border-bottom: 2px solid " . $config['color'] . "; padding-bottom: 20px;'>
                <h1 style='color: " . $config['color'] . "; margin: 0;'>" . $config['title'] . "</h1>
            </div>
            <div style='padding: 30px 0;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Hello " . htmlspecialchars($customerName) . ",</h2>
                <p style='color: #666; font-size: 16px; line-height: 1.6;'>
                    " . $config['message'] . "
                </p>
                
                <div style='background: linear-gradient(135deg, " . $config['color'] . ", " . $config['color'] . "dd); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                    <h3 style='margin: 0; font-size: 18px;'>Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . "</h3>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Status: " . ucfirst($newStatus) . "</p>
                </div>
                
                <h3 style='color: #333; margin: 30px 0 15px 0;'>Order Details:</h3>
                <table style='width: 100%; border-collapse: collapse; background: #f9f9f9; border-radius: 8px; overflow: hidden;'>
                    <thead>
                        <tr style='background: " . $config['color'] . "; color: white;'>
                            <th style='padding: 12px; text-align: left;'>Product</th>
                            <th style='padding: 12px; text-align: center;'>Qty</th>
                            <th style='padding: 12px; text-align: right;'>Price</th>
                            <th style='padding: 12px; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsList}
                    </tbody>
                    <tfoot>
                        <tr style='background: #e8f5e8; font-weight: bold;'>
                            <td colspan='3' style='padding: 15px; text-align: right;'>Total Amount:</td>
                            <td style='padding: 15px; text-align: right; color: " . $config['color'] . "; font-size: 18px;'>$" . number_format((float)$totalAmount, 2) . "</td>
                        </tr>
                    </tfoot>
                </table>
                
                <div style='background-color: " . $config['color'] . "20; border: 1px solid " . $config['color'] . "; padding: 20px; border-radius: 8px; margin: 30px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: " . $config['color'] . ";'>What's next?</h4>
                    <p style='margin: 0; color: #555;'>" . $config['next_step'] . "</p>
                </div>";

        // Add status-specific additional information
        if ($newStatus === 'shipped') {
            $mail->Body .= "
                <div style='background-color: #e8f4f8; border: 1px solid #17a2b8; padding: 20px; border-radius: 8px; margin: 30px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: #0c5460;'>📦 Shipping Information</h4>
                    <p style='margin: 0 0 10px 0; color: #555;'>Your package is now in transit!</p>
                    <p style='margin: 0; color: #555;'><strong>Tracking:</strong> A tracking number will be sent to you shortly.</p>
                </div>";
        } elseif ($newStatus === 'delivered') {
            $mail->Body .= "
                <div style='background-color: #d4edda; border: 1px solid #28a745; padding: 20px; border-radius: 8px; margin: 30px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: #155724;'>🎉 Delivered Successfully!</h4>
                    <p style='margin: 0 0 10px 0; color: #555;'>We hope you're satisfied with your purchase!</p>
                    <p style='margin: 0; color: #555;'>If you have any issues, please contact us within 7 days.</p>
                </div>";
        } elseif ($newStatus === 'cancelled') {
            $mail->Body .= "
                <div style='background-color: #f8d7da; border: 1px solid #dc3545; padding: 20px; border-radius: 8px; margin: 30px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: #721c24;'>Order Cancellation</h4>
                    <p style='margin: 0 0 10px 0; color: #555;'>Your order has been cancelled successfully.</p>
                    <p style='margin: 0; color: #555;'>Any payments will be refunded within 3-5 business days.</p>
                </div>";
        }

        $mail->Body .= "
                <div style='text-align: center; margin: 30px 0;'>
                    <p style='color: #666; margin: 0;'>Thank you for shopping with us!</p>
                </div>
            </div>
            <div style='text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #999; font-size: 14px;'>
                <p>If you have any questions, please contact our support team.</p>
                <p style='margin: 0;'>© " . date('Y') . " E-Commerce Store</p>
            </div>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Order status update email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send order confirmation email
function sendOrderConfirmationEmail($customerEmail, $customerName, $orderId, $orderItems, $totalAmount, $pdo) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jhongujol1299@gmail.com';
        $mail->Password = 'ljdo ohkv pehx idkv'; // App password
        $mail->SMTPSecure = "ssl";
        $mail->Port = 465;
        
        // Recipients
        $mail->setFrom('jhongujol1299@gmail.com', 'E-Commerce Store');
        $mail->addAddress($customerEmail, $customerName);
        
        // Build items list for email
        $itemsList = '';
        foreach ($orderItems as $item) {
            $itemTotal = $item['quantity'] * $item['item_price'];
            $itemsList .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['product_name']) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>" . (int)$item['quantity'] . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format((float)$item['item_price'], 2) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>$" . number_format($itemTotal, 2) . "</td>
                </tr>";
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = '✅ Order Confirmed - Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; border-bottom: 2px solid #4CAF50; padding-bottom: 20px;'>
                <h1 style='color: #4CAF50; margin: 0;'>Order Confirmed!</h1>
            </div>
            <div style='padding: 30px 0;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Hello " . htmlspecialchars($customerName) . ",</h2>
                <p style='color: #666; font-size: 16px; line-height: 1.6;'>
                    Great news! Your order has been confirmed and is now being prepared for shipment.
                </p>
                
                <div style='background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: center;'>
                    <h3 style='margin: 0; font-size: 18px;'>Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . "</h3>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Status: Confirmed & Processing</p>
                </div>
                
                <h3 style='color: #333; margin: 30px 0 15px 0;'>Order Details:</h3>
                <table style='width: 100%; border-collapse: collapse; background: #f9f9f9; border-radius: 8px; overflow: hidden;'>
                    <thead>
                        <tr style='background: #4CAF50; color: white;'>
                            <th style='padding: 12px; text-align: left;'>Product</th>
                            <th style='padding: 12px; text-align: center;'>Qty</th>
                            <th style='padding: 12px; text-align: right;'>Price</th>
                            <th style='padding: 12px; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$itemsList}
                    </tbody>
                    <tfoot>
                        <tr style='background: #e8f5e8; font-weight: bold;'>
                            <td colspan='3' style='padding: 15px; text-align: right;'>Total Amount:</td>
                            <td style='padding: 15px; text-align: right; color: #4CAF50; font-size: 18px;'>$" . number_format((float)$totalAmount, 2) . "</td>
                        </tr>
                    </tfoot>
                </table>
                
                <div style='background-color: #e8f5e8; border: 1px solid #4CAF50; padding: 20px; border-radius: 8px; margin: 30px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: #2e7d32;'>What happens next?</h4>
                    <ul style='margin: 0; padding-left: 20px; color: #555;'>
                        <li style='margin-bottom: 8px;'>Your order is confirmed and now being processed</li>
                        <li style='margin-bottom: 8px;'>You'll receive a tracking number once shipped</li>
                        <li style='margin-bottom: 8px;'>Expected delivery: 5-7 business days</li>
                        <li>We'll notify you of any updates</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p style='color: #666; margin: 0;'>Thank you for shopping with us!</p>
                </div>
            </div>
            <div style='text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #999; font-size: 14px;'>
                <p>If you have any questions, please contact our support team.</p>
                <p style='margin: 0;'>© " . date('Y') . " E-Commerce Store</p>
            </div>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Order confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to check if order is within grace period (5 minutes)
function isWithinGracePeriod($orderCreatedAt) {
    $orderTime = strtotime($orderCreatedAt);
    $currentTime = time();
    $timeDifference = $currentTime - $orderTime;
    $gracePeriodSeconds = 5 * 60; // 5 minutes
    
    return $timeDifference < $gracePeriodSeconds;
}

// Function to get remaining grace period time
function getRemainingGracePeriod($orderCreatedAt) {
    $orderTime = strtotime($orderCreatedAt);
    $currentTime = time();
    $timeDifference = $currentTime - $orderTime;
    $gracePeriodSeconds = 5 * 60; // 5 minutes
    $remaining = $gracePeriodSeconds - $timeDifference;
    
    if ($remaining <= 0) return 0;
    
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    return ['minutes' => $minutes, 'seconds' => $seconds, 'total_seconds' => $remaining];
}

// Function to get allowed status transitions
function getAllowedStatusTransitions($currentStatus) {
    $transitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [], // Terminal state
        'cancelled' => [] // Terminal state
    ];
    
    return $transitions[$currentStatus] ?? [];
}

// Function to sanitize input (added as it was referenced but not defined)
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = sanitizeInput($_POST['status']);
    
    // Verify that the order contains the seller's products
    $stmt = $pdo->prepare("SELECT o.* 
                          FROM orders o
                          JOIN order_items oi ON o.id = oi.order_id
                          JOIN products p ON oi.product_id = p.id
                          WHERE o.id = ? AND p.seller_id = ?
                          GROUP BY o.id");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        $currentStatus = $order['status'];
        $allowedTransitions = getAllowedStatusTransitions($currentStatus);
        
        // Check if the transition is allowed
        if (!in_array($newStatus, $allowedTransitions)) {
            echo "<div class='status-update-message error'>
                    <div class='message-header'>
                        <span class='status-badge error'>❌ ERROR</span>
                        <strong>Invalid status transition</strong>
                    </div>
                    <p>Cannot change status from <strong>" . ucfirst($currentStatus) . "</strong> to <strong>" . ucfirst($newStatus) . "</strong>.</p>
                  </div>";
            goto display_orders;
        }
        
        // Store old status for email
        $oldStatus = $order['status'];
        
        // Check if trying to process an order within grace period
        if ($order['status'] === 'pending' && $newStatus === 'processing' && isWithinGracePeriod($order['created_at'])) {
            $remaining = getRemainingGracePeriod($order['created_at']);
            echo "<div class='status-update-message error'>
                    <div class='message-header'>
                        <span class='status-badge error'>❌ BLOCKED</span>
                        <strong>Cannot process Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " yet</strong>
                    </div>
                    <p>This order is in the customer priority cancellation period. Please wait {$remaining['minutes']} minutes and {$remaining['seconds']} seconds before processing.</p>
                  </div>";
        } else {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $result = $stmt->execute([$newStatus, $orderId]);
            
            if ($result) {
                // Log the status change
                try {
                    $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                                          VALUES (?, ?, ?, ?)");
                    $notes = "Status updated from " . $oldStatus . " to " . $newStatus . " by seller.";
                    $stmt->execute([$orderId, $newStatus, $notes, $userId]);
                } catch (PDOException $e) {
                    error_log("Order status history insert failed: " . $e->getMessage());
                }
                
                // GET CUSTOMER INFORMATION FOR EMAIL
                $customerName = '';
                $customerEmail = '';
                
                if ($order['user_id']) {
                    // Logged-in user - get info from users table
                    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
                    $stmt->execute([$order['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        $customerName = trim($user['first_name'] . ' ' . $user['last_name']);
                        $customerEmail = $user['email'];
                    }
                } else {
                    // Guest order - get info from session (if available)
                    $guestInfo = $_SESSION['guest_order_' . $orderId] ?? null;
                    if ($guestInfo) {
                        $customerName = $guestInfo['customer_name'];
                        $customerEmail = $guestInfo['customer_email'];
                    }
                }
                
                // SEND EMAIL FOR EVERY STATUS CHANGE
                if ($customerEmail) {
                    // Get order items for this seller
                    $stmt = $pdo->prepare("SELECT oi.quantity, oi.price as item_price, p.name as product_name
                                          FROM order_items oi 
                                          JOIN products p ON oi.product_id = p.id
                                          WHERE oi.order_id = ? AND p.seller_id = ?");
                    $stmt->execute([$orderId, $userId]);
                    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Calculate total for this seller's items
                    $sellerTotal = 0;
                    foreach ($orderItems as $item) {
                        $sellerTotal += (float)$item['quantity'] * (float)$item['item_price'];
                    }
                    
                    // Send status update email
                    $emailSent = sendOrderStatusUpdateEmail($customerEmail, $customerName, $orderId, $newStatus, $oldStatus, $orderItems, $sellerTotal, $pdo);
                    
                    // Display success message with email status
                    $statusLabels = [
                        'pending' => '⏳ PENDING',
                        'processing' => '🔄 PROCESSING', 
                        'shipped' => '🚚 SHIPPED',
                        'delivered' => '✅ DELIVERED',
                        'cancelled' => '❌ CANCELLED'
                    ];
                    
                    $statusLabel = $statusLabels[$newStatus] ?? strtoupper($newStatus);
                    $emailStatus = $emailSent ? "✅ Email notification sent to customer." : "⚠️ Status updated but email notification failed.";
                    
                    echo "<div class='status-update-message " . $newStatus . "'>
                            <div class='message-header'>
                                <span class='status-badge " . $newStatus . "'>" . $statusLabel . "</span>
                                <strong>Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " status updated!</strong>
                            </div>
                            <p>Order status changed from <strong>" . ucfirst($oldStatus) . "</strong> to <strong>" . ucfirst($newStatus) . "</strong>.</p>
                            <p><small>" . $emailStatus . "</small></p>
                          </div>";
                } else {
                    // No email found
                    $statusLabels = [
                        'pending' => '⏳ PENDING',
                        'processing' => '🔄 PROCESSING', 
                        'shipped' => '🚚 SHIPPED',
                        'delivered' => '✅ DELIVERED',
                        'cancelled' => '❌ CANCELLED'
                    ];
                    
                    $statusLabel = $statusLabels[$newStatus] ?? strtoupper($newStatus);
                    
                    echo "<div class='status-update-message " . $newStatus . "'>
                            <div class='message-header'>
                                <span class='status-badge " . $newStatus . "'>" . $statusLabel . "</span>
                                <strong>Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " status updated!</strong>
                            </div>
                            <p>Order status changed from <strong>" . ucfirst($oldStatus) . "</strong> to <strong>" . ucfirst($newStatus) . "</strong>.</p>
                            <p><small>⚠️ Customer email not found for notification.</small></p>
                          </div>";
                }
            } else {
                echo "<p class='error-message'>Error updating order status. Please try again.</p>";
            }
        }
    } else {
        echo "<p class='error-message'>Order not found or you don't have permission to update this order.</p>";
    }
}

display_orders:

// Get seller's orders with proper LEFT JOIN to handle guest orders
$stmt = $pdo->prepare("SELECT o.*, oi.quantity, oi.price as item_price, p.name as product_name, p.id as product_id,
                      COALESCE(u.username, 'Guest Customer') as customer_name
                      FROM orders o
                      JOIN order_items oi ON o.id = oi.order_id
                      JOIN products p ON oi.product_id = p.id
                      LEFT JOIN users u ON o.user_id = u.id
                      WHERE p.seller_id = ?
                      ORDER BY o.created_at DESC");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group orders by order ID
$groupedOrders = [];
foreach ($orders as $order) {
    $orderId = $order['id'];
    if (!isset($groupedOrders[$orderId])) {
        $groupedOrders[$orderId] = [
            'order_id' => $orderId,
            'customer_name' => $order['customer_name'],
            'total_amount' => $order['total_amount'],
            'status' => $order['status'],
            'created_at' => $order['created_at'],
            'items' => []
        ];
    }
    
    $groupedOrders[$orderId]['items'][] = [
        'product_id' => $order['product_id'],
        'product_name' => $order['product_name'],
        'quantity' => $order['quantity'],
        'item_price' => $order['item_price']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Order Management</title>
<style>
.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 12px 16px;
    border-radius: 4px;
    border: 1px solid #c3e6cb;
    margin: 15px 0;
    font-weight: 500;
}

.warning-message {
    background-color: #fff3cd;
    color: #856404;
    padding: 12px 16px;
    border-radius: 4px;
    border: 1px solid #ffeaa7;
    margin: 15px 0;
    font-weight: 500;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 12px 16px;
    border-radius: 4px;
    border: 1px solid #f5c6cb;
    margin: 15px 0;
    font-weight: 500;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 8px;
    overflow: hidden;
}

.orders-table th {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    padding: 15px 10px;
    text-align: left;
    font-weight: 600;
    font-size: 14px;
}

.orders-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

.orders-table tr:hover {
    background-color: #f8f9fa;
}

.orders-table ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.orders-table li {
    padding: 3px 0;
    font-size: 14px;
}

.status-select {
    padding: 6px 10px;
    border: 2px solid #ddd;
    border-radius: 5px;
    background: white;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.status-select:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.status-select:hover {
    border-color: #4CAF50;
}

h1 {
    color: #333;
    margin: 20px 0;
    font-size: 28px;
    text-align: center;
    font-weight: 600;
}
</style>
</head>
<body>

<script>
// Auto-refresh page every 2 minutes to update grace periods
setTimeout(function() {
    location.reload();
}, 190000);
// Auto-hide error messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const errorMessages = document.querySelectorAll('.status-update-message.error');
    
    errorMessages.forEach(function(message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s ease-out';
            message.style.opacity = '0';
            
            // Remove from DOM after fade out
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 5000); // 5 seconds
    });
});
// Countdown timer for grace periods
function startCountdown(orderId, totalSeconds) {
    const timerElement = document.getElementById('timer-' + orderId);
    const selectElement = document.getElementById('status-' + orderId);
    const processingOption = selectElement ? selectElement.querySelector('option[value="processing"]') : null;
    
    if (!timerElement || totalSeconds <= 0) return;
    
    let remaining = totalSeconds;
    
    const interval = setInterval(function() {
        if (remaining <= 0) {
            clearInterval(interval);
            location.reload(); // Refresh page when grace period ends
            return;
        }
        
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        
        timerElement.innerHTML = `🔒 Processing blocked for: ${minutes}m ${seconds.toString().padStart(2, '0')}s<br><small>Customer priority cancellation period</small>`;
        
        // Keep processing option disabled during grace period
        if (processingOption) {
            processingOption.disabled = true;
            processingOption.classList.add('processing-blocked');
        }
        
        remaining--;
    }, 1000);
}
</script>

<h1>View All Orders</h1>

<?php if (empty($groupedOrders)): ?>
    <p style="text-align: center; color: #666; font-style: italic; padding: 40px;">No orders found.</p>
<?php else: ?>
<table class="orders-table">
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Products</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($groupedOrders as $order): ?>
            <?php 
            $withinGracePeriod = isWithinGracePeriod($order['created_at']);
            $remainingTime = $withinGracePeriod ? getRemainingGracePeriod($order['created_at']) : null;
            ?>
            <tr>
                <td><strong>#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                <td>
                    <ul>
                        <?php foreach ($order['items'] as $item): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <small>(x<?php echo (int)$item['quantity']; ?>)</small>  
                                - $<?php echo number_format((float)$item['item_price'], 2); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </td>
                <td><strong style="color: #4CAF50;">$<?php echo number_format((float)$order['total_amount'], 2); ?></strong></td>
                <td>
                    <span style="
                        padding: 5px 10px; 
                        border-radius: 15px; 
                        font-size: 12px; 
                        font-weight: bold; 
                        text-transform: uppercase;
                        background: <?php 
                            switch($order['status']) {
                                case 'pending': echo '#fff3cd; color: #856404;'; break;
                                case 'confirmed': echo '#d1ecf1; color: #0c5460;'; break;
                                case 'processing': echo '#cce5ff; color: #004085;'; break;
                                case 'shipped': echo '#d4edda; color: #155724;'; break;
                                case 'delivered': echo '#d4edda; color: #155724;'; break;
                                case 'cancelled': echo '#f8d7da; color: #721c24;'; break;
                                default: echo '#e2e3e5; color: #383d41;';
                            }
                        ?>
                    ">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </td>
                <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?><br>
                    <small style="color: #666;"><?php echo date('g:i A', strtotime($order['created_at'])); ?></small>
                </td>
                <td>
                    <!-- Status Update Dropdown -->
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <label for="status-<?php echo $order['order_id']; ?>" class="status-form-label">Update status:</label>
                        <select id="status-<?php echo $order['order_id']; ?>" name="status" class="status-select" 
                                onchange="if(this.value !== '' && !this.querySelector('option[value=\'' + this.value + '\']').disabled) this.form.submit();">
                            <option value="" selected disabled>Select new status</option>
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'disabled style="color: #ccc;"' : ''; ?>>Pending</option>
                            <option value="processing" 
                                <?php 
                                if ($order['status'] == 'processing') {
                                    echo 'disabled style="color: #ccc;"';
                                } elseif ($order['status'] === 'pending' && $withinGracePeriod) {
                                    echo 'disabled style="color: #ccc; text-decoration: line-through;" class="processing-blocked"';
                                }
                                ?>>
                                <?php 
                                if ($order['status'] === 'pending') {
                                    if ($withinGracePeriod) {
                                        echo '🔒 Process Order (BLOCKED - ' . $remainingTime['minutes'] . 'm ' . $remainingTime['seconds'] . 's)';
                                    } else {
                                        echo '✅ Process Order (Confirm & Lock)';
                                    }
                                } else {
                                    echo 'Processing';
                                }
                                ?>
                            </option>
                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'disabled style="color: #ccc;"' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'disabled style="color: #ccc;"' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'disabled style="color: #ccc;"' : ''; ?>>Cancelled</option>
                        </select>
                        <input type="hidden" name="update_status" value="1">
                        
                        <?php if ($order['status'] === 'pending' && $withinGracePeriod): ?>
                            <div class="countdown-timer" id="timer-<?php echo $order['order_id']; ?>">
                                🔒 Processing blocked for: <?php echo $remainingTime['minutes']; ?>m <?php echo str_pad($remainingTime['seconds'], 2, '0', STR_PAD_LEFT); ?>s<br>
                                <small>Customer priority cancellation period</small>
                            </div>
                            <script>
                                startCountdown(<?php echo $order['order_id']; ?>, <?php echo $remainingTime['total_seconds']; ?>);
                            </script>
                        <?php elseif ($order['status'] === 'pending'): ?>
                            <div class="countdown-timer ready">
                                ✅ Ready to process - Grace period ended<br>
                                <small>Customer can still cancel until you confirm</small>
                            </div>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>