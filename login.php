<?php 
require_once 'includes/functions.php';

if (isLoggedIn()) {     
    $user_type = $_SESSION['user_type'];
    switch($user_type) {
        case 'admin':
            header("Location: admin/admin-dashboard.php");
            break;
        case 'seller':
            header("Location: seller-dashboard.php");
            break;
        default:
            header("Location: index.php");
    }
    exit(); 
}  
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-Commerce Store</title>
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
        .code-input {
                font-size: 24px;
                letter-spacing: 8px;
                padding: 18px 12px;
            }

            .modal-buttons {
                flex-direction: column;
                gap: 12px;
            }
             .login-card.admin {
            display: none;
            animation: slideInFromTop 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .login-options.show-admin {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 35px 25px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            border: 2px solid rgba(0, 0, 0, 0.05);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

         .code-input {
            width: 100%;
            padding: 20px 15px;
            font-size: 28px;
            text-align: center;
            border: 3px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 15px;
            letter-spacing: 12px;
            font-weight: 700;
            transition: var(--transition);
            background: #f8fafc;
        }
        
        .code-input:focus {
            outline: none;
            border-color: #2c3e50;
            box-shadow: 0 0 20px rgba(44, 62, 80, 0.2);
            background: white;
            transform: scale(1.02);
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }
         .modal-content {
            background: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 50px 40px;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 450px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.3);
            text-align: center;
            animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
         /* Enhanced Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translate(-50%, -60%) scale(0.8);
            }
            to { 
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }
         .close {
            color: #94a3b8;
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
           .modal-header i {
            font-size: 56px;
            color: #2c3e50;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
          .modal-header h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        .close:hover {
            color: #2c3e50;
            background: rgba(44, 62, 80, 0.1);
            transform: rotate(90deg);
        }

        .login-card.admin.show {
            display: block;
        }

        @keyframes slideInFromTop {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

            .btn {
                width: 100%;
            }
            .login-card.admin:hover {
            background: var(--admin-gradient);
            color: white;
            box-shadow: 0 20px 50px rgba(44, 62, 80, 0.3);
        }

        /* Hidden clickable logo area */
        .hidden-admin-trigger {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            cursor: pointer;
            opacity: 0;
            z-index: 10;
            user-select: none;
        }

        /* Store logo in header */
        .store-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: var(--transition);
            z-index: 5;
        }

        .store-logo.clicked {
            animation: pulse 0.3s ease-in-out;
        }

         .click-counter {
            display: none;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

            .store-logo {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
         .hidden-admin-trigger {
                width: 50px;
                height: 50px;
            }
            .click-counter {
                top: 75px;
                font-size: 11px;
            }


        body {
            background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.9) 100%);
            min-height: 100vh;
            margin: 0;
            font-family: var(--font-primary);
            overflow-x: hidden;
        }

        /* Override header background to match */
        .site-header {
            background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.9) 100%) !important;
        }

        .selector-container {
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
            text-align: center;
        }
        
        .selector-header h1 {
            color: var(--accent-yellow);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .selector-header p {
            color: var(--primary-light);
            font-size: 14px;
            opacity: 0.8;
            margin-bottom: 30px;
            font-weight: 400;
            line-height: 1.6;
        }
        
        .login-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .login-card {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 15px 20px;
            text-decoration: none;
            color: var(--primary-light);
            border: 1px solid var(--border-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .login-card:hover {
            background: var(--accent-yellow);
            color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 54, 0.4);
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: var(--accent-yellow);
            text-decoration: none;
            font-size: 13px;
        }

        .register-link a:hover {
            color: var(--primary-light);
            text-decoration: underline;
        }

        .login-card i {
            font-size: 24px;
            color: var(--accent-yellow);
        }
        
        .login-card h3 {
            font-size: 16px;
            margin: 0;
            font-weight: 600;
        }
        
        .login-card p {
            font-size: 12px;
            margin: 0;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="selector-container">
        <!-- Hidden clickable area for admin trigger -->
        <div class="hidden-admin-trigger" id="adminTrigger"></div>
        
        <!-- Visible store logo -->
        <div class="store-logo" id="storeLogo">E</div>
        
        <!-- Click counter (hidden by default) -->
        <div class="click-counter" id="clickCounter">0/12</div>
        
        <div class="selector-header">
            <h1>Welcome to Our Store</h1>
            <p>Please select your login type to access your account</p>
        </div>
        
        <div class="login-options" id="loginOptions">
            <a href="login_customer.php" class="login-card customer">
                <i class="fas fa-shopping-cart"></i>
                <h3>Customer Login</h3>
                <p>Shop products, manage orders, and track deliveries. Access your shopping cart and order history.</p>
            </a>
            
            <a href="login_seller.php" class="login-card seller">
                <i class="fas fa-store"></i>
                <h3>Seller Portal</h3>
                <p>Manage your products, track sales, and handle orders. Access seller dashboard and analytics.</p>
            </a>
            
            <div class="login-card admin" id="adminCard" onclick="showAdminModal()">
                <i class="fas fa-shield-alt"></i>
                <h3>Admin Panel</h3>
                <p>Administrative access to manage users, approve sellers, and oversee platform operations.</p>
            </div>
        </div>
        
        <div class="register-section">
            <h4>New to our platform?</h4>
            <p>Create an account to start shopping or selling on our platform</p>
            <a href="register.php" class="register-btn">
                <i class="fas fa-user-plus"></i>
                <span>Create New Account</span>
            </a>
        </div>
    </div>

    <!-- Admin Code Modal -->
    <div id="adminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAdminModal()">&times;</span>
            <div class="modal-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Admin Access</h2>
                <p>Enter the 6-digit admin code to proceed</p>
            </div>
            <input type="text" id="adminCode" class="code-input" placeholder="000000" maxlength="6" pattern="\d{6}">
            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-triangle"></i> Invalid code. Please try again.
            </div>
            <div class="modal-buttons">
                <button class="btn btn-primary" onclick="verifyAdminCode()">
                    <i class="fas fa-unlock"></i> Verify
                </button>
                <button class="btn btn-secondary" onclick="closeAdminModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        let clickCount = 0;
        let clickTimer = null;
        const maxClicks = 12;
        const resetTime = 3000; // Reset counter after 3 seconds of inactivity

        const adminTrigger = document.getElementById('adminTrigger');
        const storeLogo = document.getElementById('storeLogo');
        const clickCounter = document.getElementById('clickCounter');
        const adminCard = document.getElementById('adminCard');
        const loginOptions = document.getElementById('loginOptions');

        function handleAdminTriggerClick() {
            clickCount++;
            
            // Add animation to logo
            storeLogo.classList.add('clicked');
            setTimeout(() => {
                storeLogo.classList.remove('clicked');
            }, 300);
            
            // Clear existing timer
            if (clickTimer) {
                clearTimeout(clickTimer);
            }
            
            // Check if we've reached the magic number
            if (clickCount >= maxClicks) {
                showAdminEntry();
                resetClickCounter();
                return;
            }
            
            // Set timer to reset counter after inactivity
            clickTimer = setTimeout(() => {
                resetClickCounter();
            }, resetTime);
        }

        function showAdminEntry() {
            // Show admin card with animation
            adminCard.classList.add('show');
            loginOptions.classList.add('show-admin');
            
            // Optional: Show a subtle notification
            showNotification('Admin access unlocked!');
        }

        function resetClickCounter() {
            clickCount = 0;
            if (clickTimer) {
                clearTimeout(clickTimer);
                clickTimer = null;
            }
        }

        function showNotification(message) {
            // Create a temporary notification
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #2c3e50, #4a6741);
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                z-index: 2000;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateY(0)';
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Add click event to the hidden trigger
        adminTrigger.addEventListener('click', handleAdminTriggerClick);

        // Existing modal functions
        function showAdminModal() {
            document.getElementById('adminModal').style.display = 'block';
            document.getElementById('adminCode').focus();
            // Clear any previous error messages
            document.getElementById('errorMessage').style.display = 'none';
            document.getElementById('adminCode').value = '';
        }

        function closeAdminModal() {
            document.getElementById('adminModal').style.display = 'none';
            document.getElementById('adminCode').value = '';
            document.getElementById('errorMessage').style.display = 'none';
        }

        function verifyAdminCode() {
            const enteredCode = document.getElementById('adminCode').value;
            const correctCode = '987654';
            
            if (enteredCode === correctCode) {
                // Code is correct, redirect to admin login
                window.location.href = 'login_admin.php';
            } else {
                // Show error message
                document.getElementById('errorMessage').style.display = 'block';
                document.getElementById('adminCode').value = '';
                document.getElementById('adminCode').focus();
                
                // Add shake animation
                const input = document.getElementById('adminCode');
                input.classList.add('shake');
                setTimeout(() => {
                    input.classList.remove('shake');
                }, 500);
            }
        }

        // Allow Enter key to submit
        document.getElementById('adminCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                verifyAdminCode();
            }
        });

        // Only allow numeric input
        document.getElementById('adminCode').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('adminModal');
            if (event.target == modal) {
                closeAdminModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAdminModal();
            }
        });

        // Add keyboard navigation support
        document.querySelectorAll('.login-card').forEach(card => {
            card.setAttribute('tabindex', '0');
            card.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        // Prevent accidental admin access discovery
        document.addEventListener('contextmenu', function(e) {
            if (e.target === adminTrigger || e.target === storeLogo) {
                e.preventDefault();
            }
        });

        // Hide admin access if user navigates away and comes back
        window.addEventListener('beforeunload', function() {
            resetClickCounter();
            adminCard.classList.remove('show');
            loginOptions.classList.remove('show-admin');
        });

        // Optional: Hide admin access after a period of inactivity
        let inactivityTimer;
        const inactivityTime = 300000; // 5 minutes

        function resetInactivityTimer() {
            if (inactivityTimer) clearTimeout(inactivityTimer);
            
            if (adminCard.classList.contains('show')) {
                inactivityTimer = setTimeout(() => {
                    adminCard.classList.remove('show');
                    loginOptions.classList.remove('show-admin');
                    resetClickCounter();
                }, inactivityTime);
            }
        }

        // Reset inactivity timer on any user interaction
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetInactivityTimer, true);
        });
    </script>
</body>
</html>

