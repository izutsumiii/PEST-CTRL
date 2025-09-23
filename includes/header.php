<?php
// Ensure output buffering is enabled so including this file early doesn't break redirects
if (session_status() === PHP_SESSION_NONE) {
    // functions.php starts the session, but ensure buffer is started first
    if (!ob_get_level()) ob_start();
}
require_once 'functions.php';
// Keep PHP-only operations above; presentational HTML starts below
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PEST-CTRL - Professional Pest Control Solutions</title>
    <link rel="icon" type="image/x-icon" href="assets/uploads/pest_icon_216780.ico">
    <link href="assets/css/pest-ctrl.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/script.js"></script>
    <style>
        /* PEST-CTRL Header Overrides - Keep Original Structure */
        .site-header {
            background: linear-gradient(180deg, rgba(19, 3, 37, 0.95), rgba(19, 3, 37, 0.9));
            backdrop-filter: blur(6px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.06);
        }
        
        .site-header .container {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 18px;
            justify-content: space-between;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .header-center {
            flex: 1;
            display: flex;
            justify-content: center;
            max-width: 600px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }
        
        .brand-logo {
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #F9F9F9;
            font-size: 16px;
            font-family: 'Libre Barcode 128 Text', monospace;
            font-weight: 400;
            text-decoration: none;
        }
        
        .brand-text {
            font-size: 18px;
            color: #F9F9F9;
            font-family: 'Libre Barcode 128 Text', monospace;
        }
        
        .search-box {
            flex: 1;
            max-width: 680px;
        }
        
        .search-box form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .search-box input {
            flex: 1;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(249, 249, 249, 0.3);
            background: rgba(249, 249, 249, 0.1);
            color: #F9F9F9;
        }
        
        .search-box input::placeholder {
            color: rgba(249, 249, 249, 0.7);
        }
        
        .search-box button {
            padding: 8px 12px;
            border-radius: 6px;
            background: #FFD736;
            color: #130325;
            border: none;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .action-btn {
            background: transparent;
            border: 1px solid transparent;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            color: #F9F9F9;
        }
        
        .cart-badge {
            background:rgb(174, 46, 46);
            color: #130325;
            padding: 4px 8px;
            border-radius: 999px;
            font-weight: 700;
        }
        
        .nav-links {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .nav-links a {
            color: #F9F9F9;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #FFD736;
            background: rgba(19, 3, 37, 0.8);
        }
        
        
        .cart-link {
            background: none !important;
        }
        
        /* Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .action-btn {
            background: #FFD736;
            color: #130325;
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition-normal);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-btn:hover {
            background: #e6c230;
            transform: translateY(-1px);
        }
        
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            min-width: 200px;
            background: var(--primary-light);
            border: 1px solid var(--border-secondary);
            border-radius: var(--radius-lg);
            box-shadow: 0 8px 25px var(--shadow-medium);
            z-index: 1000;
            margin-top: 8px;
            overflow: hidden;
            display: none;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 12px 16px;
            color: var(--primary-dark);
            text-decoration: none;
            transition: var(--transition-normal);
            border-bottom: 1px solid var(--border-secondary);
        }
        
        .dropdown-item:last-child {
            border-bottom: none;
        }
        
        .dropdown-item:hover {
            background: #FFD736;
            color: #130325;
        }
        
        .nav-link {
            color: #F9F9F9 !important;
            text-decoration: none;
            padding: 8px 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            color: #FFD736 !important;
            background: rgba(19, 3, 37, 0.8) !important;
        }

        /* Mobile */
        .mobile-menu {
            display: none;
        }
        
        .hamburger {
            display: none;
            border: none;
            background: transparent;
            font-size: 22px;
            color: #F9F9F9;
        }

        @media (max-width: 900px) {
            .header-center {
                display: none;
            }
            .header-right .nav-links {
                display: none;
            }
            .hamburger {
                display: block;
            }
            .brand-text {
                font-size: 16px;
            }
            .site-header .container {
                justify-content: space-between;
            }
        }

        /* Mobile drawer */
        .mobile-drawer {
            position: fixed;
            top: 0;
            left: -100%;
            width: 280px;
            height: 100vh;
            background: #F9F9F9;
            box-shadow: 6px 0 18px rgba(0,0,0,0.08);
            transition: all 280ms ease;
            z-index: 1050;
            padding: 18px;
        }
        
        .mobile-drawer.show {
            left: 0;
        }
        
        .mobile-drawer .close-btn {
            background: transparent;
            border: none;
            font-size: 20px;
        }
        
        .mobile-drawer .links {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
        }
        
        .mobile-drawer .links a {
            color: #130325;
        }
        
        
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-left">
                <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
                <a class="brand" href="index.php">
                    <div class="brand-logo">
                        <i class="fas fa-bug" style="color: #F9F9F9; margin-right: 8px;"></i>PEST-CTRL
                    </div>
                </a>
            </div>

            <div class="header-center">
                <div class="search-box">
                    <form action="products.php" method="GET" role="search">
                        <input name="search" type="text" placeholder="Search products...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>

            <div class="header-right">
                <div class="nav-links">
                    <a href="index.php">Home</a>
                    <a href="products.php">Products</a>
                    <a href="cart.php" class="cart-link"><i class="fas fa-shopping-cart"></i></a>
                </div>

                <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <?php
                            $displayName = 'Account';
                            if (!empty($_SESSION['first_name'])) $displayName = htmlspecialchars($_SESSION['first_name']);
                            elseif (!empty($_SESSION['username'])) $displayName = htmlspecialchars($_SESSION['username']);
                        ?>
                       <button class="action-btn" id="userMenuBtn"><?php echo $displayName; ?> <i class="fas fa-caret-down"></i></button>
                                    <div class="dropdown-menu" id="userMenu">
                                        <?php if (isAdmin()): ?>
                                            <a class="dropdown-item" href="admin-dashboard.php">Admin Dashboard</a>
                                        <?php elseif (isSeller()): ?>
                                            <a class="dropdown-item" href="seller-dashboard.php">Seller Dashboard</a>
                                             <a class="dropdown-item" href="view-orders.php">View Orders</a>
                                            <a class="dropdown-item" href="sales-analytics.php">Seller Analytics</a>
                                             <a class="dropdown-item" href="manage-products.php">Manage Products</a>
                                              <a class="dropdown-item" href="order-confirmation.php">Order-Confirmation</a>
                                        <?php else: ?>
                                            <a class="dropdown-item" href="user-dashboard.php">Dashboard</a>
                                        <?php endif; ?>
                                        <a class="dropdown-item" href="edit-profile.php">Edit Profile</a>
                                        <a class="dropdown-item" href="logout.php">Logout</a>
                                    </div>
                    </div>
                <?php else: ?>
                    <div class="nav-links">
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Mobile Drawer -->
    <nav class="mobile-drawer" id="mobileDrawer">
        <div style="display:flex;align-items:center;justify-content:space-between">
            <a class="brand" href="index.php"><div class="brand-logo"><i class="fas fa-bug" style="color: #F9F9F9; margin-right: 8px;"></i>PEST-CTRL</div></a>
            <button class="close-btn" id="drawerClose"><i class="fas fa-times"></i></button>
        </div>
        <div class="links">
            <a href="index.php">Home</a>
            <a href="products.php">Products</a>
            <a href="cart.php">Cart</a>
            <?php if (isLoggedIn()): ?>
                <?php if (isSeller()): ?>
                    <a href="seller-dashboard.php">Seller Dashboard</a>
                <?php elseif (isAdmin()): ?>
                    <a href="admin-dashboard.php">Admin Dashboard</a>
                <?php else: ?>
                    <a href="user-dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="edit-profile.php">Edit Profile</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        // Mobile drawer toggle
        (function(){
            const hb = document.getElementById('hamburgerBtn');
            const drawer = document.getElementById('mobileDrawer');
            const closeBtn = document.getElementById('drawerClose');
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userMenu = document.getElementById('userMenu');
            if (hb && drawer) hb.addEventListener('click', ()=> drawer.classList.add('show'));
            if (closeBtn && drawer) closeBtn.addEventListener('click', ()=> drawer.classList.remove('show'));
            if (userMenuBtn && userMenu) {
                userMenuBtn.addEventListener('click', (e)=> {
                    e.stopPropagation();
                    userMenu.classList.toggle('show');
                });
                document.addEventListener('click', (e)=>{
                    if (!userMenu.contains(e.target) && e.target !== userMenuBtn) {
                        userMenu.classList.remove('show');
                    }
                });
            }
        })();
    </script>

    <main>