<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Check if user has verified OTP
if (!isset($_SESSION['verified_reset_email']) || !isset($_SESSION['reset_token'])) {
    header("Location: forgot-password.php");
    exit();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['reset_password'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $email = $_SESSION['verified_reset_email'];
        $token = $_SESSION['reset_token'];
        
        // Validate passwords
        if (empty($password)) {
            $error = "Password is required.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Verify token is still valid
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
            $stmt->execute([$email, $token]);
            $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resetRecord) {
                // Hash the new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Update user's password
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE email = ?");
                $result = $stmt->execute([$hashedPassword, $email]);
                
                if ($result) {
                    // Delete the used reset token
                    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    // Clear session variables
                    unset($_SESSION['verified_reset_email']);
                    unset($_SESSION['reset_token']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['otp_verified']);
                    unset($_SESSION['otp_verified_time']);
                    
                    $success = "Your password has been reset successfully! You can now log in with your new password.";
                    $password_reset_success = true;
                } else {
                    $error = "Failed to update password. Please try again.";
                }
            } else {
                $error = "Reset token has expired. Please start the process again.";
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
    <title>Reset Password - E-Commerce Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            max-width: 450px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .reset-password-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
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

        .header .subtitle {
            font-size: 16px;
            color: #718096;
            font-weight: 400;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            gap: 12px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            position: relative;
        }

        .step.completed {
            background: #48bb78;
            color: white;
        }

        .step.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .step-line {
            width: 40px;
            height: 2px;
            background: #48bb78;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-group input[type="password"] {
            width: 100%;
            padding: 16px 50px 16px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            background: white;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            color: #667eea;
        }

        .form-group small {
            font-size: 13px;
            color: #718096;
            display: block;
            margin-top: 6px;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
        }

        .strength-weak { color: #e53e3e; }
        .strength-medium { color: #ed8936; }
        .strength-strong { color: #48bb78; }

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
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(72, 187, 120, 0.3);
            color: white;
            text-decoration: none;
        }

        .links {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #764ba2;
            text-decoration: underline;
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

        .success-actions {
            text-align: center;
            margin-top: 24px;
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .password-match {
            border-color: #48bb78 !important;
            box-shadow: 0 0 0 3px rgba(72, 187, 120, 0.1) !important;
        }

        .password-mismatch {
            border-color: #e53e3e !important;
            box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1) !important;
        }

        @media (max-width: 480px) {
            .reset-password-container {
                padding: 24px;
                margin: 10px;
            }
            
            .header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <div class="header">
            <div class="icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Reset Password</h1>
            <div class="subtitle">Create your new password</div>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step completed">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="step-line"></div>
            <div class="step completed">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div class="step-line"></div>
            <div class="step active pulse">
                <i class="fas fa-lock"></i>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php if (isset($password_reset_success)): ?>
                <div class="success-actions">
                    <a href="login.php" class="btn-success">
                        <i class="fas fa-sign-in-alt"></i> Go to Login
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!isset($password_reset_success)): ?>
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
                    <small>Password must be at least 8 characters long</small>
                    <div class="password-strength" id="password-strength"></div>
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
                    <div id="password-match-status" class="password-strength"></div>
                </div>
                
                <button type="submit" name="reset_password" id="reset-btn" class="btn-primary">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>
        <?php endif; ?>

        <div class="links">
            <p>Remember your password? <a href="login.php">Login here</a></p>
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

        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';

            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            switch (strength) {
                case 0:
                case 1:
                case 2:
                    feedback = '<span class="strength-weak">Weak password</span>';
                    break;
                case 3:
                case 4:
                    feedback = '<span class="strength-medium">Medium strength</span>';
                    break;
                case 5:
                    feedback = '<span class="strength-strong">Strong password</span>';
                    break;
            }

            return feedback;
        }

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const strengthDiv = document.getElementById('password-strength');
            strengthDiv.innerHTML = checkPasswordStrength(this.value);
            
            // Check password match
            checkPasswordMatch();
        });

        // Password match checker
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const confirmInput = document.getElementById('confirm_password');
            const statusDiv = document.getElementById('password-match-status');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    confirmInput.classList.remove('password-mismatch');
                    confirmInput.classList.add('password-match');
                    statusDiv.innerHTML = '<span class="strength-strong">✓ Passwords match</span>';
                } else {
                    confirmInput.classList.remove('password-match');
                    confirmInput.classList.add('password-mismatch');
                    statusDiv.innerHTML = '<span class="strength-weak">✗ Passwords do not match</span>';
                }
            } else {
                confirmInput.classList.remove('password-match', 'password-mismatch');
                statusDiv.innerHTML = '';
            }
        }

        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // Form submission handler
        document.getElementById('reset-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const resetBtn = document.getElementById('reset-btn');
            
            if (password !== confirmPassword) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Please make sure both passwords match.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Password Too Short',
                    text: 'Password must be at least 8 characters long.',
                    confirmButtonColor: '#667eea'
                });
                return false;
            }
            
            // Show loading state
            resetBtn.innerHTML = '<div class="loading-spinner"></div> Updating...';
            resetBtn.disabled = true;
            
            return true;
        });

        // Show success message with SweetAlert if password was reset successfully
        <?php if (isset($password_reset_success) && $password_reset_success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Password Reset Successful!',
                text: 'Your password has been updated successfully. You can now log in with your new password.',
                confirmButtonText: 'Go to Login',
                confirmButtonColor: '#48bb78',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: true,
                timer: 0
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        <?php endif; ?>

        // Show error message with SweetAlert if there's an error
        <?php if (isset($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Reset Failed',
                text: '<?php echo addslashes($error); ?>',
                confirmButtonColor: '#e53e3e'
            });
        <?php endif; ?>
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?>