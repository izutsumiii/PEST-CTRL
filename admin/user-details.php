<?php
require_once '../config/database.php';
require_once 'includes/admin_header.php';

requireAdmin();

// Get user ID from URL
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$userId) {
    header('Location: admin-customers.php?msg=' . urlencode('Invalid user ID') . '&type=error');
    exit();
}

// Initialize variables
$message = '';
$messageType = '';

// Handle actions
if (isset($_GET['action'])) {
    $action = sanitizeInput($_GET['action']);
    
    try {
        switch ($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
                $stmt->execute([$userId]);
                if ($stmt->rowCount() > 0) {
                    $message = "User activated successfully!";
                    $messageType = 'success';
                }
                break;
                
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
                $stmt->execute([$userId]);
                if ($stmt->rowCount() > 0) {
                    $message = "User deactivated successfully!";
                    $messageType = 'success';
                }
                break;
                
            case 'delete':
                // Check for orders first
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
                $stmt->execute([$userId]);
                $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($orderCount > 0) {
                    $message = "Cannot delete user. User has {$orderCount} order(s).";
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    if ($stmt->rowCount() > 0) {
                        header('Location: admin-customers.php?msg=' . urlencode('User deleted successfully') . '&type=success');
                        exit();
                    }
                }
                break;
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $messageType = 'error';
    }
    
    // Redirect to avoid resubmission
    if (empty($message)) {
        header("Location: user-details.php?id={$userId}");
        exit();
    }
}

// Display messages from redirect
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = sanitizeInput($_GET['msg']);
    $messageType = sanitizeInput($_GET['type']);
}

// Get user details
try {
    $stmt = $pdo->prepare("
        SELECT *, 
               CASE WHEN is_active IS NULL THEN TRUE ELSE is_active END as active_status
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: admin-customers.php?msg=' . urlencode('User not found') . '&type=error');
        exit();
    }
    
} catch (PDOException $e) {
    header('Location: admin-customers.php?msg=' . urlencode('Error retrieving user details') . '&type=error');
    exit();
}

// Get user's orders with pagination
$orderPage = isset($_GET['order_page']) ? max(1, intval($_GET['order_page'])) : 1;
$orderLimit = 5;
$orderOffset = ($orderPage - 1) * $orderLimit;

try {
    // Check if orders table exists and get orders
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'orders'");
    $stmt->execute();
    $ordersTableExists = $stmt->rowCount() > 0;
    
    $orders = [];
    $totalOrders = 0;
    $totalOrderPages = 0;
    
    if ($ordersTableExists) {
        // Get orders count
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        $totalOrderPages = ceil($totalOrders / $orderLimit);
        
        // Get orders with details
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   COALESCE(o.total_amount, 0) as total_amount,
                   COALESCE(o.status, 'pending') as status
            FROM orders o 
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $orderLimit, $orderOffset]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $ordersError = "Error loading orders: " . $e->getMessage();
    $orders = [];
    $totalOrders = 0;
}

// Get user statistics
try {
    $stats = [
        'total_orders' => $totalOrders,
        'total_spent' => 0,
        'last_order_date' => null,
        'account_age_days' => 0
    ];
    
    if ($ordersTableExists && $totalOrders > 0) {
        // Total spent
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_spent'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'];
        
        // Last order date
        $stmt = $pdo->prepare("SELECT MAX(created_at) as last_order FROM orders WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['last_order_date'] = $stmt->fetch(PDO::FETCH_ASSOC)['last_order'];
    }
    
    // Account age
    if ($user['created_at']) {
        $stats['account_age_days'] = floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24));
    }
    
} catch (PDOException $e) {
    $statsError = "Error loading statistics: " . $e->getMessage();
}

// Check if is_active column exists
$isActiveExists = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    $isActiveExists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $isActiveExists = false;
}
?>

<div class="breadcrumb">
    <a href="admin-customers.php">← Back to Customers</a>
</div>

<h1>User Details</h1>

<?php if (!empty($message)): ?>
    <div class="<?php echo $messageType; ?>-message">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>

<div class="admin-content">
    <div class="user-details-container">
        
        <!-- User Information Card -->
        <div class="user-info-card">
            <div class="card-header">
                <h2>User Information</h2>
                <div class="user-status">
                    <span class="status-badge status-<?php echo $user['active_status'] ? 'active' : 'inactive'; ?>">
                        <?php echo $user['active_status'] ? 'Active' : 'Inactive'; ?>
                    </span>
                    <span class="user-type-badge user-type-<?php echo $user['user_type']; ?>">
                        <?php echo ucfirst($user['user_type']); ?>
                    </span>
                </div>
            </div>
            
            <div class="card-content">
                <div class="info-grid">
                    <div class="info-item">
                        <label>User ID:</label>
                        <span><?php echo htmlspecialchars($user['id']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>Username:</label>
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>Full Name:</label>
                        <span>
                            <?php 
                            $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                            echo htmlspecialchars($fullName ?: 'Not provided'); 
                            ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <label>Phone:</label>
                        <span><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>Address:</label>
                        <span><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <label>Registration Date:</label>
                        <span>
                            <?php 
                            echo $user['created_at'] 
                                ? date('F j, Y \a\t g:i A', strtotime($user['created_at'])) 
                                : 'Not available'; 
                            ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <label>Account Age:</label>
                        <span><?php echo $stats['account_age_days']; ?> days</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Statistics Card -->
        <div class="user-stats-card">
            <div class="card-header">
                <h2>Statistics</h2>
            </div>
            
            <div class="card-content">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number">₱<?php echo number_format($stats['total_spent'], 2); ?></div>
                        <div class="stat-label">Total Spent</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            if ($stats['last_order_date']) {
                                echo date('M j, Y', strtotime($stats['last_order_date']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </div>
                        <div class="stat-label">Last Order</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-number">
                            <?php 
                            echo $stats['total_orders'] > 0 
                                ? '₱' . number_format($stats['total_spent'] / $stats['total_orders'], 2)
                                : '₱0.00';
                            ?>
                        </div>
                        <div class="stat-label">Avg. Order Value</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Actions Card -->
        <?php if ($isActiveExists): ?>
        <div class="admin-actions-card">
            <div class="card-header">
                <h2>Admin Actions</h2>
            </div>
            
            <div class="card-content">
                <div class="action-buttons-group">
                    <?php if ($user['active_status']): ?>
                        <a href="user-details.php?id=<?php echo $userId; ?>&action=deactivate" 
                           class="btn-deactivate"
                           onclick="return confirm('Are you sure you want to deactivate this user?')">
                           Deactivate User
                        </a>
                    <?php else: ?>
                        <a href="user-details.php?id=<?php echo $userId; ?>&action=activate" 
                           class="btn-activate"
                           onclick="return confirm('Are you sure you want to activate this user?')">
                           Activate User
                        </a>
                    <?php endif; ?>
                    
                    <a href="user-details.php?id=<?php echo $userId; ?>&action=delete" 
                       class="btn-delete"
                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone. Users with orders cannot be deleted.')">
                       Delete User
                    </a>
                    
                    <?php if ($user['user_type'] === 'customer'): ?>
                        <a href="mailto:<?php echo urlencode($user['email']); ?>" class="btn-email">
                            Send Email
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Orders History Card -->
        <div class="orders-history-card">
            <div class="card-header">
                <h2>Order History (<?php echo number_format($totalOrders); ?> orders)</h2>
            </div>
            
            <div class="card-content">
                <?php if (isset($ordersError)): ?>
                    <p class="error-message"><?php echo htmlspecialchars($ordersError); ?></p>
                <?php elseif (!$ordersTableExists): ?>
                    <p class="info-message">Orders table not found in database.</p>
                <?php elseif (empty($orders)): ?>
                    <p class="info-message">No orders found for this user.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                        <td>
                                            <?php 
                                            echo $order['created_at'] 
                                                ? date('M j, Y g:i A', strtotime($order['created_at'])) 
                                                : 'N/A'; 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                               class="btn-view-order">View Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Orders Pagination -->
                    <?php if ($totalOrderPages > 1): ?>
                        <div class="pagination">
                            <?php if ($orderPage > 1): ?>
                                <a href="user-details.php?id=<?php echo $userId; ?>&order_page=<?php echo $orderPage - 1; ?>" 
                                   class="page-link">« Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= min($totalOrderPages, 5); $i++): ?>
                                <a href="user-details.php?id=<?php echo $userId; ?>&order_page=<?php echo $i; ?>" 
                                   class="page-link <?php echo $i === $orderPage ? 'active' : ''; ?>">
                                   <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($orderPage < $totalOrderPages): ?>
                                <a href="user-details.php?id=<?php echo $userId; ?>&order_page=<?php echo $orderPage + 1; ?>" 
                                   class="page-link">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($user['user_type'] === 'seller'): ?>
        <!-- Seller Specific Information -->
        <div class="seller-info-card">
            <div class="card-header">
                <h2>Seller Information</h2>
            </div>
            
            <div class="card-content">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Seller Status:</label>
                        <span class="status-badge status-<?php echo strtolower($user['seller_status'] ?? 'pending'); ?>">
                            <?php echo ucfirst($user['seller_status'] ?? 'Pending'); ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <label>Document Verified:</label>
                        <span class="status-badge status-<?php echo ($user['document_verified'] ?? 0) ? 'verified' : 'unverified'; ?>">
                            <?php echo ($user['document_verified'] ?? 0) ? 'Verified' : 'Not Verified'; ?>
                        </span>
                    </div>
                    
                    <div class="info-item">
                        <label>Document Path:</label>
                        <span>
                            <?php if ($user['document_path'] ?? ''): ?>
                                <a href="<?php echo htmlspecialchars($user['document_path']); ?>" target="_blank">
                                    View Document
                                </a>
                            <?php else: ?>
                                No document uploaded
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any interactive functionality here
    console.log('User details page loaded for user ID: <?php echo $userId; ?>');
});
</script>

<?php require_once '../includes/footer.php'; ?>