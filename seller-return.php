<?php
ob_start(); // Add this line immediately after opening PHP tag
session_start();
require_once 'config/database.php';
require_once 'includes/seller_header.php';
requireLogin();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';
$sellerId = $_SESSION['user_id'];

function sendReturnDecisionEmail($returnInfo, $action, $note = '') {
    global $pdo;
    
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
        $mail->addAddress($returnInfo['customer_email'], $returnInfo['customer_first_name']);
        
        $mail->isHTML(true);
        
        if ($action === 'approve') {
            $mail->Subject = '✅ Return Request Approved - Order #' . str_pad($returnInfo['order_id'], 6, '0', STR_PAD_LEFT);
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; border-bottom: 2px solid #28a745; padding-bottom: 20px;'>
                    <h1 style='color: #28a745; margin: 0;'>Return Request Approved ✅</h1>
                </div>
                <div style='padding: 30px 0;'>
                    <h2 style='color: #333;'>Good News!</h2>
                    <p style='color: #666; font-size: 16px;'>Your return request has been approved by the seller.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 10px 0;'><strong>Order ID:</strong> #" . str_pad($returnInfo['order_id'], 6, '0', STR_PAD_LEFT) . "</p>
                        <p style='margin: 10px 0;'><strong>Product:</strong> " . htmlspecialchars($returnInfo['product_name']) . "</p>
                        <p style='margin: 10px 0;'><strong>Status:</strong> <span style='color: #28a745; font-weight: bold;'>APPROVED</span></p>
                    </div>
                    
                    <div style='background-color: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;'>
                        <h3 style='color: #155724; margin-top: 0;'>Next Steps:</h3>
                        <ol style='color: #155724; margin: 10px 0;'>
                            <li>Prepare the product for return (original packaging if possible)</li>
                            <li>The seller will contact you for pickup/shipping details</li>
                            <li>Your refund will be processed within 3-5 business days after receiving the product</li>
                        </ol>
                    </div>
                </div>
                <div style='text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #999; font-size: 14px;'>
                    <p>Thank you for your patience!</p>
                    <p style='margin: 0;'>© 2024 E-Commerce Store</p>
                </div>
            </div>";
        } else {
            $mail->Subject = '❌ Return Request Rejected - Order #' . str_pad($returnInfo['order_id'], 6, '0', STR_PAD_LEFT);
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='text-align: center; border-bottom: 2px solid #dc3545; padding-bottom: 20px;'>
                    <h1 style='color: #dc3545; margin: 0;'>Return Request Rejected</h1>
                </div>
                <div style='padding: 30px 0;'>
                    <p style='color: #666; font-size: 16px;'>We regret to inform you that your return request has been rejected.</p>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 10px 0;'><strong>Order ID:</strong> #" . str_pad($returnInfo['order_id'], 6, '0', STR_PAD_LEFT) . "</p>
                        <p style='margin: 10px 0;'><strong>Product:</strong> " . htmlspecialchars($returnInfo['product_name']) . "</p>
                        <p style='margin: 10px 0;'><strong>Status:</strong> <span style='color: #dc3545; font-weight: bold;'>REJECTED</span></p>
                    </div>
                    
                    <div style='background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0;'>
                        <h3 style='color: #721c24; margin-top: 0;'>Seller's Reason:</h3>
                        <p style='color: #721c24; margin: 0;'>" . nl2br(htmlspecialchars($note)) . "</p>
                    </div>
                    
                    <div style='background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p style='margin: 0; color: #0c5460; font-size: 14px;'>
                            If you believe this decision is incorrect, please contact our customer support team or the seller directly.
                        </p>
                    </div>
                </div>
                <div style='text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #999; font-size: 14px;'>
                    <p>Thank you for your understanding</p>
                    <p style='margin: 0;'>© 2024 E-Commerce Store</p>
                </div>
            </div>";
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Return decision email failed: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $returnId = intval($_POST['return_id']);
    $action = $_POST['action'];
    $rejectionNote = isset($_POST['rejection_note']) ? trim($_POST['rejection_note']) : '';
    
    try {
        // Verify this return belongs to seller's product
        $stmt = $pdo->prepare("
            SELECT rr.*, o.user_id as customer_id, u.email as customer_email, 
                u.first_name as customer_first_name, p.name as product_name
            FROM return_requests rr
            JOIN orders o ON rr.order_id = o.id
            JOIN products p ON rr.product_id = p.id
            JOIN users u ON o.user_id = u.id
            WHERE rr.id = ? AND p.seller_id = ?
        ");
        $stmt->execute([$returnId, $sellerId]);
        $returnRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$returnRequest) {
            $_SESSION['error'] = 'Invalid return request';
        } else {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE return_requests SET status = 'approved', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$returnId]);
                $_SESSION['success'] = 'Return request approved successfully';
                
                // Send approval email
                sendReturnDecisionEmail($returnRequest, $action);
                
                // Create notification for customer
              // Create notification for customer
                $notificationMessage = "Your return request for Order #" . str_pad($returnRequest['order_id'], 6, '0', STR_PAD_LEFT) . " has been approved by the seller.";
                $notifStmt = $pdo->prepare("
                    INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) 
                    VALUES (?, 'return_approved', ?, ?, NOW())
                ");
                $notifStmt->execute([
                    $returnRequest['order_id'],
                    $notificationMessage,
                    $sellerId
                ]);
                
            } elseif ($action === 'reject') {
                if (empty($rejectionNote)) {
                    $_SESSION['error'] = 'Please provide a reason for rejection';
                } else {
                    $stmt = $pdo->prepare("UPDATE return_requests SET status = 'rejected', rejection_note = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$rejectionNote, $returnId]);
                    $_SESSION['success'] = 'Return request rejected';
                    
                    // Send rejection email
                    sendReturnDecisionEmail($returnRequest, $action, $rejectionNote);
                    
                    // Create notification for customer
                    $notificationMessage = "Your return request for Order #" . str_pad($returnRequest['order_id'], 6, '0', STR_PAD_LEFT) . " has been rejected. Reason: " . $rejectionNote;
                    $notifStmt = $pdo->prepare("
                    INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) 
                    VALUES (?, 'return_rejected', ?, ?, NOW())
                ");
                    $notifStmt->execute([
                        $returnRequest['order_id'],
                        $notificationMessage,
                        $sellerId
                    ]);
                }
            }
        }
    } catch (Exception $e) {
        error_log("Return action error: " . $e->getMessage());
        $_SESSION['error'] = 'Error processing request: ' . $e->getMessage();
    }
    
    header('Location: seller-returns.php');
    exit;
}

// Get all return requests for seller's products with photo count
try {
    $stmt = $pdo->prepare("
        SELECT rr.id, rr.order_id, rr.product_id, rr.reason, rr.status, rr.rejection_note, 
               rr.created_at, rr.updated_at,
               o.id as order_id_check, o.total_amount, o.created_at as order_date, o.user_id as customer_id,
               u.first_name, u.last_name, u.email, u.phone,
               p.name as product_name, p.price as product_price, p.seller_id as product_seller_id,
               (SELECT COUNT(*) FROM return_photos WHERE return_request_id = rr.id) as photo_count,
               oi.quantity as order_quantity
        FROM return_requests rr
        INNER JOIN orders o ON rr.order_id = o.id
        INNER JOIN users u ON o.user_id = u.id
        INNER JOIN products p ON rr.product_id = p.id
        INNER JOIN order_items oi ON oi.order_id = o.id AND oi.product_id = p.id
        WHERE p.seller_id = ?
        ORDER BY 
            CASE rr.status 
                WHEN 'pending' THEN 1 
                WHEN 'approved' THEN 2 
                WHEN 'rejected' THEN 3 
            END,
            rr.created_at DESC
    ");
    
    $stmt->execute([$sellerId]);
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching returns: " . $e->getMessage());
    $returns = [];
    $_SESSION['error'] = 'Error loading return requests: ' . $e->getMessage();
}
?>
<style>
body {
    background: #f5f5f5;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.returns-container {
    max-width: 1400px;
    margin: 40px auto;
    padding: 20px;
}

.returns-container h1 {
    color: #2c3e50;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
}

.return-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
}

.return-card:hover {
    box-shadow: 0 6px 25px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.return-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px solid #f0f0f0;
    padding-bottom: 20px;
    margin-bottom: 25px;
}

.return-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 1.4rem;
    font-weight: 700;
}

.return-header p {
    color: #7f8c8d;
    margin: 8px 0 0 0;
    font-size: 1rem;
}

.status-badge {
    padding: 8px 20px;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.status-pending {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border: 2px solid #ffc107;
}

.status-approved {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border: 2px solid #28a745;
}

.status-rejected {
    background: linear-gradient(135deg, #f8d7da, #f1b0b7);
    color: #721c24;
    border: 2px solid #dc3545;
}

.return-card > div[style*="margin: 20px 0"] p {
    color: #2c3e50;
    font-size: 1rem;
    margin: 10px 0;
    line-height: 1.6;
}

.return-card > div[style*="margin: 20px 0"] p strong {
    color: #34495e;
    font-weight: 600;
}

.return-card > div[style*="background: #f8f9fa"] {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef) !important;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    border-left: 5px solid #007bff;
}

.return-card > div[style*="background: #f8f9fa"] strong {
    color: #2c3e50;
    font-size: 1.1rem;
    display: block;
    margin-bottom: 10px;
}

.return-card > div[style*="background: #f8f9fa"] p {
    color: #495057;
    margin: 10px 0 0 0;
    line-height: 1.8;
    font-size: 0.95rem;
}

.return-photos {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 15px;
    margin: 25px 0;
}

.return-photos + div strong {
    color: #2c3e50;
    font-size: 1.1rem;
    display: block;
    margin-bottom: 15px;
}

.return-photo {
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    border: 3px solid #dee2e6;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.return-photo:hover {
    border-color: #007bff;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}

.return-photo img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.return-photo:hover img {
    transform: scale(1.1);
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
}

.btn {
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.btn-approve {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.btn-approve:hover {
    background: linear-gradient(135deg, #218838, #1aa179);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.4);
}

.btn-reject {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.btn-reject:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.4);
}

.alert {
    padding: 18px 25px;
    border-radius: 10px;
    margin-bottom: 25px;
    transition: all 0.5s ease;
    font-size: 1rem;
    font-weight: 500;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border: 2px solid #28a745;
    border-left: 6px solid #28a745;
}

.alert-error {
    background: linear-gradient(135deg, #f8d7da, #f1b0b7);
    color: #721c24;
    border: 2px solid #dc3545;
    border-left: 6px solid #dc3545;
}

.cancel-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cancel-modal-content {
    background: white;
    padding: 35px;
    border-radius: 15px;
    max-width: 550px;
    width: 90%;
    position: relative;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-50px) scale(0.9);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.cancel-modal-content h3 {
    color: #2c3e50;
    font-size: 1.5rem;
    margin: 0 0 15px 0;
    font-weight: 700;
}

.cancel-modal-content > p {
    color: #495057;
    font-size: 1rem;
    margin-bottom: 20px;
}

.close-modal {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 32px;
    cursor: pointer;
    color: #adb5bd;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.close-modal:hover {
    color: #dc3545;
    background: #f8f9fa;
}

.cancel-modal-content label {
    color: #2c3e50;
    font-weight: 600;
}

.cancel-modal-content textarea {
    color: #495057;
}

.cancel-modal-content textarea::placeholder {
    color: #adb5bd;
}

.cancel-modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 25px;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.4);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.4);
}

/* Rejection note display styling */
.return-card > div[style*="background: #f8d7da"] {
    background: linear-gradient(135deg, #f8d7da, #f1b0b7) !important;
    padding: 20px;
    border-radius: 10px;
    margin-top: 20px;
    border-left: 6px solid #dc3545 !important;
}

.return-card > div[style*="background: #f8d7da"] strong {
    color: #721c24;
    font-size: 1.1rem;
    display: block;
    margin-bottom: 10px;
}

.return-card > div[style*="background: #f8d7da"] p {
    color: #721c24 !important;
    margin: 10px 0 0 0 !important;
    line-height: 1.8;
}

/* No returns message */
.return-card p[style*="text-align: center"] {
    color: #6c757d;
    font-size: 1.2rem;
    font-style: italic;
}

@media (max-width: 768px) {
    .returns-container {
        padding: 15px;
    }
    
    .return-card {
        padding: 20px;
    }
    
    .return-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
    
    .return-photos {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
<?php
// Calculate statistics
$pendingCount = count(array_filter($returns, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($returns, fn($r) => $r['status'] === 'approved'));
$rejectedCount = count(array_filter($returns, fn($r) => $r['status'] === 'rejected'));
?>


<div class="returns-container">
    <h1 style="margin-bottom: 30px; color: #333;">Return Requests Management</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (empty($returns)): ?>
        <div class="return-card">
            <p style="text-align: center; color: #666; font-size: 1.1rem;">No return requests yet</p>
        </div>
    <?php else: ?>
        <?php foreach ($returns as $return): ?>
            <div class="return-card">
                <div class="return-header">
                    <div>
                        <h3 style="margin: 0; color: #333;">Order #<?php echo str_pad($return['order_id'], 6, '0', STR_PAD_LEFT); ?></h3>
                        <p style="color: #666; margin: 5px 0;">Product: <?php echo htmlspecialchars($return['product_name']); ?></p>
                    </div>
                    <span class="status-badge status-<?php echo htmlspecialchars($return['status']); ?>">
                        <?php echo ucfirst(htmlspecialchars($return['status'])); ?>
                    </span>
                </div>
                
                <div style="margin: 20px 0;">
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($return['email']); ?></p>
                    <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($return['created_at'])); ?></p>
                </div>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                    <strong>Reason:</strong>
                    <p style="margin: 10px 0 0 0;"><?php echo nl2br(htmlspecialchars($return['reason'])); ?></p>
                </div>
                
                <?php
                // Get photos for this specific return
                try {
                    $photoStmt = $pdo->prepare("SELECT photo_path FROM return_photos WHERE return_request_id = ?");
                    $photoStmt->execute([$return['id']]);
                    $photos = $photoStmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (Exception $e) {
                    error_log("Error fetching photos: " . $e->getMessage());
                    $photos = [];
                }
                ?>
                
                <?php if (!empty($photos)): ?>
                    <div>
                        <strong>Attached Photos (<?php echo count($photos); ?>):</strong>
                        <div class="return-photos">
                            <?php foreach ($photos as $photo): ?>
                                <div class="return-photo">
                                    <a href="<?php echo htmlspecialchars($photo); ?>" target="_blank">
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="Return photo">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($return['status'] === 'pending'): ?>
                    <div class="action-buttons">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="return_id" value="<?php echo $return['id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-approve" onclick="return confirm('Approve this return request?')">
                                ✓ Approve Return
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-reject" onclick="openRejectModal(<?php echo $return['id']; ?>)">
                            ✗ Reject Return
                        </button>
                    </div>
                <?php elseif ($return['status'] === 'rejected' && !empty($return['rejection_note'])): ?>
                    <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #dc3545;">
                        <strong style="color: #721c24;">Rejection Reason:</strong>
                        <p style="margin: 10px 0 0 0; color: #721c24;"><?php echo nl2br(htmlspecialchars($return['rejection_note'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="cancel-modal" style="display: none;">
    <div class="cancel-modal-content">
        <button class="close-modal" type="button" onclick="closeRejectModal()">&times;</button>
        <h3>Reject Return Request</h3>
        <p>Please provide a reason for rejecting this return request</p>
        
        <form method="POST">
            <input type="hidden" name="return_id" id="rejectReturnId" value="">
            <input type="hidden" name="action" value="reject">
            
            <div style="margin: 20px 0;">
                <label for="rejection_note" style="display: block; margin-bottom: 10px; font-weight: 600; color: #333;">
                    Rejection Reason: <span style="color: #dc3545;">*</span>
                </label>
                <textarea 
                    name="rejection_note" 
                    id="rejection_note" 
                    rows="4" 
                    required
                    placeholder="Explain why you're rejecting this return request (e.g., product was used, damaged by customer, outside return window, etc.)"
                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical;"
                ></textarea>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #ffc107;">
                <p style="margin: 0; color: #856404;">
                    <strong>Note:</strong> The customer will receive an email with your rejection reason. Please be professional and clear.
                </p>
            </div>
            
            <div class="cancel-modal-buttons">
                <button type="button" class="btn btn-primary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Submit Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(returnId) {
    document.getElementById('rejectReturnId').value = returnId;
    document.getElementById('rejectModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('rejection_note').value = '';
}

// Close modal when clicking outside
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
