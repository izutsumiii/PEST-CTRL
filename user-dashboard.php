<?php
require_once 'includes/header.php';
require_once 'config/database.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Function to check if customer can cancel order (only if status is pending)
function canCustomerCancelOrder($order) {
    return $order['status'] === 'pending';
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to get user orders (if not already defined)
function getUserOrders() {
    global $pdo, $userId;
    
    $stmt = $pdo->prepare("SELECT o.*, 
                          GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') as items
                          FROM orders o
                          LEFT JOIN order_items oi ON o.id = oi.order_id
                          LEFT JOIN products p ON oi.product_id = p.id
                          WHERE o.user_id = ?
                          GROUP BY o.id
                          ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle order cancellation
if (isset($_POST['cancel_order'])) {
    $orderId = intval($_POST['order_id']);
    
    // Verify that this order belongs to the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Check if order is still pending
        if ($order['status'] === 'pending') {
            
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Update order status to cancelled
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                $result = $stmt->execute([$orderId]);
                
                if ($result) {
                    // Restore product stock
                    $stmt = $pdo->prepare("SELECT oi.product_id, oi.quantity 
                                          FROM order_items oi 
                                          WHERE oi.order_id = ?");
                    $stmt->execute([$orderId]);
                    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($orderItems as $item) {
                        $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                        $stmt->execute([$item['quantity'], $item['product_id']]);
                    }
                    
                    // Log the cancellation (if order_status_history table exists)
                    try {
                        $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by) 
                                              VALUES (?, 'cancelled', 'Order cancelled by customer', ?)");
                        $stmt->execute([$orderId, $userId]);
                    } catch (PDOException $e) {
                        // Table might not exist, continue without logging
                        error_log("Order status history insert failed: " . $e->getMessage());
                    }
                    
                    $pdo->commit();
                    
                    $cancelMessage = "<div class='alert alert-success'>
                                        <strong>Order Cancelled Successfully!</strong><br>
                                        Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " has been cancelled and product stock has been restored.<br>
                                        If you made a payment, the refund will be processed within 3-5 business days.
                                      </div>";
                } else {
                    $pdo->rollback();
                    $cancelMessage = "<div class='alert alert-error'>Error cancelling order. Please try again or contact support.</div>";
                }
            } catch (Exception $e) {
                $pdo->rollback();
                error_log("Order cancellation error: " . $e->getMessage());
                $cancelMessage = "<div class='alert alert-error'>An error occurred while cancelling the order. Please try again.</div>";
            }
        } else if ($order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'delivered') {
            $cancelMessage = "<div class='alert alert-error'>This order has already been processed and cannot be cancelled. Please contact customer support for assistance.</div>";
        } else if ($order['status'] === 'cancelled') {
            $cancelMessage = "<div class='alert alert-warning'>This order has already been cancelled.</div>";
        } else {
            $cancelMessage = "<div class='alert alert-error'>This order cannot be cancelled because it has already been processed by the seller.</div>";
        }
    } else {
        $cancelMessage = "<div class='alert alert-error'>Order not found or you don't have permission to cancel this order.</div>";
    }
}

// Get user orders
$orders = getUserOrders();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if user exists
if (!$user) {
    // User doesn't exist, redirect to login
    header('Location: login.php?error=user_not_found');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard</title>

<style>
/* Dashboard Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: var(--font-primary);
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    line-height: 1.6;
}

/* Main heading */
h1 {
    color: var(--primary-dark);
    text-align: center;
    margin: 30px 0;
    font-size: 2.5rem;
    font-weight: 600;
    text-shadow: 0 2px 4px var(--shadow-light);
}

/* Container for the entire dashboard */
.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

/* User info section */
.user-info {
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
    color: var(--primary-light);
    padding: 30px;
    border-radius: var(--radius-xl);
    box-shadow: 0 8px 25px var(--shadow-medium);
    position: relative;
    overflow: hidden;
}

.user-info::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.1);
    border-radius: 15px;
    pointer-events: none;
}

.user-info h2 {
    color: var(--accent-yellow);
    margin-bottom: 25px;
    font-size: 1.8rem;
    font-weight: 500;
    border-bottom: 2px solid rgba(255, 215, 54, 0.3);
    padding-bottom: 10px;
}

.user-info p {
    margin: 15px 0;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    position: relative;
    z-index: 1;
}

.user-info p strong {
    display: inline-block;
    width: 80px;
    font-weight: 600;
}

.user-info a {
    display: inline-block;
    margin-top: 20px;
    padding: 12px 25px;
    background: var(--accent-yellow);
    color: var(--primary-dark);
    text-decoration: none;
    border-radius: var(--radius-full);
    font-weight: 600;
    transition: var(--transition-normal);
    border: 2px solid var(--accent-yellow);
    position: relative;
    z-index: 1;
}

.user-info a:hover {
    background: #e6c230;
    border-color: #e6c230;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px var(--shadow-medium);
}

/* Orders section */
.user-orders {
    background: var(--primary-light);
    padding: 30px;
    border-radius: var(--radius-xl);
    box-shadow: 0 8px 25px var(--shadow-medium);
    border: 1px solid var(--border-secondary);
}

.user-orders h2 {
    color: var(--primary-dark);
    margin-bottom: 25px;
    font-size: 1.8rem;
    font-weight: 500;
    border-bottom: 3px solid var(--accent-yellow);
    padding-bottom: 10px;
    display: inline-block;
}

.user-orders p {
    color: var(--text-muted);
    font-size: 1.1rem;
    text-align: center;
    margin-top: 40px;
    font-style: italic;
}

/* Table styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: var(--primary-light);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: 0 2px 8px var(--shadow-light);
}

thead {
    background: linear-gradient(135deg, var(--primary-dark), rgba(19, 3, 37, 0.9));
}

thead th {
    color: var(--primary-light);
    padding: 15px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

tbody tr {
    border-bottom: 1px solid #dee2e6;
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f8f9fa;
}

tbody tr:last-child {
    border-bottom: none;
}

tbody td {
    padding: 15px 12px;
    color: #495057;
    font-size: 0.95rem;
}

tbody td:first-child {
    font-weight: 600;
    color: #2c3e50;
}

/* Status styling */
tbody td:nth-child(4) {
    font-weight: 500;
    text-transform: capitalize;
}

/* Action links */
tbody td a {
    color: #3498db;
    text-decoration: none;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 4px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

tbody td a:hover {
    background-color: #3498db;
    color: white;
    border-color: #3498db;
}

/* Responsive design */
@media (max-width: 768px) {
    .dashboard-container {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 15px;
    }
    
    h1 {
        font-size: 2rem;
        margin: 20px 0;
    }
    
    .user-info,
    .user-orders {
        padding: 20px;
    }
    
    .user-info h2,
    .user-orders h2 {
        font-size: 1.5rem;
    }
    
    table {
        font-size: 0.85rem;
    }
    
    thead th,
    tbody td {
        padding: 10px 8px;
    }
    
    /* Stack table on very small screens */
    @media (max-width: 600px) {
        table, thead, tbody, th, td, tr {
            display: block;
        }
        
        thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        
        tr {
            border: 1px solid #ccc;
            margin-bottom: 10px;
            border-radius: 8px;
            padding: 10px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        td {
            border: none;
            position: relative;
            padding: 10px 10px 10px 35%;
            text-align: left;
        }
        
        td:before {
            content: attr(data-label) ": ";
            position: absolute;
            left: 6px;
            width: 30%;
            padding-right: 10px;
            white-space: nowrap;
            font-weight: 600;
            color: #2c3e50;
        }
    }
}

/* Additional animations */
.user-info,
.user-orders {
    animation: fadeInUp 0.6s ease-out;
}

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

.user-orders {
    animation-delay: 0.2s;
}
</style>
</head>
<body>

<script>
// Order Management JavaScript
class OrderManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Cancel order modal events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('cancel-order-btn')) {
                const orderId = e.target.dataset.orderId;
                const orderNumber = e.target.dataset.orderNumber;
                this.showCancelModal(orderId, orderNumber);
            }

            if (e.target.classList.contains('close-modal') || e.target === document.getElementById('cancelModal')) {
                this.closeCancelModal();
            }
        });

        // Keyboard events
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeCancelModal();
            }
        });
    }

    showCancelModal(orderId, orderNumber) {
        const cancelOrderId = document.getElementById('cancelOrderId');
        const cancelOrderNumber = document.getElementById('cancelOrderNumber');
        const cancelModal = document.getElementById('cancelModal');
        
        if (cancelOrderId && cancelOrderNumber && cancelModal) {
            cancelOrderId.value = orderId;
            cancelOrderNumber.textContent = orderNumber;
            cancelModal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    closeCancelModal() {
        const cancelModal = document.getElementById('cancelModal');
        if (cancelModal) {
            cancelModal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new OrderManager();
});
</script>

<h1>My Dashboard</h1>

<?php if (isset($cancelMessage)): ?>
    <?php echo $cancelMessage; ?>
<?php endif; ?>

<div class="dashboard-container">
    <div class="user-info">
        <h2>Profile Information</h2>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
        <a href="edit-profile.php">Edit Profile</a>
    </div>

    <div class="user-orders">
        <h2>Order History</h2>
        <?php if (empty($orders)): ?>
            <p class="no-orders">No orders yet. <a href="products.php">Start shopping now!</a></p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php 
                $canCancel = canCustomerCancelOrder($order);
                ?>
                <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                    <div class="order-header">
                        <div class="order-number">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        <div class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-details">
                            <div class="detail-item">
                                <div class="detail-label">Total Amount</div>
                                <div class="detail-value">$<?php echo number_format((float)$order['total_amount'], 2); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    <span class="order-status <?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Order Date</div>
                                <div class="detail-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($order['items'])): ?>
                            <div class="order-items">
                                <strong>Items:</strong> <?php echo htmlspecialchars($order['items']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="order-actions">
                            <a href="order-confirmation.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                View Details
                            </a>
                            
                            <?php if ($canCancel): ?>
                                <button type="button" 
                                        class="btn btn-danger cancel-order-btn" 
                                        data-order-id="<?php echo $order['id']; ?>"
                                        data-order-number="#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>">
                                    Cancel Order
                                </button>
                            <?php elseif ($order['status'] === 'cancelled'): ?>
                                <span class="btn" style="background: #6c757d; color: white; cursor: default;">
                                    Order Cancelled
                                </span>
                            <?php elseif ($order['status'] === 'processing'): ?>
                                <span class="btn" style="background: #17a2b8; color: white; cursor: default;">
                                    Being Processed
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Order Modal -->
<div id="cancelModal" class="cancel-modal">
    <div class="cancel-modal-content">
        <button class="close-modal">&times;</button>
        <h3>Cancel Order Confirmation</h3>
        <p>Are you sure you want to cancel order <strong id="cancelOrderNumber">#000000</strong>?</p>
        
        <p style="color: #dc3545; font-weight: 600; margin: 20px 0;">
            This action cannot be undone. Once cancelled, you'll need to place a new order.
        </p>
        
        <form method="POST" action="" style="margin: 0;">
            <input type="hidden" name="order_id" id="cancelOrderId" value="">
            <input type="hidden" name="cancel_order" value="1">
            <div class="cancel-modal-buttons">
                <button type="button" class="btn btn-primary close-modal">Keep Order</button>
                <button type="submit" class="btn btn-danger">
                    Yes, Cancel Order
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>