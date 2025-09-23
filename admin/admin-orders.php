<?php
require_once 'includes/admin_header.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

// Check if user is admin (assuming admin role check exists)
if (!isAdmin()) {
    header("Location: user-dashboard.php");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    $userId = $_SESSION['user_id'];
    
    // Valid status values
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($newStatus, $validStatuses)) {
        try {
            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);
            
            // Insert into order status history
            $stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, notes, updated_by, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$orderId, $newStatus, $notes, $userId]);
            
            $successMessage = "Order status updated successfully.";
        } catch (PDOException $e) {
            $errorMessage = "Error updating order status: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Invalid status value.";
    }
}

// Pagination settings
$ordersPerPage = 20;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $ordersPerPage;

// Search and filter parameters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query conditions
$conditions = [];
$params = [];

if (!empty($searchTerm)) {
    $conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR o.id = ?)";
    $searchParam = "%$searchTerm%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchTerm]);
}

if (!empty($statusFilter)) {
    $conditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $ordersPerPage);

// Get orders with user information
$query = "SELECT o.*, u.username, u.email, u.first_name, u.last_name, u.phone,
                 COUNT(oi.id) as item_count
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          LEFT JOIN order_items oi ON o.id = oi.order_id
          $whereClause
          GROUP BY o.id
          ORDER BY o.created_at DESC 
          LIMIT $ordersPerPage OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$statsQuery = "SELECT 
    COUNT(*) as total_orders,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
    COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as average_order_value
FROM orders";
$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="admin-orders-container">
    <div class="page-header">
        <h1>Order Management</h1>
        <div class="page-actions">
            <button onclick="exportOrders()" class="export-btn">Export Orders</button>
            <a href="admin-dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-error"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <!-- Order Statistics -->
    <div class="order-stats">
        <div class="stat-card">
            <h3>Total Orders</h3>
            <p class="stat-number"><?php echo number_format($stats['total_orders']); ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Revenue</h3>
            <p class="stat-number">₱<?php echo number_format($stats['total_revenue'], 2); ?></p>
        </div>
        <div class="stat-card">
            <h3>Average Order Value</h3>
            <p class="stat-number">₱<?php echo number_format($stats['average_order_value'], 2); ?></p>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <p class="stat-number"><?php echo number_format($stats['pending_orders']); ?></p>
        </div>
    </div>

    <!-- Search and Filter Form -->
    <div class="search-filter-container">
        <form method="GET" class="search-filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Order ID, Customer name or email" 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">From Date:</label>
                    <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">To Date:</label>
                    <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="filter-btn">Filter</button>
                    <a href="admin-orders.php" class="clear-filter-btn">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="orders-table-container">
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>No orders found matching your criteria.</p>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="admin-order-details.php?id=<?php echo $order['id']; ?>" class="order-link">
                                    #<?php echo $order['id']; ?>
                                </a>
                            </td>
                            <td>
                                <div class="customer-info">
                                    <strong><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></strong>
                                    <div class="customer-email"><?php echo htmlspecialchars($order['email']); ?></div>
                                    <?php if ($order['phone']): ?>
                                        <div class="customer-phone"><?php echo htmlspecialchars($order['phone']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo $order['item_count']; ?> item(s)</td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="payment-info">
                                    <div class="payment-method"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></div>
                                    <div class="payment-status payment-<?php echo $order['payment_status']; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="order-date">
                                    <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                    <div class="order-time"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                                </div>
                            </td> 
                            <!-- <td> Button not sure if need paba ni admin maka change sa mga products status
                                <div class="action-buttons">
                                    <a href="admin-order-details.php?id=<?php echo $order['id']; ?>" class="view-btn" title="View Details">View</a>
                                    <button onclick="openStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" 
                                            class="status-btn" title="Update Status">Status</button>
                                </div>
                            </td> -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>" class="page-btn">Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                   class="page-btn <?php echo $i === $currentPage ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>" class="page-btn">Next</a>
            <?php endif; ?>
        </div>
        
        <div class="pagination-info">
            Showing <?php echo (($currentPage - 1) * $ordersPerPage) + 1; ?> to 
            <?php echo min($currentPage * $ordersPerPage, $totalOrders); ?> of 
            <?php echo $totalOrders; ?> orders
        </div>
    <?php endif; ?>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Order Status</h3>
            <span class="close" onclick="closeStatusModal()">&times;</span>
        </div>
        <form id="statusForm" method="POST">
            <input type="hidden" id="modalOrderId" name="order_id">
            <input type="hidden" name="update_status" value="1">
            
            <div class="form-group">
                <label for="modalStatus">New Status:</label>
                <select id="modalStatus" name="status" required>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="modalNotes">Notes (optional):</label>
                <textarea id="modalNotes" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="update-btn">Update Status</button>
                <button type="button" onclick="closeStatusModal()" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-orders-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #eee;
}

.page-actions {
    display: flex;
    gap: 10px;
}

.export-btn, .back-btn {
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.export-btn {
    background: #28a745;
    color: white;
    border: none;
    cursor: pointer;
}

.back-btn {
    background: #6c757d;
    color: white;
}

.order-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #dee2e6;
}

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #007bff;
    margin: 10px 0 0 0;
}

.search-filter-container {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.filter-group input, .filter-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.filter-btn, .clear-filter-btn {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    display: inline-block;
    text-align: center;
    margin-right: 10px;
}

.filter-btn {
    background: #007bff;
    color: white;
    border: none;
    cursor: pointer;
}

.clear-filter-btn {
    background: #6c757d;
    color: white;
}

.orders-table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.orders-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.orders-table tbody tr:hover {
    background: #f8f9fa;
}

.order-link {
    color: #007bff;
    text-decoration: none;
    font-weight: 600;
}

.customer-info {
    font-size: 14px;
}

.customer-email, .customer-phone {
    color: #6c757d;
    font-size: 12px;
    margin-top: 2px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce7ff; color: #004085; }
.status-shipped { background: #d4edda; color: #155724; }
.status-delivered { background: #d1ecf1; color: #0c5460; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.payment-info {
    font-size: 14px;
}

.payment-status {
    font-size: 12px;
    margin-top: 2px;
}

.payment-paid { color: #28a745; }
.payment-pending { color: #ffc107; }
.payment-failed { color: #dc3545; }

.order-date {
    font-size: 14px;
}

.order-time {
    color: #6c757d;
    font-size: 12px;
    margin-top: 2px;
}

.action-buttons {
    display: flex;
    gap: 5px;
}

.view-btn, .status-btn {
    padding: 4px 8px;
    border: none;
    border-radius: 3px;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
}

.view-btn {
    background: #17a2b8;
    color: white;
}

.status-btn {
    background: #ffc107;
    color: #212529;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-bottom: 10px;
}

.page-btn {
    padding: 8px 12px;
    text-decoration: none;
    border: 1px solid #ddd;
    border-radius: 4px;
    color: #007bff;
}

.page-btn.active {
    background: #007bff;
    color: white;
}

.pagination-info {
    text-align: center;
    color: #6c757d;
    font-size: 14px;
}

.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

#statusForm {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.update-btn, .cancel-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.update-btn {
    background: #28a745;
    color: white;
}

.cancel-btn {
    background: #6c757d;
    color: white;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.no-orders {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

@media (max-width: 768px) {
    .orders-table-container {
        overflow-x: auto;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .order-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
function openStatusModal(orderId, currentStatus) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('modalStatus').value = currentStatus;
    document.getElementById('modalNotes').value = '';
    document.getElementById('statusModal').style.display = 'block';
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
}

function exportOrders() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const exportUrl = 'admin-export-orders.php?' + urlParams.toString();
    window.open(exportUrl, '_blank');
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    const modal = document.getElementById('statusModal');
    if (event.target === modal) {
        closeStatusModal();
    }
}
</script>

<?php
require_once '../includes/footer.php';

// Helper function to check if user is admin (you'll need to implement this)


?>