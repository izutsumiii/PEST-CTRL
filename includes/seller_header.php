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
        .sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: 240px;
            background: #130325;
            border-right: 1px solid rgba(255, 215, 54, 0.2);
            padding: 16px 12px;
            z-index: 1000;
        }
        .sidebar .logo { text-align: center; margin: 12px 6px 18px; }
        .sidebar .logo a { display: inline-flex; align-items: center; gap: 12px; color: #F9F9F9; text-decoration: none; font-weight: 900; font-size: 40px; line-height: 1; }
        .sidebar .logo a i { font-size: 26px; }
        .sidebar .section-title { color: #9ca3af; font-size: 11px; text-transform: uppercase; letter-spacing: .08em; margin: 14px 8px 6px; }
        .sidebar .nav-links { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
        .sidebar .nav-links a { display: flex; align-items: center; gap: 10px; color: #F9F9F9; text-decoration: none; padding: 10px 12px; border-radius: 8px; font-size: 13px; width: 100%; }
        .sidebar .nav-links a:hover { background: rgba(255, 215, 54, 0.1); color: #FFD736; }
        .sidebar .nav-links a.active { background: rgba(255, 215, 54, 0.15); color: #FFD736; border: 1px solid rgba(255, 215, 54, 0.25); }
        .sidebar .user-box { margin-top: auto; padding: 10px; border-top: 1px solid rgba(255, 215, 54, 0.15); color: #F9F9F9; font-size: 12px; }
        .sidebar .user-top { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .sidebar .user-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #FFD736, #e6c230); color: #130325; display: flex; align-items: center; justify-content: center; font-weight: 700; }

        /* Content offset so content doesn't start below the fold */
        main { margin-left: 240px; }

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
        main h1, main h2 { color: #F9F9F9; }
        
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
    <div class="seller-layout">
        <aside class="sidebar">
            <div class="logo"><a href="seller-dashboard.php"><span>PEST-CTRL</span></a></div>

            <?php if (isLoggedIn() && isSeller()): ?>
                <div class="section-title">Overview</div>
                <div class="nav-links">
                    <a href="seller-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                </div>

                <div class="section-title">Orders</div>
                <div class="nav-links">
                    <a href="view-orders.php"><i class="fas fa-clipboard-list"></i> View Orders</a>
                    <a href="sales-analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a>
                </div>

                <div class="section-title">Catalog</div>
                <div class="nav-links">
                    <a href="manage-products.php"><i class="fas fa-boxes"></i> Manage Products</a>
                </div>

                <div class="user-box">
                    <div class="user-top">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                        <div><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    </div>
                    <div class="nav-links">
                        <a href="seller-edit-profile.php"><i class="fas fa-user"></i> Edit Profile</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="nav-links" style="margin-top:12px;">
                    <a href="login_seller.php"><i class="fas fa-sign-in-alt"></i> Seller Login</a>
                </div>
            <?php endif; ?>
        </aside>
    </div>
    
    <script>/* no dropdown JS needed for sidebar */</script>
    
    <main>
