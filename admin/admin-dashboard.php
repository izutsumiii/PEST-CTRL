<?php
require_once '../config/database.php';
require_once 'includes/admin_header.php';

requireAdmin();

// Get stats for dashboard
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
$stmt->execute();
$totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get pending sellers
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'seller' AND seller_status = 'pending'");
$stmt->execute();
$pendingSellers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get pending products for approval
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'pending'");
$stmt->execute();
$pendingProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <p>Manage your PEST-CTRL platform</p>
</div>

<div class="stats">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p><?php echo $totalUsers; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Products</h3>
        <p><?php echo $totalProducts; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Orders</h3>
        <p><?php echo $totalOrders; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Revenue</h3>
        <p>₱<?php echo number_format($totalRevenue, 2); ?></p>
    </div>
    <div class="stat-card">
        <h3>Pending Sellers</h3>
        <p><?php echo $pendingSellers; ?></p>
    </div>
    <div class="stat-card">
        <h3>Pending Products</h3>
        <p><?php echo $pendingProducts; ?></p>
    </div>
</div>

<div class="admin-sections">
    <!-- <div class="section">
        <h2>Quick Actions</h2>
        <div class="quick-actions">
            <a href="admin-sellers.php" class="action-btn">Manage Sellers</a>
            <a href="admin-customers.php" class="action-btn">Manage Customers</a>
            <a href="admin-products.php" class="action-btn">Manage Products</a>
            <a href="admin-orders.php" class="action-btn">Manage Orders</a>
        </div>
    </div> -->
    
    <div class="section">
        <h2>Pending Seller Approvals</h2>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'seller' AND seller_status = 'pending' LIMIT 5");
        $stmt->execute();
        $pendingSellersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <?php if (empty($pendingSellersList)): ?>
            <p>No pending seller approvals.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingSellersList as $seller): ?>
                        <tr>
                            <td><?php echo $seller['username']; ?></td>
                            <td><?php echo $seller['email']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($seller['created_at'])); ?></td>
                            <td>
                                <a href="admin-sellers.php?action=approve&id=<?php echo $seller['id']; ?>">Approve</a>
                                <a href="admin-sellers.php?action=reject&id=<?php echo $seller['id']; ?>">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="admin-sellers.php">View All Sellers</a>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Pending Product Approvals</h2>
        <?php
        $stmt = $pdo->prepare("SELECT p.*, u.username as seller_name 
                              FROM products p 
                              JOIN users u ON p.seller_id = u.id 
                              WHERE p.status = 'pending' LIMIT 5");
        $stmt->execute();
        $pendingProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <?php if (empty($pendingProductsList)): ?>
            <p>No pending product approvals.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingProductsList as $product): ?>
                        <tr>
                            <td><?php echo $product['name']; ?></td>
                            <td><?php echo $product['seller_name']; ?></td>
                            <td>₱<?php echo $product['price']; ?></td>
                            <td>
                                <a href="admin-products.php?action=approve&id=<?php echo $product['id']; ?>">Approve</a>
                                <a href="admin-products.php?action=reject&id=<?php echo $product['id']; ?>">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="admin-products.php">View All Pending Products</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>