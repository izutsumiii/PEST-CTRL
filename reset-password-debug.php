<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Add sanitizeInput function if not exists
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}

$token = isset($_GET['token']) ? sanitizeInput($_GET['token']) : '';
$error = '';
$success = '';
$validToken = false;
$userEmail = '';
$debug_info = [];

// Validate token if provided
if ($token) {
    $debug_info[] = "Token received: " . substr($token, 0, 20) . "...";
    
    if (strlen($token) !== 64) {
        $error = "Invalid reset link. Please request a new password reset.";
        $debug_info[] = "Token length invalid: " . strlen($token);
    } else {
        // Hash the token to compare with database
        $tokenHash = hash('sha256', $token);
        $debug_info[] = "Token hash: " . substr($tokenHash, 0, 20) . "...";
        
        // Check if token exists and is valid (simplified approach)
        $stmt = $pdo->prepare("
            SELECT pr.* 
            FROM password_resets pr 
            WHERE pr.token_hash = ? 
            AND pr.expires_at > NOW() 
            AND pr.used_at IS NULL
        ");
        $stmt->execute([$tokenHash]);
        $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resetRecord) {
            $debug_info[] = "Token found in database";
            $debug_info[] = "User ID: " . $resetRecord['user_id'];
            $debug_info[] = "Expires at: " . $resetRecord['expires_at'];
            
            // Get user info separately
            $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$resetRecord['user_id']]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userInfo) {
                $debug_info[] = "User info retrieved: " . $userInfo['email'];
                $resetRecord['email'] = $userInfo['email'];
                $resetRecord['first_name'] = $userInfo['first_name'];
                $resetRecord['last_name'] = $userInfo['last_name'];
            } else {
                $debug_info[] = "User info not found";
            }
        } else {
            $debug_info[] = "Token not found or invalid";
        }
        
        if ($resetRecord) {
            $validToken = true;
            $userEmail = $resetRecord['email'];
            $_SESSION['reset_user_id'] = $resetRecord['user_id'];
            $_SESSION['reset_token_hash'] = $tokenHash;
            $debug_info[] = "Token validation successful";
        } else {
            $error = "Invalid or expired reset link. Please request a new password reset.";
            $debug_info[] = "Token validation failed";
        }
    }
} else {
    $error = "No reset token provided. Please use the link from your email.";
    $debug_info[] = "No token provided";
}

// Handle password reset
if (isset($_POST['reset_password']) && $validToken) {
    $debug_info[] = "Password reset form submitted";
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
        $debug_info[] = "CSRF token validation failed";
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $debug_info[] = "Password length: " . strlen($password);
        $debug_info[] = "Confirm password length: " . strlen($confirm_password);
        
        // Validate passwords
        if (empty($password)) {
            $error = "Password is required.";
            $debug_info[] = "Password is empty";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
            $debug_info[] = "Password too short";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $error = "Password must contain at least one lowercase letter, one uppercase letter, and one number.";
            $debug_info[] = "Password doesn't meet complexity requirements";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
            $debug_info[] = "Passwords don't match";
        } else {
            $debug_info[] = "Password validation passed";
            
            // Verify token is still valid (simplified approach)
            $stmt = $pdo->prepare("
                SELECT * FROM password_resets 
                WHERE user_id = ? AND token_hash = ? AND expires_at > NOW() AND used_at IS NULL
            ");
            $stmt->execute([$_SESSION['reset_user_id'], $_SESSION['reset_token_hash']]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resetRecord) {
                $debug_info[] = "Token re-validation successful";
                
                // Hash the new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $debug_info[] = "Password hashed successfully";
                
                // Start transaction
                $pdo->beginTransaction();
                $debug_info[] = "Database transaction started";
                
                try {
                    // Update user's password
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateResult = $stmt->execute([$hashedPassword, $_SESSION['reset_user_id']]);
                    $debug_info[] = "Password update result: " . ($updateResult ? "SUCCESS" : "FAILED");
                    $debug_info[] = "Rows affected: " . $stmt->rowCount();
                    
                    // Mark token as used
                    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
                    $tokenUpdateResult = $stmt->execute([$resetRecord['id']]);
                    $debug_info[] = "Token mark as used result: " . ($tokenUpdateResult ? "SUCCESS" : "FAILED");
                    
                    // Delete all other reset tokens for this user
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ? AND id != ?");
                    $deleteResult = $stmt->execute([$_SESSION['reset_user_id'], $resetRecord['id']]);
                    $debug_info[] = "Delete other tokens result: " . ($deleteResult ? "SUCCESS" : "FAILED");
                    $debug_info[] = "Other tokens deleted: " . $stmt->rowCount();
                    
                    // Commit transaction
                    $pdo->commit();
                    $debug_info[] = "Transaction committed successfully";
                    
                    $success = "Your password has been reset successfully! You can now log in with your new password.";
                    $password_reset_success = true;
                    
                    // Log successful password reset
                    error_log("Password reset successful for user ID: " . $resetRecord['user_id']);
                    $debug_info[] = "Password reset completed successfully";
                    
                    // Clear session variables
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_token_hash']);
                    
                } catch (Exception $e) {
                    // Rollback transaction
                    $pdo->rollback();
                    $error = "Failed to update password. Please try again.";
                    error_log("Password reset failed: " . $e->getMessage());
                    $debug_info[] = "Transaction failed: " . $e->getMessage();
                }
            } else {
                $error = "Reset token has expired or been used. Please request a new password reset.";
                $debug_info[] = "Token re-validation failed";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password (Debug) - E-Commerce Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reset-password-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .header .icon i {
            font-size: 32px;
            color: white;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .email-display {
            background: linear-gradient(135deg, #e6fffa, #b2f5ea);
            border: 1px solid #81e6d9;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
            font-size: 14px;
        }

        .form-group input[type="password"] {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            background: white;
            transition: all 0.3s ease;
        }

        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .success-message, .error-message {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 500;
        }

        .success-message {
            background: linear-gradient(135deg, #c6f6d5, #9ae6b4);
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .error-message {
            background: linear-gradient(135deg, #fed7d7, #feb2b2);
            color: #742a2a;
            border: 1px solid #feb2b2;
        }

        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin-top: 24px;
            text-align: left;
        }

        .debug-info h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .debug-info ul {
            list-style: none;
            padding: 0;
        }

        .debug-info li {
            background: #e9ecef;
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-bug"></i>
            </div>
            <h1>Reset Password (Debug)</h1>
            <div class="subtitle">Debug version to troubleshoot password reset issues</div>
        </div>

        <?php if ($validToken && $userEmail): ?>
            <div class="email-display">
                <i class="fas fa-envelope"></i>
                Resetting password for: <strong><?php echo htmlspecialchars($userEmail); ?></strong>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($validToken && !isset($password_reset_success)): ?>
            <form method="POST" action="" id="reset-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> New Password:
                    </label>
                    <input type="password" id="password" name="password" required>
                    <small>Password must be at least 8 characters with uppercase, lowercase, and number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password:
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <small>Enter the same password again</small>
                </div>
                
                <button type="submit" name="reset_password" id="reset-btn" class="btn-primary">
                    <i class="fas fa-save"></i> Update Password (Debug)
                </button>
            </form>
        <?php endif; ?>

        <?php if (!empty($debug_info)): ?>
            <div class="debug-info">
                <h3><i class="fas fa-bug"></i> Debug Information:</h3>
                <ul>
                    <?php foreach ($debug_info as $info): ?>
                        <li><?php echo htmlspecialchars($info); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="links" style="text-align: center; margin-top: 32px;">
            <p>Remember your password? <a href="login_customer.php">Login here</a></p>
            <p>Use production version: <a href="reset-password-secure.php">reset-password-secure.php</a></p>
        </div>
    </div>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
