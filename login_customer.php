<?php 
require_once 'includes/functions.php'; 
require_once 'config/database.php';  

if (isLoggedIn()) {     
    header("Location: index.php");     
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
        
        // Only allow customer logins on this page
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND user_type = 'customer' AND is_active = 1");         
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
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 days                                          
                    
                    // Store token in database                     
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");                     
                    $stmt->execute([$rememberToken, $user['id']]);                                          
                    
                    // Set cookie                     
                    setcookie('remember_token', $rememberToken, $expiry, '/', '', true, true);                 
                }                                  
                
                // Update last login                 
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                                 
                // Redirect to customer dashboard
                header("Location: index.php");                 
                exit();             
            } else {                 
                $error = "Invalid username or password.";             
            }         
        } else {             
            $error = "Invalid customer credentials or account is inactive.";         
        }     
    } 
}  

// Check for remember me cookie 
if (isset($_COOKIE['remember_token']) && !isLoggedIn()) {     
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND user_type = 'customer' AND is_active = 1");     
    $stmt->execute([$_COOKIE['remember_token']]);     
    $user = $stmt->fetch(PDO::FETCH_ASSOC);          
    
    if ($user) {         
        $_SESSION['user_id'] = $user['id'];         
        $_SESSION['username'] = $user['username'];         
        $_SESSION['user_type'] = $user['user_type'];                  
        
        header("Location: index.php");         
        exit();     
    } 
} 

// Include header after all redirects and session operations
require_once 'includes/header.php';
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login - PEST-CTRL</title>
    <link rel="icon" type="image/x-icon" href="assets/uploads/pest_icon_216780.ico">
    <link href="assets/css/pest-ctrl.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Login Customer Page Styles */
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
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

        .form-group input:focus {
            outline: none;
            background: rgba(249, 249, 249, 0.2);
            border-color: rgba(255, 215, 54, 0.5);
            box-shadow: 0 0 15px rgba(255, 215, 54, 0.3);
        }
        
        .form-group input::placeholder {
            color: rgba(249, 249, 249, 0.7);
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
            color: #666;
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
        }
        
        .remember-me input[type="checkbox"] {
            margin-right: 8px;
        }
        
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
        
        .login-links {
            text-align: center;
            margin-top: 16px;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .login-links a {
            color: var(--accent-yellow);
            text-decoration: none;
            margin: 0 8px;
            font-size: 0.75rem;
        }
        
        .login-links a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }
        
        .other-logins {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        .other-logins a {
            display: inline-block;
            margin: 5px 10px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            color: white;
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
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
        }
        
        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-user"></i> Customer Login</h1>
            <div class="subtitle">Access your PEST-CTRL account</div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>     
            <div class="success-message">         
                <?php echo $_SESSION['success']; ?>         
                <?php unset($_SESSION['success']); ?>     
            </div> 
        <?php endif; ?>  

        <?php if (isset($error)): ?>     
            <div class="error-message">         
                <?php echo $error; ?>     
            </div> 
        <?php endif; ?>  

        <form method="POST" action="">     
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">          
            
            <div class="form-group">         
                <label for="username"><i class="fas fa-user"></i> Username or Email:</label>         
                <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>     
            </div>          
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password:</label>
                <div class="password-input-container">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <a href="forgot-password-secure.php" class="forgot-password">Forgot Password?</a>
            </div>     
            
            <div class="form-group remember-me">         
                <label>             
                    <input type="checkbox" name="remember_me" <?php echo isset($_POST['remember_me']) ? 'checked' : ''; ?>>             
                    Remember me for 30 days        
                </label>     
            </div>          
            
            <button type="submit" name="login"><i class="fas fa-sign-in-alt"></i> Login as Customer</button> 
        </form>  

        <div class="login-links">
            <p style = "font-size: 13px;">Don't have an account? <a href="register.php">Register here</a></p>
        </div>
        
        <div class="other-logins">
            <p style="margin-bottom: 10px; font-size: 14px; opacity: 0.8;">Login as:</p>
            <a href="login_seller.php"><i class="fas fa-store"></i> Seller</a>
            <!-- <a href="login_admin.php"><i class="fas fa-user-shield"></i> Admin</a> -->
        </div>
    </div>

    <script> 
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
    </script>
</body>
</html>