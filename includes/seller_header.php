<?php
require_once 'functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - PEST-CTRL</title>
    <link rel="icon" type="image/x-icon" href="assets/uploads/pest_icon_216780.ico">
    <link href="assets/css/pest-ctrl.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-primary);
            background-color: var(--bg-secondary);
        }
        
        header {
            background: linear-gradient(180deg, #130325, #130325);
            backdrop-filter: blur(6px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.06);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0;
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 18px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo a {
            color: #F9F9F9;
            text-decoration: none;
            font-size: 16px;
            font-family: 'Libre Barcode 128 Text', monospace;
            font-weight: 400;
        }
        
        .logo a:hover {
            color: var(--accent-yellow);
        }
        
        .seller-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 0.85em;
            color: #F9F9F9;
        }
        
        .seller-badge {
            background: linear-gradient(135deg, #FFD736, #e6c230);
            color: #130325;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .nav-links a {
            color: #F9F9F9;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9em;
        }
        
        .nav-links a:hover {
            background-color: rgba(255, 215, 54, 0.2);
            color: #FFD736;
            transform: translateY(-1px);
        }
        
        .nav-links a.active {
            background-color: rgba(255, 215, 54, 0.2);
            border-bottom: 2px solid #FFD736;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            right: 0;
            top: 100%;
            margin-top: 10px;
        }
        
        .dropdown-content a {
            color: #2c3e50 !important;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s ease;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
            transform: none;
        }
        
        .dropdown.show .dropdown-content {
            display: block;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            color: #F9F9F9;
            transition: all 0.3s ease;
            font-size: 0.9em;
        }
        
        .user-menu:hover {
            color: #FFD736;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD736, #e6c230);
            color: #130325;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 15px;
                padding: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }
            
            .seller-info {
                order: -1;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="seller-dashboard.php">
                    <i class="fas fa-bug" style="color: #F9F9F9; margin-right: 8px;"></i>PEST-CTRL
                </a>
            </div>
            
            <?php if (isLoggedIn() && isSeller()): ?>
                <div class="seller-info">
                    <span class="seller-badge">
                        <i class="fas fa-store"></i> Seller
                    </span>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="nav-links">
                <?php if (isLoggedIn() && isSeller()): ?>
                    <a href="seller-dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    
                    <a href="view-orders.php">
                        <i class="fas fa-clipboard-list"></i> View Orders
                    </a>
                    
                    <a href="sales-analytics.php">
                        <i class="fas fa-chart-bar"></i> Analytics
                    </a>
                    
                    <a href="manage-products.php">
                        <i class="fas fa-boxes"></i> Manage Products
                    </a>
                    
                    <a href="order-confirmation.php">
                        <i class="fas fa-check-circle"></i> Order Confirmation
                    </a>
                    
                    <div class="dropdown">
                        <div class="user-menu">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-content">
                            <a href="edit-profile.php">
                                <i class="fas fa-user"></i> Edit Profile
                            </a>
                            <a href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login_seller.php">
                        <i class="fas fa-sign-in-alt"></i> Seller Login
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    
    <script>
        // Handle dropdown clicks
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.dropdown');
            
            dropdowns.forEach(dropdown => {
                const toggle = dropdown.querySelector('.dropdown-toggle, .user-menu');
                
                if (toggle) {
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        // Close all other dropdowns
                        dropdowns.forEach(otherDropdown => {
                            if (otherDropdown !== dropdown) {
                                otherDropdown.classList.remove('show');
                            }
                        });
                        
                        // Toggle current dropdown
                        dropdown.classList.toggle('show');
                    });
                }
            });
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    dropdowns.forEach(dropdown => {
                        dropdown.classList.remove('show');
                    });
                }
            });
        });
    </script>
    
    <main>
