<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

// Get selected time period (default to monthly)
$timePeriod = isset($_GET['period']) ? sanitizeInput($_GET['period']) : 'monthly';

// Get sales data
$stmt = $pdo->prepare("SELECT 
                      COUNT(DISTINCT o.id) as total_orders,
                      SUM(oi.quantity * oi.price) as total_revenue,
                      AVG(oi.quantity * oi.price) as avg_order_value,
                      COUNT(DISTINCT o.user_id) as total_customers
                      FROM orders o
                      JOIN order_items oi ON o.id = oi.order_id
                      JOIN products p ON oi.product_id = p.id
                      WHERE p.seller_id = ? AND o.status = 'delivered'");
$stmt->execute([$userId]);
$salesData = $stmt->fetch(PDO::FETCH_ASSOC);

// Get revenue data based on selected time period
$revenueData = [];
$chartLabels = [];
$chartData = [];

switch ($timePeriod) {
    case 'weekly':
        // Get weekly revenue for the last 8 weeks
        $stmt = $pdo->prepare("SELECT 
                            YEARWEEK(o.created_at) as week,
                            CONCAT('Week ', WEEK(o.created_at), ' ', YEAR(o.created_at)) as label,
                            SUM(oi.quantity * oi.price) as weekly_revenue
                            FROM orders o
                            JOIN order_items oi ON o.id = oi.order_id
                            JOIN products p ON oi.product_id = p.id
                            WHERE p.seller_id = ? AND o.status = 'delivered'
                            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
                            GROUP BY YEARWEEK(o.created_at)
                            ORDER BY week DESC
                            LIMIT 8");
        $stmt->execute([$userId]);
        $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case '6months':
        // Get monthly revenue for the last 6 months
        $stmt = $pdo->prepare("SELECT 
                            YEAR(o.created_at) as year,
                            MONTH(o.created_at) as month,
                            CONCAT(MONTHNAME(o.created_at), ' ', YEAR(o.created_at)) as label,
                            SUM(oi.quantity * oi.price) as monthly_revenue
                            FROM orders o
                            JOIN order_items oi ON o.id = oi.order_id
                            JOIN products p ON oi.product_id = p.id
                            WHERE p.seller_id = ? AND o.status = 'delivered'
                            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            GROUP BY YEAR(o.created_at), MONTH(o.created_at)
                            ORDER BY year DESC, month DESC
                            LIMIT 6");
        $stmt->execute([$userId]);
        $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'yearly':
        // Get yearly revenue for the last 3 years
        $stmt = $pdo->prepare("SELECT 
                            YEAR(o.created_at) as year,
                            CONCAT('Year ', YEAR(o.created_at)) as label,
                            SUM(oi.quantity * oi.price) as yearly_revenue
                            FROM orders o
                            JOIN order_items oi ON o.id = oi.order_id
                            JOIN products p ON oi.product_id = p.id
                            WHERE p.seller_id = ? AND o.status = 'delivered'
                            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 3 YEAR)
                            GROUP BY YEAR(o.created_at)
                            ORDER BY year DESC
                            LIMIT 3");
        $stmt->execute([$userId]);
        $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'monthly':
    default:
        // Get monthly revenue for the last 6 months (default)
        $stmt = $pdo->prepare("SELECT 
                            YEAR(o.created_at) as year,
                            MONTH(o.created_at) as month,
                            CONCAT(MONTHNAME(o.created_at), ' ', YEAR(o.created_at)) as label,
                            SUM(oi.quantity * oi.price) as monthly_revenue
                            FROM orders o
                            JOIN order_items oi ON o.id = oi.order_id
                            JOIN products p ON oi.product_id = p.id
                            WHERE p.seller_id = ? AND o.status = 'delivered'
                            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                            GROUP BY YEAR(o.created_at), MONTH(o.created_at)
                            ORDER BY year DESC, month DESC
                            LIMIT 6");
        $stmt->execute([$userId]);
        $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
}

// Prepare data for chart display
foreach ($revenueData as $data) {
    $chartLabels[] = $data['label'];
    
    if ($timePeriod === 'weekly') {
        $chartData[] = $data['weekly_revenue'] ? floatval($data['weekly_revenue']) : 0;
    } elseif ($timePeriod === 'yearly') {
        $chartData[] = $data['yearly_revenue'] ? floatval($data['yearly_revenue']) : 0;
    } else {
        $chartData[] = $data['monthly_revenue'] ? floatval($data['monthly_revenue']) : 0;
    }
}

// Reverse to show chronological order
$chartLabels = array_reverse($chartLabels);
$chartData = array_reverse($chartData);

// Get top products
$stmt = $pdo->prepare("SELECT 
                      p.name,
                      SUM(oi.quantity) as total_sold,
                      SUM(oi.quantity * oi.price) as total_revenue
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      JOIN orders o ON oi.order_id = o.id
                      WHERE p.seller_id = ? AND o.status = 'delivered'
                      GROUP BY p.id
                      ORDER BY total_sold DESC
                      LIMIT 5");
$stmt->execute([$userId]);
$topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get rating distribution
$ratingDistribution = getSellerRatingDistribution($userId);
?>

<h1>Sales Analytics</h1>

<div class="time-period-selector">
    <h2>Select Time Period</h2>
    <div class="period-buttons">
        <a href="sales-analytics.php?period=weekly" class="<?php echo $timePeriod === 'weekly' ? 'active' : ''; ?>">Weekly</a>
        <a href="sales-analytics.php?period=monthly" class="<?php echo $timePeriod === 'monthly' ? 'active' : ''; ?>">Monthly</a>
        <a href="sales-analytics.php?period=6months" class="<?php echo $timePeriod === '6months' ? 'active' : ''; ?>">6 Months</a>
        <a href="sales-analytics.php?period=yearly" class="<?php echo $timePeriod === 'yearly' ? 'active' : ''; ?>">Yearly</a>
    </div>
</div>

<div class="sales-dashboard">
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <p><?php echo $salesData['total_orders'] ? $salesData['total_orders'] : 0; ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Total Revenue</h3>
            <p>₱<?php echo $salesData['total_revenue'] ? number_format($salesData['total_revenue'], 2) : '0.00'; ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Average Order Value</h3>
            <p>₱<?php echo $salesData['avg_order_value'] ? number_format($salesData['avg_order_value'], 2) : '0.00'; ?></p>
        </div>
        
        <div class="stat-card">
            <h3>Total Customers</h3>
            <p><?php echo $salesData['total_customers'] ? $salesData['total_customers'] : 0; ?></p>
        </div>
    </div>
    
    <div class="charts-section">
        <div class="chart">
            <h3>
                <?php 
                switch ($timePeriod) {
                    case 'weekly': echo 'Weekly Revenue (Last 8 Weeks)'; break;
                    case '6months': echo 'Monthly Revenue (Last 6 Months)'; break;
                    case 'yearly': echo 'Yearly Revenue (Last 3 Years)'; break;
                    case 'monthly':
                    default: echo 'Monthly Revenue (Last 6 Months)'; break;
                }
                ?>
            </h3>
            <div class="chart-container" style="position: relative; width: 100%; height: 400px; padding: 20px; background: rgba(255, 255, 255, 0.1); border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px);">
                <canvas id="revenueChart"></canvas>
            </div>
            <script>
                const ctx = document.getElementById('revenueChart').getContext('2d');
                
                // Define gradient
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
                gradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Revenue',
                            data: <?php echo json_encode($chartData); ?>,
                            borderColor: '#2563eb',
                            backgroundColor: gradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#2563eb',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: '#2563eb',
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 20,
                                right: 20,
                                bottom: 20,
                                left: 20
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                    drawBorder: false
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    },
                                    font: {
                                        size: 12
                                    },
                                    color: '#64748b'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        size: 12
                                    },
                                    color: '#64748b'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 12,
                                    usePointStyle: true,
                                    pointStyle: 'circle',
                                    padding: 20,
                                    color: '#64748b'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    label: function(context) {
                                        return 'Revenue: $' + context.raw.toLocaleString();
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        }
                    }
                });
            </script>
            <?php if (!empty($revenueData)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $revenueData = array_reverse($revenueData); // Show in chronological order
                        foreach ($revenueData as $revenue): 
                        ?>
                            <tr>
                                <td><?php echo $revenue['label']; ?></td>
                                <td>
                                    $<?php 
                                    if ($timePeriod === 'weekly') {
                                        echo number_format($revenue['weekly_revenue'], 2);
                                    } elseif ($timePeriod === 'yearly') {
                                        echo number_format($revenue['yearly_revenue'], 2);
                                    } else {
                                        echo number_format($revenue['monthly_revenue'], 2);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No revenue data available for the selected period.</p>
            <?php endif; ?>
        </div>
        
        <div class="chart">
            <h3>Top Selling Products</h3>
            <?php if (!empty($topProducts)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['total_sold']; ?></td>
                                <td>₱<?php echo number_format($product['total_revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No product sales data available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="rating-analytics">
        <h2>Rating Analytics</h2>
        
        <div class="rating-stats">
            <div class="stat-card">
                <h3>Average Rating</h3>
                <p class="rating-value"><?php echo number_format($ratingDistribution['avg_rating'], 1); ?>/5</p>
                <div class="rating-stars">
                    <?php
                    $avgRating = $ratingDistribution['avg_rating'];
                    $fullStars = floor($avgRating);
                    $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                    
                    echo str_repeat('★', $fullStars);
                    echo $hasHalfStar ? '½' : '';
                    echo str_repeat('☆', $emptyStars);
                    ?>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>Total Reviews</h3>
                <p><?php echo $ratingDistribution['total_reviews']; ?></p>
            </div>
            
            <div class="stat-card">
                <h3>Highly Rated Products (4+ stars)</h3>
                <p><?php echo $ratingDistribution['highly_rated']; ?> / <?php echo $ratingDistribution['total_products']; ?></p>
            </div>
        </div>
    </div>
</div>

<style>


/* Sales Analytics Dashboard Styles */

/* General Styling */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-primary);
    line-height: 1.6;
    color: #F9F9F9;
    background: linear-gradient(135deg, #130325 0%, #1a0a2e 100%);
    min-height: 100vh;
    font-size: 0.9em;
}

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

.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Page Title */
h1 {
    font-size: 3rem;
    font-weight: 700;
    color: white;
    text-align: center;
    margin-bottom: 40px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    position: relative;
}

h1::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 4px;
    background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
    border-radius: 2px;
}

/* Time Period Selector */
.time-period-selector {
    background: rgba(255, 255, 255, 0.1);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    margin-bottom: 30px;
    backdrop-filter: blur(10px);
    color: #F9F9F9;
}

.time-period-selector h2 {
    color: #F9F9F9;
    font-size: 1.5rem;
    margin-bottom: 20px;
    text-align: center;
}

.period-buttons {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.period-buttons a {
    padding: 12px 25px;
    text-decoration: none;
    color: #F9F9F9;
    background: rgba(255, 255, 255, 0.1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    border-radius: 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9rem;
}

.period-buttons a:hover {
    background: #FFD736;
    color: #130325;
    border-color: #FFD736;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 215, 54, 0.4);
}

.period-buttons a.active {
    background: linear-gradient(135deg, #FFD736, #e6c230);
    color: #130325;
    border-color: transparent;
    box-shadow: 0 5px 15px rgba(255, 215, 54, 0.4);
}

/* Sales Dashboard */
.sales-dashboard {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.stat-card h3 {
    color: #666;
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.stat-card p {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
}

/* Specific stat card colors */
.stat-card:nth-child(1)::before { background: linear-gradient(90deg, #ff6b6b, #ee5a52); }
.stat-card:nth-child(2)::before { background: linear-gradient(90deg, #4ecdc4, #44a08d); }
.stat-card:nth-child(3)::before { background: linear-gradient(90deg, #45b7d1, #96c93d); }
.stat-card:nth-child(4)::before { background: linear-gradient(90deg, #f093fb, #f5576c); }

/* Charts Section */
.charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.chart {
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

.chart h3 {
    color: #F9F9F9;
    font-size: 1.3rem;
    margin-bottom: 25px;
    text-align: center;
    padding-bottom: 15px;
    border-bottom: 3px solid #FFD736;
    position: relative;
}

.chart h3::after {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 50%;
    transform: translateX(-50%);
    width: 50px;
    height: 3px;
    background: #764ba2;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

thead {
    background: linear-gradient(135deg, #130325, #1a0a2e);
    color: #F9F9F9;
}

th, td {
    padding: 15px 20px;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
}

th {
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.9rem;
}

tbody tr {
    transition: all 0.3s ease;
}

tbody tr:hover {
    background-color: rgba(255, 215, 54, 0.1);
    transform: scale(1.02);
}

tbody tr:nth-child(even) {
    background-color: rgba(255, 255, 255, 0.05);
}

tbody tr:nth-child(even):hover {
    background-color: rgba(255, 215, 54, 0.15);
}

td {
    font-weight: 500;
}

td:last-child {
    font-weight: 600;
    color: #27ae60;
}

/* Rating Analytics */
.rating-analytics {
    background: rgba(255, 255, 255, 0.1);
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    margin-top: 30px;
    color: #F9F9F9;
    backdrop-filter: blur(10px);
}

.rating-analytics h2 {
    color: #F9F9F9;
    font-size: 2rem;
    text-align: center;
    margin-bottom: 30px;
    position: relative;
    padding-bottom: 15px;
}

.rating-analytics h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #ff6b6b, #4ecdc4);
    border-radius: 2px;
}

.rating-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.rating-stats .stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: relative;
    overflow: hidden;
}

.rating-stats .stat-card::before {
    display: none;
}

.rating-stats .stat-card h3 {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 15px;
}

.rating-stats .stat-card p {
    color: white;
}

.rating-value {
    font-size: 3rem !important;
    font-weight: 800 !important;
    margin-bottom: 10px !important;
}

.rating-stars {
    font-size: 1.5rem;
    color: #ffd700;
    margin-top: 10px;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
}

/* Empty State Messages */
.chart p,
.charts-section p {
    text-align: center;
    color: #666;
    font-style: italic;
    font-size: 1.1rem;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 2px dashed #ddd;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .charts-section {
        grid-template-columns: 1fr;
    }
    
    .chart {
        min-width: auto;
    }
}

@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    
    h1 {
        font-size: 2.2rem;
        margin-bottom: 30px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }
    
    .stat-card {
        padding: 20px;
    }
    
    .stat-card p {
        font-size: 1.8rem;
    }
    
    .period-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .period-buttons a {
        width: 80%;
        text-align: center;
    }
    
    .chart,
    .rating-analytics {
        padding: 20px;
    }
    
    th, td {
        padding: 12px 15px;
        font-size: 0.9rem;
    }
    
    .rating-stats {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 1.8rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card p {
        font-size: 1.6rem;
    }
    
    .rating-value {
        font-size: 2.5rem !important;
    }
    
    th, td {
        padding: 10px;
        font-size: 0.8rem;
    }
    
    .time-period-selector,
    .chart,
    .rating-analytics {
        padding: 15px;
    }
}

/* Animation Effects */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.time-period-selector {
    animation: fadeInUp 0.6s ease-out;
}

.stat-card {
    animation: fadeInUp 0.6s ease-out;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

.chart {
    animation: slideInLeft 0.8s ease-out;
}

.rating-analytics {
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 0.3s;
    animation-fill-mode: both;
}

/* Loading States */
.stat-card:hover::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.8s ease;
}

.stat-card:hover::after {
    left: 100%;
}

/* Success/Error styling for revenue values */
td:last-child {
    position: relative;
}

td:last-child::before {
    content: '💰';
    margin-right: 5px;
    opacity: 0.7;
}



</style>

<?php require_once 'includes/footer.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.0/chart.min.js" integrity="sha512-n/G+dROKbKL3GVngGWmWfwK0yPctjZQM752diVYnXZtD/48agpUKLIn0xDQL9ydZ91x6BiOmTIFwWjjFi2kEFg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
</head>
<body>
    

</body>
</html>