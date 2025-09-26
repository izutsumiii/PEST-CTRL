<?php
require_once 'includes/header.php';
require_once 'config/database.php';
//this is seller dashboard.php
requireSeller();

$userId = $_SESSION['user_id'];

// Get seller stats - ALL VARIABLES DEFINED HERE
// 1. Total Products
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'active'");
$stmt->execute([$userId]);
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. Total Items Sold
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')");
$stmt->execute([$userId]);
$totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 3. Expected Revenue (all active orders)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')");
$stmt->execute([$userId]);
$expectedRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 4. Unique Orders Count
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) as unique_orders FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')");
$stmt->execute([$userId]);
$uniqueOrders = $stmt->fetch(PDO::FETCH_ASSOC)['unique_orders'] ?? 0;

// 5. Average Order Value
$avgOrderValue = $uniqueOrders > 0 ? $expectedRevenue / $uniqueOrders : 0;

// 6. Pending Revenue - Orders that are pending/processing (payment not yet secured)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status IN ('pending', 'processing')");
$stmt->execute([$userId]);
$pendingRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 7. Paid Revenue - Different logic for different payment methods
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND (
                          (o.payment_method IN ('stripe', 'paypal') AND o.status NOT IN ('cancelled', 'refunded'))
                          OR 
                          (o.payment_method = 'cod' AND o.status = 'delivered')
                      )");
$stmt->execute([$userId]);
$paidRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 8. Shipped Revenue - Revenue from shipped orders (awaiting delivery confirmation)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status = 'shipped'");
$stmt->execute([$userId]);
$shippedRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 9. Delivered Revenue - Only delivered orders (payment fully confirmed)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status = 'delivered'");
$stmt->execute([$userId]);
$confirmedRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 10. COD Pending Revenue - COD orders that are shipped but not delivered
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND o.payment_method = 'cod' 
                      AND o.status IN ('pending', 'processing', 'shipped')");
$stmt->execute([$userId]);
$codPendingRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;


// Get orders by status
$stmt = $pdo->prepare("SELECT o.status, COUNT(*) as count FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? 
                      GROUP BY o.status");
$stmt->execute([$userId]);
$ordersByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to associative array for easier access
$statusCounts = [];
foreach ($ordersByStatus as $status) {
    $statusCounts[$status['status']] = $status['count'];
}

// Get low stock products
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? AND stock_quantity < 10 ORDER BY stock_quantity ASC");
$stmt->execute([$userId]);
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Get recent orders with payment status
$stmt = $pdo->prepare("SELECT o.*, 
                      oi.quantity, 
                      oi.price as item_price,
                      p.name as product_name,
                     p.image_url as product_image,
                      u.username as customer_name,
                      u.email as customer_email,
                      CASE 
                        WHEN o.payment_method = 'cod' AND o.status != 'delivered' THEN 'Pending Payment'
                        WHEN o.payment_method = 'cod' AND o.status = 'delivered' THEN 'Paid (COD)'
                        WHEN o.payment_method = 'stripe' OR o.payment_method = 'paypal' THEN 'Paid Online'
                        ELSE 'Paid'
                      END as payment_status,
                      (oi.price * oi.quantity) as total_amount
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN users u ON o.user_id = u.id
                      WHERE p.seller_id = ? 
                      ORDER BY o.created_at DESC 
                      LIMIT 15");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
$currentDate = date('Y-m-d H:i:s');
// Get sales data for different time periods
// Last 1 Week
$stmt = $pdo->prepare("SELECT 
                      DATE(o.created_at) as date,
                      COALESCE(SUM(oi.price * oi.quantity), 0) as revenue,
                      COUNT(DISTINCT o.id) as orders
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? 
                      AND o.status = 'delivered'
                      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                      AND o.created_at <= NOW()
                      GROUP BY DATE(o.created_at)
                      ORDER BY date ASC");
$stmt->execute([$userId]);
$weeklySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
$weeklyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $found = false;
    foreach ($weeklySales as $sale) {
        if ($sale['date'] === $date) {
            $weeklyData[] = $sale;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $weeklyData[] = [
            'date' => $date,
            'revenue' => '0.00',
            'orders' => '0'
        ];
    }
}
$weeklySales = $weeklyData;
// Last 1 Month
$stmt = $pdo->prepare("SELECT 
                      DATE(o.created_at) as date,
                      COALESCE(SUM(oi.price * oi.quantity), 0) as revenue,
                      COUNT(DISTINCT o.id) as orders
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? 
                      AND o.status = 'delivered'
                      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      AND o.created_at <= NOW()
                      GROUP BY DATE(o.created_at)
                      ORDER BY date ASC");
$stmt->execute([$userId]);
$monthlySalesDaily = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill missing dates for the month
$monthlyData = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $found = false;
    foreach ($monthlySalesDaily as $sale) {
        if ($sale['date'] === $date) {
            $monthlyData[] = $sale;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $monthlyData[] = [
            'date' => $date,
            'revenue' => '0.00',
            'orders' => '0'
        ];
    }
}
$monthlySalesDaily = $monthlyData;

// Last 6 Months - Fixed query with proper month handling
$stmt = $pdo->prepare("SELECT 
                      DATE_FORMAT(o.created_at, '%Y-%m') as month,
                      COALESCE(SUM(oi.price * oi.quantity), 0) as revenue,
                      COUNT(DISTINCT o.id) as orders
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? 
                      AND o.status = 'delivered'
                      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      AND o.created_at <= NOW()
                      GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
                      ORDER BY month ASC");
$stmt->execute([$userId]);
$monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill missing months for 6 months
$sixMonthsData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $found = false;
    foreach ($monthlySales as $sale) {
        if ($sale['month'] === $month) {
            $sixMonthsData[] = $sale;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $sixMonthsData[] = [
            'month' => $month,
            'revenue' => '0.00',
            'orders' => '0'
        ];
    }
}
$monthlySales = $sixMonthsData;

// Last 1 Year - Fixed query
$stmt = $pdo->prepare("SELECT 
                      DATE_FORMAT(o.created_at, '%Y-%m') as month,
                      COALESCE(SUM(oi.price * oi.quantity), 0) as revenue,
                      COUNT(DISTINCT o.id) as orders
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? 
                      AND o.status = 'delivered'
                      AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                      AND o.created_at <= NOW()
                      GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
                      ORDER BY month ASC");
$stmt->execute([$userId]);
$yearlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill missing months for the year
$yearlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-{$i} months"));
    $found = false;
    foreach ($yearlySales as $sale) {
        if ($sale['month'] === $month) {
            $yearlyData[] = $sale;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $yearlyData[] = [
            'month' => $month,
            'revenue' => '0.00',
            'orders' => '0'
        ];
    }
}
$yearlySales = $yearlyData;

// Debug information removed for production to avoid stray characters before DOCTYPE
// Get top selling products
$stmt = $pdo->prepare("SELECT p.name, p.id, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as revenue
                      FROM products p
                      JOIN order_items oi ON p.id = oi.product_id
                      WHERE p.seller_id = ?
                      GROUP BY p.id, p.name
                      ORDER BY total_sold DESC
                      LIMIT 5");
$stmt->execute([$userId]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<?php require_once 'includes/seller_header.php'; ?>
<style>
/* Offset for fixed seller header */
main { margin-top: 140px; }
body { background: linear-gradient(135deg, #130325 0%, #1a0a2e 100%); color: #F9F9F9; font-size: 0.9em; }

h1 {
    color: #F9F9F9;
    font-size: 1.8em;
    margin-bottom: 30px;
    text-align: center;
}

h2 {
    color: #F9F9F9;
    font-size: 1.4em;
    margin-bottom: 20px;
}

h3 {
    color: #F9F9F9;
    font-size: 1.2em;
    margin-bottom: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    text-align: center;
    border-left: 4px solid #007bff;
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

.stat-card.revenue { border-left-color: #28a745; }
.stat-card.paid { border-left-color: #17a2b8; }
.stat-card.pending { border-left-color: #ffc107; }
.stat-card.products { border-left-color: #6f42c1; }
.stat-card.sales { border-left-color: #fd7e14; }
.stat-card.conversion { border-left-color: #e83e8c; }

.stat-value {
    font-size: 2em;
    font-weight: bold;
    margin: 10px 0;
    color: #FFD736;
}

/* Title/labels inside stat cards */
.stat-card h3,
.stat-card p,
.stat-card span,
.stat-card small,
.stat-card .label {
    color: #FFFFFF;
}

.dashboard-sections {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.section {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

.status-cards {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    gap: 10px;
}

.status-card {
    text-align: center;
    padding: 15px;
    border-radius: 8px;
    min-width: 80px;
}

.status-card.pending { background-color: #fff3cd; color: #856404; }
.status-card.processing { background-color: #cce5ff; color: #004085; }
.status-card.shipped { background-color: #d4edda; color: #155724; }
.status-card.delivered { background-color: #d1ecf1; color: #0c5460; }
.status-card.cancelled { background-color: #f8d7da; color: #721c24; }

.status-count {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
}

.alert-badge {
    background-color: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 8px;
    font-size: 0.8em;
    margin-left: 10px;
}

.alert-list {
    max-height: 200px;
    overflow-y: auto;
}

.alert-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    margin-bottom: 5px;
    border-radius: 4px;
}

.alert-item.low-stock { background-color: #fff3cd; }
.alert-item.out-of-stock { background-color: #f8d7da; }

.payment-status.paid { 
    color: #155724; 
    background-color: #d4edda; 
    padding: 3px 8px; 
    border-radius: 4px; 
}

.payment-status.pending { 
    color: #856404; 
    background-color: #fff3cd; 
    padding: 3px 8px; 
    border-radius: 4px; 
}

.order-status {
    padding: 3px 8px;
    border-radius: 4px;
    text-transform: capitalize;
}

.order-status.pending { background-color: #fff3cd; color: #856404; }
.order-status.processing { background-color: #cce5ff; color: #004085; }
.order-status.shipped { background-color: #d4edda; color: #155724; }
.order-status.delivered { background-color: #d1ecf1; color: #0c5460; }
.order-status.cancelled { background-color: #f8d7da; color: #721c24; }

.orders-table-container {
    overflow-x: auto;
    margin-bottom: 15px;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 8px 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.orders-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.product-list {
    max-height: 250px;
    overflow-y: auto;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.rank {
    font-weight: bold;
    color: #666;
    min-width: 30px;
}
.payment-status {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.payment-status.paid { 
    color: #065f46; 
    background-color: #d1fae5; 
}

.payment-status.pending { 
    color: #92400e; 
    background-color: #fef3c7; 
}

.order-status {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    letter-spacing: 0.05em;
}

.order-status.pending { background-color: #dbeafe; color: #1e40af; }
.order-status.processing { background-color: #fef3c7; color: #92400e; }
.order-status.shipped { background-color: #e0e7ff; color: #5b21b6; }
.order-status.delivered { background-color: #d1fae5; color: #065f46; }
.order-status.cancelled { background-color: #fee2e2; color: #991b1b; }

.orders-table tbody tr:hover {
    background-color: #f9fafb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.15s ease-in-out;
}

/* Mobile card hover effect */
@media (max-width: 768px) {
    .mobile-order-card {
        transition: all 0.2s ease-in-out;
    }
    
    .mobile-order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }
}

/* Loading animation for product images */
.product-image-loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.product-info {
    flex-grow: 1;
}

.product-name {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}

.product-stats {
    font-size: 0.9em;
    color: #666;
}

.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s ease;
}

.action-btn:hover {
    background-color: #e9ecef;
    transform: translateY(-2px);
}

.action-btn.primary {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.action-btn.primary:hover {
    background-color: #0056b3;
}

.icon {
    font-size: 1.2em;
}

.chart-table {
    width: 100%;
    border-collapse: collapse;
}

.chart-table th,
.chart-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.chart-table th {
    background-color: #f8f9fa;
}

.view-all, .no-alerts {
    text-align: center;
    margin-top: 15px;
}

.btn-primary {
    background-color: #007bff;
    color: white;
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}

.restock-btn, .view-btn {
    background-color: #28a745;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.8em;
}

.view-btn {
    background-color: #17a2b8;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .dashboard-sections {
        grid-template-columns: 1fr;
    }
    
    .status-cards {
        flex-direction: column;
        gap: 5px;
    }
    
    .action-grid {
        grid-template-columns: 1fr;
    }
}
</style>

     <div class="container mx-auto px-4 py-8">
        <br><br>
                <!-- Key Performance Indicators -->
         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-blue-500" data-aos="fade-up" data-aos-delay="100">
        <h3 class="text-lg font-medium text-gray-600">Expected Revenue</h3>
        <p class="text-3xl font-bold my-2" style="color:#FFD736;">₱<?php echo number_format($expectedRevenue, 2); ?></p>
        <small class="text-gray-500">All active orders</small>
    </div>
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-green-500" data-aos="fade-up" data-aos-delay="200">
        <h3 class="text-lg font-medium text-gray-600">Confirmed Revenue</h3>
        <p class="text-3xl font-bold my-2" style="color:#FFD736;">₱<?php echo number_format($paidRevenue, 2); ?></p>
        <small class="text-gray-500">Payment received/secured</small>
    </div>
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500" data-aos="fade-up" data-aos-delay="300">
        <h3 class="text-lg font-medium text-gray-600">Pending Revenue</h3>
        <p class="text-3xl font-bold my-2" style="color:#FFD736;">₱<?php echo number_format($pendingRevenue + $shippedRevenue, 2); ?></p>
        <small class="text-gray-500">Processing + Shipped orders</small>
    </div>
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-purple-500" data-aos="fade-up" data-aos-delay="400">
        <h3 class="text-lg font-medium text-gray-600">Total Products</h3>
        <p class="text-3xl font-bold text-gray-800 my-2"><?php echo $totalProducts; ?></p>
        <small class="text-gray-500">Active listings</small>
    </div>
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-red-500" data-aos="fade-up" data-aos-delay="500">
        <h3 class="text-lg font-medium text-gray-600">Items Sold</h3>
        <p class="text-3xl font-bold text-gray-800 my-2"><?php echo $totalSales; ?></p>
        <small class="text-gray-500">Total quantity sold</small>
    </div>
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500" data-aos="fade-up" data-aos-delay="600">
        <h3 class="text-lg font-medium text-gray-600">Avg Order Value</h3>
        <p class="text-3xl font-bold my-2" style="color:#FFD736;">₱<?php echo number_format($avgOrderValue, 2); ?></p>
        <small class="text-gray-500"><?php echo $uniqueOrders; ?> orders average</small>
    </div>
</div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Order Status Overview -->
            <div class="bg-white rounded-lg shadow p-6" data-aos="fade-right">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Order Status Overview</h2>
                <div class="grid grid-cols-5 gap-4">
                    <div class="status-card bg-blue-50 rounded-lg p-4 text-center">
                        <span class="text-2xl font-bold text-blue-600 block"><?php echo $statusCounts['pending'] ?? 0; ?></span>
                        <span class="text-gray-600 text-sm">Pending</span>
                    </div>
                    <div class="status-card bg-yellow-50 rounded-lg p-4 text-center">
                        <span class="text-2xl font-bold text-yellow-600 block"><?php echo $statusCounts['processing'] ?? 0; ?></span>
                        <span class="text-gray-600 text-sm">Processing</span>
                    </div>
                    <div class="status-card bg-purple-50 rounded-lg p-4 text-center">
                        <span class="text-2xl font-bold text-purple-600 block"><?php echo $statusCounts['shipped'] ?? 0; ?></span>
                        <span class="text-gray-600 text-sm">Shipped</span>
                    </div>
                    <div class="status-card bg-green-50 rounded-lg p-4 text-center">
                        <span class="text-2xl font-bold text-green-600 block"><?php echo $statusCounts['delivered'] ?? 0; ?></span>
                        <span class="text-gray-600 text-sm">Delivered</span>
                    </div>
                    <div class="status-card bg-red-50 rounded-lg p-4 text-center">
                        <span class="text-2xl font-bold text-red-600 block"><?php echo $statusCounts['cancelled'] ?? 0; ?></span>
                        <span class="text-gray-600 text-sm">Cancelled</span>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-white rounded-lg shadow p-6" data-aos="fade-left">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Low Stock Alert</h2>
                    <?php if (count($lowStockProducts) > 0): ?>
                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full"><?php echo count($lowStockProducts); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (empty($lowStockProducts)): ?>
                    <p class="text-green-600">✅ All products have sufficient stock.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="alert-item flex items-center justify-between p-3 rounded-lg <?php echo $product['stock_quantity'] == 0 ? 'bg-red-50' : 'bg-yellow-50'; ?>">
                                <span class="font-medium"><?php echo htmlspecialchars($product['name']); ?></span>
                                <div class="flex items-center space-x-4">
                                    <span class="<?php echo $product['stock_quantity'] == 0 ? 'text-red-600 font-bold' : 'text-yellow-600'; ?>">
                                        <?php echo $product['stock_quantity']; ?> 
                                        <?php echo $product['stock_quantity'] == 0 ? 'OUT OF STOCK' : 'left'; ?>
                                    </span>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Restock</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sales Trend Chart with Dropdown Controls -->
        <div class="bg-white rounded-lg shadow p-6 mb-8" data-aos="fade-up">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800 mb-2 sm:mb-0">Sales Trend</h2>
                <div class="flex flex-col sm:flex-row gap-2">
                    <!-- Time Period Dropdown -->
                    <div class="relative">
                        <select id="timePeriodSelect" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="week">Last 1 Week</option>
                            <option value="month">Last 1 Month</option>
                            <option value="6months" selected>Last 6 Months</option>
                            <option value="year">Last 1 Year</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Chart Type Dropdown -->
                    <div class="relative">
                        <select id="chartTypeSelect" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="bar">Bar Chart</option>
                            <option value="line">Line Chart</option>
                            <option value="area">Area Chart</option>
                            <option value="mixed" selected>Mixed Chart</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Loading indicator -->
            <div id="chartLoading" class="hidden flex items-center justify-center h-80">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
            
            <!-- Chart container -->
            <div id="chartContainer" class="h-80">
                <canvas id="salesChart"></canvas>
            </div>
            
            <!-- No data message -->
            <div id="noDataMessage" class="hidden text-center py-20">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <p class="text-gray-500">No sales data available for the selected period.</p>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="bg-white rounded-lg shadow p-6 mb-8" data-aos="fade-up">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Top Selling Products</h2>
            <?php if (empty($topProducts)): ?>
                <p class="text-gray-500">No sales data available.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="product-item flex items-center justify-between p-3 rounded-lg hover:bg-gray-50">
                            <div class="flex items-center space-x-4">
                                <span class="bg-gray-100 text-gray-800 font-bold rounded-full w-8 h-8 flex items-center justify-center">#<?php echo $index + 1; ?></span>
                                <div>
                                    <span class="font-medium block"><?php echo htmlspecialchars($product['name']); ?></span>
                                    <span class="text-gray-500 text-sm">
                                        <?php echo $product['total_sold']; ?> sold • 
                                        $<?php echo number_format($product['revenue'], 2); ?> revenue
                                    </span>
                                </div>
                            </div>
                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Orders -->
       <div class="bg-white rounded-lg shadow p-6 mb-8" data-aos="fade-up">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-800">Recent Orders</h2>
        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            <?php echo count($recentOrders); ?> orders
        </span>
    </div>
    
    <?php if (empty($recentOrders)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Recent Orders</h3>
            <p class="text-gray-500">You haven't received any orders yet. Start promoting your products!</p>
            <a href="seller-products.php" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition duration-300">
                Manage Products
            </a>
        </div>
    <?php else: ?>
        <!-- Mobile-friendly card layout for small screens -->
        <div class="block md:hidden space-y-4">
            <?php foreach ($recentOrders as $order): ?>
                <div class="border rounded-lg p-4 bg-gray-50 hover:bg-gray-100 transition duration-200">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="font-mono text-sm font-bold text-blue-600">#<?php echo $order['id']; ?></span>
                            <span class="order-status <?php echo $order['status']; ?> text-xs px-2 py-1 rounded-full">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        <span class="text-sm text-gray-500">
                            <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="mb-2">
                        <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($order['product_name']); ?></h4>
                        <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="text-sm text-gray-600">Quantity: <?php echo $order['quantity']; ?> × ₱<?php echo number_format($order['item_price'], 2); ?></p>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="payment-status <?php echo ($order['payment_method'] == 'cod' && $order['status'] != 'delivered') ? 'pending' : 'paid'; ?> text-xs px-2 py-1 rounded">
                            <?php echo $order['payment_status']; ?>
                        </span>
                        <span class="font-bold text-lg text-gray-800">
                            $<?php echo number_format($order['total_amount'], 2); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop table layout -->
       <div class="hidden md:block">
    <!-- Container with fixed height and scrollbars -->
    <div class="overflow-auto max-h-96 border border-gray-200 rounded-lg shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Order
                    </th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Product
                    </th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Customer
                    </th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Qty/Price
                    </th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Total
                    </th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Payment
                    </th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th scope="col" class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($recentOrders as $order): ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="font-mono text-xs font-bold text-blue-600">#<?php echo $order['id']; ?></span>
                                <?php if (!empty($order['product_sku'])): ?>
                                    <span class="text-xs text-gray-400">SKU: <?php echo htmlspecialchars($order['product_sku']); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td class="px-3 py-2">
                            <div class="flex items-center space-x-2">
                                <?php if (!empty($order['product_image'])): ?>
                                    <img class="w-8 h-8 rounded object-cover" 
                                         src="<?php echo htmlspecialchars($order['product_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                         onerror="this.src='placeholder-product.png'">
                                <?php else: ?>
                                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate max-w-32" title="<?php echo htmlspecialchars($order['product_name']); ?>">
                                        <?php echo htmlspecialchars($order['product_name']); ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                <span class="text-xs text-gray-400"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                            </div>
                        </td>
                        
                        <td class="px-3 py-2 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900"><?php echo $order['quantity']; ?> item(s)</span>
                                <span class="text-xs text-gray-400">$<?php echo number_format($order['item_price'], 2); ?></span>
                            </div>
                        </td>
                        
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span class="font-bold text-sm text-gray-900">
                                $<?php echo number_format($order['total_amount'], 2); ?>
                            </span>
                        </td>
                        
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span class="payment-status <?php echo ($order['payment_method'] == 'cod' && $order['status'] != 'delivered') ? 'pending' : 'paid'; ?> inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                <?php echo $order['payment_status']; ?>
                            </span>
                        </td>
                        
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span class="order-status <?php echo $order['status']; ?> inline-flex px-2 py-1 text-xs font-semibold rounded-full capitalize">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        
                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">
                            <div class="flex flex-col">
                                <span><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                                <span class="text-xs"><?php echo date('g:i A', strtotime($order['created_at'])); ?></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- View All Orders Button -->
    <div class="mt-4 text-center">
        <a href="seller-orders.php" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition duration-300 space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>View All Orders</span>
        </a>
    </div>
</div><?php endif; ?>
</div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6" data-aos="fade-up">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="add-product.php" class="action-btn bg-blue-600 hover:bg-blue-700 text-white rounded-lg p-4 flex items-center space-x-3 transition duration-300">
                    <svg class="feather w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Add New Product</span>
                </a>
                <a href="seller-products.php" class="action-btn bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 rounded-lg p-4 flex items-center space-x-3 transition duration-300">
                    <svg class="feather w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27,6.96 12,12.01 20.73,6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span>Manage Products</span>
                </a>
                <a href="seller-orders.php" class="action-btn bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 rounded-lg p-4 flex items-center space-x-3 transition duration-300">
                    <svg class="feather w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line>
                        <line x1="3" y1="12" x2="3.01" y2="12"></line>
                        <line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    <span>View All Orders</span>
                </a>
                <a href="seller-analytics.php" class="action-btn bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 rounded-lg p-4 flex items-center space-x-3 transition duration-300">
                    <svg class="feather w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Sales Analytics</span>
                </a>
                <a href="seller-customers.php" class="action-btn bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 rounded-lg p-4 flex items-center space-x-3 transition duration-300">
                    <svg class="feather w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Customer Management</span>
                </a>
                <a href="seller-reports.php" class="action-btn bg-white border border-gray-200 hover:bg-gray-50 text-gray-800 rounded-lg p-4 flex items-center space-x-3 transition duration-300">
                    <svg class="feather w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <polyline points="23,6 13.5,15.5 8.5,10.5 1,18"></polyline>
                        <polyline points="17,6 23,6 23,12"></polyline>
                    </svg>
                    <span>Generate Reports</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Initialize AOS animations
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Sales Chart with Real Database Data
       let salesChart = null;
        let chartUpdateInterval = null;
        async function fetchLatestSalesData() {
    try {
        const response = await fetch('ajax/get-sales-data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_sales_data',
                seller_id: <?php echo $userId; ?>
            })
        });
        
        if (response.ok) {
            const data = await response.json();
            return data;
        }
    } catch (error) {
        console.warn('Failed to fetch latest sales data:', error);
    }
    
    // Fallback to current data if fetch fails
    return {
        week: <?php echo json_encode($weeklySales); ?>,
        month: <?php echo json_encode($monthlySalesDaily); ?>,
        '6months': <?php echo json_encode($monthlySales); ?>,
        year: <?php echo json_encode($yearlySales); ?>
    };
}
        // Real data from database for all time periods
        const salesDataSets = {
            week: <?php echo json_encode($weeklySales); ?>,
            month: <?php echo json_encode($monthlySalesDaily); ?>,
            '6months': <?php echo json_encode($monthlySales); ?>,
            year: <?php echo json_encode($yearlySales); ?>
        };

        function formatLabels(data, period) {
    if (!data || data.length === 0) return [];
    
    return data.map(item => {
        let dateStr = item.date || item.month;
        
        switch(period) {
            case 'week':
            case 'month':
                const date = new Date(dateStr);
                const today = new Date();
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                
                // Show "Today" and "Yesterday" for recent dates
                if (date.toDateString() === today.toDateString()) {
                    return 'Today';
                } else if (date.toDateString() === yesterday.toDateString()) {
                    return 'Yesterday';
                } else {
                    return date.toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric' 
                    });
                }
                
            case '6months':
            case 'year':
                const monthDate = new Date(dateStr + '-01');
                const currentMonth = new Date();
                
                if (monthDate.getFullYear() === currentMonth.getFullYear() && 
                    monthDate.getMonth() === currentMonth.getMonth()) {
                    return 'This Month';
                } else {
                    return monthDate.toLocaleDateString('en-US', { 
                        month: 'short', 
                        year: monthDate.getFullYear() !== currentMonth.getFullYear() ? 'numeric' : undefined 
                    });
                }
                
            default:
                return dateStr;
        }
    });
}

                                    function getChartConfig(data, labels, chartType) {
                                const baseConfig = {
                                    data: {
                                        labels: labels,
                                        datasets: []
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        interaction: {
                                            intersect: false,
                                            mode: 'index'
                                        },
                                        plugins: {
                                            legend: {
                                                display: true,
                                                position: 'top',
                                                labels: {
                                                    usePointStyle: true,
                                                    padding: 20
                                                }
                                            },
                                            tooltip: {
                                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                                titleColor: 'white',
                                                bodyColor: 'white',
                                                borderColor: 'rgba(59, 130, 246, 1)',
                                                borderWidth: 1,
                                                callbacks: {
                                                    label: function(context) {
                                                        const label = context.dataset.label || '';
                                                        const value = context.parsed.y;
                                                        
                                                        if (label.includes('Revenue')) {
                                                            return label + ': $' + value.toLocaleString('en-US', {
                                                                minimumFractionDigits: 2,
                                                                maximumFractionDigits: 2
                                                            });
                                                        } else {
                                                            return label + ': ' + value.toLocaleString();
                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        scales: {
                                            y: {
                                                type: 'linear',
                                                display: true,
                                                position: 'left',
                                                title: {
                                                    display: true,
                                                    text: 'Revenue ($)',
                                                    font: {
                                                        weight: 'bold'
                                                    }
                                                },
                                                grid: {
                                                    color: 'rgba(0, 0, 0, 0.1)',
                                                    drawBorder: false
                                                },
                                                ticks: {
                                                    callback: function(value) {
                                                        return '$' + value.toLocaleString();
                                                    }
                                                }
                                            },
                                            x: {
                                                grid: {
                                                    display: false
                                                },
                                                ticks: {
                                                    maxRotation: 45,
                                                    minRotation: 0
                                                }
                                            }
                                        },
                                        elements: {
                                            point: {
                                                radius: 4,
                                                hoverRadius: 8
                                            },
                                            line: {
                                                tension: 0.4
                                            }
                                        },
                                        animation: {
                                            duration: 1000,
                                            easing: 'easeInOutQuart'
                                        }
                                    }
                                };

                                const revenues = data.map(item => parseFloat(item.revenue || 0));
                                const orders = data.map(item => parseInt(item.orders || 0));

                                // Color scheme
                                const colors = {
                                    primary: {
                                        bg: 'rgba(59, 130, 246, 0.1)',
                                        border: 'rgba(59, 130, 246, 1)',
                                        fill: 'rgba(59, 130, 246, 0.3)'
                                    },
                                    secondary: {
                                        bg: 'rgba(16, 185, 129, 0.1)',
                                        border: 'rgba(16, 185, 129, 1)',
                                        fill: 'rgba(16, 185, 129, 0.3)'
                                    }
                                };

                                switch(chartType) {
                                    case 'bar':
                                        baseConfig.type = 'bar';
                                        baseConfig.data.datasets = [
                                            {
                                                label: 'Revenue ($)',
                                                data: revenues,
                                                backgroundColor: colors.primary.fill,
                                                borderColor: colors.primary.border,
                                                borderWidth: 1,
                                                borderRadius: 4,
                                                borderSkipped: false
                                            }
                                        ];
                                        break;

                                    case 'line':
                                        baseConfig.type = 'line';
                                        baseConfig.data.datasets = [
                                            {
                                                label: 'Revenue ($)',
                                                data: revenues,
                                                borderColor: colors.primary.border,
                                                backgroundColor: colors.primary.bg,
                                                borderWidth: 3,
                                                fill: false,
                                                tension: 0.4,
                                                pointBackgroundColor: colors.primary.border,
                                                pointBorderColor: '#ffffff',
                                                pointBorderWidth: 2
                                            }
                                        ];
                                        break;

                                    case 'area':
                                        baseConfig.type = 'line';
                                        baseConfig.data.datasets = [
                                            {
                                                label: 'Revenue ($)',
                                                data: revenues,
                                                borderColor: colors.primary.border,
                                                backgroundColor: colors.primary.fill,
                                                borderWidth: 3,
                                                fill: true,
                                                tension: 0.4,
                                                pointBackgroundColor: colors.primary.border,
                                                pointBorderColor: '#ffffff',
                                                pointBorderWidth: 2
                                            }
                                        ];
                                        break;

                                    case 'mixed':
                                    default:
                                        baseConfig.type = 'bar';
                                        baseConfig.data.datasets = [
                                            {
                                                label: 'Revenue ($)',
                                                data: revenues,
                                                backgroundColor: colors.primary.fill,
                                                borderColor: colors.primary.border,
                                                borderWidth: 1,
                                                borderRadius: 4,
                                                borderSkipped: false,
                                                yAxisID: 'y'
                                            },
                                            {
                                                label: 'Orders',
                                                data: orders,
                                                borderColor: colors.secondary.border,
                                                backgroundColor: colors.secondary.border,
                                                borderWidth: 3,
                                                type: 'line',
                                                yAxisID: 'y1',
                                                fill: false,
                                                tension: 0.4,
                                                pointBackgroundColor: colors.secondary.border,
                                                pointBorderColor: '#ffffff',
                                                pointBorderWidth: 2
                                            }
                                        ];
                                        
                                        // Add second y-axis for mixed chart
                                        baseConfig.options.scales.y1 = {
                                            type: 'linear',
                                            display: true,
                                            position: 'right',
                                            grid: {
                                                drawOnChartArea: false
                                            },
                                            title: {
                                                display: true,
                                                text: 'Orders',
                                                font: {
                                                    weight: 'bold'
                                                }
                                            },
                                            ticks: {
                                                callback: function(value) {
                                                    return value.toLocaleString();
                                                }
                                            }
                                        };
                                        break;
                                }

                                return baseConfig;
                            }

                                async function updateChart(useCachedData = false) {
                            const period = document.getElementById('timePeriodSelect').value;
                            const chartType = document.getElementById('chartTypeSelect').value;
                            
                            // Show loading
                            document.getElementById('chartLoading').classList.remove('hidden');
                            document.getElementById('chartContainer').classList.add('hidden');
                            document.getElementById('noDataMessage').classList.add('hidden');

                            try {
                                // Get fresh data unless using cached data
                                let salesDataSets;
                                if (useCachedData) {
                                    salesDataSets = {
                                        week: <?php echo json_encode($weeklySales); ?>,
                                        month: <?php echo json_encode($monthlySalesDaily); ?>,
                                        '6months': <?php echo json_encode($monthlySales); ?>,
                                        year: <?php echo json_encode($yearlySales); ?>
                                    };
                                } else {
                                    salesDataSets = await fetchLatestSalesData();
                                }

                                const data = salesDataSets[period];
                                
                                setTimeout(() => {
                                    if (!data || data.length === 0) {
                                        // Show no data message
                                        document.getElementById('chartLoading').classList.add('hidden');
                                        document.getElementById('noDataMessage').classList.remove('hidden');
                                        
                                        const periodNames = {
                                            'week': 'last week',
                                            'month': 'last month', 
                                            '6months': 'last 6 months',
                                            'year': 'last year'
                                        };
                                        document.querySelector('#noDataMessage p').textContent = 
                                            `No sales data available for the ${periodNames[period]}.`;
                                        return;
                                    }

                                    // Destroy existing chart
                                    if (salesChart) {
                                        salesChart.destroy();
                                    }

                                    // Format labels based on period
                                    const labels = formatLabels(data, period);
                                    
                                    // Create new chart
                                    const ctx = document.getElementById('salesChart').getContext('2d');
                                    const config = getChartConfig(data, labels, chartType);
                                    salesChart = new Chart(ctx, config);

                                    // Hide loading and show chart
                                    document.getElementById('chartLoading').classList.add('hidden');
                                    document.getElementById('chartContainer').classList.remove('hidden');
                                }, 300);

                            } catch (error) {
                                console.error('Error updating chart:', error);
                                document.getElementById('chartLoading').classList.add('hidden');
                                document.getElementById('noDataMessage').classList.remove('hidden');
                            }
                        }

                        function startAutoUpdate() {
    // Clear existing interval
    if (chartUpdateInterval) {
        clearInterval(chartUpdateInterval);
    }
    
    // Update every 2 minutes if page is visible
    chartUpdateInterval = setInterval(() => {
        if (!document.hidden) {
            updateChart(false); // Use fresh data
        }
    }, 120000); // 2 minutes
}

function stopAutoUpdate() {
    if (chartUpdateInterval) {
        clearInterval(chartUpdateInterval);
        chartUpdateInterval = null;
    }
}

        // Event listeners for dropdowns
        document.getElementById('timePeriodSelect').addEventListener('change', () => updateChart(true));
        document.getElementById('chartTypeSelect').addEventListener('change', () => updateChart(true));

        // Initialize chart with default period (6 months)
        document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoUpdate();
    } else {
        startAutoUpdate();
        // Update chart when page becomes visible
        updateChart(false);
    }
});

        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            });
        });

        // Add click animation to action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Auto-refresh data every 5 minutes (optional)
        setInterval(() => {
            // Only refresh if the page is visible
            if (!document.hidden) {
                location.reload();
            }
        }, 300000); // 5 minutes

        // Add loading states for better UX
        document.addEventListener('DOMContentLoaded', function() {
    updateChart(true); // Use cached data for initial load
    startAutoUpdate(); // Start auto-update
});

        // Status card hover effects
        document.querySelectorAll('.status-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
        window.addEventListener('beforeunload', function() {
    stopAutoUpdate();
    if (salesChart) {
        salesChart.destroy();
    }
});

        // Table row hover effects
        document.querySelectorAll('.orders-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f9fafb';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = 'transparent';
            });
        });
    </script>
</body>
</html>
