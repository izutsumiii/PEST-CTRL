<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';
//this is seller dashboard.php
requireSeller();

$userId = $_SESSION['user_id'];

// 1. Total Products (unchanged)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'active'");
$stmt->execute([$userId]);
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. Total Items Sold (unchanged)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')");
$stmt->execute([$userId]);
$totalSales = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 3. Expected Revenue - All active orders (unchanged)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')");
$stmt->execute([$userId]);
$expectedRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 4. Unique Orders Count (unchanged)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT o.id) as unique_orders FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ? AND o.status NOT IN ('cancelled', 'refunded')");
$stmt->execute([$userId]);
$uniqueOrders = $stmt->fetch(PDO::FETCH_ASSOC)['unique_orders'] ?? 0;

// 5. Average Order Value (unchanged)
$avgOrderValue = $uniqueOrders > 0 ? $expectedRevenue / $uniqueOrders : 0;

// 6. FIXED: Confirmed Revenue - Payment already received/secured
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND (
                          -- Online payments are secured immediately when not cancelled/refunded
                          (o.payment_method IN ('paypal', 'gcash', 'credit_card', 'debit_card') 
                           AND o.status NOT IN ('cancelled', 'refunded'))
                          OR 
                          -- COD payments are secured only when delivered
                          (o.payment_method = 'cod' AND o.status = 'delivered')
                      )");
$stmt->execute([$userId]);
$confirmedRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 7. FIXED: Pending Revenue - COD orders not yet delivered
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND o.payment_method IN ('cod', 'cash_on_delivery') 
                      AND o.status IN ('pending', 'processing', 'shipped')
                      AND o.status NOT IN ('cancelled', 'refunded', 'delivered')");
$stmt->execute([$userId]);
$pendingRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 8. FIXED: Online Payment Revenue (already secured - all non-cancelled online payments)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND o.payment_method IN ('paypal', 'gcash', 'credit_card', 'debit_card')
                      AND o.status NOT IN ('cancelled', 'refunded')");
$stmt->execute([$userId]);
$onlinePaymentRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 9. FIXED: COD Delivered Revenue (payment actually received)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND o.payment_method IN ('cod', 'cash_on_delivery') 
                      AND o.status = 'delivered'");
$stmt->execute([$userId]);
$codDeliveredRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 10. ADDITIONAL: COD Pending Revenue (for detailed breakdown)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(oi.price * oi.quantity), 0) as total FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? 
                      AND o.payment_method = 'cod' 
                      AND o.status IN ('pending', 'processing', 'shipped')
                      AND o.status NOT IN ('cancelled', 'refunded', 'delivered')");
$stmt->execute([$userId]);
$codPendingRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 11. ADDITIONAL: Get order status counts for dashboard
// 11. ADDITIONAL: Get order status counts for dashboard (FIXED - includes cancelled)
$stmt = $pdo->prepare("SELECT 
                          o.status,
                          COUNT(DISTINCT o.id) as count
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE p.seller_id = ?
                      GROUP BY o.status");
$stmt->execute([$userId]);
$statusResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert to associative array for easier access
$statusCounts = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0,
    'refunded' => 0
];
$stmt->execute([$userId]);



foreach ($statusResults as $status) {
    $statusCounts[$status['status']] = $status['count'];
}

// Get low stock products
$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? AND stock_quantity < 10 ORDER BY stock_quantity ASC");
$stmt->execute([$userId]);
$lowStockProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Validation: Confirm revenue calculation accuracy
$calculatedExpected = $confirmedRevenue + $pendingRevenue;
$difference = abs($expectedRevenue - $calculatedExpected);

// Log any significant discrepancies (for debugging)
if ($difference > 0.01) {
                error_log("Revenue calculation discrepancy detected: Expected: {$expectedRevenue}, Calculated: {$calculatedExpected}, Difference: {$difference}");
}

// Additional validation: Ensure confirmed revenue components add up correctly
$calculatedConfirmed = $onlinePaymentRevenue + $codDeliveredRevenue;
$confirmedDifference = abs($confirmedRevenue - $calculatedConfirmed);

if ($confirmedDifference > 0.01) {
    error_log("Confirmed revenue calculation discrepancy: Confirmed: $confirmedRevenue, Calculated: $calculatedConfirmed, Difference: $confirmedDifference");
}

// Debug information (remove this in production)
$debugInfo = [
    'expected_revenue' => $expectedRevenue,
    'confirmed_revenue' => $confirmedRevenue,
    'pending_revenue' => $pendingRevenue,
    'online_payment_revenue' => $onlinePaymentRevenue,
    'cod_delivered_revenue' => $codDeliveredRevenue,
    'cod_pending_revenue' => $codPendingRevenue,
    'calculation_check' => $calculatedExpected,
    'difference' => $difference,
    'confirmed_calculation_check' => $calculatedConfirmed,
    'confirmed_difference' => $confirmedDifference
];

// Uncomment for debugging
// echo "<!-- Debug Revenue Info: " . json_encode($debugInfo, JSON_PRETTY_PRINT) . " -->";

// Get recent orders with enhanced payment status
$stmt = $pdo->prepare("SELECT o.*, 
                      oi.quantity, 
                      oi.price as item_price,
                      p.name as product_name,
                      p.image_url as product_image,
                      p.sku as product_sku,
                      u.username as customer_name,
                      u.email as customer_email,
                      CASE 
                        -- COD Payment Status Logic
                        WHEN o.payment_method IN ('cod', 'cash_on_delivery') AND o.status = 'delivered' THEN 'Payment Received (COD)'
                        WHEN o.payment_method IN ('cod', 'cash_on_delivery') AND o.status = 'shipped' THEN 'Awaiting Delivery (COD)'
                        WHEN o.payment_method IN ('cod', 'cash_on_delivery') AND o.status IN ('pending', 'processing') THEN 'Pending Payment (COD)'
                        
                        -- Online Payment Status Logic
                        WHEN o.payment_method = 'paypal' AND o.status NOT IN ('cancelled', 'refunded') THEN 'Paid via PayPal'
                        WHEN o.payment_method = 'gcash' AND o.status NOT IN ('cancelled', 'refunded') THEN 'Paid via GCash'
                        WHEN o.payment_method = 'credit_card' AND o.status NOT IN ('cancelled', 'refunded') THEN 'Paid via Credit Card'
                        WHEN o.payment_method = 'debit_card' AND o.status NOT IN ('cancelled', 'refunded') THEN 'Paid via Debit Card'
                        
                        -- Fallback cases
                        WHEN o.status IN ('cancelled', 'refunded') THEN 'Payment Cancelled/Refunded'
                        ELSE 'Payment Status Unknown'
                      END as payment_status,
                      (oi.price * oi.quantity) as total_amount,
                      -- Add revenue status for clarity
                      CASE 
                    WHEN o.payment_method IN ('paypal', 'gcash', 'credit_card', 'debit_card') 
                        AND o.status NOT IN ('cancelled', 'refunded') THEN 'confirmed'
                    WHEN o.payment_method IN ('cod', 'cash_on_delivery') AND o.status = 'delivered' THEN 'confirmed'
                    WHEN o.payment_method IN ('cod', 'cash_on_delivery') AND o.status IN ('pending', 'processing', 'shipped') THEN 'pending'
                        ELSE 'cancelled'
                      END as revenue_status
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN users u ON o.user_id = u.id
                      WHERE p.seller_id = ? 
                      ORDER BY o.created_at DESC 
                      LIMIT 15");
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Rest of your existing code for sales data, top products, etc...
// [Sales data queries remain the same as in your original code]

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

// Get top selling products

// Determine selected time period for top products
$selectedPeriod = isset($_GET['period']) ? sanitizeInput($_GET['period']) : '6months';
switch ($selectedPeriod) {
    case 'weekly':
        $productDateCondition = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'monthly':
        $productDateCondition = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'yearly':
        $productDateCondition = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    case '6months':
    default:
        $productDateCondition = "o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        break;
}

$stmt = $pdo->prepare("SELECT p.name, p.id, SUM(oi.quantity) as total_sold, SUM(oi.price * oi.quantity) as revenue,
       GROUP_CONCAT(DISTINCT o.payment_method ORDER BY o.payment_method SEPARATOR ', ') as payment_methods
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    JOIN orders o ON oi.order_id = o.id
    WHERE p.seller_id = ? AND o.status = 'delivered' AND $productDateCondition
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 5");
$stmt->execute([$userId]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<style>
/* Base Styles */
body { 
    background: linear-gradient(135deg, #130325 0%, #1a0a2e 100%); 
    color: #F9F9F9; 
    font-size: 0.9em; 
}

main { 
    margin-top: 140px; 
}

/* Typography */
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

/* Stat Cards */
.stat-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    text-align: center;
    border-left: 4px solid #007bff;
    color: #F9F9F9;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
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

.stat-card h3,
.stat-card p,
.stat-card span,
.stat-card small,
.stat-card .label {
    color: #FFFFFF;
}

/* Section Containers */
.section {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

/* Status Cards */
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
    transition: transform 0.3s ease;
}

.status-card:hover {
    transform: scale(1.05);
}

.status-card.pending { background-color: #dbeafe; color: #1e40af; }
.status-card.processing { background-color: #fef3c7; color: #92400e; }
.status-card.shipped { background-color: #e0e7ff; color: #5b21b6; }
.status-card.delivered { background-color: #d1fae5; color: #065f46; }
.status-card.cancelled { background-color: #fee2e2; color: #991b1b; }

.status-count {
    display: block;
    font-size: 1.5em;
    font-weight: bold;
}

/* Alert Components */
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

/* Payment Status */
.payment-status {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 3px 8px;
    border-radius: 4px;
    display: inline-block;
}

.payment-status.paid { 
    color: #065f46; 
    background-color: #d1fae5; 
}

.payment-status.pending { 
    color: #92400e; 
    background-color: #fef3c7; 
}

/* Order Status */
.order-status {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
    letter-spacing: 0.05em;
    padding: 3px 8px;
    border-radius: 4px;
    display: inline-block;
}

.order-status.pending { background-color: #dbeafe; color: #1e40af; }
.order-status.processing { background-color: #fef3c7; color: #92400e; }
.order-status.shipped { background-color: #e0e7ff; color: #5b21b6; }
.order-status.delivered { background-color: #d1fae5; color: #065f46; }
.order-status.cancelled { background-color: #fee2e2; color: #991b1b; }

/* Tables */
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
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.orders-table th {
    background-color: rgba(255, 255, 255, 0.05);
    font-weight: bold;
    color: #F9F9F9;
}

.orders-table tbody tr {
    transition: all 0.15s ease-in-out;
}

.orders-table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
}

/* Product List */
.product-list {
    max-height: 250px;
    overflow-y: auto;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.product-info {
    flex-grow: 1;
}

.product-name {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #F9F9F9;
}

.product-stats {
    font-size: 0.9em;
    color: rgba(255, 255, 255, 0.7);
}

.rank {
    font-weight: bold;
    color: #FFD736;
    min-width: 30px;
}

/* Buttons */
.btn-primary,
.restock-btn,
.view-btn {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-primary:hover {
    background-color: #0056b3;
}

.restock-btn {
    background-color: #28a745;
    color: white;
    padding: 4px 8px;
    font-size: 0.8em;
}

.restock-btn:hover {
    background-color: #218838;
}

.view-btn {
    background-color: #17a2b8;
    color: white;
    padding: 4px 8px;
    font-size: 0.8em;
}

.view-btn:hover {
    background-color: #138496;
}

/* Loading Animation */
.product-image-loading {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

@keyframes ripple {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .status-cards {
        flex-direction: column;
        gap: 5px;
    }
    
    .mobile-order-card {
        transition: all 0.2s ease-in-out;
    }
    
    .mobile-order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
    }
}
/* Recent Orders Section */
.section {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

/* Recent Orders Table Styles */
.orders-table-container {
    overflow-x: auto;
    margin-bottom: 15px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    background: rgba(255, 255, 255, 0.05);
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.orders-table thead {
    background: rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 10;
}

.orders-table th {
    padding: 12px 12px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #FFD736;
    border-bottom: 2px solid rgba(255, 255, 255, 0.2);
}

.orders-table td {
    padding: 12px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
}

.orders-table tbody tr {
    background: rgba(255, 255, 255, 0.03);
    transition: all 0.15s ease-in-out;
}

.orders-table tbody tr:hover {
    background: #1a0a2e !important; /* darker purple hover */
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}
.orders-table tbody tr:hover td,
.orders-table tbody tr:hover span,
.orders-table tbody tr:hover a {
    color: #F9F9F9 !important; /* keep text readable */
}

/* Order ID and Product Info */
.orders-table .font-mono {
    color: #17a2b8;
    font-weight: 700;
}

.orders-table .text-gray-400 {
    color: rgba(255, 255, 255, 0.5);
}

.orders-table .text-gray-500 {
    color: rgba(255, 255, 255, 0.6);
}

.orders-table .text-gray-900 {
    color: #F9F9F9;
}

/* Product Image */
.orders-table img {
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

/* Payment Status Badges */
.payment-status {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 4px 10px;
    border-radius: 4px;
    display: inline-block;
    white-space: nowrap;
}

.payment-status.paid { 
    color: #065f46; 
    background-color: #d1fae5;
    border: 1px solid #10b981;
}

.payment-status.pending { 
    color: #92400e; 
    background-color: #fef3c7;
    border: 1px solid #f59e0b;
}

/* Order Status Badges */
.order-status {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: capitalize;
    letter-spacing: 0.05em;
    padding: 4px 10px;
    border-radius: 4px;
    display: inline-block;
    white-space: nowrap;
}

.order-status.pending { 
    background-color: #dbeafe; 
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.order-status.processing { 
    background-color: #fef3c7; 
    color: #92400e;
    border: 1px solid #f59e0b;
}

.order-status.shipped { 
    background-color: #e0e7ff; 
    color: #5b21b6;
    border: 1px solid #8b5cf6;
}

.order-status.delivered { 
    background-color: #d1fae5; 
    color: #065f46;
    border: 1px solid #10b981;
}

.order-status.cancelled { 
    background-color: #fee2e2; 
    color: #991b1b;
    border: 1px solid #ef4444;
}

/* Empty State */
.orders-table + div .text-center {
    padding: 48px 24px;
}

.orders-table + div svg {
    color: rgba(255, 255, 255, 0.4);
}

.orders-table + div h3 {
    color: #F9F9F9;
}

.orders-table + div p {
    color: rgba(255, 255, 255, 0.6);
}

/* View All Orders Button */
.orders-table ~ .mt-4 a {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 123, 255, 0.3);
}

.orders-table ~ .mt-4 a:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 123, 255, 0.4);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .orders-table th,
    .orders-table td {
        padding: 8px 6px;
        font-size: 0.75rem;
    }
    
    .orders-table th {
        font-size: 0.7rem;
    }
    
    .payment-status,
    .order-status {
        font-size: 0.65rem;
        padding: 3px 8px;
    }
    
    .mobile-order-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s ease-in-out;
    }
    
    .mobile-order-card:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
    }
}

/* Scrollbar Styling for Dark Theme */
.orders-table-container::-webkit-scrollbar {
    height: 8px;
}

.orders-table-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.orders-table-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.orders-table-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-white mb-8">Seller Dashboard</h1>

                <!-- Key Performance Indicators -->
         <!-- Replace the KPI section with this corrected version -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-blue-500" data-aos="fade-up" data-aos-delay="100">
        <h3 class="text-lg font-medium text-gray-600">Expected Revenue</h3>
        <p class="text-3xl font-bold text-gray-800 my-2">₱<?php echo number_format($expectedRevenue, 2); ?></p>
        <small class="text-gray-500">All active orders</small>
    </div>
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-green-500" data-aos="fade-up" data-aos-delay="200">
        <h3 class="text-lg font-medium text-gray-600">Confirmed Revenue</h3>
        <p class="text-3xl font-bold text-gray-800 my-2">₱<?php echo number_format($confirmedRevenue, 2); ?></p>
        <small class="text-gray-500">Online payments + COD delivered</small>
    </div>
    <div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500" data-aos="fade-up" data-aos-delay="300">
        <h3 class="text-lg font-medium text-gray-600">Pending Revenue</h3>
        <p class="text-3xl font-bold text-gray-800 my-2">₱<?php echo number_format($pendingRevenue, 2); ?></p>
        <small class="text-gray-500">COD orders awaiting delivery</small>
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
        <p class="text-3xl font-bold text-gray-800 my-2">₱<?php echo number_format($avgOrderValue, 2); ?></p>
        <small class="text-gray-500"><?php echo $uniqueOrders; ?> orders average</small>
    </div>
</div>

<!-- Optional: Add a breakdown section for more detailed revenue tracking -->
<div class="stat-card bg-white rounded-lg shadow p-6 border-l-4 border-green-500 mb-8" data-aos="fade-up">
    <h2 class="text-lg font-bold text-white mb-4">Revenue Breakdown</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="stat-card paid bg-green-50 rounded-lg shadow p-4 border-l-4 border-green-500">
            <h3 class="text-sm font-medium text-green-800 mb-1">Online Payments (Secured)</h3>
            <p class="stat-value text-green-600">₱<?php echo number_format($onlinePaymentRevenue, 2); ?></p>
            <small class="text-green-700">Gcash, PayPal, Credit Card, Debit Card</small>
        </div>
        <div class="stat-card paid bg-blue-50 rounded-lg shadow p-4 border-l-4 border-blue-500">
            <h3 class="text-sm font-medium text-blue-800 mb-1">COD Delivered</h3>
            <p class="stat-value text-blue-600">₱<?php echo number_format($codDeliveredRevenue, 2); ?></p>
            <small class="text-blue-700">Cash payments received</small>
        </div>
    </div>
</div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Order Status Overview -->
            <div class="section" data-aos="fade-right">
                <h2 class="text-xl font-bold mb-4">Order Status Overview</h2>
                <div class="status-cards">
                    <div class="status-card pending">
                        <span class="status-count"><?php echo $statusCounts['pending'] ?? 0; ?></span>
                        <span>Pending</span>
                    </div>
                    <div class="status-card processing">
                        <span class="status-count"><?php echo $statusCounts['processing'] ?? 0; ?></span>
                        <span>Processing</span>
                    </div>
                    <div class="status-card shipped">
                        <span class="status-count"><?php echo $statusCounts['shipped'] ?? 0; ?></span>
                        <span>Shipped</span>
                    </div>
                    <div class="status-card delivered">
                        <span class="status-count"><?php echo $statusCounts['delivered'] ?? 0; ?></span>
                        <span>Delivered</span>
                    </div>
                    <div class="status-card cancelled">
                        <span class="status-count"><?php echo $statusCounts['cancelled'] ?? 0; ?></span>
                        <span>Cancelled</span>
                    </div>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="section" data-aos="fade-left">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold">Low Stock Alert</h2>
                    <?php if (count($lowStockProducts) > 0): ?>
                        <span class="alert-badge"><?php echo count($lowStockProducts); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (empty($lowStockProducts)): ?>
                    <p class="text-green-600">✅ All products have sufficient stock.</p>
                <?php else: ?>
                    <div class="alert-list">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <div class="alert-item <?php echo $product['stock_quantity'] == 0 ? 'out-of-stock' : 'low-stock'; ?>">
                                <span class="font-medium"><?php echo htmlspecialchars($product['name']); ?></span>
                                <div class="flex items-center space-x-4">
                                    <span class="<?php echo $product['stock_quantity'] == 0 ? 'text-red-600 font-bold' : 'text-yellow-600'; ?>">
                                        <?php echo $product['stock_quantity']; ?> 
                                        <?php echo $product['stock_quantity'] == 0 ? 'OUT OF STOCK' : 'left'; ?>
                                    </span>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="restock-btn">Restock</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sales Trend Chart with Dropdown Controls -->
        <div class="section mb-8" data-aos="fade-up">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
                <h2 class="text-xl font-bold mb-2 sm:mb-0">Sales Trend</h2>
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
        <div class="section mb-8" data-aos="fade-up">
            <h2 class="text-xl font-bold mb-4">Top Selling Products</h2>
            <?php if (empty($topProducts)): ?>
            <p class="text-gray-500">No sales data available.</p>
            <?php else: ?>
            <div class="product-list">
                <?php foreach ($topProducts as $index => $product): ?>
                <div class="product-item">
                    <span class="rank">#<?php echo $index + 1; ?></span>
                    <div class="product-info">
                    <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                    <span class="product-stats">
                        <?php echo $product['total_sold']; ?> sold • 
                        ₱<?php echo number_format($product['revenue'], 2); ?> revenue
                    </span>
                    <?php
                    $methodLabels = [
                        'gcash' => 'GCash',
                        'paypal' => 'PayPal',
                        'credit_card' => 'Credit Card',
                        'debit_card' => 'Debit Card',
                        'cod' => 'Cash on Delivery',
                    ];
                    $methods = array_filter(array_map('trim', explode(',', $product['payment_methods'])));
                    $displayMethods = array_map(function($m) use ($methodLabels) {
                        return $methodLabels[$m] ?? ucfirst($m);
                    }, $methods);
                    ?>
                    <span class="text-xs text-gray-400 block mt-1">
                        Payment Methods Used: <?php echo implode(', ', $displayMethods); ?>
                    </span>
                    </div>
                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="view-btn">View</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

       <!-- Recent Orders -->
<div class="section mb-8" data-aos="fade-up">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-white">Recent Orders</h2>
        <span class="bg-blue-600 bg-opacity-20 text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded-full border border-blue-500">
            <?php echo count($recentOrders); ?> orders
        </span>
    </div>

    <?php if (empty($recentOrders)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
            </svg>
            <h3 class="text-lg font-medium text-white mb-2">No Recent Orders</h3>
            <p class="text-gray-300">You haven't received any orders yet. Start promoting your products!</p>
        </div>
    <?php else: ?>
        <!-- Responsive Recent Orders List -->
        <div class="orders-table-container overflow-auto max-h-96 border border-gray-700 rounded-lg shadow-lg">
            <table class="orders-table min-w-full divide-y divide-gray-700 text-sm">
                <thead class="bg-gray-800 bg-opacity-50 sticky top-0">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Order</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Product</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Customer</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Qty/Price</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Total</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Payment</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-yellow-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-transparent divide-y divide-gray-700">
                    <?php foreach ($recentOrders as $order): ?>
                        <tr class="transition duration-150">
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-mono text-xs font-bold text-cyan-400">#<?php echo $order['id']; ?></span>
                                    <?php if (!empty($order['product_sku'])): ?>
                                        <span class="text-xs text-gray-400">SKU: <?php echo htmlspecialchars($order['product_sku']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center space-x-2">
                                    <?php if (!empty($order['product_image'])): ?>
                                        <img class="w-8 h-8 rounded object-cover border border-gray-600" 
                                             src="<?php echo htmlspecialchars($order['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['product_name']); ?>"
                                             onerror="this.src='placeholder-product.png'">
                                    <?php else: ?>
                                        <div class="w-8 h-8 bg-gray-700 rounded flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate max-w-32" title="<?php echo htmlspecialchars($order['product_name']); ?>">
                                            <?php echo htmlspecialchars($order['product_name']); ?>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-white"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                    <span class="text-xs text-gray-400"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-white"><?php echo $order['quantity']; ?> item(s)</span>
                                    <span class="text-xs text-gray-400">₱<?php echo number_format($order['item_price'], 2); ?></span>
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="font-bold text-sm text-yellow-400">
                                    ₱<?php echo number_format($order['total_amount'], 2); ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="payment-status <?php echo ($order['payment_method'] == 'cod' && $order['status'] != 'delivered') ? 'pending' : 'paid'; ?> inline-flex px-2 py-1 text-xs font-semibold rounded">
                                    <?php echo $order['payment_status']; ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="order-status <?php echo $order['status']; ?> inline-flex px-2 py-1 text-xs font-semibold rounded capitalize">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs text-gray-400">
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
    <?php endif; ?>

    <!-- View All Orders Button -->
    <!-- <div class="mt-4 text-center">
        <a href="seller-orders.php" 
           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-800 hover:from-blue-700 hover:to-blue-900 text-white text-sm font-medium rounded-lg transition duration-300 space-x-2 shadow-lg">
            <span>View All Orders</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div> -->
</div>

    <!-- View All Orders Button -->
    <!-- <div class="mt-4 text-center">
        <a href="seller-orders.php" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition duration-300 space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>View All Orders</span>
        </a>
    </div> -->


        <!-- Quick Actions -->
        
    
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