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
        
        body { font-family: var(--font-primary); background-color: var(--bg-secondary); }

        /* Sidebar layout (match admin layout approach) */
        .seller-layout { display: contents; }
        
        /* CSS Variables for sidebar */
        :root {
            --sidebar-width: 240px;
            --sidebar-width-collapsed: 70px;
        }
        
        .sidebar {
            position: fixed;
            top: 60px; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: #130325;
            border-right: 1px solid rgba(255, 215, 54, 0.2);
            padding: 16px 12px;
            z-index: 100;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }
        .sidebar .logo { text-align: center; margin: 12px 6px 18px; }
        .sidebar .logo a { display: inline-flex; align-items: center; gap: 12px; color: #F9F9F9; text-decoration: none; font-weight: 900; font-size: 40px; line-height: 1; }
        .sidebar .logo a i { font-size: 26px; }
        .sidebar .section-title { color: #9ca3af; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; margin: 14px 8px 6px; }
        .sidebar .nav-links { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
        .sidebar .nav-links a { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            color: #F9F9F9; 
            text-decoration: none; 
            padding: 10px 12px; 
            border-radius: 8px; 
            font-size: 13px; 
            width: 100%; 
            transition: all 0.3s ease; 
            position: relative;
        }
        .sidebar .nav-links a:hover { background: rgba(255, 215, 54, 0.1); color: #FFD736; }
        .sidebar .nav-links a.active { background: rgba(255, 215, 54, 0.15); color: #FFD736; border: 1px solid rgba(255, 215, 54, 0.25); }
        
        /* Tooltip styles for collapsed sidebar */
        .sidebar.collapsed .nav-links a::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(19, 3, 37, 0.95);
            color: #F9F9F9;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            margin-left: 15px;
            border: 1px solid rgba(255, 215, 54, 0.2);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            min-width: 80px;
            text-align: center;
        }
        
        .sidebar.collapsed .nav-links a:hover::after {
            opacity: 1;
            visibility: visible;
        }
        
        /* Hamburger menu button - now in logo area */
        .hamburger-btn {
            background: transparent;
            border: none;
            border-radius: 8px;
            width: 100%;
            height: 50px;
            cursor: pointer;
            z-index: 1001;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFD736;
            font-size: 18px;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .hamburger-btn:hover {
            background: rgba(255, 215, 54, 0.1);
            transform: scale(1.02);
        }
        
        .sidebar.collapsed .hamburger-btn {
            transform: rotate(180deg);
        }
        
        /* Collapsed state styles - improved to prevent glitching */
        .sidebar.collapsed .hide-on-collapse {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            pointer-events: none;
        }
        
        .sidebar.collapsed .nav-links a {
            justify-content: center;
            padding: 10px 8px;
            position: relative;
        }
        
        .sidebar.collapsed .nav-links a span {
            position: absolute;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            pointer-events: none;
        }
        
        .sidebar.collapsed .section-title {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            pointer-events: none;
        }
        
        /* Ensure smooth transitions for all elements */
        .sidebar .nav-links a span {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .sidebar .section-title {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        /* Sidebar logo */
        .sidebar .sidebar-logo {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255, 215, 54, 0.15);
            margin-bottom: 20px;
            text-align: center;
            position: relative;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar .sidebar-logo a {
            color: #FFD736;
            text-decoration: none;
            font-family: 'Libre Barcode 128 Text', monospace;
            font-size: 24px;
            font-weight: 400;
            transition: all 0.3s ease;
            display: block;
            position: relative;
            width: 100%;
        }
        
        .sidebar .sidebar-logo a:hover {
            color: #F9F9F9;
        }
        
        /* Hide full logo when collapsed, show shortened version */
        .sidebar .sidebar-logo .full-logo {
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                       visibility 0.4s cubic-bezier(0.4, 0, 0.2, 1),
                       transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar .sidebar-logo .short-logo {
            opacity: 0;
            visibility: hidden;
            position: absolute;
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                       visibility 0.4s cubic-bezier(0.4, 0, 0.2, 1),
                       transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 18px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            white-space: nowrap;
        }
        
        .sidebar.collapsed .sidebar-logo .full-logo {
            opacity: 0;
            visibility: hidden;
            transform: scale(0.9);
        }
        
        .sidebar.collapsed .sidebar-logo .short-logo {
            opacity: 1;
            visibility: visible;
            position: absolute;
            transform: translate(-50%, -50%) scale(1);
        }
        
        /* Sidebar user profile section (non-clickable) - shows background when collapsed */
        .sidebar .user-profile-section {
            margin-top: auto;
            padding: 15px 10px;
            border-top: 1px solid rgba(255, 215, 54, 0.15);
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin: 20px 8px 10px 8px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .sidebar .user-profile-section .user-profile-info {
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .sidebar.collapsed .user-profile-section .user-profile-info {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }
        
        .sidebar .user-profile-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .sidebar .user-name {
            color: #F9F9F9;
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }
        
        .sidebar .user-role {
            color: rgba(249, 249, 249, 0.7);
            font-size: 12px;
            margin: 0;
        }
        
        .sidebar .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD736, #e6c230);
            color: #130325;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 10px;
        }

        /* Full-width header design - spans over sidebar */
        .invisible-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: rgba(19, 3, 37, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 215, 54, 0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        
        /* Left section of header (hamburger menu) */
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Hamburger button in header (left side) */
        .header-hamburger {
            background: transparent;
            border: none;
            color: #FFD736;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .header-hamburger:hover {
            background: rgba(255, 215, 54, 0.1);
            transform: scale(1.1);
        }
        
        /* Right section of header (user profile) */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #F9F9F9;
            text-decoration: none;
            font-weight: 700;
            font-size: 18px;
            transition: all 0.3s ease;
        }
        
        .header-logo i {
            color: #FFD736;
            font-size: 20px;
        }
        
        .header-logo .barcode-text {
            font-family: 'Libre Barcode 128 Text', monospace;
            font-size: 24px;
            font-weight: 400;
            color: #FFD736;
            transition: all 0.3s ease;
        }
        
        /* Logo animation when sidebar toggles */
        .sidebar.collapsed ~ .invisible-header .header-logo .barcode-text {
            transform: scale(0.8);
            opacity: 0.7;
        }
        
        .header-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #F9F9F9;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .header-user-info:hover {
            background: rgba(255, 215, 54, 0.1);
            color: #FFD736;
        }
        
        .header-user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD736, #e6c230);
            color: #130325;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .header-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: #ffffff;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            border-radius: 8px;
            z-index: 1000;
            display: none;
            margin-top: 8px;
        }
        
        .header-dropdown.show {
            display: block;
        }
        
        .header-dropdown a {
            display: block;
            padding: 12px 16px;
            color: #2c3e50;
            text-decoration: none;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }
        
        .header-dropdown a:hover {
            background-color: #f1f1f1;
        }
        
        .header-dropdown a i {
            margin-right: 8px;
            width: 16px;
        }

        /* Content offset so content doesn't start below the fold */
        main { 
            margin-left: var(--sidebar-width); 
            margin-top: 60px; /* Account for invisible header */
            transition: all 0.3s ease;
        }

        /* Shared seller page styles (mirrors seller dashboard) */
        .section {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            color: #F9F9F9;
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }
        .orders-table-container {
            overflow-x: auto;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            background: rgba(255, 255, 255, 0.05);
        }
        .orders-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .orders-table thead { background: rgba(255, 255, 255, 0.1); position: sticky; top: 0; z-index: 10; }
        .orders-table th { padding: 12px; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #FFD736; border-bottom: 2px solid rgba(255,255,255,0.2); }
        .orders-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #F9F9F9; }
        .orders-table tbody tr { background: rgba(255, 255, 255, 0.03); transition: all 0.15s ease-in-out; }
        .orders-table tbody tr:hover { background: #1a0a2e !important; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .orders-table tbody tr:hover td, .orders-table tbody tr:hover a, .orders-table tbody tr:hover span { color: #F9F9F9 !important; }
        main h1, main h2 { 
            color: #F9F9F9; 
            font-family: var(--font-primary);
            font-size: 24px;
            font-weight: 700;
            text-align: left;
            margin: 0 0 15px 0;
            padding-left: 20px;
        }
        
        /* Much more aggressive reduction in top spacing for main content */
        main {
            padding-top: 0;
            margin-top: -60px;
        }
        
        /* Additional spacing reduction for specific elements */
        .container, .mx-auto, .px-4, .py-8 {
            margin-top: -20px !important;
            padding-top: 0 !important;
        }
        
        /* Reduce spacing for dashboard and other pages */
        .bg-gray-50, .stat-card, .section {
            margin-top: -10px !important;
        }
        
        /* Additional aggressive spacing reduction for view-orders and manage-products */
        .orders-wrapper, .admin-content, .page-header {
            margin-top: -30px !important;
            padding-top: 0 !important;
        }
        
        /* Target specific elements in these pages */
        .orders-table-container, .admin-table, .products-table {
            margin-top: -15px !important;
        }
        
        /* Reduce spacing for any remaining containers */
        div[style*="margin"], div[style*="padding"] {
            margin-top: -20px !important;
        }
        
        /* Remove yellow lines from manage-products page */
        h1, h2, .page-header, .section-header {
            border-bottom: none !important;
        }
        
        /* Remove any yellow borders */
        .parent-category, .category-group, .form-section {
            border-left: none !important;
            border-bottom: none !important;
        }

        /* Sidebar dropdown functionality */
        .nav-dropdown {
            position: relative;
        }
        
        .nav-dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .nav-dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: rgba(0, 0, 0, 0.1);
            margin-left: 20px;
            border-left: 2px solid rgba(255, 215, 54, 0.3);
        }
        
        .nav-dropdown-content.show {
            max-height: 200px;
        }
        
        .nav-dropdown-content a {
            padding: 8px 16px 8px 24px;
            font-size: 14px;
            color: #F9F9F9;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
            border-left: 2px solid transparent;
        }
        
        .nav-dropdown-content a:hover {
            background: rgba(255, 215, 54, 0.1);
            border-left-color: #FFD736;
            color: #FFD736;
        }
        
        .nav-dropdown-arrow {
            transition: transform 0.3s ease;
            font-size: 12px;
        }
        
        .nav-dropdown-arrow.rotated {
            transform: rotate(90deg);
        }
        
        .sidebar.collapsed .nav-dropdown-content {
            display: none;
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
        
        @media (max-width: 768px) { main { margin-left: 0; } .sidebar { width: 100%; position: static; border-right: none; } }
    </style>
</head>
<body>
    <!-- Full-width Header -->
    <div class="invisible-header">
        <div class="header-left">
            <button class="header-hamburger" onclick="toggleSellerSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <?php if (isLoggedIn() && isSeller()): ?>
        <div class="header-right">
            <div class="header-user">
                <div class="header-user-info" onclick="toggleHeaderDropdown()">
                    <div class="header-user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="header-dropdown" id="headerDropdown">
                    <a href="seller-edit-profile.php"><i class="fas fa-user"></i>Edit Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="seller-layout">
        <aside class="sidebar" id="sellerSidebar">
            <!-- Logo inside sidebar -->
            <div class="sidebar-logo">
                <a href="seller-dashboard.php">
                    <span class="full-logo barcode-text">PEST-CTRL</span>
                    <span class="short-logo barcode-text">PC</span>
                </a>
            </div>

            <?php if (isLoggedIn() && isSeller()): ?>
                <!-- User profile section without avatar -->
                <div class="user-profile-section">
                    <div class="user-profile-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="user-role">Seller</div>
                    </div>
                </div>

                <div class="section-title hide-on-collapse">Overview</div>
                <div class="nav-links">
                    <a href="seller-dashboard.php" data-tooltip="Dashboard"><i class="fas fa-tachometer-alt"></i><span class="hide-on-collapse"> Dashboard</span></a>
                </div>

                <div class="section-title hide-on-collapse">Orders</div>
                <div class="nav-links">
                    <a href="view-orders.php" data-tooltip="View Orders"><i class="fas fa-clipboard-list"></i><span class="hide-on-collapse"> View Orders</span></a>
                    <a href="sales-analytics.php" data-tooltip="Analytics"><i class="fas fa-chart-bar"></i><span class="hide-on-collapse"> Analytics</span></a>
                </div>

                <div class="section-title hide-on-collapse">Catalog</div>
                    <div class="nav-links">
                    <div class="nav-dropdown">
                        <a href="#" class="nav-dropdown-toggle" onclick="toggleNavDropdown(event, 'manage-products')" data-tooltip="Manage Products">
                            <span><i class="fas fa-boxes"></i><span class="hide-on-collapse"> Manage Products</span></span>
                            <i class="fas fa-chevron-right nav-dropdown-arrow hide-on-collapse"></i>
                        </a>
                        <div class="nav-dropdown-content" id="manage-products-dropdown">
                            <a href="manage-products.php"><i class="fas fa-plus"></i> Add Product</a>
                            <a href="view-products.php"><i class="fas fa-list"></i> View Products</a>
                        </div>
                    </div>
                </div>


            <?php else: ?>
                <div class="nav-links" style="margin-top:12px;">
                    <a href="login_seller.php"><i class="fas fa-sign-in-alt"></i> Seller Login</a>
                </div>
            <?php endif; ?>
        </aside>
    </div>
    
    <script>
        function toggleSellerSidebar() {
            const sidebar = document.getElementById('sellerSidebar');
            const main = document.querySelector('main');
            const header = document.querySelector('.invisible-header');
            const barcodeText = document.querySelector('.barcode-text');
            
            sidebar.classList.toggle('collapsed');
            
            // Adjust main content margin
            if (sidebar.classList.contains('collapsed')) {
                main.style.marginLeft = '70px';
                // Animate logo when sidebar collapses
                if (barcodeText) {
                    barcodeText.style.transform = 'scale(0.8)';
                    barcodeText.style.opacity = '0.7';
                }
            } else {
                main.style.marginLeft = '240px';
                // Animate logo when sidebar expands
                if (barcodeText) {
                    barcodeText.style.transform = 'scale(1)';
                    barcodeText.style.opacity = '1';
                }
            }
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sellerSidebarCollapsed', isCollapsed);
        }

        function toggleHeaderDropdown() {
            const dropdown = document.getElementById('headerDropdown');
            dropdown.classList.toggle('show');
        }

        function toggleNavDropdown(event, dropdownId) {
            event.preventDefault();
            const dropdown = document.getElementById(dropdownId + '-dropdown');
            const arrow = event.currentTarget.querySelector('.nav-dropdown-arrow');
            
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                arrow.classList.remove('rotated');
            } else {
                // Close other dropdowns first
                document.querySelectorAll('.nav-dropdown-content.show').forEach(d => {
                    d.classList.remove('show');
                });
                document.querySelectorAll('.nav-dropdown-arrow.rotated').forEach(a => {
                    a.classList.remove('rotated');
                });
                
                dropdown.classList.add('show');
                arrow.classList.add('rotated');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const headerUser = document.querySelector('.header-user');
            const dropdown = document.getElementById('headerDropdown');
            
            if (headerUser && !headerUser.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // On page load, check if sidebar was collapsed
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sellerSidebar');
            const main = document.querySelector('main');
            const barcodeText = document.querySelector('.barcode-text');
            const isCollapsed = localStorage.getItem('sellerSidebarCollapsed') === 'true';
            
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                main.style.marginLeft = '70px';
                // Set logo animation state
                if (barcodeText) {
                    barcodeText.style.transform = 'scale(0.8)';
                    barcodeText.style.opacity = '0.7';
                }
            }
        });
    </script>
    
    <main>
