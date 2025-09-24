<?php
require_once '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PEST-CTRL</title>
    <link rel="icon" type="image/x-icon" href="../assets/uploads/pest_icon_216780.ico">
    <link href="../assets/css/pest-ctrl.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: var(--font-primary);
            background: linear-gradient(135deg, #130325 0%, #1a0a2e 100%);
            color: #F9F9F9;
            font-size: 13px;
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
            padding: 8px 14px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .logo a {
            color: #F9F9F9;
            text-decoration: none;
            font-size: 14px;
            font-family: 'Libre Barcode 128 Text', monospace;
            font-weight: 400;
        }
        
        .logo a:hover {
            color: var(--accent-yellow);
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            color: #F9F9F9;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #FFD736, #e6c230);
            color: #130325;
            padding: 3px 8px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .nav-links a {
            color: #F9F9F9;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 12px;
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
            background-color: #ffffff;
            min-width: 180px;
            padding: 6px 0;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
            border: 1px solid #e5e7eb;
            z-index: 1;
            border-radius: 8px;
            right: 0;
            top: 100%;
            margin-top: 8px;
        }
        
        .dropdown-content a {
            color: #374151 !important;
            padding: 6px 10px;
            text-decoration: none;
            display: block;
            font-size: 12px;
            line-height: 1.2;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        
        .dropdown-content a:hover {
            background-color: #f3f4f6;
            color: #111827 !important;
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
        }
        
        .user-menu:hover {
            color: #FFD736;
        }
        
        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFD736, #e6c230);
            color: #130325;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                gap: 12px;
                padding: 0.75rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
            }
            
            .admin-info {
                order: -1;
            }
        }

        /* Admin page shared styles to mirror seller theme */
        .page-header {
            max-width: 1200px;
            margin: 70px auto 14px auto;
            padding: 0 14px;
        }
        .page-header h1 {
            color: #F9F9F9;
            margin-bottom: 4px;
            font-size: 18px;
        }
        .page-header p {
            color: rgba(249, 249, 249, 0.7);
            font-size: 12px;
        }

        .stats {
            max-width: 1200px;
            margin: 0 auto 16px auto;
            padding: 0 14px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border-left: 3px solid #007bff;
            color: #F9F9F9;
        }
        .stat-card h3 { color: #F9F9F9; font-weight: 600; font-size: 12px; }
        .stat-card p { color: #FFD736; font-weight: 800; font-size: 1.1rem; }

        .admin-sections {
            max-width: 1200px;
            margin: 0 auto 24px auto;
            padding: 0 14px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        /* Generic page content container for spacing around tables */
        .admin-content { max-width: 1200px; margin: 0 auto; padding: 0 18px; }
        .admin-sections .section {
            background: #ffffff;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            color: #2c3e50;
        }
        .admin-sections .section h2 { color: #2c3e50; margin-bottom: 8px; font-size: 14px; }
        .admin-sections .section p { color: #7f8c8d; font-size: 12px; }

        /* Table styling inside admin sections - match Products page */
        .admin-sections .section table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
            color: #2c3e50;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-sections .section table thead th {
            background: #34495e;
            color: #ffffff;
            text-align: left;
            padding: 15px;
            font-weight: 600;
            border-bottom: 1px solid #2e3f50;
            font-size: 14px;
        }
        .admin-sections .section table tbody td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
            vertical-align: top;
            color: #1f2937; /* darker text */
        }
        .admin-sections .section table tbody td small { color: #1f2937; }
        .admin-sections .section table tbody td strong { color: #111827; }
        .admin-sections .section table tbody tr:hover {
            background: #f8f9fa;
        }
        /* Dashboard table action buttons */
        .admin-sections .section table td:last-child a {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-right: 8px;
            color: #ffffff;
        }
        .admin-sections .section table td:last-child a[href*="approve"] { background: #28a745; }
        .admin-sections .section table td:last-child a[href*="approve"]:hover { background: #218838; }
        .admin-sections .section table td:last-child a[href*="reject"] { background: #dc3545; }
        .admin-sections .section table td:last-child a[href*="reject"]:hover { background: #c82333; }
        .admin-sections .section a { color: #3498db; text-decoration: none; font-weight: 600; }
        .admin-sections .section a:hover { color: #2980b9; text-decoration: underline; }

        /* Generic admin tables used across pages */
        .table-container {
            margin: 12px 0;
            overflow-x: auto;
        }
        /* Generic admin tables - match Products page */
        table.admin-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-collapse: collapse;
        }
        table.admin-table thead th {
            background: #34495e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        table.admin-table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: top;
            color: #1f2937; /* darker text */
        }
        table.admin-table td small { color: #1f2937; }
        table.admin-table td strong { color: #111827; }
        table.admin-table tr:hover {
            background: #f8f9fa;
        }

        /* Status badges (customers, sellers) */
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-suspended { background: #d1ecf1; color: #0c5460; }
        .status-banned { background: #f8d7da; color: #721c24; }

        .verified { color: #16a34a; font-weight: 600; }

        /* Action buttons in tables (match Products page styles) */
        .action-buttons { display: flex; flex-wrap: wrap; gap: 6px; }
        .action-buttons a, .action-buttons button, .btn-view, .btn-approve, .btn-reject,
        .btn-suspend, .btn-ban, .btn-verify, .btn-unverify, .btn-upload,
        .btn-activate, .btn-deactivate, .btn-delete, .btn-reactivate {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            white-space: nowrap;
            color: #ffffff;
        }
        .btn-view { background: #17a2b8; }
        .btn-approve, .btn-activate { background: #28a745; }
        .btn-reactivate { background: #28a745; }
        .btn-reject, .btn-delete, .btn-ban { background: #dc3545; }
        .btn-suspend { background: #6c757d; }
        .btn-verify { background: #007bff; }
        .btn-unverify { background: #6b7280; color: #ffffff; }
        .btn-upload { background: #6f42c1; }
        .btn-deactivate { background: #f59e0b; color: #ffffff; }

        .btn-view:hover { background: #138496; }
        .btn-approve:hover, .btn-activate:hover, .btn-reactivate:hover { background: #218838; }
        .btn-reject:hover, .btn-delete:hover, .btn-ban:hover { background: #c82333; }
        .btn-suspend:hover { background: #5a6268; }
        .btn-verify:hover { background: #0056b3; }
        .btn-unverify:hover { background: #4b5563; }
        .btn-upload:hover { background: #59359a; }
        .btn-deactivate:hover { background: #d97706; }

        /* Pagination - match Products page */
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .pagination .page-link, .pagination a { 
            padding: 8px 12px; background: #ffffff; color: #3498db; text-decoration: none; 
            border-radius: 5px; border: 1px solid #ddd; transition: all 0.3s ease; font-weight: 600;
        }
        .pagination .page-link:hover, .pagination a:hover { background: #3498db; color: #ffffff; }
        .pagination .page-link.active, .pagination a.active { background: #3498db; color: #ffffff; }
        .pagination .page-ellipsis { color: #95a5a6; }
        .pagination-info { color: rgba(249, 249, 249, 0.9); margin-top: 6px; text-align: center; }

        /* Search form - align with Products page */
        .search-section { margin: 8px 0; display: flex; justify-content: center; }
        .search-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; max-width: 720px; width: 100%; }
        .search-input { padding: 8px 15px; border: 1px solid #ddd; border-radius: 5px; width: 360px; flex: 1; }
        .btn-search { background: #3498db; color: #ffffff; border: none; padding: 8px 15px; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-clear { background: #ffffff; color: #3498db; border: 1px solid #ddd; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-weight: 700; font-size: 12px; }
        .btn-search:hover { background: #2980b9; }
        .btn-clear:hover { background: #3498db; color: #ffffff; border-color: #3498db; }

        /* Customers & Sellers: search container card */
        .page-admin-customers .search-section,
        .page-admin-sellers .search-section {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 14px;
            max-width: 1200px;
            margin: 10px auto;
        }

        /* Filters - match Products page */
        .admin-filters { max-width: 1200px; margin: 0 auto 20px auto; padding: 20px; background: #ffffff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-filters h2 { color: #2c3e50; font-size: 16px; margin: 0 0 10px 0; }
        .filter-row { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
        .status-filters { display: flex; flex-wrap: wrap; gap: 10px; }
        .status-filters a { padding: 8px 15px; background: #ecf0f1; color: #2c3e50; text-decoration: none; border-radius: 20px; transition: all 0.3s ease; font-size: 14px; }
        .status-filters a:hover { background: #d5dbdb; }
        .status-filters a.active { background: #3498db; color: #ffffff; }

        /* Admin Sellers: Upload Document modal (compact) */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1050; }
        .modal .modal-content {
            background: #ffffff;
            color: #2c3e50;
            width: 92%;
            max-width: 420px;
            margin: 10% auto;
            padding: 12px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            position: relative;
        }
        .modal .close { position: absolute; top: 8px; right: 10px; font-size: 18px; cursor: pointer; color: #6b7280; }
        .modal h2 { font-size: 16px; margin: 0 0 10px 0; color: #111827; }
        .modal .form-group { margin-bottom: 10px; }
        .modal label { display: block; font-size: 12px; color: #374151; margin-bottom: 6px; }
        .modal input[type="file"] { width: 100%; font-size: 12px; }
        .modal button[type="submit"] {
            background: #FFD736;
            color: #130325;
            border: 1px solid #FFD736;
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
        }
        .modal button[type="submit"]:hover { filter: brightness(0.95); transform: translateY(-1px); }

        /* Compact page titles and list headers for Customers/Sellers pages */
        main > h1 { text-align: center; font-size: 16px; margin: 72px auto 8px auto; }
        .page-admin-sellers main > h1, .page-admin-customers main > h1 { font-size: 56px; line-height: 1.1; }
        .page-admin-dashboard .page-header h1 { font-size: 56px; line-height: 1.1; }
        .admin-content h2 { text-align: center; font-size: 14px; }

        /* Narrow search bar and center it */
        .search-section { display: flex; justify-content: center; }
        .search-form { width: 100%; max-width: 420px; }
    </style>
</head>
<body class="<?php echo 'page-' . strtolower(pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME)); ?>">
    <header>
        <nav>
            <div class="logo">
                <a href="admin-dashboard.php">
                    <i class="fas fa-bug" style="color: #F9F9F9; margin-right: 8px;"></i>PEST-CTRL Admin
                </a>
            </div>
            
            <?php if (isLoggedIn() && isAdmin()): ?>
                <div class="admin-info">
                    <span class="admin-badge">
                        <i class="fas fa-user-shield"></i> Admin
                    </span>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="nav-links">
                <?php if (isLoggedIn() && isAdmin()): ?>
                    <a href="admin-dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-users"></i> Users <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-content">
                            <a href="admin-customers.php">
                                <i class="fas fa-shopping-cart"></i> Customers
                            </a>
                            <a href="admin-sellers.php">
                                <i class="fas fa-store"></i> Sellers
                            </a>
                            <a href="admin-admins.php">
                                <!-- <i class="fas fa-user-shield"></i> Admins -->
                            </a>
                        </div>
                    </div>
                    
                    <a href="admin-products.php">
                        <i class="fas fa-boxes"></i> Products
                    </a>
                    
                    <!-- <a href="admin-orders.php">
                        <i class="fas fa-clipboard-list"></i> Orders
                    </a> -->
                    
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <i class="fas fa-cog"></i> System <i class="fas fa-chevron-down"></i>
                        </a>
                        <div class="dropdown-content">
                            <a href="admin-categories.php">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                            <a href="admin-settings.php">
                                <i class="fas fa-cogs"></i> Settings
                            </a>
                            <a href="admin-reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                            <!-- <a href="security-logs.php"> -->
                                <!-- <i class="fas fa-shield-alt"></i> Security Logs -->
                            </a>
                        </div>
                    </div>
                    
                    <div class="dropdown">
                        <div class="user-menu">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                            </div>
                            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-content">
                            <!-- <a href="admin-profile.php"> -->
                                <!-- <i class="fas fa-user"></i> My Profile -->
                            </a>
                            <!-- <a href="admin-security.php"> -->
                                <!-- <i class="fas fa-lock"></i> Security Settings -->
                            </a>
                            <a href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../login_admin.php">
                        <i class="fas fa-sign-in-alt"></i> Admin Login
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

            // Confirmation modal for actions
            const confirmModal = document.createElement('div');
            confirmModal.innerHTML = `
                <div class="modal" id="confirmModal" style="display:none;">
                    <div class="modal-content">
                        <span class="close" id="confirmClose">&times;</span>
                        <h2 id="confirmTitle">Confirm Action</h2>
                        <p id="confirmMessage" style="margin: 8px 0 14px 0; color: #374151; font-size: 13px;">Are you sure you want to proceed?</p>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button id="confirmCancel" style="background:#e5e7eb; color:#374151; border:1px solid #d1d5db; padding:6px 10px; border-radius:6px; font-weight:700; font-size:12px;">Cancel</button>
                            <button id="confirmOk" style="background:#dc3545; color:#fff; border:none; padding:6px 10px; border-radius:6px; font-weight:700; font-size:12px;">Confirm</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(confirmModal);

            function openConfirm(message, onConfirm) {
                const modal = document.getElementById('confirmModal');
                const msg = document.getElementById('confirmMessage');
                msg.textContent = message || 'Are you sure you want to proceed?';
                modal.style.display = 'block';
                const ok = document.getElementById('confirmOk');
                const cancel = document.getElementById('confirmCancel');
                const close = document.getElementById('confirmClose');
                const cleanup = () => { modal.style.display = 'none'; ok.onclick = null; cancel.onclick = null; close.onclick = null; };
                ok.onclick = () => { cleanup(); onConfirm(); };
                cancel.onclick = cleanup;
                close.onclick = cleanup;
                window.addEventListener('click', function handler(e){ if(e.target === modal){ cleanup(); window.removeEventListener('click', handler); } });
                document.addEventListener('keydown', function escHandler(e){ if(e.key==='Escape'){ cleanup(); document.removeEventListener('keydown', escHandler);} });
            }

            // Bind any link/button with data-confirm
            document.querySelectorAll('[data-confirm]')?.forEach(el => {
                el.addEventListener('click', function(e){
                    const href = this.getAttribute('href');
                    const message = this.getAttribute('data-confirm');
                    if (href) {
                        e.preventDefault();
                        openConfirm(message, () => { window.location.href = href; });
                    }
                });
            });
        });
    </script>
    
    <main>