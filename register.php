<?php
// Move all logic to the top BEFORE including header.php
// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/PHPMailer/src/Exception.php';
require 'PHPMailer/PHPMailer/src/PHPMailer.php';
require 'PHPMailer/PHPMailer/src/SMTP.php';

// Include database config
require_once 'config/database.php';

// Include functions file (this likely contains the isLoggedIn function)
require_once 'includes/functions.php'; // Add this line

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in BEFORE any output
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Rest of your register.php code goes here...

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to send OTP email using PHPMailer
function sendOTPEmail($email, $name, $otp) {
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
        $mail->Subject = '🔐 Email Verification - Your OTP Code';
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px;'>
                <h1 style='color: #007bff; margin: 0;'>Email Verification</h1>
            </div>
            <div style='padding: 30px 0; text-align: center;'>
                <h2 style='color: #333; margin-bottom: 20px;'>Hello $name,</h2>
                <p style='color: #666; font-size: 16px;'>Your OTP verification code is:</p>
                <div style='background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px; border-radius: 10px; margin: 20px 0; box-shadow: 0 4px 8px rgba(0,0,0,0.1);'>
                    <h1 style='font-size: 36px; letter-spacing: 8px; margin: 0; font-family: \"Courier New\", monospace;'>$otp</h1>
                </div>
                <p style='color: #666; font-size: 16px; line-height: 1.5;'>
                    Please enter this 6-digit code to verify your email address.<br>
                    <strong>This code will expire in 10 minutes.</strong>
                </p>
                <div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404; font-size: 14px;'>
                        <strong>Security Note:</strong> Please do not share this code with anyone. Our team will never ask for your OTP.
                    </p>
                </div>
            </div>
            <div style='text-align: center; border-top: 1px solid #eee; padding-top: 20px; color: #999; font-size: 14px;'>
                <p>If you didn't request this verification, please ignore this email.</p>
                <p style='margin: 0;'>© 2024 Your E-Commerce Store</p>
            </div>
        </div>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Password validation function
function validatePassword($password) {
    $errors = [];
    
    // Minimum length
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // Check for uppercase
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }
    
    // Check for lowercase
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }
    
    // Check for number
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }
    
    return $errors;
}

// Handle OTP verification
if (isset($_POST['verify_otp'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $entered_otp = sanitizeInput($_POST['otp']);
        
        if (isset($_SESSION['registration_otp']) && $_SESSION['registration_otp'] === $entered_otp) {
            // Check OTP expiry (10 minutes)
            if (isset($_SESSION['otp_timestamp']) && (time() - $_SESSION['otp_timestamp']) > 600) {
                $error = "OTP has expired. Please request a new one.";
            } else {
                // OTP is correct, proceed with registration
                $userData = $_SESSION['temp_user_data'];
                
                // Hash password
                $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
                
                // Insert new user (email is already verified via OTP)
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, user_type, email_verified)
                                       VALUES (?, ?, ?, ?, ?, ?, 1)");
                $result = $stmt->execute([
                    $userData['username'], 
                    $userData['email'], 
                    $hashedPassword, 
                    $userData['first_name'], 
                    $userData['last_name'], 
                    $userData['user_type']
                ]);
                
                if ($result) {
                    // Clear session data
                    unset($_SESSION['registration_otp']);
                    unset($_SESSION['temp_user_data']);
                    unset($_SESSION['otp_email']);
                    unset($_SESSION['otp_timestamp']);
                    
                    $_SESSION['success'] = "Registration successful! Your account has been created and verified.";
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Error creating account. Please try again.";
                }
            }
        } else {
            $error = "Invalid OTP. Please try again.";
        }
    }
}

// Handle resend OTP
if (isset($_POST['resend_otp'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        if (isset($_SESSION['temp_user_data']) && isset($_SESSION['otp_email'])) {
            // Generate new OTP
            $_SESSION['registration_otp'] = sprintf('%06d', mt_rand(100000, 999999));
            $_SESSION['otp_timestamp'] = time();
            
            $userData = $_SESSION['temp_user_data'];
            $email = $_SESSION['otp_email'];
            $name = $userData['first_name'] . ' ' . $userData['last_name'];
            
            if (sendOTPEmail($email, $name, $_SESSION['registration_otp'])) {
                $success = "New OTP has been sent to your email address.";
            } else {
                $error = "Failed to send OTP. Please try again.";
            }
        } else {
            $error = "Session expired. Please start registration again.";
            header("Location: register.php");
            exit();
        }
    }
}

// Handle initial registration form
if (isset($_POST['register'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $firstName = sanitizeInput($_POST['first_name']);
        $lastName = sanitizeInput($_POST['last_name']);
        $userType = isset($_POST['user_type']) ? sanitizeInput($_POST['user_type']) : 'customer';
        
        // Validate inputs
        $errors = [];
        
        // Check username
        if (empty($username)) {
            $errors[] = "Username is required.";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = "Username must be 3-20 characters and can only contain letters, numbers, and underscores.";
        }
        
        // Check email
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        // Check password strength
        if (empty($password)) {
            $errors[] = "Password is required.";
        } else {
            $passwordErrors = validatePassword($password);
            if (!empty($passwordErrors)) {
                $errors = array_merge($errors, $passwordErrors);
            }
        }
        
        // Check if username or email already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                if ($existingUser['username'] === $username) {
                    $errors[] = "Username already exists.";
                }
                if ($existingUser['email'] === $email) {
                    $errors[] = "Email already exists.";
                }
            }
        }
        
        if (empty($errors)) {
            // Store user data temporarily in session
            $_SESSION['temp_user_data'] = [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'user_type' => $userType
            ];
            
            // Generate OTP and store in session
            $_SESSION['registration_otp'] = sprintf('%06d', mt_rand(100000, 999999));
            $_SESSION['otp_email'] = $email;
            $_SESSION['otp_timestamp'] = time();
            
            // Send OTP email
            $name = $firstName . ' ' . $lastName;
            if (sendOTPEmail($email, $name, $_SESSION['registration_otp'])) {
                $showOtpForm = true;
                $success = "Registration form submitted! Please check your email for the OTP code.";
            } else {
                $error = "Failed to send verification email. Please try again.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}

// Check if we should show OTP form
$showOtpForm = isset($_SESSION['temp_user_data']) && isset($_SESSION['registration_otp']);

// NOW include the header - AFTER all processing is complete
require_once 'includes/header.php';
?>

<div class="register-container">
    <div class="register-header">
        <h1><i class="fas fa-user-plus"></i> Create Account</h1>
        <div class="subtitle">Join PEST-CTRL today</div>
    </div>

    <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="success-message">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

<?php if (!$showOtpForm): ?>
<!-- Registration Form -->
<form method="POST" action="" id="registerForm">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <div class="form-group">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
        <small>3-20 characters, letters, numbers, and underscores only</small>
    </div>

    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <div class="form-group">
        <label for="password">Password:</label>
        <div class="password-input-container">
            <input type="password" id="password" name="password" required>
            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                <i class="fa-solid fa-eye"></i>
            </button>
        </div>
        
        <div class="password-strength-meter">
            <div class="password-strength-bar"></div>
        </div>
        <small id="password-strength-text">Password strength: None</small>
        <small class="password-requirements">Must be at least 8 characters with uppercase, lowercase, and numbers</small>
    </div>
    
    <div class="form-group">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
    </div>
    
    <div class="form-group">
        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
    </div>
    
    <div class="form-group">
        <label for="user_type">Account Type:</label>
        <select id="user_type" name="user_type">
            <option value="customer" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'customer') ? 'selected' : ''; ?>>Customer</option>
            <option value="seller" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'seller') ? 'selected' : ''; ?>>Seller</option>
        </select>
    </div>
    
    <button type="submit" name="register" id="registerButton">Register</button>
</form>

<?php else: ?>
<!-- OTP Verification Form -->
<div id="otpverify" class="otp-container">
    <h2>Email Verification</h2>
    <p>We've sent a 6-digit OTP code to <strong><?php echo htmlspecialchars($_SESSION['otp_email']); ?></strong></p>
    <p>Please enter the code below to verify your email address:</p>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-group">
            <label for="otp_inp">Enter OTP:</label>
            <input type="text" id="otp_inp" name="otp" maxlength="6" pattern="[0-9]{6}" required>
        </div>
        
        <button type="submit" name="verify_otp" id="otp_btn">Verify OTP</button>
        <div class="button-group">
            <button type="submit" name="resend_otp" id="resend_otp">Resend OTP</button>
            <button type="button" id="cancel_otp" onclick="cancelRegistration()">Cancel</button>
        </div>
    </form>
</div>
<?php endif; ?>

    <p class="login-link">Already have an account? <a href="login.php">Login here</a></p>
</div>

<style>
/* External CSS Library */
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

/* CSS Variables (from modern-style.css) */
:root {
    --primary-dark: #130325;
    --primary-light: #F9F9F9;
    --accent-yellow: #FFD736;
    --border-secondary: rgba(249, 249, 249, 0.3);
    --shadow-dark: rgba(0, 0, 0, 0.3);
    --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Register Page Styles */
body {
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.9) 100%);
    min-height: 100vh;
    margin: 0;
    font-family: var(--font-primary);
}

/* Override header background to match */
.site-header {
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.9) 100%) !important;
}

/* Fix header link hover effects to match main header */
.nav-links a:hover {
    color: #FFD736 !important;
    background: rgba(19, 3, 37, 0.8) !important;
}

.register-container {
    max-width: 380px;
    margin: 40px auto;
    padding: 20px;
    border: 1px solid var(--border-secondary);
    border-radius: 15px;
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
    color: var(--primary-light);
    box-shadow: 0 10px 40px var(--shadow-dark);
    position: relative;
    overflow: hidden;
}

.register-header {
    text-align: center;
    margin-bottom: 30px;
}

.register-header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: var(--accent-yellow);
}

.register-header .subtitle {
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

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid rgba(249, 249, 249, 0.3);
    border-radius: 10px;
    font-size: 13px;
    background: rgba(249, 249, 249, 0.1);
    color: #F9F9F9;
    box-sizing: border-box;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    background: rgba(249, 249, 249, 0.2);
    border-color: rgba(255, 215, 54, 0.5);
    box-shadow: 0 0 15px rgba(255, 215, 54, 0.3);
}

.form-group input::placeholder {
    color: rgba(249, 249, 249, 0.7);
}

.otp-container {
    max-width: 380px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid var(--border-secondary);
    border-radius: 15px;
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
    color: var(--primary-light);
    box-shadow: 0 10px 40px var(--shadow-dark);
}

.password-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.toggle-password {
    position: absolute;
    right: 10px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.password-strength-meter {
    width: 100%;
    height: 4px;
    background-color: #e0e0e0;
    border-radius: 2px;
    margin-top: 5px;
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.password-strength-bar.weak { background-color: #ff4444; }
.password-strength-bar.medium { background-color: #ffaa00; }
.password-strength-bar.strong { background-color: #00aa00; }

.success-message {
    background: rgba(40, 167, 69, 0.2);
    border: 1px solid rgba(40, 167, 69, 0.5);
    color: #28a745;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
}

.error-message {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid rgba(220, 53, 69, 0.5);
    color: #dc3545;
    padding: 12px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
}

#otp_inp {
    text-align: center;
    font-size: 18px;
    letter-spacing: 2px;
    width: 150px;
    margin: 0 auto 15px auto;
    display: block;
}

.button-group {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 10px;
}

#resend_otp {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    flex: 1;
    max-width: 120px;
}

#resend_otp:hover {
    background-color: #545b62;
}

#cancel_otp {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    flex: 1;
    max-width: 120px;
}

#cancel_otp:hover {
    background-color: #c82333;
}

/* Main form buttons */
button[type="submit"] {
    width: 100%;
    padding: 8px 16px;
    background-color: #FFD736;
    color: #130325;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
}

button[type="submit"]:hover:not(:disabled) {
    background-color: #e6c230;
}

.login-link {
    text-align: center;
    margin-top: 20px;
    font-size: 13px;
}

.login-link a {
    color: var(--accent-yellow);
    text-decoration: none;
}

.login-link a:hover {
    color: var(--primary-light);
    text-decoration: underline;
}
</style>

<script>
// Password strength calculator
function calculatePasswordStrength(password) {
    let strength = 0;
    
    // Length
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // Character variety
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    
    return strength;
}

// Toggle password visibility
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleButton = passwordField.nextElementSibling;
    const icon = toggleButton.querySelector("i");

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        passwordField.type = 'password';
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}

// Update password strength meter
function updatePasswordStrength() {
    const password = document.getElementById('password').value;
    const strength = calculatePasswordStrength(password);
    const strengthBar = document.querySelector('.password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    let strengthClass = 'weak';
    let strengthMessage = 'Weak';
    
    if (strength >= 4) {
        strengthClass = 'strong';
        strengthMessage = 'Strong';
    } else if (strength >= 2) {
        strengthClass = 'medium';
        strengthMessage = 'Medium';
    }
    
    strengthBar.className = 'password-strength-bar ' + strengthClass;
    strengthBar.style.width = (strength / 4 * 100) + '%';
    strengthText.textContent = 'Password strength: ' + strengthMessage;
}

// Cancel registration function
function cancelRegistration() {
    if (confirm('Are you sure you want to cancel registration? You will need to fill out the form again.')) {
        window.location.href = 'register.php';
    }
}

// Form validation for registration
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const strength = calculatePasswordStrength(password);
            
            if (strength < 2) {
                e.preventDefault();
                alert('Please choose a stronger password. Your password should include uppercase letters, lowercase letters, and numbers.');
                return false;
            }
        });
        
        // Add event listener for password strength
        const passwordField = document.getElementById('password');
        if (passwordField) {
            passwordField.addEventListener('input', updatePasswordStrength);
        }
    }
    
    // Focus on OTP input if available
    const otpInput = document.getElementById('otp_inp');
    if (otpInput) {
        otpInput.focus();
    }
});
</script>
