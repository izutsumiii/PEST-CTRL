<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

// Apply seller dashboard theme to analytics
echo '<style>
body{background:#130325 !important;}
main{margin-left:240px;}
.section{background:rgba(255,255,255,0.1);padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.3);color:#F9F9F9;backdrop-filter:blur(10px)}
.stat-card{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;padding:16px}
.period-badge{background:rgba(255,215,54,0.12);border:1px solid #FFD736;color:#FFD736;padding:2px 8px;border-radius:999px;font-size:12px;font-weight:600}
.orders-table-container{overflow-x:auto;margin-bottom:15px;border:1px solid rgba(255,255,255,0.2);border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.3);background:rgba(255,255,255,0.05)}
.orders-table{width:100%;border-collapse:collapse;font-size:.875rem}
.orders-table thead{background:rgba(255,255,255,0.1);position:sticky;top:0;z-index:10}
.orders-table th{padding:12px 12px;text-align:left;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#FFD736;border-bottom:2px solid rgba(255,255,255,0.2)}
.orders-table td{padding:12px;border-bottom:1px solid rgba(255,255,255,0.1);color:#F9F9F9}
.orders-table tbody tr{background:rgba(255,255,255,0.03);transition:all .15s ease-in-out}
.orders-table tbody tr:hover{background:#1a0a2e !important;transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.3)}
/* Override header styling */
h1{color:#F9F9F9 !important;font-family:var(--font-primary) !important;font-size:24px !important;font-weight:700 !important;text-align:left !important;margin:0 0 15px 0 !important;padding-left:20px !important;background:none !important;text-shadow:none !important;}
h2{color:#F9F9F9 !important;font-family:var(--font-primary) !important;font-size:24px !important;font-weight:700 !important;text-align:left !important;margin:0 0 15px 0 !important;padding-left:20px !important;}
/* Much more aggressive reduction in top spacing for main content */
main{padding-top:0 !important;margin-top:0px !important;}
.container{margin-top:-20px !important;padding-top:0 !important;}
.py-8{padding-top:0 !important;padding-bottom:2rem !important;}
.mb-8{margin-top:-20px !important;}
</style>';

requireSeller();
$userId = $_SESSION['user_id'];



// Get selected time period from URL parameter
$selectedPeriod = isset($_GET['period']) ? sanitizeInput($_GET['period']) : '6months';
$customStartDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$customEndDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

// Helper function to get date condition based on period
function getPeriodDateCondition($period, $startDate = '', $endDate = '') {
    // Handle custom date range
    if ($period === 'custom' && !empty($startDate) && !empty($endDate)) {
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        return "o.created_at >= '$startDate' AND o.created_at <= '$endDate 23:59:59'";
    }
    
    switch($period) {
        case 'weekly':
            return "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        case 'monthly':
            return "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        case 'yearly':
            return "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        case '6months':
        default:
            return "o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
    }
}

$dateCondition = getPeriodDateCondition($selectedPeriod, $customStartDate, $customEndDate);
// Get top 5 selling products for the selected period
        $stmt = $pdo->prepare("SELECT 
                                  p.name,
                                  SUM(oi.quantity) as total_sold
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              JOIN orders o ON oi.order_id = o.id
                              WHERE p.seller_id = ? 
                              AND o.status NOT IN ('cancelled', 'refunded')
                              AND $dateCondition
                              GROUP BY p.id, p.name
                              ORDER BY total_sold DESC
                              LIMIT 5");
        $stmt->execute([$userId]);
        $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $productNames = [];
        $productSales = [];
        
        if (count($topProducts) > 0) {
            foreach ($topProducts as $product) {
                $productNames[] = $product['name'];
                $productSales[] = $product['total_sold'];
            }
        } else {
            // Default data if no products sold
            $productNames = ['No Sales Yet'];
            $productSales = [0];
        }
// 1. Total Products (unchanged)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'active'");
$stmt->execute([$userId]);
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. Total Items Sold (based on selected period)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded') 
                      AND $dateCondition");
$stmt->execute([$userId]);
$totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 3. Expected Revenue - Based on selected period
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')
                      AND $dateCondition");
$stmt->execute([$userId]);
$expectedRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 4. Unique Orders Count (based on selected period)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) as unique_orders FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')
                      AND $dateCondition");
$stmt->execute([$userId]);
$uniqueOrders = $stmt->fetch(PDO::FETCH_ASSOC)['unique_orders'] ?? 0;

// 5. Average Order Value
$avgOrderValue = $uniqueOrders > 0 ? $expectedRevenue / $uniqueOrders : 0;

// 6. Confirmed Revenue - Based on selected period
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND (
                          (o.payment_method IN ('paypal', 'gcash', 'credit_card', 'debit_card') 
                           AND o.status NOT IN ('cancelled', 'refunded'))
                          OR 
                          (o.payment_method = 'cod' AND o.status = 'delivered')
                      )
                      AND $dateCondition");
$stmt->execute([$userId]);
$confirmedRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 7. Pending Revenue - Based on selected period
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND o.payment_method IN ('cod', 'cash_on_delivery') 
                      AND o.status IN ('pending', 'processing', 'shipped')
                      AND o.status NOT IN ('cancelled', 'refunded', 'delivered')
                      AND $dateCondition");
$stmt->execute([$userId]);
$pendingRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get status counts for selected period
$stmt = $pdo->prepare("SELECT 
                          o.status,
                          COUNT(DISTINCT o.id) as count
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? 
                      AND $dateCondition
                      GROUP BY o.status");
$stmt->execute([$userId]);
$statusResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusCounts = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($statusResults as $status) {
    if (isset($statusCounts[$status['status']])) {
        $statusCounts[$status['status']] = $status['count'];
    }
}

$periodLabels = [
    'weekly' => 'Last 7 Days',
    'monthly' => 'Last Month',
    '6months' => 'Last 6 Months',
    'yearly' => 'Last Year',
    'custom' => !empty($customStartDate) && !empty($customEndDate) ? 
        date('M j', strtotime($customStartDate)) . ' - ' . date('M j, Y', strtotime($customEndDate)) : 
        'Custom Range'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #130325 0%, #1a0a2e 100%);
            min-height: 100vh;
            color: #F9F9F9;
            font-size: 0.9em;
        }
        
        .time-period-selector {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 45px;
        }
        
        .period-dropdown {
            padding: 12px 16px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,215,54,0.3);
            border-radius: 8px;
            color: #F9F9F9;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 160px;
        }
        
        .period-dropdown:hover {
            border-color: #FFD736;
            box-shadow: 0 0 0 3px rgba(255,215,54,0.2);
        }
        
        .period-dropdown:focus {
            outline: none;
            border-color: #FFD736;
            box-shadow: 0 0 0 3px rgba(255,215,54,0.2);
        }
        
        .period-dropdown option {
            background: #1a0a2e;
            color: #F9F9F9;
        }
        
        /* Custom Date Range Picker Styles */
        .custom-date-range {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,215,54,0.3);
            border-radius: 8px;
            padding: 8px;
            transition: all 0.3s ease;
        }
        
        .date-inputs {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .date-input {
            padding: 8px 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,215,54,0.3);
            border-radius: 6px;
            color: #F9F9F9;
            font-size: 13px;
            transition: all 0.3s ease;
            min-width: 100px;
            height: 36px;
        }
        
        /* Custom date input styling */
        .date-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.7;
        }
        
        .date-input::-webkit-datetime-edit-text {
            color: #FFD736;
        }
        
        .date-input::-webkit-datetime-edit-month-field,
        .date-input::-webkit-datetime-edit-day-field,
        .date-input::-webkit-datetime-edit-year-field {
            color: #F9F9F9;
        }
        
        .date-input:focus {
            outline: none;
            border-color: #FFD736;
            box-shadow: 0 0 0 3px rgba(255,215,54,0.2);
            background: rgba(255,255,255,0.15);
        }
        
        .date-input::placeholder {
            color: rgba(249,249,249,0.5);
        }
        
        .date-separator {
            color: #FFD736;
            font-weight: 600;
            font-size: 13px;
        }
        
        .apply-date-btn {
            background: #FFD736;
            color: #130325;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            height: 36px;
        }
        
        .apply-date-btn:hover {
            background: #e6c230;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255,215,54,0.4);
        }
        
        .apply-date-btn:active {
            transform: translateY(0);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .date-inputs {
                flex-direction: column;
                align-items: stretch;
            }
            
            .date-input {
                min-width: auto;
                width: 100%;
            }
            
            .date-separator {
                text-align: center;
            }
        }
        
        .period-info {
            margin-top: 10px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #007bff;
            color: #F9F9F9;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: translateX(-100%);
            transition: transform 0.8s ease;
        }
        
        .stat-card:hover::before {
            transform: translateX(100%);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        
        .stat-value {
            font-size: 2.2em;
            font-weight: bold;
            margin: 15px 0;
            background: linear-gradient(135deg, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .period-badge {
            display: inline-block;
            background: rgba(0, 123, 255, 0.2);
            color: #60a5fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-center mb-8 bg-gradient-to-r from-blue-400 to-purple-600 bg-clip-text text-transparent">
            Sales Analytics
        </h1>

        <!-- Time Period Selector -->
        <div class="time-period-selector" data-aos="fade-down">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold mb-2">Select Time Period</h2>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <!-- Custom Date Range Picker -->
                    <div class="custom-date-range">
                        <div class="date-inputs">
                            <input type="date" id="startDate" class="date-input" 
                                   value="<?php echo $customStartDate; ?>" 
                                   placeholder="Start Date"
                                   title="Start Date">
                            <span class="date-separator">to</span>
                            <input type="date" id="endDate" class="date-input" 
                                   value="<?php echo $customEndDate; ?>" 
                                   placeholder="End Date"
                                   title="End Date">
                            <button type="button" class="apply-date-btn" onclick="applyCustomDateRange()">
                                <i class="fas fa-check"></i> Apply
                            </button>
                        </div>
                    </div>
                    
                    <select id="timePeriodSelector" class="period-dropdown" onchange="changePeriod(this.value)">
                        <option value="weekly" <?php echo $selectedPeriod === 'weekly' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="monthly" <?php echo $selectedPeriod === 'monthly' ? 'selected' : ''; ?>>Last Month</option>
                        <option value="6months" <?php echo $selectedPeriod === '6months' ? 'selected' : ''; ?>>Last 6 Months</option>
                        <option value="yearly" <?php echo $selectedPeriod === 'yearly' ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                    <div class="period-badge">
                        <?php echo $periodLabels[$selectedPeriod]; ?>
                    </div>
                </div>
            </div>
            <div class="period-info">
                Showing data from 
                <?php
                $startDate = '';
                $endDate = date('M j, Y');
                
                switch($selectedPeriod) {
                    case 'weekly':
                        $startDate = date('M j, Y', strtotime('-7 days'));
                        break;
                    case 'monthly':
                        $startDate = date('M j, Y', strtotime('-1 month'));
                        break;
                    case '6months':
                        $startDate = date('M j, Y', strtotime('-6 months'));
                        break;
                    case 'yearly':
                        $startDate = date('M j, Y', strtotime('-1 year'));
                        break;
                    case 'custom':
                        if (!empty($customStartDate) && !empty($customEndDate)) {
                            $startDate = date('M j, Y', strtotime($customStartDate));
                            $endDate = date('M j, Y', strtotime($customEndDate));
                        } else {
                            $startDate = 'Select dates';
                            $endDate = '';
                        }
                        break;
                }
                
                if ($selectedPeriod === 'custom' && empty($customStartDate)) {
                    echo 'Select custom date range';
                } else {
                    echo $startDate . ($endDate ? ' to ' . $endDate : '');
                }
                ?>
            </div>
        </div>

        <!-- Key Performance Indicators -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="stat-card border-l-blue-500" data-aos="fade-up" data-aos-delay="100">
                <h3 class="text-lg font-medium text-gray-300">Expected Revenue</h3>
                <div class="stat-value">₱<?php echo number_format($expectedRevenue, 2); ?></div>
                <small class="text-gray-400">All active orders</small>
                <div class="period-badge"><?php echo $periodLabels[$selectedPeriod]; ?></div>
            </div>
            
            <div class="stat-card border-l-green-500" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-lg font-medium text-gray-300">Confirmed Revenue</h3>
                <div class="stat-value">₱<?php echo number_format($confirmedRevenue, 2); ?></div>
                <small class="text-gray-400">Online payments + COD delivered</small>
                <div class="period-badge"><?php echo $periodLabels[$selectedPeriod]; ?></div>
            </div>
            
            <div class="stat-card border-l-yellow-500" data-aos="fade-up" data-aos-delay="300">
                <h3 class="text-lg font-medium text-gray-300">Pending Revenue</h3>
                <div class="stat-value">₱<?php echo number_format($pendingRevenue, 2); ?></div>
                <small class="text-gray-400">COD orders awaiting delivery</small>
                <div class="period-badge"><?php echo $periodLabels[$selectedPeriod]; ?></div>
            </div>
            
            <div class="stat-card border-l-purple-500" data-aos="fade-up" data-aos-delay="400">
                <h3 class="text-lg font-medium text-gray-300">Total Products</h3>
                <div class="stat-value"><?php echo $totalProducts; ?></div>
                <small class="text-gray-400">Active listings</small>
            </div>
            
            <div class="stat-card border-l-red-500" data-aos="fade-up" data-aos-delay="500">
                <h3 class="text-lg font-medium text-gray-300">Items Sold</h3>
                <div class="stat-value"><?php echo $totalSales; ?></div>
                <small class="text-gray-400">Total quantity sold</small>
                <div class="period-badge"><?php echo $periodLabels[$selectedPeriod]; ?></div>
            </div>
            
            <div class="stat-card border-l-indigo-500" data-aos="fade-up" data-aos-delay="600">
                <h3 class="text-lg font-medium text-gray-300">Avg Order Value</h3>
                <div class="stat-value">$<?php echo number_format($avgOrderValue, 2); ?></div>
                <small class="text-gray-400"><?php echo $uniqueOrders; ?> orders average</small>
                <div class="period-badge"><?php echo $periodLabels[$selectedPeriod]; ?></div>
            </div>
        </div>

        <!-- Order Status Overview -->
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg shadow p-6 mb-8" data-aos="fade-up">
            <h2 class="text-2xl font-bold text-gray-100 mb-6">
                Order Status Overview
                <span class="period-badge ml-3"><?php echo $periodLabels[$selectedPeriod]; ?></span>
            </h2>
            <div class="grid grid-cols-5 gap-4">
                <div class="status-card bg-blue-50 bg-opacity-20 rounded-lg p-4 text-center">
                    <span class="text-3xl font-bold text-blue-400 block"><?php echo $statusCounts['pending']; ?></span>
                    <span class="text-gray-300 text-sm">Pending</span>
                </div>
                <div class="status-card bg-yellow-50 bg-opacity-20 rounded-lg p-4 text-center">
                    <span class="text-3xl font-bold text-yellow-400 block"><?php echo $statusCounts['processing']; ?></span>
                    <span class="text-gray-300 text-sm">Processing</span>
                </div>
                <div class="status-card bg-purple-50 bg-opacity-20 rounded-lg p-4 text-center">
                    <span class="text-3xl font-bold text-purple-400 block"><?php echo $statusCounts['shipped']; ?></span>
                    <span class="text-gray-300 text-sm">Shipped</span>
                </div>
                <div class="status-card bg-green-50 bg-opacity-20 rounded-lg p-4 text-center">
                    <span class="text-3xl font-bold text-green-400 block"><?php echo $statusCounts['delivered']; ?></span>
                    <span class="text-gray-300 text-sm">Delivered</span>
                </div>
                <div class="status-card bg-red-50 bg-opacity-20 rounded-lg p-4 text-center">
                    <span class="text-3xl font-bold text-red-400 block"><?php echo $statusCounts['cancelled']; ?></span>
                    <span class="text-gray-300 text-sm">Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg shadow p-6" data-aos="fade-up" data-aos-delay="100">
                <h2 class="text-2xl font-bold text-gray-100 mb-4">
                    Revenue Trend
                    <span class="period-badge ml-3"><?php echo $periodLabels[$selectedPeriod]; ?></span>
                </h2>
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg shadow p-6" data-aos="fade-up" data-aos-delay="200">
                <h2 class="text-2xl font-bold text-gray-100 mb-4">
                    Top Products
                    <span class="period-badge ml-3"><?php echo $periodLabels[$selectedPeriod]; ?></span>
                </h2>
                <canvas id="productsChart"></canvas>
            </div>
        </div>

        <!-- Detailed Analytics Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg shadow p-6" data-aos="fade-up" data-aos-delay="300">
                <h2 class="text-xl font-bold text-gray-100 mb-4">Payment Methods</h2>
                <canvas id="paymentMethodsChart"></canvas>
            </div>

            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg shadow p-6" data-aos="fade-up" data-aos-delay="400">
                <h2 class="text-xl font-bold text-gray-100 mb-4">Performance Metrics</h2>
                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-5 rounded-lg">
                        <span class="text-gray-300">Conversion Rate</span>
                        <span class="text-xl font-bold text-green-400">
                            <?php echo $totalProducts > 0 ? number_format(($totalSales / $totalProducts) * 100, 1) : 0; ?>%
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-5 rounded-lg">
                        <span class="text-gray-300">Revenue per Product</span>
                        <span class="text-xl font-bold text-blue-400">
                            $<?php echo $totalProducts > 0 ? number_format($expectedRevenue / $totalProducts, 2) : 0; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-5 rounded-lg">
                        <span class="text-gray-300">Items per Order</span>
                        <span class="text-xl font-bold text-purple-400">
                            <?php echo $uniqueOrders > 0 ? number_format($totalSales / $uniqueOrders, 1) : 0; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center p-3 bg-white bg-opacity-5 rounded-lg">
                        <span class="text-gray-300">Fulfillment Rate</span>
                        <span class="text-xl font-bold text-yellow-400">
                            <?php 
                            $totalOrders = array_sum($statusCounts);
                            echo $totalOrders > 0 ? number_format(($statusCounts['delivered'] / $totalOrders) * 100, 1) : 0; 
                            ?>%
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg shadow p-6" data-aos="fade-up" data-aos-delay="500">
                <h2 class="text-xl font-bold text-gray-100 mb-4">Order Status Flow</h2>
                <canvas id="statusFlowChart"></canvas>
            </div>
        </div>

        <!-- Sales Summary Table -->
        <div class="bg-white bg-opacity-10 backdrop-blur-lg rounded-lg shadow p-6 mb-8" data-aos="fade-up" data-aos-delay="600">
            <h2 class="text-2xl font-bold text-gray-100 mb-6">
                Sales Summary
                <span class="period-badge ml-3"><?php echo $periodLabels[$selectedPeriod]; ?></span>
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-600">
                            <th class="pb-3 text-gray-300 font-semibold">Metric</th>
                            <th class="pb-3 text-gray-300 font-semibold text-right">Value</th>
                            <th class="pb-3 text-gray-300 font-semibold text-right">Percentage</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-200">
                        <tr class="border-b border-gray-700 hover:bg-white hover:bg-opacity-5 transition-colors">
                            <td class="py-3">Expected Revenue</td>
                            <td class="py-3 text-right font-bold text-blue-400">$<?php echo number_format($expectedRevenue, 2); ?></td>
                            <td class="py-3 text-right">100%</td>
                        </tr>
                        <tr class="border-b border-gray-700 hover:bg-white hover:bg-opacity-5 transition-colors">
                            <td class="py-3 pl-4">↳ Confirmed</td>
                            <td class="py-3 text-right font-bold text-green-400">$<?php echo number_format($confirmedRevenue, 2); ?></td>
                            <td class="py-3 text-right">
                                <?php echo $expectedRevenue > 0 ? number_format(($confirmedRevenue / $expectedRevenue) * 100, 1) : 0; ?>%
                            </td>
                        </tr>
                        <tr class="border-b border-gray-700 hover:bg-white hover:bg-opacity-5 transition-colors">
                            <td class="py-3 pl-4">↳ Pending</td>
                            <td class="py-3 text-right font-bold text-yellow-400">$<?php echo number_format($pendingRevenue, 2); ?></td>
                            <td class="py-3 text-right">
                                <?php echo $expectedRevenue > 0 ? number_format(($pendingRevenue / $expectedRevenue) * 100, 1) : 0; ?>%
                            </td>
                        </tr>
                        <tr class="border-b border-gray-700 hover:bg-white hover:bg-opacity-5 transition-colors">
                            <td class="py-3">Total Orders</td>
                            <td class="py-3 text-right font-bold"><?php echo number_format($uniqueOrders); ?></td>
                            <td class="py-3 text-right">-</td>
                        </tr>
                        <tr class="border-b border-gray-700 hover:bg-white hover:bg-opacity-5 transition-colors">
                            <td class="py-3">Average Order Value</td>
                            <td class="py-3 text-right font-bold text-purple-400">$<?php echo number_format($avgOrderValue, 2); ?></td>
                            <td class="py-3 text-right">-</td>
                        </tr>
                        <tr class="hover:bg-white hover:bg-opacity-5 transition-colors">
                            <td class="py-3">Total Items Sold</td>
                            <td class="py-3 text-right font-bold text-red-400"><?php echo number_format($totalSales); ?></td>
                            <td class="py-3 text-right">-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Chart.js default configuration
        Chart.defaults.color = '#F9F9F9';
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';

          <?php
     $chartLabels = [];
        $dateFormat = '';
        $groupBy = '';
        
        switch($selectedPeriod) {
            case 'weekly':
                $chartLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                $dateFormat = '%w'; // Day of week (0=Sunday, 1=Monday, etc.)
                $groupBy = 'DAYOFWEEK(o.created_at)';
                break;
            case 'monthly':
                $chartLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                $dateFormat = 'WEEK';
                $groupBy = 'WEEK(o.created_at, 1)';
                break;
            case '6months':
                $chartLabels = ['Month 1', 'Month 2', 'Month 3', 'Month 4', 'Month 5', 'Month 6'];
                $dateFormat = '%Y-%m';
                $groupBy = 'DATE_FORMAT(o.created_at, "%Y-%m")';
                break;
            case 'yearly':
                $chartLabels = ['Jan-Feb', 'Mar-Apr', 'May-Jun', 'Jul-Aug', 'Sep-Oct', 'Nov-Dec'];
                $dateFormat = 'BIMONTH';
                $groupBy = 'FLOOR((MONTH(o.created_at) - 1) / 2)';
                break;
            default:
                $chartLabels = ['Month 1', 'Month 2', 'Month 3', 'Month 4', 'Month 5', 'Month 6'];
                $dateFormat = '%Y-%m';
                $groupBy = 'DATE_FORMAT(o.created_at, "%Y-%m")';
        }
        
        // Get REAL Expected Revenue data grouped by time period
        $stmt = $pdo->prepare("SELECT 
                                  $groupBy as time_group,
                                  COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              JOIN orders o ON oi.order_id = o.id
                              WHERE p.seller_id = ? 
                              AND o.status NOT IN ('cancelled', 'refunded')
                              AND $dateCondition
                              GROUP BY time_group
                              ORDER BY time_group ASC");
        $stmt->execute([$userId]);
        $expectedRevenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get REAL Confirmed Revenue data grouped by time period
        $stmt = $pdo->prepare("SELECT 
                                  $groupBy as time_group,
                                  COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
                              FROM order_items oi 
                              JOIN products p ON oi.product_id = p.id 
                              JOIN orders o ON oi.order_id = o.id
                              WHERE p.seller_id = ? 
                              AND (
                                  (o.payment_method IN ('paypal', 'gcash', 'credit_card', 'debit_card') 
                                   AND o.status NOT IN ('cancelled', 'refunded'))
                                  OR 
                                  (o.payment_method = 'cod' AND o.status = 'delivered')
                              )
                              AND $dateCondition
                              GROUP BY time_group
                              ORDER BY time_group ASC");
        $stmt->execute([$userId]);
        $confirmedRevenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create arrays for chart data
        $expectedDataPoints = array_fill(0, count($chartLabels), 0);
        $confirmedDataPoints = array_fill(0, count($chartLabels), 0);
        
        // Map database results to chart data points
        if ($selectedPeriod === 'weekly') {
            foreach ($expectedRevenueData as $data) {
                $dayOfWeek = $data['time_group'];
                // Convert MySQL day (1=Sunday) to our array index (0=Monday)
                $index = ($dayOfWeek == 1) ? 6 : $dayOfWeek - 2;
                if ($index >= 0 && $index < 7) {
                    $expectedDataPoints[$index] = round($data['revenue'], 2);
                }
            }
            foreach ($confirmedRevenueData as $data) {
                $dayOfWeek = $data['time_group'];
                $index = ($dayOfWeek == 1) ? 6 : $dayOfWeek - 2;
                if ($index >= 0 && $index < 7) {
                    $confirmedDataPoints[$index] = round($data['revenue'], 2);
                }
            }
        } elseif ($selectedPeriod === 'monthly') {
            // For monthly, map weeks to Week 1-4
            $weekMapping = [];
            $weekIndex = 0;
            foreach ($expectedRevenueData as $data) {
                if ($weekIndex < 4) {
                    $expectedDataPoints[$weekIndex] = round($data['revenue'], 2);
                    $weekIndex++;
                }
            }
            $weekIndex = 0;
            foreach ($confirmedRevenueData as $data) {
                if ($weekIndex < 4) {
                    $confirmedDataPoints[$weekIndex] = round($data['revenue'], 2);
                    $weekIndex++;
                }
            }
        } elseif ($selectedPeriod === 'yearly') {
            // For yearly, map to bi-monthly periods
            foreach ($expectedRevenueData as $data) {
                $bimonth = $data['time_group'];
                if ($bimonth >= 0 && $bimonth < 6) {
                    $expectedDataPoints[$bimonth] += round($data['revenue'], 2);
                }
            }
            foreach ($confirmedRevenueData as $data) {
                $bimonth = $data['time_group'];
                if ($bimonth >= 0 && $bimonth < 6) {
                    $confirmedDataPoints[$bimonth] += round($data['revenue'], 2);
                }
            }
        } else {
            // For 6 months, map each month
            $monthIndex = 0;
            foreach ($expectedRevenueData as $data) {
                if ($monthIndex < 6) {
                    $expectedDataPoints[$monthIndex] = round($data['revenue'], 2);
                    $monthIndex++;
                }
            }
            $monthIndex = 0;
            foreach ($confirmedRevenueData as $data) {
                if ($monthIndex < 6) {
                    $confirmedDataPoints[$monthIndex] = round($data['revenue'], 2);
                    $monthIndex++;
                }
            }
        }
        ?>
        
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Expected Revenue',
                    data: [<?php echo implode(',', $expectedDataPoints); ?>],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Confirmed Revenue',
                    data: [<?php echo implode(',', $confirmedDataPoints); ?>],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += '$' + context.parsed.y.toFixed(2);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
        

         const productsCtx = document.getElementById('productsChart').getContext('2d');
        const productsChart = new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($productNames); ?>,
                datasets: [{
                    label: 'Units Sold',
                    data: <?php echo json_encode($productSales); ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)',
                        'rgb(251, 146, 60)',
                        'rgb(34, 197, 94)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Units Sold: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });

        // Payment Methods Chart
       // Payment Methods Chart - REAL DATA
        <?php
        $stmt = $pdo->prepare("SELECT 
                                  o.payment_method,
                                  COUNT(DISTINCT o.id) as order_count
                              FROM orders o 
                              JOIN order_items oi ON o.id = oi.order_id 
                              JOIN products p ON oi.product_id = p.id 
                              WHERE p.seller_id = ? 
                              AND o.status NOT IN ('cancelled', 'refunded')
                              AND $dateCondition
                              GROUP BY o.payment_method
                              ORDER BY order_count DESC");
        $stmt->execute([$userId]);
        $paymentMethodsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $paymentLabels = [];
        $paymentCounts = [];
        $paymentColors = [
            'cod' => 'rgba(251, 191, 36, 0.8)',
            'cash_on_delivery' => 'rgba(251, 191, 36, 0.8)',
            'paypal' => 'rgba(59, 130, 246, 0.8)',
            'gcash' => 'rgba(34, 197, 94, 0.8)',
            'credit_card' => 'rgba(168, 85, 247, 0.8)',
            'debit_card' => 'rgba(236, 72, 153, 0.8)'
        ];
        
        $paymentBorderColors = [
            'cod' => 'rgb(251, 191, 36)',
            'cash_on_delivery' => 'rgb(251, 191, 36)',
            'paypal' => 'rgb(59, 130, 246)',
            'gcash' => 'rgb(34, 197, 94)',
            'credit_card' => 'rgb(168, 85, 247)',
            'debit_card' => 'rgb(236, 72, 153)'
        ];
        
        $bgColors = [];
        $borderColors = [];
        
        if (count($paymentMethodsData) > 0) {
            foreach ($paymentMethodsData as $payment) {
                $method = $payment['payment_method'];
                $displayName = ucwords(str_replace('_', ' ', $method));
                if ($method == 'cod' || $method == 'cash_on_delivery') {
                    $displayName = 'COD';
                }
                $paymentLabels[] = $displayName;
                $paymentCounts[] = $payment['order_count'];
                $bgColors[] = isset($paymentColors[$method]) ? $paymentColors[$method] : 'rgba(156, 163, 175, 0.8)';
                $borderColors[] = isset($paymentBorderColors[$method]) ? $paymentBorderColors[$method] : 'rgb(156, 163, 175)';
            }
        } else {
            $paymentLabels = ['No Data'];
            $paymentCounts = [1];
            $bgColors = ['rgba(156, 163, 175, 0.8)'];
            $borderColors = ['rgb(156, 163, 175)'];
        }
        ?>
        
        const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
        const paymentChart = new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($paymentLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($paymentCounts); ?>,
                    backgroundColor: <?php echo json_encode($bgColors); ?>,
                    borderColor: <?php echo json_encode($borderColors); ?>,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' orders (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Status Flow Chart - REAL DATA (already using real data from $statusCounts)
        const statusFlowCtx = document.getElementById('statusFlowChart').getContext('2d');
        const statusFlowChart = new Chart(statusFlowCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'],
                datasets: [{
                    data: [
                        <?php echo $statusCounts['pending']; ?>,
                        <?php echo $statusCounts['processing']; ?>,
                        <?php echo $statusCounts['shipped']; ?>,
                        <?php echo $statusCounts['delivered']; ?>,
                        <?php echo $statusCounts['cancelled']; ?>
                    ],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(251, 191, 36)',
                        'rgb(168, 85, 247)',
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' orders (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Function to change time period
        function changePeriod(period) {
            document.getElementById('loadingOverlay').classList.add('show');
            document.body.style.opacity = '0.7';
            
            setTimeout(() => {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('period', period);
                // Remove custom date parameters when switching to predefined periods
                currentUrl.searchParams.delete('start_date');
                currentUrl.searchParams.delete('end_date');
                window.location.href = currentUrl.toString();
            }, 500);
        }
        
        // Function to apply custom date range
        function applyCustomDateRange() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates.');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be later than end date.');
                return;
            }
            
            document.getElementById('loadingOverlay').classList.add('show');
            document.body.style.opacity = '0.7';
            
            setTimeout(() => {
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('period', 'custom');
                currentUrl.searchParams.set('start_date', startDate);
                currentUrl.searchParams.set('end_date', endDate);
                window.location.href = currentUrl.toString();
            }, 500);
        }
        
        // Function to format date display
        function formatDateDisplay(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const day = date.getDate();
            const weekNumber = Math.ceil((date.getDate() - date.getDay() + 1) / 7);
            return `${day} W${weekNumber}`;
        }
        
        // Update date inputs on page load
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            
            // Set default dates if not already set
            if (!startDateInput.value) {
                const today = new Date();
                const sixMonthsAgo = new Date(today.getFullYear(), today.getMonth() - 6, today.getDate());
                startDateInput.value = sixMonthsAgo.toISOString().split('T')[0];
            }
            
            if (!endDateInput.value) {
                const today = new Date();
                endDateInput.value = today.toISOString().split('T')[0];
            }
        });
        

        // Enhanced hover effects for stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
                this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
                this.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            });
        });

        // Status card hover effects
        document.querySelectorAll('.status-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.1)';
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.zIndex = '1';
            });
        });

        // Smooth dropdown animation
        const dropdown = document.getElementById('timePeriodSelector');
        dropdown.addEventListener('focus', function() {
            this.style.transform = 'scale(1.05)';
        });
        
        dropdown.addEventListener('blur', function() {
            this.style.transform = 'scale(1)';
        });

        // Hide loading overlay when page loads
        window.addEventListener('load', function() {
            document.getElementById('loadingOverlay').classList.remove('show');
            document.body.style.opacity = '1';
        });

        // Add keyboard navigation for dropdown
        dropdown.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                this.click();
            }
        });
    </script>
</body>
</html>