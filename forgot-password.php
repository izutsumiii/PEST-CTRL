<?php
require_once 'includes/header.php';
require_once 'config/database.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

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

function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendOTPEmail($email, $otp, $name) {
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
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = '🔐 Password Reset OTP - E-Commerce Store';
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px;'>
                <h1 style='color: #007bff; margin: 0;'>Password Reset OTP</h1>
            </div>
            <div style='padding: 30px 0;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Hello $name,</h2>
                <p style='color: #666; font-size: 16px; line-height: 1.5;'>
                    You requested to reset your password. Use the OTP code below to proceed:
                </p>
                <div style='text-align: center; margin: 30px 0; background: linear-gradient(135deg, #007bff, #0056b3); padding: 20px; border-radius: 10px;'>
                    <div style='color: white; font-size: 36px; font-weight: bold; letter-spacing: 8px; font-family: monospace;'>$otp</div>
                    <div style='color: rgba(255,255,255,0.8); font-size: 14px; margin-top: 10px;'>Your OTP Code</div>
                </div>
                <div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404; font-size: 14px;'>
                        <strong>Important:</strong> This OTP will expire in 24 hours. Do not share this code with anyone.
                    </p>
                </div>
                <p style='color: #666; font-size: 14px; line-height: 1.5;'>
                    If you didn't request a password reset, please ignore this email. Your password won't be changed.
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
        error_log("OTP email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// DEBUG: Show debug info - set to false in production
$show_debug = true;

// Additional debug: Check if reset-password.php exists
if ($show_debug && !file_exists('reset-password.php')) {
    $error = "ERROR: reset-password.php file not found! Please create this file.";
}

// Handle "Change Email" action
if (isset($_POST['change_email'])) {
    // Clean up session variables to restart the process
    unset($_SESSION['reset_email']);
    unset($_SESSION['temp_reset_token']);
    
    // Clean up any existing OTP records for the old email if we have one
    if (isset($_POST['old_email'])) {
        $old_email = sanitizeInput($_POST['old_email']);
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$old_email]);
    }
    
    $info_message = "You can now enter a new email address.";
}

if (isset($_POST['send_otp'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $email = sanitizeInput($_POST['email']);
        
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Clean up old OTP tokens for this email
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? OR expires_at < NOW()");
            $stmt->execute([$email]);
            
            // Generate OTP and unique token
            $otp = generateOTP(6);
            $token = bin2hex(random_bytes(32));
            // Changed expiry time from +10 minutes to +24 hours
            $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Store OTP and token in database
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, otp, expires_at) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$email, $token, $otp, $expiry]);
            
            if ($result) {
                // Send OTP email
                $name = $user['first_name'] . ' ' . $user['last_name'];
                if (sendOTPEmail($email, $otp, $name)) {
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['temp_reset_token'] = $token;
                    $success = "A 6-digit OTP has been sent to your email address. Please check your inbox and enter the code below. The OTP will expire in 24 hours.";
                    $show_otp_form = true;
                    
                    // DEBUG: Log what was inserted
                    error_log("DEBUG: OTP sent successfully. Email: $email, OTP: $otp, Token: $token, Expiry: $expiry");
                } else {
                    $error = "Failed to send OTP email. Please try again later.";
                }
            } else {
                $error = "An error occurred. Please try again.";
            }
        } else {
            $success = "If an account with that email exists, an OTP has been sent.";
        }
    }
}

if (isset($_POST['verify_otp'])) {
    echo "<div style='background: #000; color: #0f0; padding: 10px; margin: 10px; font-family: monospace; white-space: pre-wrap; border-radius: 5px;'>";
    echo "DEBUG: OTP VERIFICATION STARTED\n";
    echo "POST Data: " . print_r($_POST, true) . "\n";
    echo "SESSION Data: " . print_r($_SESSION, true) . "\n";
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
        echo "ERROR: CSRF token validation failed\n";
    } else {
        $otp = sanitizeInput($_POST['otp']);
        $email = $_SESSION['reset_email'] ?? '';
        
        echo "Sanitized OTP: '$otp'\n";
        echo "Session Email: '$email'\n";
        
        if (empty($email)) {
            $error = "Session expired. Please start the reset process again.";
            echo "ERROR: Email is empty\n";
        } elseif (empty($otp) || strlen($otp) !== 6 || !ctype_digit($otp)) {
            $error = "Please enter a valid 6-digit OTP code.";
            $show_otp_form = true;
            echo "ERROR: Invalid OTP format. Length: " . strlen($otp) . ", Is digit: " . (ctype_digit($otp) ? 'yes' : 'no') . "\n";
        } else {
            // Check what's in the database
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? ORDER BY created_at DESC");
            $stmt->execute([$email]);
            $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "All records for email '$email':\n" . print_r($allRecords, true) . "\n";
            
            // Now verify OTP
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW()");
            $stmt->execute([$email, $otp]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "Query: SELECT * FROM password_resets WHERE email = '$email' AND otp = '$otp' AND expires_at > NOW()\n";
            echo "Current time: " . date('Y-m-d H:i:s') . "\n";
            echo "Reset record found: " . print_r($resetRecord, true) . "\n";
            
            if ($resetRecord) {
                // OTP is valid, set session variables for reset-password.php
                $_SESSION['verified_reset_email'] = $email;
                $_SESSION['reset_token'] = $resetRecord['token'];
                
                // Clean up temporary session variables
                unset($_SESSION['reset_email']);
                unset($_SESSION['temp_reset_token']);
                
                echo "SUCCESS: OTP verified successfully!\n";
                echo "Setting session variables:\n";
                echo "verified_reset_email = '$email'\n";
                echo "reset_token = '{$resetRecord['token']}'\n";
                echo "About to redirect to reset-password.php\n";
                echo "</div>";
                
                // Multiple redirect attempts
                echo "<script>
                    console.log('JavaScript: About to redirect to reset-password.php');
                    setTimeout(function() {
                        window.location.href = 'reset-password.php';
                    }, 1000);
                </script>";
                
                header("Location: reset-password.php");
                exit();
            } else {
                // Check if OTP exists but expired
                $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ?");
                $stmt->execute([$email, $otp]);
                $expiredRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "Checking for expired record: " . print_r($expiredRecord, true) . "\n";
                
                if ($expiredRecord) {
                    $error = "OTP has expired. Please request a new OTP.";
                    echo "ERROR: OTP expired\n";
                } else {
                    $error = "Invalid OTP code. Please check and try again.";
                    echo "ERROR: OTP not found in database\n";
                }
                $show_otp_form = true;
            }
        }
    }
    echo "</div>";
}

// Check if we should show OTP form
if (isset($_SESSION['reset_email']) && !isset($show_otp_form)) {
    $show_otp_form = true;
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
        .forgot-password-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        
        .header .subtitle {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input[type="email"], 
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            background: rgba(255,255,255,0.9);
            box-sizing: border-box;
        }
        
        .otp-input {
            text-align: center;
            font-size: 24px !important;
            letter-spacing: 8px;
            font-family: monospace;
            font-weight: bold;
        }
        
        .form-group small {
            font-size: 13px;
            opacity: 0.8;
            display: block;
            margin-top: 5px;
        }
        
        .otp-status {
            font-size: 12px;
            margin-top: 8px;
            text-align: center;
            min-height: 16px;
        }
        
        .email-display {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 5px;
            border: 1px solid rgba(255,255,255,0.2);
            font-family: monospace;
            font-size: 14px;
            text-align: center;
            margin-bottom: 15px;
        }
        
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button[type="submit"]:hover:not(:disabled) {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
        }
        
        button[type="submit"]:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .secondary-btn {
            background: transparent !important;
            border: 1px solid rgba(255,255,255,0.5) !important;
            margin-top: 10px;
            font-size: 14px !important;
            padding: 8px 12px !important;
        }
        
        .change-email-btn {
            background: rgba(255, 193, 7, 0.2) !important;
            border: 1px solid rgba(255, 193, 7, 0.6) !important;
            color: #fff3cd !important;
        }
        
        .change-email-btn:hover {
            background: rgba(255, 193, 7, 0.3) !important;
            border-color: rgba(255, 193, 7, 0.8) !important;
        }
        
        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }
        
        .links {
            text-align: center;
            margin-top: 20px;
        }
        
        .links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        
        .links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .success-message, .error-message, .info-message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
        }
        
        .info-message {
            background: rgba(23, 162, 184, 0.2);
            border: 1px solid rgba(23, 162, 184, 0.5);
        }
        
        .step-indicator {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            opacity: 0.8;
        }
        
        .debug-info {
            background: rgba(0,0,0,0.8);
            color: #0f0;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="forgot-password-container">
        <div class="header">
            <h1><i class="fas fa-key"></i> Forgot Password</h1>
            <div class="subtitle">
                <?php if (isset($show_otp_form) && $show_otp_form): ?>
                    Enter the OTP sent to your email
                <?php else: ?>
                    Reset your account password
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($show_otp_form) && $show_otp_form): ?>
            <div class="step-indicator">Step 2 of 3: Verify OTP</div>
        <?php else: ?>
            <div class="step-indicator">Step 1 of 3: Enter Email</div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-message">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($info_message)): ?>
            <div class="info-message">
                <?php echo $info_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($show_otp_form) && $show_otp_form): ?>
            <!-- Show current email address -->
            <div class="email-display">
                <i class="fas fa-envelope"></i> OTP sent to: <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
            </div>
            
            <!-- OTP Verification Form -->
            <form method="POST" action="" id="otp-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="otp"><i class="fas fa-shield-alt"></i> Enter OTP Code:</label>
                    <input type="text" id="otp" name="otp" class="otp-input" maxlength="6" pattern="[0-9]{6}" placeholder="000000" autocomplete="off" required>
                    <small>Enter the 6-digit code sent to your email address (expires in 24 hours)</small>
                    <div id="otp-status" class="otp-status"></div>
                </div>
                
                <button type="submit" name="verify_otp" id="verify-btn">
                    <i class="fas fa-check"></i> Verify OTP
                </button>
            </form>
            
            <!-- Button group for Resend OTP and Change Email -->
            <div class="button-group">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="email" value="<?php echo $_SESSION['reset_email'] ?? ''; ?>">
                    <button type="submit" name="send_otp" class="secondary-btn">
                        <i class="fas fa-redo"></i> Resend OTP
                    </button>
                </form>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="old_email" value="<?php echo $_SESSION['reset_email'] ?? ''; ?>">
                    <button type="submit" name="change_email" class="secondary-btn change-email-btn" onclick="return confirm('This will cancel the current OTP process. Are you sure you want to change the email address?');">
                        <i class="fas fa-edit"></i> Change Email
                    </button>
                </form>
            </div>
            
            <!-- Debug info -->
            <?php if ($show_debug): ?>
                <div class="debug-info">
                    Current Session:
                    <?php print_r($_SESSION); ?>
                    
                    Current Time: <?php echo date('Y-m-d H:i:s'); ?>
                    
                    Recent password_resets records:
                    <?php
                    if (isset($_SESSION['reset_email'])) {
                        try {
                            $debug_stmt = $pdo->prepare("SELECT email, otp, token, expires_at, created_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 5");
                            $debug_stmt->execute([$_SESSION['reset_email']]);
                            $records = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
                            print_r($records);
                        } catch (Exception $e) {
                            echo "Error fetching debug data: " . $e->getMessage();
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Email Form -->
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address:</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <small>Enter your email address and we'll send you a 6-digit OTP code (valid for 24 hours).</small>
                </div>
                
                <button type="submit" name="send_otp">
                    <i class="fas fa-paper-plane"></i> Send OTP
                </button>
            </form>
        <?php endif; ?>

        <div class="links">
            <p>Remember your password? <a href="login.php">Login here</a></p>
            <p>Need an account? <a href="register.php">Register here</a></p>
        </div>
    </div>

    <script>
        // Enhanced OTP input handling
        const otpInput = document.getElementById('otp');
        const statusDiv = document.getElementById('otp-status');
        const verifyBtn = document.getElementById('verify-btn');
        
        if (otpInput) {
            // Focus the input
            otpInput.focus();
            
            // Function to update status
            function updateStatus(message, color = '#48dbfb') {
                if (statusDiv) {
                    statusDiv.innerHTML = message;
                    statusDiv.style.color = color;
                }
            }
            
            // Handle input
            otpInput.addEventListener('input', function(e) {
                // Only allow numbers
                let value = this.value.replace(/[^0-9]/g, '');
                this.value = value;
                
                console.log('OTP Input changed:', value);
                
                // Update status and button
                if (value.length === 0) {
                    updateStatus('Enter your 6-digit OTP code', '#ccc');
                    verifyBtn.disabled = true;
                } else if (value.length < 6) {
                    updateStatus(`${value.length}/6 digits entered`, '#feca57');
                    verifyBtn.disabled = true;
                } else if (value.length === 6) {
                    updateStatus('✓ Ready to verify!', '#48dbfb');
                    verifyBtn.disabled = false;
                }
            });

            // Handle paste events
            otpInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const numbers = paste.replace(/[^0-9]/g, '').substring(0, 6);
                this.value = numbers;
                console.log('OTP Pasted:', numbers);
                
                // Trigger input event to update status
                this.dispatchEvent(new Event('input'));
            });

            // Handle form submission
            document.getElementById('otp-form').addEventListener('submit', function(e) {
                const otpValue = otpInput.value;
                console.log('Form submitted with OTP:', otpValue);
                
                if (otpValue.length !== 6 || !otpValue.match(/^\d{6}$/)) {
                    e.preventDefault();
                    updateStatus('Please enter a valid 6-digit OTP', '#ff6b6b');
                    return false;
                }
                
                // Show loading state
                updateStatus('Verifying your OTP...', '#48dbfb');
                verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
                verifyBtn.disabled = true;
                
                // Allow form to submit normally
                return true;
            });
            
            // Initial setup
            updateStatus('Enter your 6-digit OTP code', '#ccc');
            verifyBtn.disabled = true;
        }

        // Log any JavaScript errors
        window.addEventListener('error', function(e) {
            console.error('JavaScript Error:', e.error);
        });
        
        // Log form data for debugging
        console.log('Page loaded. Session email:', '<?php echo $_SESSION['reset_email'] ?? 'none'; ?>');
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>