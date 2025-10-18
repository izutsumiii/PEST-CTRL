<?php
session_start();
date_default_timezone_set('Asia/Manila');
require_once 'config/database.php';
require_once 'includes/functions.php';

// PHPMailer for notifications
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: user-dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'];
$orderId = intval($_POST['order_id']);
$reason = trim($_POST['return_reason']);
$mainIssue = isset($_POST['main_issue']) ? trim($_POST['main_issue']) : '';
$subIssue = isset($_POST['sub_issue']) ? trim($_POST['sub_issue']) : '';

// Validate main issue
if (empty($mainIssue)) {
    $_SESSION['error_message'] = 'Please select what happened to your order';
    header('Location: user-dashboard.php');
    exit;
}

// Validate reason
if (empty($reason)) {
    $_SESSION['error_message'] = 'Please provide details about the issue';
    header('Location: user-dashboard.php');
    exit;
}

// FIXED: Proper file validation
if (!isset($_FILES['return_photos']) || !is_array($_FILES['return_photos']['name'])) {
    $_SESSION['error_message'] = 'No photos uploaded. Please upload exactly 4 photos.';
    header('Location: user-dashboard.php');
    exit;
}

$totalFiles = count($_FILES['return_photos']['name']);
if ($totalFiles === 0) {
    $_SESSION['error_message'] = 'Please upload exactly 4 photos showing the issue';
    header('Location: user-dashboard.php');
    exit;
}
// FIXED: Better file counting and validation
$validFiles = [];
$uploadErrors = [];
$actualFileCount = 0;

// First, count actual files being uploaded
for ($i = 0; $i < $totalFiles; $i++) {
    if (isset($_FILES['return_photos']['error'][$i]) && 
        $_FILES['return_photos']['error'][$i] !== UPLOAD_ERR_NO_FILE &&
        !empty($_FILES['return_photos']['name'][$i])) {
        $actualFileCount++;
    }
}

// Validate we have exactly 4 files
if ($actualFileCount !== 4) {
    $_SESSION['error_message'] = "Please upload exactly 4 photos (found $actualFileCount)";
    header('Location: user-dashboard.php');
    exit;
}

for ($i = 0; $i < $totalFiles; $i++) {
    if (!isset($_FILES['return_photos']['error'][$i])) {
        continue;
    }
    
    $errorCode = $_FILES['return_photos']['error'][$i];
    
    // Skip empty slots
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    
    $tmpName = $_FILES['return_photos']['tmp_name'][$i];
    $fileName = $_FILES['return_photos']['name'][$i];
    $fileSize = $_FILES['return_photos']['size'][$i];
    
    // Check for upload errors
    if ($errorCode !== UPLOAD_ERR_OK) {
        $uploadErrors[] = "File upload error: $fileName (code: $errorCode)";
        continue;
    }
    
    // Validate temp file exists
    if (!file_exists($tmpName) || !is_uploaded_file($tmpName)) {
        $uploadErrors[] = "$fileName is not a valid upload";
        continue;
    }
    
    // Validate file type
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($extension, $allowedExtensions)) {
        $uploadErrors[] = "$fileName - Invalid type";
        continue;
    }
    
    // Validate file size (5MB max)
    if ($fileSize > 5 * 1024 * 1024) {
        $uploadErrors[] = "$fileName exceeds 5MB";
        continue;
    }
    
    // Validate it's an image
    $imageInfo = @getimagesize($tmpName);
    if ($imageInfo === false) {
        $uploadErrors[] = "$fileName is not a valid image";
        continue;
    }
    
    $validFiles[] = [
        'tmp_name' => $tmpName,
        'name' => $fileName,
        'size' => $fileSize,
        'extension' => $extension
    ];
}

// Final validation
if (count($validFiles) !== 4) {
    $msg = count($validFiles) . " valid files out of 4 required.";
    if (!empty($uploadErrors)) {
        $msg .= " Errors: " . implode("; ", $uploadErrors);
    }
    $_SESSION['error_message'] = $msg;
    header('Location: user-dashboard.php');
    exit;
}

// Check if we have exactly 4 valid files
$validCount = count($validFiles);
if ($validCount !== 4) {
    $errorMsg = "Found $validCount valid file(s) but exactly 4 are required. ";
    if (!empty($uploadErrors)) {
        $errorMsg .= 'Issues: ' . implode('; ', array_slice($uploadErrors, 0, 3));
        if (count($uploadErrors) > 3) {
            $errorMsg .= '...';
        }
    }
    $_SESSION['error_message'] = $errorMsg;
    header('Location: user-dashboard.php');
    exit;
}

try {
    // Verify order belongs to user and is delivered
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'delivered'");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        $_SESSION['error_message'] = 'Invalid order or order not eligible for return';
        header('Location: user-dashboard.php');
        exit;
    }

    // Get product ID from order
    $stmt = $pdo->prepare("SELECT product_id FROM order_items WHERE order_id = ? LIMIT 1");
    $stmt->execute([$orderId]);
    $productId = $stmt->fetchColumn();
    
    if (!$productId) {
        throw new Exception('Product not found for this order');
    }

    // Build full reason with issue type
    $issueTypes = [
        'damaged' => 'Received Damaged Item(s)',
        'incorrect' => 'Received Incorrect Item(s)',
        'not_received' => 'Did Not Receive Some/All Items'
    ];
    
    $subIssueTypes = [
        'damaged_item' => 'Damaged item',
        'defective_item' => 'Product is defective or does not work',
        'parcel_not_delivered' => 'Parcel not delivered',
        'missing_parts' => 'Missing part of the order',
        'empty_parcel' => 'Empty parcel'
    ];
    
    $fullReason = "**Issue Type:** " . ($issueTypes[$mainIssue] ?? 'Unknown') . "\n";
    if (!empty($subIssue)) {
        $fullReason .= "**Specific Issue:** " . ($subIssueTypes[$subIssue] ?? 'Unknown') . "\n";
    }
    $fullReason .= "**Details:** " . $reason;

    $pdo->beginTransaction();
    
    // Insert return request
    $stmt = $pdo->prepare("INSERT INTO return_requests (order_id, user_id, product_id, reason, status, created_at) 
                          VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$orderId, $userId, $productId, $fullReason]);
    $returnRequestId = $pdo->lastInsertId();

    if (!$returnRequestId) {
        throw new Exception('Failed to create return request');
    }

    // Create upload directory with proper error handling
    $uploadDir = 'uploads/returns/' . $returnRequestId . '/';
    
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory: ' . $uploadDir);
        }
    }
    
    // Ensure directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable: ' . $uploadDir);
    }

    // FIXED: Upload files one at a time with unique names
$uploadedPhotos = [];
$uploadFailures = [];
$photoCounter = 1;

foreach ($validFiles as $file) {
    $tmpName = $file['tmp_name'];
    $extension = $file['extension'];
    $originalName = $file['name'];
    
    // Verify file still exists before processing
    if (!file_exists($tmpName) || !is_uploaded_file($tmpName)) {
        $uploadFailures[] = "$originalName: Temp file no longer valid";
        continue;
    }
    
    // Generate unique filename using counter + timestamp + random
    $uniqueId = uniqid('', true); // More entropy
    $filename = sprintf(
        'return_%d_photo%d_%s.%s',
        $returnRequestId,
        $photoCounter,
        str_replace('.', '', $uniqueId),
        $extension
    );
    $filepath = $uploadDir . $filename;
    
    // Ensure no duplicate
    if (file_exists($filepath)) {
        $filename = sprintf(
            'return_%d_photo%d_%s_%d.%s',
            $returnRequestId,
            $photoCounter,
            str_replace('.', '', $uniqueId),
            time(),
            $extension
        );
        $filepath = $uploadDir . $filename;
    }
    
    // Move file
    if (!move_uploaded_file($tmpName, $filepath)) {
        $uploadFailures[] = "$originalName: Failed to save";
        continue;
    }
    
    // Verify file was saved correctly
    clearstatcache(true, $filepath);
    if (!file_exists($filepath) || filesize($filepath) === 0) {
        $uploadFailures[] = "$originalName: File empty after save";
        @unlink($filepath);
        continue;
    }
    
    // Verify it's still a valid image
    if (@getimagesize($filepath) === false) {
        $uploadFailures[] = "$originalName: Corrupted after upload";
        @unlink($filepath);
        continue;
    }
    
    // Insert into database
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO return_photos (return_request_id, photo_path, uploaded_at) 
             VALUES (?, ?, NOW())"
        );
        
        if (!$stmt->execute([$returnRequestId, $filepath])) {
            $uploadFailures[] = "$originalName: Database error";
            @unlink($filepath);
            continue;
        }
        
        $uploadedPhotos[] = $filepath;
        $photoCounter++;
        
    } catch (PDOException $e) {
        $uploadFailures[] = "$originalName: " . $e->getMessage();
        @unlink($filepath);
        continue;
    }
}

// Verify exactly 4 photos uploaded
if (count($uploadedPhotos) !== 4) {
    $pdo->rollback();
    
    // Delete uploaded files
    foreach ($uploadedPhotos as $photo) {
        @unlink($photo);
    }
    @rmdir($uploadDir);
    
    $errorMsg = count($uploadedPhotos) . " of 4 photos saved.";
    if (!empty($uploadFailures)) {
        $errorMsg .= " Issues: " . implode("; ", array_slice($uploadFailures, 0, 2));
    }
    throw new Exception($errorMsg);
}

    // Get seller email and info
// Get seller email and info
        $stmt = $pdo->prepare("
            SELECT u.email, u.first_name, u.last_name, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN users u ON p.seller_id = u.id
            WHERE oi.order_id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $sellerInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get customer info
    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $customerInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    // Send email notifications
    if ($sellerInfo && $customerInfo) {
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
            $mail->addAddress($sellerInfo['email'], $sellerInfo['first_name'] . ' ' . $sellerInfo['last_name']);
            $mail->addReplyTo($customerInfo['email'], $customerInfo['first_name'] . ' ' . $customerInfo['last_name']);
            
            $mail->isHTML(true);
            $mail->Subject = '🔄 New Return Request - Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
            
            $emailReason = nl2br(htmlspecialchars($fullReason));
            
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <div style='text-align: center; border-bottom: 2px solid #ff9800; padding-bottom: 20px; margin-bottom: 20px;'>
                    <h1 style='color: #ff9800; margin: 0;'>🔄 New Return Request</h1>
                </div>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h2 style='color: #333; margin-top: 0;'>Order Details</h2>
                    <p style='margin: 10px 0;'><strong>Order Number:</strong> #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . "</p>
                    <p style='margin: 10px 0;'><strong>Product:</strong> " . htmlspecialchars($sellerInfo['product_name']) . "</p>
                </div>
                
                <div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #1565c0; margin-top: 0;'>Customer Information</h3>
                    <p style='margin: 10px 0;'><strong>Name:</strong> {$customerInfo['first_name']} {$customerInfo['last_name']}</p>
                    <p style='margin: 10px 0;'><strong>Email:</strong> {$customerInfo['email']}</p>
                    <p style='margin: 10px 0;'><strong>Request Date:</strong> " . date('F j, Y g:i A') . "</p>
                </div>
                
                <div style='background: #fff3cd; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0;'>
                    <h3 style='color: #856404; margin-top: 0;'>Return Reason:</h3>
                    <div style='color: #856404;'>{$emailReason}</div>
                </div>
                
                <div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #0c5460; font-size: 14px;'>
                        <strong>✓ Photos Received:</strong> 4 photos successfully uploaded with this request.
                    </p>
                </div>
                
               
                
                <div style='text-align: center; border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px; color: #999; font-size: 14px;'>
                    <p>This is an automated notification from your E-Commerce Store</p>
                </div>
            </div>";
            
            $mail->send();
            
            // Customer confirmation email
            $mail->clearAddresses();
            $mail->addAddress($customerInfo['email'], $customerInfo['first_name'] . ' ' . $customerInfo['last_name']);
            $mail->Subject = '✅ Return Request Submitted - Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
            
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <div style='text-align: center; border-bottom: 2px solid #28a745; padding-bottom: 20px; margin-bottom: 20px;'>
                    <h1 style='color: #28a745; margin: 0;'>✅ Return Request Received</h1>
                </div>
                
                <p style='color: #666; font-size: 16px;'>Dear {$customerInfo['first_name']},</p>
                <p style='color: #666; font-size: 16px;'>Your return request has been successfully submitted with 4 photos and is now under review.</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 10px 0;'><strong>Order Number:</strong> #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . "</p>
                    <p style='margin: 10px 0;'><strong>Status:</strong> <span style='color: #ffc107; font-weight: bold;'>PENDING REVIEW</span></p>
                    <p style='margin: 10px 0;'><strong>Submitted:</strong> " . date('F j, Y g:i A') . "</p>
                </div>
                
                <div style='background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;'>
                    <p style='color: #0c5460; margin: 0;'>The seller will review your request within 3-5 business days. You'll receive an email notification once a decision is made.</p>
                </div>
            </div>";
            
            $mail->send();
            
        } catch (Exception $e) {
            error_log("Return notification email failed: {$mail->ErrorInfo}");
        }
    }

$_SESSION['return_success_message'] = 'Your return request has been successfully submitted! Order #' . str_pad($orderId, 6, '0', STR_PAD_LEFT) . ' has been sent for review. We\'ll notify you via email within 3-5 business days.';
header('Location: user-dashboard.php?status=return_refund');
exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Clean up on error
    if (isset($returnRequestId) && isset($uploadDir) && is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($uploadDir);
    }
    
    error_log("Return request error: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error processing return request: ' . $e->getMessage();
    header('Location: user-dashboard.php');
    exit;
}
?>
