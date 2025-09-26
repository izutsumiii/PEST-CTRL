<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : '';
$userImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'assets/uploads/default-profile.jpg';
?>
<style> 
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        background: linear-gradient(135deg, #1a1c2e 0%, #16181f 100%);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .sidebar.collapsed {
        width: var(--sidebar-width-collapsed);
    }

    .sidebar-link {
        color: #a0a3bd;
        transition: all 0.2s ease;
        border-radius: 8px;
        margin: 4px 16px;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar-link:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }

    .sidebar-link.active {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.1);
    }

    .logo-text {
        background: linear-gradient(45deg, #6b8cff, #8b9fff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        transition: opacity 0.3s ease;
    }

    .main-content {
        margin-left: var(--sidebar-width);
        background-color: #f8f9fa;
        min-height: 100vh;
        padding: 20px;
        transition: all 0.3s ease;
        width: calc(100% - var(--sidebar-width));
    }

    .collapsed ~ .main-content {
        margin-left: var(--sidebar-width-collapsed);
        width: calc(100% - var(--sidebar-width-collapsed));
    }

    .toggle-btn {
        position: absolute;
        right: -15px;
        top: 20px;
        background: white;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        border: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        z-index: 100;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .collapsed .toggle-btn {
        transform: rotate(180deg);
    }

    .collapsed .hide-on-collapse {
        opacity: 0;
        visibility: hidden;
    }

    .collapsed .logo-text {
        opacity: 0;
    }

    .collapsed .profile-info {
        opacity: 0;
    }

    .collapsed .sidebar-link {
        text-align: center;
        padding: 1rem !important;
        margin: 4px 8px;
    }

    .collapsed .sidebar-link i {
        margin: 0 !important;
    }

    .profile-info {
        transition: opacity 0.2s ease;
    }

    .profile-section {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Mobile menu button */
    .mobile-menu-btn {
        display: none;
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1001;
        background: #1a1c2e;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .mobile-menu-btn {
            display: block;
        }

        .sidebar {
            position: fixed;
            left: -100%;
            top: 0;
            bottom: 0;
            transition: 0.3s ease-in-out;
        }
        
        .sidebar.mobile-show {
            left: 0;
        }
        
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
            padding-top: 60px;
        }
        
        .toggle-btn {
            display: none;
        }

        /* Prevent body scroll when menu is open */
        body.menu-open {
            overflow: hidden;
        }

        /* Overlay when menu is open */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .sidebar-overlay.show {
            display: block;
        }
    }
</style>

<button class="mobile-menu-btn" onclick="toggleMobileMenu()">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar-overlay" onclick="toggleMobileMenu()"></div>

<div class="d-flex">
    <nav class="sidebar d-flex flex-column flex-shrink-0 position-fixed">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-chevron-left"></i>
        </button>

        <div class="p-4">
            <h4 class="logo-text fw-bold mb-0">E-Commerce</h4>
            <p class="text-muted small hide-on-collapse">Dashboard</p>
        </div>

        <div class="nav flex-column">
            <?php if ($userType == 'admin'): ?>
                <a href="admin-dashboard.php" class="sidebar-link <?php echo $currentPage == 'admin-dashboard.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-home me-3"></i>
                    <span class="hide-on-collapse">Dashboard</span>
                </a>
                <a href="admin-products.php" class="sidebar-link <?php echo $currentPage == 'admin-products.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-box me-3"></i>
                    <span class="hide-on-collapse">Products</span>
                </a>
                <a href="admin-orders.php" class="sidebar-link <?php echo $currentPage == 'admin-orders.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-shopping-cart me-3"></i>
                    <span class="hide-on-collapse">Orders</span>
                </a>
                <a href="admin-customers.php" class="sidebar-link <?php echo $currentPage == 'admin-customers.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-users me-3"></i>
                    <span class="hide-on-collapse">Customers</span>
                </a>
            <?php elseif ($userType == 'seller'): ?>
                <a href="seller-dashboard.php" class="sidebar-link <?php echo $currentPage == 'seller-dashboard.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-home me-3"></i>
                    <span class="hide-on-collapse">Dashboard</span>
                </a>
                <a href="manage-products.php" class="sidebar-link <?php echo $currentPage == 'manage-products.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-box me-3"></i>
                    <span class="hide-on-collapse">My Products</span>
                </a>
                <a href="sales-analytics.php" class="sidebar-link <?php echo $currentPage == 'sales-analytics.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-chart-line me-3"></i>
                    <span class="hide-on-collapse">Sales Analytics</span>
                </a>
                <a href="view-orders.php" class="sidebar-link <?php echo $currentPage == 'view-orders.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-shopping-cart me-3"></i>
                    <span class="hide-on-collapse">Orders</span>
                </a>
            <?php else: ?>
                <a href="index.php" class="sidebar-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-home me-3"></i>
                    <span class="hide-on-collapse">Home</span>
                </a>
                <a href="products.php" class="sidebar-link <?php echo $currentPage == 'products.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-shopping-bag me-3"></i>
                    <span class="hide-on-collapse">Shop</span>
                </a>
                <a href="cart.php" class="sidebar-link <?php echo $currentPage == 'cart.php' ? 'active' : ''; ?> text-decoration-none p-3">
                    <i class="fas fa-shopping-cart me-3"></i>
                    <span class="hide-on-collapse">Cart</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="profile-section mt-auto p-4">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="d-flex align-items-center">
                    <img src="<?php echo $userImage; ?>" style="height:60px; width:60px; object-fit:cover;" class="rounded-circle" alt="Profile">
                    <div class="ms-3 profile-info">
                        <h6 class="text-white mb-0"><?php echo $userName; ?></h6>
                        <small class="text-muted"><?php echo ucfirst($userType); ?></small>
                        <div class="mt-2">
                            <a href="edit-profile.php" class="text-muted small text-decoration-none">Edit Profile</a>
                            <span class="text-muted mx-1">|</span>
                            <a href="logout.php" class="text-muted small text-decoration-none">Logout</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <a href="login.php" class="btn btn-outline-light btn-sm w-100 mb-2">Login</a>
                    <a href="register.php" class="btn btn-light btn-sm w-100">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="main-content">
        <div class="container-fluid">
            <!-- Content will go here -->

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('collapsed');
    
    // Save state to localStorage
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    const body = document.body;
    
    sidebar.classList.toggle('mobile-show');
    overlay.classList.toggle('show');
    body.classList.toggle('menu-open');
}

// On page load, check if sidebar was collapsed
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.sidebar');
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    // Only apply collapsed state on desktop
    if (window.innerWidth > 768 && isCollapsed) {
        sidebar.classList.add('collapsed');
    }
    
    // Handle window resize
    const handleResize = () => {
        if (window.innerWidth > 768) {
            // Remove mobile specific classes
            document.body.classList.remove('menu-open');
            document.querySelector('.sidebar-overlay').classList.remove('show');
            sidebar.classList.remove('mobile-show');
        }
    };
    
    window.addEventListener('resize', handleResize);
    
    // Close mobile menu when clicking a link
    const mobileLinks = sidebar.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                toggleMobileMenu();
            }
        });
    });
});
</script>
