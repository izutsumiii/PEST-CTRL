<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';


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

/**
 * Generate a secure password reset token
 * @return string 64+ character secure token
 */
function generateSecureToken() {
    return bin2hex(random_bytes(32)); // 64 character token
}

/**
 * Send password reset email with secure link
 * @param string $email User's email
 * @param string $token Secure reset token
 * @param string $name User's name
 * @return bool Success status
 */
function sendPasswordResetEmail($email, $token, $name) {
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
        $mail->addAddress($email, $name);
        
        // Create secure reset link
        $resetLink = "http://localhost/PESTCTRL1.2.3mdz/reset-password-secure.php?token=" . $token;
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = '🔐 Password Reset Request - E-Commerce Store';
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px;'>
                <h1 style='color: #007bff; margin: 0;'>Password Reset Request</h1>
            </div>
            <div style='padding: 30px 0;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Hello $name,</h2>
                <p style='color: #666; font-size: 16px; line-height: 1.5;'>
                    You requested to reset your password. Click the button below to create a new password:
                </p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$resetLink' style='display: inline-block; background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;'>
                        🔐 Reset My Password
                    </a>
                </div>
                <div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404; font-size: 14px;'>
                        <strong>Important:</strong> This link will expire in 1 hour for security reasons. If you didn't request a password reset, please ignore this email.
                    </p>
                </div>
                <p style='color: #666; font-size: 14px; line-height: 1.5;'>
                    If the button doesn't work, you can copy and paste this link into your browser:<br>
                    <a href='$resetLink' style='color: #007bff; word-break: break-all;'>$resetLink</a>
                </p>
            </div>
            <div style='text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #999; font-size: 14px;'>
                <p>Best regards,<br>E-Commerce Store Team</p>
                <p style='margin: 0;'>© 2024 Your E-Commerce Store</p>
            </div>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password reset email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

$success = '';
$error = '';

// Handle password reset request
if (isset($_POST['request_reset'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $email = sanitizeInput($_POST['email']);
        
        // Check if email exists in database
        $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Clean up any existing reset tokens for this user (new schema uses user_id)
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW()");
            $stmt->execute([$user['id']]);
            
            // Generate secure token
            $token = generateSecureToken();
            // Hash the token for storage in token_hash (do NOT email the hash)
            $tokenHash = hash('sha256', $token);
            
            // Set expiration time (1 hour from now) using database time
            $stmt = $pdo->query("SELECT DATE_ADD(NOW(), INTERVAL 1 HOUR) as expiry");
            $expiryResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $expiry = $expiryResult['expiry'];
            
            // Store token hash with user_id in database (secure schema)
            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
            $result = $stmt->execute([$user['id'], $tokenHash, $expiry]);
            
            if ($result) {
                // Send password reset email
                $name = $user['first_name'] . ' ' . $user['last_name'];
                $emailSent = sendPasswordResetEmail($email, $token, $name);
                
                if ($emailSent) {
                    $success = "Password reset link has been sent to your email address!";
                } else {
                    $error = "Failed to send email. Please check your email configuration.";
                }
            } else {
                $error = "Database error occurred. Please try again.";
            }
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - E-Commerce Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
            min-height: 100vh;
            padding: 0;
            position: relative;
            overflow-x: hidden;
        }

        .forgot-password-container {
            background: var(--primary-dark);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-secondary);
            padding: 24px 20px;
            border-radius: 24px;
            box-shadow: 0 20px 40px var(--shadow-light);
            max-width: 420px;
            width: 100%;
            text-align: center;
            position: relative;
            overflow: hidden;
            margin: 140px auto 24px;
            color: var(--primary-light);
        }


        .form-group {
            margin-bottom: 24px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary-light);
            font-size: 14px;
        }

        .form-group input[type="email"] {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border-secondary);
            border-radius: 12px;
            font-size: 16px;
            background: var(--primary-light);
            color: var(--primary-dark);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-group input[type="email"]:focus {
            outline: none;
            border-color: var(--accent-yellow);
            box-shadow: 0 0 0 3px rgba(255, 215, 54, 0.15);
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: var(--accent-yellow);
            color: var(--primary-dark);
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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

        

        .links {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .links a {
            color: var(--accent-yellow);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #e6c230;
            text-decoration: underline;
        }
        
        .links { font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="forgot-password-container">

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>


        <form method="POST" action="" id="forgot-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email">
                    <i class="fas fa-envelope"></i> Email Address:
                </label>
                <input type="email" id="email" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       required autocomplete="email">
            </div>
            
            <button type="submit" name="request_reset" id="submit-btn" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>


        

        <div class="links">
            <p>Remember your password? <a href="login_customer.php">Login here</a></p>
            <p>Need an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
        // Auto-focus email input
        document.getElementById('email').focus();
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>
