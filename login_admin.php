<?php 
require_once 'includes/functions.php'; 
require_once 'config/database.php';  

if (isLoggedIn()) {     
    header("Location: admin\admin-dashboard.php");     
    exit(); 
}  

// Generate CSRF token 
if (empty($_SESSION['csrf_token'])) {     
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
}  

if (isset($_POST['login'])) {     
    // Validate CSRF token     
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {         
        $error = "Invalid form submission. Please try again.";     
    } else {         
        $username = sanitizeInput($_POST['username']);         
        $password = $_POST['password'];         
        $rememberMe = isset($_POST['remember_me']);                  
        
        // Only allow admin logins on this page
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND user_type = 'admin' AND is_active = 1");         
        $stmt->execute([$username, $username]);         
        $user = $stmt->fetch(PDO::FETCH_ASSOC);                  
        
        if ($user) {             
            if (password_verify($password, $user['password'])) {                 
                // Set session variables                 
                $_SESSION['user_id'] = $user['id'];                 
                $_SESSION['username'] = $user['username'];                 
                $_SESSION['user_type'] = $user['user_type'];                                  
                
                // Set remember me token if requested                 
                if ($rememberMe) {                     
                    $rememberToken = bin2hex(random_bytes(32));                     
                    $expiry = time() + (7 * 24 * 60 * 60); // 7 days for admin (shorter for security)                                        
                    
                    // Store token in database                     
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");                     
                    $stmt->execute([$rememberToken, $user['id']]);                                          
                    
                    // Set cookie                     
                    setcookie('remember_token', $rememberToken, $expiry, '/', '', true, true);                 
                }                                  
                
                // Update last login and log admin access
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                // $stmt->execute([$user['id']]);
                
                // Log admin login for security audit
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, ip_address, user_agent, timestamp) VALUES (?, 'login', ?, ?, NOW())");
             
                                 
                // Redirect to admin dashboard
                header("Location: admin/admin-dashboard.php");                 
                exit();             
            } else {                 
                $error = "Invalid username or password.";             
            }         
        } else {             
            $error = "Invalid admin credentials or account not found.";         
        }     
    } 
}  

// Check for remember me cookie 
if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {     
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND user_type = 'admin' AND is_active = 1");     
    $stmt->execute([$_COOKIE['remember_token']]);     
    $user = $stmt->fetch(PDO::FETCH_ASSOC);          
    
    if ($user) {         
        $_SESSION['user_id'] = $user['id'];         
        $_SESSION['username'] = $user['username'];         
        $_SESSION['user_type'] = $user['user_type'];                  
        
        header("Location: admin/admin-dashboard.php");         
        exit();     
    } 
} 
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PEST-CTRL</title>
    <link rel="icon" type="image/x-icon" href="assets/uploads/pest_icon_216780.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Admin Login Page Styles */
        body {
            background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.9) 100%);
            min-height: 100vh;
            margin: 0;
            font-family: var(--font-primary);
        }
        
        .login-container {
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
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: var(--accent-yellow);
        }

        .login-header .subtitle {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .login-header .subtitle {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .security-notice {
            background: rgba(231, 76, 60, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13px;
            text-align: center;
            border-left: 4px solid #e74c3c;
            position: relative;
            z-index: 1;
        }
        
        .security-notice i {
            margin-right: 8px;
            color: #e74c3c;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            background: rgba(255,255,255,0.95);
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            background: #ffffff;
            box-shadow: 0 0 15px rgba(255, 215, 54, 0.3);
            transform: translateY(-2px);
        }
        
        .password-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            color: #666;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: #333;
        }
        
        .forgot-password {
            color: var(--accent-yellow);
            font-size: 14px;
            text-decoration: none;
            float: right;
            margin-top: 5px;
        }
        
        .forgot-password:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .remember-warning {
            font-size: 12px;
            opacity: 0.7;
            margin-top: 5px;
            font-style: italic;
        }
        
        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, var(--accent-yellow), #e6c230);
            color: var(--primary-dark);
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 54, 0.3);
        }
        
        button[type="submit"]:hover {
            background: linear-gradient(135deg, #e6c230, var(--accent-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 54, 0.4);
        }
        
        button[type="submit"]:active {
            transform: translateY(0);
        }
        
        .login-links {
            text-align: center;
            margin-top: 25px;
            position: relative;
            z-index: 1;
        }
        
        .login-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .login-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .other-logins {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
        }
        
        .other-logins a {
            display: inline-block;
            margin: 5px 10px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            color: var(--primary-light);
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .other-logins a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .success-message, .error-message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid rgba(46, 204, 113, 0.5);
            color: #2ecc71;
        }
        
        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid rgba(231, 76, 60, 0.5);
            color: #e74c3c;
        }
        
        .admin-features {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13px;
            position: relative;
            z-index: 1;
        }
        
        .admin-features h4 {
            margin: 0 0 12px 0;
            font-size: 15px;
            color: #3498db;
        }
        
        .admin-features ul {
            margin: 8px 0;
            padding-left: 20px;
        }
        
        .admin-features li {
            margin-bottom: 6px;
            opacity: 0.9;
        }

        /* Code Verification Styles */
        .code-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(19, 3, 37, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .code-verification {
            background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 20px 60px var(--shadow-dark);
            text-align: center;
            max-width: 380px;
            width: 90%;
            color: var(--primary-light);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-secondary);
        }

        .code-verification::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.05), transparent);
            animation: shine 3s infinite;
        }

        .code-header {
            position: relative;
            z-index: 1;
            margin-bottom: 25px;
        }

        .code-header i {
            font-size: 40px;
            color: var(--accent-yellow);
            margin-bottom: 12px;
            display: block;
        }

        .code-header h2 {
            font-size: 22px;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--accent-yellow);
        }

        .code-header p {
            opacity: 0.8;
            font-size: 14px;
        }

        .admin-code-input {
            width: 100%;
            padding: 15px;
            font-size: 28px;
            text-align: center;
            border: 2px solid var(--border-secondary);
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: var(--primary-light);
            letter-spacing: 10px;
            font-weight: 600;
            margin-bottom: 20px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .admin-code-input:focus {
            outline: none;
            border-color: var(--accent-yellow);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 0 20px rgba(255, 215, 54, 0.3);
        }

        .admin-code-input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .code-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .code-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .code-btn-verify {
            background: linear-gradient(135deg, var(--accent-yellow), #e6c230);
            color: var(--primary-dark);
            box-shadow: 0 4px 15px rgba(255, 215, 54, 0.3);
        }

        .code-btn-verify:hover {
            background: linear-gradient(135deg, #e6c230, var(--accent-yellow));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 54, 0.4);
        }

        .code-btn-back {
            background: rgba(255,255,255,0.1);
            color: var(--primary-light);
            border: 1px solid var(--border-secondary);
        }

        .code-btn-back:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }

        .code-error {
            color: #dc3545;
            font-size: 13px;
            margin-top: 10px;
            display: none;
            position: relative;
            z-index: 1;
        }

        .login-form-locked {
            pointer-events: none;
            opacity: 0.3;
            filter: blur(2px);
        }

        @keyframes codeShake {
            0%, 20%, 40%, 60%, 80% {
                transform: translateX(0);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: translateX(-10px);
            }
        }
    </style>
</head>
<body>
    <!-- Code Verification Overlay -->
    <div id="codeOverlay" class="code-overlay">
        <div class="code-verification">
            <div class="code-header">
                <i class="fas fa-key"></i>
                <h2>Admin Security Code</h2>
                <p>Enter the 6-digit admin access code to proceed</p>
            </div>
          <input type="password" id="adminSecurityCode" class="admin-code-input" placeholder="••••••" maxlength="6" pattern="\d{6}">

            <div class="code-error" id="codeError">
                <i class="fas fa-exclamation-triangle"></i> Invalid code. Access denied.
            </div>
            <div class="code-buttons">
                <button class="code-btn code-btn-verify" onclick="verifySecurityCode()">
                    <i class="fas fa-unlock"></i> Verify
                </button>
                <button class="code-btn code-btn-back" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>
        </div>
    </div>

    <div class="login-container login-form-locked" id="loginContainer">
        <div class="login-header">
            <h1><i class="fas fa-cog"></i> Admin Portal</h1>
            <div class="subtitle">Secure Administrative Access</div>
        </div>

        <div class="security-notice">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Security Notice:</strong> Admin access is logged and monitored. Only authorized personnel should access this area.
        </div>

        <div class="admin-features">
            <h4><i class="fas fa-cogs"></i> Admin Dashboard Features:</h4>
            <ul>
                <li>User Management (Customers, Sellers, Admins)</li>
                <li>Seller Application Reviews</li>
                <li>Product & Category Management</li>
                <li>Order Tracking & Management</li>
                <li>Security & Audit Logs</li>
            </ul>
        </div>

        <?php if (isset($_SESSION['success'])): ?>     
            <div class="success-message">         
                <?php echo $_SESSION['success']; ?>         
                <?php unset($_SESSION['success']); ?>     
            </div> 
        <?php endif; ?>  

        <?php if (isset($error)): ?>     
            <div class="error-message">         
                <i class="fas fa-times-circle"></i> <?php echo $error; ?>     
            </div> 
        <?php endif; ?>  

        <form method="POST" action="" id="loginForm">     
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">          
            
            <div class="form-group">         
                <label for="username"><i class="fas fa-user-shield"></i> Admin Username or Email:</label>         
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required disabled>     
            </div>          
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Admin Password:</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" required disabled>
                    <button type="button" class="toggle-password" onclick="togglePassword('password')" disabled>
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>     
            
            <div class="form-group remember-me">         
                <!-- Remember me functionality commented out for security -->
            </div>          
            
            <button type="submit" name="login" disabled><i class="fas fa-sign-in-alt"></i> Secure Admin Login</button> 
        </form>  
        
        <div class="other-logins">
            <p style="margin-bottom: 15px; font-size: 14px; opacity: 0.8;">Switch Login Type:</p>
            <a href="login_customer.php"><i class="fas fa-shopping-cart"></i> Customer</a>
            <a href="login_seller.php"><i class="fas fa-store"></i> Seller</a>
        </div>
    </div>

    <script> 
        // Security code verification
        function verifySecurityCode() {
            const enteredCode = document.getElementById('adminSecurityCode').value;
            const correctCode = '987654';
            
            if (enteredCode === correctCode) {
                // Code is correct, hide overlay and enable form
                document.getElementById('codeOverlay').style.display = 'none';
                const loginContainer = document.getElementById('loginContainer');
                loginContainer.classList.remove('login-form-locked');
                
                // Enable form elements
                document.getElementById('username').disabled = false;
                document.getElementById('password').disabled = false;
                document.querySelector('button[name="login"]').disabled = false;
                document.querySelector('.toggle-password').disabled = false;
                
                // Focus on username field
                document.getElementById('username').focus();
                
                // Add success indicator
                const codeOverlay = document.getElementById('codeOverlay');
                codeOverlay.style.background = 'rgba(46, 204, 113, 0.1)';
                setTimeout(() => {
                    codeOverlay.style.display = 'none';
                }, 500);
                
            } else {
                // Show error message and shake animation
                const errorDiv = document.getElementById('codeError');
                const codeInput = document.getElementById('adminSecurityCode');
                
                errorDiv.style.display = 'block';
                codeInput.value = '';
                codeInput.style.animation = 'codeShake 0.5s';
                codeInput.focus();
                
                setTimeout(() => {
                    codeInput.style.animation = '';
                }, 500);
            }
        }

        function goBack() {
            window.location.href = 'index.php'; // or wherever you want to redirect
        }

        // Allow Enter key to submit code
        document.getElementById('adminSecurityCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verifySecurityCode();
            }
        });

        // Only allow numeric input for security code
        document.getElementById('adminSecurityCode').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

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

        // Auto-focus on security code field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('adminSecurityCode').focus();
        });

        // Prevent form submission if code not verified
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (document.getElementById('codeOverlay').style.display !== 'none') {
                e.preventDefault();
                alert('Please verify the security code first.');
            }
        });
    </script>
</body>
</html>