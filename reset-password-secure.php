<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

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
// Validate token if provided
if ($token) {
    if (strlen($token) !== 64) {
        $error = "Invalid reset link. Please request a new password reset.";
    } else {
        // DON'T hash the token if storing plain token in database
        // Remove this line: $tokenHash = hash('sha256', $token);
        
        // Check if token exists and is valid using token_hash
        $tokenHash = hash('sha256', $token);
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
            // Get user's email by joining users via user_id
            $validToken = true;
            $_SESSION['reset_user_id'] = $resetRecord['user_id'];
            $_SESSION['reset_token_hash'] = $tokenHash;
            
            $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$resetRecord['user_id']]);
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
            $userEmail = $userRow ? $userRow['email'] : '';
        } else {
            $error = "Invalid or expired reset link. Please request a new password reset.";
        }
    }
} else {
    $error = "No reset token provided. Please use the link from your email.";
}

// Handle password reset
if (isset($_POST['reset_password']) && $validToken) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate passwords
        if (empty($password)) {
            $error = "Password is required.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password)) {
            $error = "Password must contain at least one lowercase letter, one uppercase letter, and one number.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Verify token is still valid using user_id and token_hash
            $stmt = $pdo->prepare("
                SELECT * FROM password_resets 
                WHERE user_id = ? AND token_hash = ? AND expires_at > NOW() AND used_at IS NULL
            ");
            $stmt->execute([$_SESSION['reset_user_id'], $_SESSION['reset_token_hash']]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resetRecord) {
                // Hash the new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // Update user's password via user_id
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateResult = $stmt->execute([$hashedPassword, $_SESSION['reset_user_id']]);
                    
                    // Mark token as used
                    $stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
                    $deleteResult = $stmt->execute([$resetRecord['id']]);
                    
                    // Delete all other reset tokens for this user
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
                    $cleanupResult = $stmt->execute([$_SESSION['reset_user_id']]);
                    
                    // Commit transaction
                    $pdo->commit();
                    
                    $success = "Your password has been reset successfully! You can now log in with your new password.";
                    $password_reset_success = true;
                    
                    // Log successful password reset
                    error_log("Password reset successful for user_id: " . $_SESSION['reset_user_id']);
                    
                    // Clear session variables - FIXED variable names
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_token_hash']);
                    
                    // Redirect to customer login
                    header("Location: login_customer.php");
                    exit();
                
                } catch (Exception $e) {
                    // Rollback transaction
                    $pdo->rollback();
                    $error = "Failed to update password. Please try again.";
                    error_log("Password reset failed: " . $e->getMessage());
                }
            } else {
                $error = "Reset token has expired or been used. Please request a new password reset.";
            }
        }
    }
}

require_once 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - E-Commerce Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
            min-height: 100vh;
            padding: 0;
        }

        .reset-password-container {
            background: var(--primary-dark);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 20px 16px;
            box-shadow: 0 20px 40px var(--shadow-light);
            border: 1px solid var(--border-secondary);
            max-width: 360px;
            width: 100%;
            position: relative;
            overflow: hidden;
            margin: 140px auto 24px;
            color: var(--primary-light);
        }


        .email-display {
            background: linear-gradient(135deg, rgba(19, 3, 37, 0.05), rgba(19, 3, 37, 0.08));
            border: 1px solid var(--border-secondary);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 24px;
            text-align: center;
            color: var(--primary-light);
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--primary-light);
            font-size: 13px;
        }

        .form-group input[type="password"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid var(--border-secondary);
            border-radius: 12px;
            font-size: 13px;
            background: var(--primary-light);
            color: var(--primary-dark);
            transition: all 0.3s ease;
        }

        .form-group input[type="password"]:focus,
        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--accent-yellow);
            box-shadow: 0 0 0 3px rgba(255, 215, 54, 0.15);
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
            background: var(--accent-yellow);
            color: var(--primary-dark);
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 215, 54, 0.3);
        }

        .success-message, .error-message {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            font-weight: 500;
        }

        .form-group small {
            color: var(--primary-light);
            opacity: 0.85;
            font-size: 12px;
        }

        .links { font-size: 0.8rem; }

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

        .input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #718096;
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--accent-yellow);
        }
    </style>
</head>
<body>
    <div class="reset-password-container">

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
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" required>
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </span>
                    </div>
                    <small>Password must be at least 8 characters with uppercase, lowercase, and number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password:
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required>
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password-eye"></i>
                        </span>
                    </div>
                    <small>Enter the same password again</small>
                </div>
                
                <button type="submit" name="reset_password" id="reset-btn" class="btn-primary">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>
        <?php endif; ?>

        <div class="links" style="text-align: center; margin-top: 32px;">
            <p>Remember your password? <a href="login_customer.php">Login here</a></p>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(inputId + '-eye');
            
            if (input.type === 'password') {
                input.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
