<?php
// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

require_once 'includes/seller_header.php';
require_once 'config/database.php';

// Seller dashboard theme styles (section/table unification)
echo '<style>
body{background:#130325 !important;}
main{margin-left:240px;}
.section{background:rgba(255,255,255,0.1);padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.3);color:#F9F9F9;backdrop-filter:blur(10px)}
.orders-table-container{overflow-x:auto;margin-bottom:15px;border:1px solid rgba(255,255,255,0.2);border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.3);background:rgba(255,255,255,0.05)}
.orders-table{width:100%;border-collapse:collapse;font-size:.875rem}
.orders-table thead{background:rgba(255,255,255,0.1);position:sticky;top:0;z-index:10}
.orders-table th{padding:12px 12px;text-align:left;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#FFD736;border-bottom:2px solid rgba(255,255,255,0.2)}
.orders-table td{padding:12px;border-bottom:1px solid rgba(255,255,255,0.1);color:#F9F9F9}
.orders-table tbody tr{background:rgba(255,255,255,0.03);transition:all .15s ease-in-out}
.orders-table tbody tr:hover{background:#1a0a2e !important;transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.3)}
.status-badge{border-radius:999px;padding:4px 10px;font-weight:700;font-size:12px}
</style>';

requireSeller();

$userId = $_SESSION['user_id'];

function getGracePeriodMinutes($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'order_grace_period'");
        $stmt->execute();
        $gracePeriod = $stmt->fetchColumn();
        
        return $gracePeriod ? intval($gracePeriod) : 5;
    } catch (PDOException $e) {
        error_log("Error fetching grace period setting: " . $e->getMessage());
        return 5;
    }
}

function isWithinGracePeriod($orderCreatedAt, $pdo) {
    $orderTime = strtotime($orderCreatedAt);
    $currentTime = time();
    $timeDifference = $currentTime - $orderTime;
    $gracePeriodMinutes = getGracePeriodMinutes($pdo);
    $gracePeriodSeconds = $gracePeriodMinutes * 60;
    
    return $timeDifference < $gracePeriodSeconds;
}

function getRemainingGracePeriod($orderCreatedAt, $pdo) {
    $orderTime = strtotime($orderCreatedAt);
    $currentTime = time();
    $timeDifference = $currentTime - $orderTime;
    $gracePeriodMinutes = getGracePeriodMinutes($pdo);
    $gracePeriodSeconds = $gracePeriodMinutes * 60;
    $remaining = $gracePeriodSeconds - $timeDifference;
    
    if ($remaining <= 0) return 0;
    
    $minutes = floor($remaining / 60);
    $seconds = $remaining % 60;
    
    return [
        'minutes' => $minutes, 
        'seconds' => $seconds, 
        'total_seconds' => $remaining,
        'grace_period_minutes' => $gracePeriodMinutes
    ];
}

function getAllowedStatusTransitions($currentStatus) {
    $transitions = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => []
    ];
    
    return $transitions[$currentStatus] ?? [];
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function sendOrderStatusUpdateEmail($customerEmail, $customerName, $orderId, $newStatus, $oldStatus, $orderItems, $totalAmount, $pdo, $cancellationReason = '') {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jhongujol1299@gmail.com';
        $mail->Password = 'ljdo ohkv pehx idkv';
        $mail->SMTPSecure = "ssl";
        $mail->Port = 465;
        
        $mail->setFrom('jhongujol1299@gmail.com', 'E-Commerce Store');
        $mail->addAddress($customerEmail, $customerName);
        
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
                'message' => 'Your order has been cancelled by the seller.',
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

        $itemsList = '';
        foreach ($orderItems as $item) {
            $itemTotal = $item['quantity'] * $item['item_price'];
            $itemsList .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($item['product_name']) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>" . (int)$item['quantity'] . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₱" . number_format((float)$item['item_price'], 2) . "</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>₱" . number_format($itemTotal, 2) . "</td>
                </tr>";
        }
        
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
                            <td style='padding: 15px; text-align: right; color: " . $config['color'] . "; font-size: 18px;'>₱" . number_format((float)$totalAmount, 2) . "</td>
                        </tr>
                    </tfoot>
                </table>
                
                <div style='background-color: " . $config['color'] . "20; border: 1px solid " . $config['color'] . "; padding: 20px; border-radius: 8px; margin: 30px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: " . $config['color'] . ";'>What's next?</h4>
                    <p style='margin: 0; color: #555;'>" . $config['next_step'] . "</p>
                </div>";

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
            $reasonText = '';
            if (!empty($cancellationReason)) {
                $reasonText = "
                    <div style='background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                        <h5 style='margin: 0 0 8px 0; color: #856404;'>📝 Cancellation Reason:</h5>
                        <p style='margin: 0; color: #555; font-style: italic;'>" . nl2br(htmlspecialchars($cancellationReason)) . "</p>
                    </div>";
            }
            
            $mail->Body .= "
                <div style='background-color: #f8d7da; border: 1px solid #dc3545; padding: 20px; border-radius: 8px; margin: 30px 0;'>
                    <h4 style='margin: 0 0 10px 0; color: #721c24;'>Order Cancellation</h4>
                    <p style='margin: 0 0 10px 0; color: #555;'>Your order has been cancelled by the seller.</p>
                    <p style='margin: 0; color: #555;'>Any payments will be refunded within 3-5 business days.</p>
                </div>
                {$reasonText}";
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

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = sanitizeInput($_POST['status']);
    $cancellationReason = isset($_POST['cancellation_reason']) ? sanitizeInput($_POST['cancellation_reason']) : '';
    
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
        
        if (!in_array($newStatus, $allowedTransitions)) {
            echo "<div class='status-update-message error'>
                    <div class='message-header'>
                        <span class='status-badge error'>❌ ERROR</span>
                        <strong>Invalid status transition</strong>
                    </div>
                    <p>Cannot change status from <strong>" . ucfirst($currentStatus) . "</strong> to <strong>" . ucfirst($newStatus) . "</strong>.</p>
                  </div>";
        }
        elseif ($currentStatus === 'pending' && $newStatus === 'processing' && isWithinGracePeriod($order['created_at'], $pdo)) {
            $remaining = getRemainingGracePeriod($order['created_at'], $pdo);
            $gracePeriodMinutes = $remaining['grace_period_minutes'];
            echo "<div class='status-update-message error'>
                    <div class='message-header'>
                        <span class='status-badge error'>❌ BLOCKED</span>
                        <strong>Cannot process Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " yet</strong>
                    </div>
                    <p>This order is in the customer priority cancellation period ({$gracePeriodMinutes} minutes). Please wait {$remaining['minutes']} minutes and {$remaining['seconds']} seconds before processing.</p>
                  </div>";
        }
        else {
            $oldStatus = $order['status'];
            
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $result = $stmt->execute([$newStatus, $orderId]);
            
            if ($result) {
                try {
                    // Verify $userId is valid - it should be set from requireSeller() at the top
                    if (!isset($userId) || empty($userId)) {
                        // Fallback: try to get from session directly
                        $userId = $_SESSION['user_id'] ?? null;
                    }
                    
                    if ($userId !== null) {
                        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                                            VALUES (?, ?, ?, ?)");
                        $notes = "Status updated from " . $oldStatus . " to " . $newStatus . " by seller.";
                        if ($newStatus === 'cancelled' && !empty($cancellationReason)) {
                            $notes .= " Reason: " . $cancellationReason;
                        }
                        $stmt->execute([$orderId, $newStatus, $notes, $userId]);
                    } else {
                        error_log("Order status history not created: No valid user ID in session");
                    }
                } catch (PDOException $e) {
                    error_log("Order status history insert failed: " . $e->getMessage());
                }
                
                $customerName = '';
                $customerEmail = '';
                
                if ($order['user_id']) {
                    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
                    $stmt->execute([$order['user_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user) {
                        $customerName = trim($user['first_name'] . ' ' . $user['last_name']);
                        $customerEmail = $user['email'];
                    }
                } else {
                    $guestInfo = $_SESSION['guest_order_' . $orderId] ?? null;
                    if ($guestInfo) {
                        $customerName = $guestInfo['customer_name'];
                        $customerEmail = $guestInfo['customer_email'];
                    }
                }
                
                if ($customerEmail) {
                    $stmt = $pdo->prepare("SELECT oi.quantity, oi.price as item_price, p.name as product_name
                                          FROM order_items oi 
                                          JOIN products p ON oi.product_id = p.id
                                          WHERE oi.order_id = ? AND p.seller_id = ?");
                    $stmt->execute([$orderId, $userId]);
                    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $sellerTotal = 0;
                    foreach ($orderItems as $item) {
                        $sellerTotal += (float)$item['quantity'] * (float)$item['item_price'];
                    }
                    
                    $emailSent = sendOrderStatusUpdateEmail($customerEmail, $customerName, $orderId, $newStatus, $oldStatus, $orderItems, $sellerTotal, $pdo, $cancellationReason);
                    
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
                echo "<div class='status-update-message error'>
                        <div class='message-header'>
                            <span class='status-badge error'>❌ ERROR</span>
                            <strong>Failed to update order status</strong>
                        </div>
                        <p>There was an error updating the order status. Please try again.</p>
                      </div>";
            }
        }
    } else {
        echo "<div class='status-update-message error'>
                <div class='message-header'>
                    <span class='status-badge error'>❌ ERROR</span>
                    <strong>Order not found</strong>
                </div>
                <p>The order was not found or you don't have permission to update it.</p>
              </div>";
    }
}

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
    background: #ffffff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(19, 3, 37, 0.1);
}

.orders-table th {
    background: linear-gradient(135deg, #130325, #241344);
    color: #F9F9F9;
    padding: 14px 12px;
    text-align: left;
    font-weight: 700;
    font-size: 14px;
    border-bottom: 2px solid #FFD736;
}

.orders-table td {
    padding: 12px 12px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
    color: #2c3e50;
}

.orders-table tr:hover {
    background-color: #f8f9ff;
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

.orders-wrapper { max-width: 1200px; margin: 0 auto; padding: 0 20px 20px; }

h1 {
    color: #130325;
    margin: 24px 0;
    font-size: 30px;
    text-align: center;
    font-weight: 800;
    letter-spacing: 1px;
}

.status-update-message {
    position: fixed;
    right: 20px;
    bottom: 20px;
    max-width: 360px;
    background: #130325;
    color: #F9F9F9;
    border: 1px solid rgba(255, 215, 54, 0.4);
    border-left: 4px solid #FFD736;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    padding: 14px 16px;
    z-index: 2000;
    animation: toastIn 300ms ease-out;
}

.status-update-message .message-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
    background: rgba(255, 215, 54, 0.15);
    color: #FFD736;
    border: 1px solid rgba(255, 215, 54, 0.4);
}

.status-update-message.success { border-left-color: #28a745; }
.status-update-message.error { border-left-color: #dc3545; }
.status-update-message.processing { border-left-color: #17a2b8; }

@keyframes toastIn {
    from { transform: translateY(10px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.btn-process-order,
.btn-cancel-order {
    padding: 10px 16px;
    margin: 4px 0;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: block;
    width: 100%;
    text-align: center;
}

.btn-process-order {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.btn-process-order:hover {
    background: linear-gradient(135deg, #218838, #1aa179);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
    transform: translateY(-1px);
}

.btn-cancel-order {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.btn-cancel-order:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    transform: translateY(-1px);
}

.countdown-timer {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    border: 2px solid #ffc107;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    color: #856404;
    animation: pulse 2s infinite;
    font-size: 13px;
}

.countdown-timer.ready {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border-color: #28a745;
    color: #155724;
    animation: none;
}

.cancel-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.6);
    animation: fadeIn 0.3s ease;
}

.cancel-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideDown 0.3s ease;
}

.cancel-modal-header {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cancel-modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
}

.cancel-modal-close {
    color: white;
    font-size: 32px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    width: 32px;
    height: 32px;
    line-height: 32px;
    text-align: center;
    transition: transform 0.2s;
}

.cancel-modal-close:hover {
    transform: scale(1.2);
}

.cancel-modal-body {
    padding: 25px;
}

.cancel-modal-body label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
    font-size: 15px;
}

.cancel-reason-textarea {
    width: 100%;
    min-height: 120px;
    padding: 12px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    font-family: Arial, sans-serif;
    resize: vertical;
    transition: border-color 0.3s;
    box-sizing: border-box;
}

.cancel-reason-textarea:focus {
    outline: none;
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.cancel-modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-modal-cancel {
    padding: 10px 20px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-modal-cancel:hover {
    background: #5a6268;
}

.btn-modal-confirm {
    padding: 10px 20px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

.btn-modal-confirm:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    transform: translateY(-1px);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideDown {
    from { 
        transform: translateY(-50px);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }
}

.status-form-label {
    display: block;
    font-weight: 600;
    color: #130325;
    margin-bottom: 6px;
    font-size: 13px;
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
// Auto-hide all notification messages after 4 seconds
document.addEventListener('DOMContentLoaded', function() {
    const allMessages = document.querySelectorAll('.status-update-message');
    
    allMessages.forEach(function(message) {
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s ease-out';
            message.style.opacity = '0';
            
            // Remove from DOM after fade out
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 4000); // 4 seconds
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
function showStatusDropdown(orderId, preselectedStatus) {
    // Hide action buttons
    var actionButtons = document.getElementById('action-buttons-' + orderId);
    if (actionButtons) {
        actionButtons.style.display = 'none';
    }

    // Show status form
    var form = document.getElementById('status-form-' + orderId);
    if (form) {
        form.style.display = 'block';

        // Preselect the status if provided
        if (preselectedStatus) {
            var select = form.querySelector('select');
            if (select) {
                select.value = preselectedStatus;
                // Auto-submit if cancelled or processing
                if (preselectedStatus === 'cancelled' || preselectedStatus === 'processing') {
                    form.submit();
                }
            }
        }
    }
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
            $withinGracePeriod = isWithinGracePeriod($order['created_at'], $pdo);
            $remainingTime = $withinGracePeriod ? getRemainingGracePeriod($order['created_at'], $pdo) : null;
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
                <!-- Replace the entire <td> Action column section in your table with this code -->

<td>
    <?php if ($order['status'] === 'pending'): ?>
        <?php if ($withinGracePeriod): ?>
            <!-- Grace period active - show countdown timer -->
            <div class="countdown-timer" id="timer-<?php echo $order['order_id']; ?>">
                🔒 Processing blocked for: <?php echo $remainingTime['minutes']; ?>m <?php echo str_pad($remainingTime['seconds'], 2, '0', STR_PAD_LEFT); ?>s<br>
                <small>Customer priority cancellation period</small>
            </div>
            <script>
                startCountdown(<?php echo $order['order_id']; ?>, <?php echo $remainingTime['total_seconds']; ?>);
            </script>
        <?php else: ?>
            <!-- Grace period ended - show action buttons -->
            <div id="action-buttons-<?php echo $order['order_id']; ?>">
                <button type="button" class="btn-process-order" onclick="showStatusDropdown(<?php echo $order['order_id']; ?>, 'processing')">
                    ✅ Process Order
                </button>
                <button type="button" class="btn-cancel-order" onclick="showStatusDropdown(<?php echo $order['order_id']; ?>, 'cancelled')">
                    ❌ Cancel
                </button>
            </div>
            
            <div class="countdown-timer ready" style="margin-top: 8px;">
                ✅ Ready to process - Grace period ended<br>
                <small>Customer can still cancel until you confirm</small>
            </div>
            
            <!-- Status Update Form (hidden by default) -->
            <form method="POST" action="" style="margin: 8px 0 0 0; display: none;" id="status-form-<?php echo $order['order_id']; ?>">
                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                <label for="status-<?php echo $order['order_id']; ?>" class="status-form-label">Update status:</label>
                <select id="status-<?php echo $order['order_id']; ?>" name="status" class="status-select" 
                        onchange="if(this.value !== '' && !this.querySelector('option[value=\'' + this.value + '\']').disabled) this.form.submit();">
                    <option value="" selected disabled>Select new status</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <input type="hidden" name="update_status" value="1">
            </form>
        <?php endif; ?>
    <?php elseif ($order['status'] === 'processing'): ?>
        <!-- Processing orders - show dropdown directly -->
        <form method="POST" action="" style="margin: 0;">
            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
            <label for="status-<?php echo $order['order_id']; ?>" class="status-form-label">Update status:</label>
            <select id="status-<?php echo $order['order_id']; ?>" name="status" class="status-select" 
                    onchange="if(this.value !== '' && !this.querySelector('option[value=\'' + this.value + '\']').disabled) this.form.submit();">
                <option value="" selected disabled>Select new status</option>
                <option value="shipped">Shipped</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <input type="hidden" name="update_status" value="1">
        </form>
    <?php elseif ($order['status'] === 'shipped'): ?>
        <!-- Shipped orders - show dropdown directly -->
        <form method="POST" action="" style="margin: 0;">
            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
            <label for="status-<?php echo $order['order_id']; ?>" class="status-form-label">Update status:</label>
            <select id="status-<?php echo $order['order_id']; ?>" name="status" class="status-select" 
                    onchange="if(this.value !== '' && !this.querySelector('option[value=\'' + this.value + '\']').disabled) this.form.submit();">
                <option value="" selected disabled>Select new status</option>
                <option value="delivered">Delivered</option>
            </select>
            <input type="hidden" name="update_status" value="1">
        </form>
    <?php elseif (in_array($order['status'], ['delivered', 'cancelled'])): ?>
        <!-- Terminal states - no actions available -->
        <span style="color: #999; font-style: italic; font-size: 13px;">No actions available</span>
    <?php endif; ?>
</td>

        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</body>
</html>

// Ensure the file ends cleanly


