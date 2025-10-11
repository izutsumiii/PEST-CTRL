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
        
        /* Sidebar layout */
        /* Sidebar wrapper should not push content below the fold */
        .admin-layout { display: contents; }
        
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
        .sidebar .logo { text-align: center; }
        .sidebar .logo a {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            color: #F9F9F9; text-decoration: none; font-weight: 700; padding: 6px 8px;
        }
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
        .sidebar .nav-links a:hover { background: rgba(255, 215, 54, 0.1); color: #FFD736; transform: none; }
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

        /* Content offset */
        main { 
            margin-left: var(--sidebar-width); 
            margin-top: 60px; /* Account for invisible header */
            transition: all 0.3s ease;
        }

        /* Main content user bar (upper-left) */
        .main-userbar { 
            display: flex; align-items: center; gap: 10px; padding: 8px 10px; 
            position: fixed; top: 10px; right: 16px; z-index: 1500;
        }
        .main-userbar .user { display: inline-flex; align-items: center; gap: 10px; cursor: pointer; color: #F9F9F9; }
        .main-userbar .user .avatar { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg, #FFD736, #e6c230); color: #130325; display: flex; align-items: center; justify-content: center; font-weight: 800; }
        .main-userbar .menu { position: relative; }
        .main-userbar .dropdown { position: absolute; top: 120%; left: 0; background:#fff; color:#111827; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.15); display:none; min-width:160px; z-index:2000; }
        .main-userbar .dropdown a { display:block; padding:8px 10px; color:#374151; text-decoration:none; font-size:13px; }
        .main-userbar .dropdown a:hover { background:#f3f4f6; }
        
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
            margin: -40px auto 14px auto; /* top spacing knob A */
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

        /* Compact page titles and list headers for admin pages */
        main > h1 { text-align: center; font-size: 16px; margin: 8px auto 8px auto; /* top spacing knob B */ }

        /* Ensure small, consistent top gap across all common admin pages */
        .page-admin-dashboard .page-header,
        .page-admin-dashboard main > h1,
        .page-admin-customers .page-header,
        .page-admin-customers main > h1,
        .page-admin-sellers .page-header,
        .page-admin-sellers main > h1,
        .page-admin-products .admin-header,
        .page-admin-products .page-header,
        .page-admin-products main > h1,
        .page-admin-categories .admin-header,
        .page-admin-categories .page-header,
        .page-admin-categories main > h1 {
            margin-top: 8px !important;
        }

        /* Fallback: if a page uses a different first block, keep it tight */
        main > *:first-child { margin-top: 8px; }
        .page-admin-sellers main > h1, .page-admin-customers main > h1 { font-size: 56px; line-height: 1.1; }
        .page-admin-dashboard .page-header h1 { font-size: 56px; line-height: 1.1; }
        .admin-content h2 { text-align: center; font-size: 14px; }

        /* Narrow search bar and center it */
        .search-section { display: flex; justify-content: center; }
        .search-form { width: 100%; max-width: 420px; }
    </style>
</head>
<body class="<?php echo 'page-' . strtolower(pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME)); ?>">
    <!-- Full-width Header -->
    <div class="invisible-header">
        <div class="header-left">
            <button class="header-hamburger" onclick="toggleAdminSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            </div>

        <?php if (isLoggedIn() && isAdmin()): ?>
        <div class="header-right">
            <div class="header-user">
                <div class="header-user-info" onclick="toggleAdminHeaderDropdown()">
                    <div class="header-user-avatar"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="header-dropdown" id="adminHeaderDropdown">
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="admin-layout">
        <aside class="sidebar" id="adminSidebar">
            <!-- Logo inside sidebar -->
            <div class="sidebar-logo">
                <a href="admin-dashboard.php">
                    <span class="full-logo barcode-text">PEST-CTRL Admin</span>
                    <span class="short-logo barcode-text">PC</span>
                </a>
            </div>

            <?php if (isLoggedIn() && isAdmin()): ?>
                <!-- User profile section without avatar -->
                <div class="user-profile-section">
                    <div class="user-profile-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>

                <div class="section-title hide-on-collapse">Overview</div>
                <div class="nav-links">
                    <a href="admin-dashboard.php" data-tooltip="Dashboard"><i class="fas fa-tachometer-alt"></i><span class="hide-on-collapse"> Dashboard</span></a>
                </div>

                <div class="section-title hide-on-collapse">Users</div>
                <div class="nav-links">
                    <a href="admin-customers.php" data-tooltip="Customers"><i class="fas fa-shopping-cart"></i><span class="hide-on-collapse"> Customers</span></a>
                    <a href="admin-sellers.php" data-tooltip="Sellers"><i class="fas fa-store"></i><span class="hide-on-collapse"> Sellers</span></a>
                </div>

                <div class="section-title hide-on-collapse">Catalog</div>
                <div class="nav-links">
                    <a href="admin-products.php" data-tooltip="Products"><i class="fas fa-boxes"></i><span class="hide-on-collapse"> Products</span></a>
                    <a href="admin-categories.php" data-tooltip="Categories"><i class="fas fa-tags"></i><span class="hide-on-collapse"> Categories</span></a>
                </div>

                <div class="section-title hide-on-collapse">System</div>
                <div class="nav-links">
                    <a href="admin-settings.php" data-tooltip="Settings"><i class="fas fa-cogs"></i><span class="hide-on-collapse"> Settings</span></a>
                    <a href="admin-reports.php" data-tooltip="Reports"><i class="fas fa-chart-bar"></i><span class="hide-on-collapse"> Reports</span></a>
                    </div>


            <?php else: ?>
                <div class="nav-links" style="margin-top: 12px;">
                    <a href="../login_admin.php"><i class="fas fa-sign-in-alt"></i> Admin Login</a>
                </div>
            <?php endif; ?>
        </aside>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
    
        <script>
            function toggleAdminSidebar() {
                const sidebar = document.getElementById('adminSidebar');
                const main = document.querySelector('main');
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
                localStorage.setItem('adminSidebarCollapsed', isCollapsed);
            }

            function toggleAdminHeaderDropdown() {
                const dropdown = document.getElementById('adminHeaderDropdown');
                dropdown.classList.toggle('show');
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                const headerUser = document.querySelector('.header-user');
                const dropdown = document.getElementById('adminHeaderDropdown');
                
                if (headerUser && !headerUser.contains(event.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // On page load, check if sidebar was collapsed
            document.addEventListener('DOMContentLoaded', () => {
                const sidebar = document.getElementById('adminSidebar');
                const main = document.querySelector('main');
                const barcodeText = document.querySelector('.barcode-text');
                const isCollapsed = localStorage.getItem('adminSidebarCollapsed') === 'true';
                
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