<?php
require_once '../config/database.php';
require_once 'includes/admin_header.php';

requireAdmin();

// Initialize variables
$message = '';
$messageType = '';

// Handle customer actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitizeInput($_GET['action']);
    $customerId = intval($_GET['id']);
    
    try {
        switch ($action) {
            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ? AND user_type = 'customer'");
                $stmt->execute([$customerId]);
                if ($stmt->rowCount() > 0) {
                    $message = "Customer deactivated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Customer not found or already inactive.";
                    $messageType = 'error';
                }
                break;
                
            case 'activate':
                $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ? AND user_type = 'customer'");
                $stmt->execute([$customerId]);
                if ($stmt->rowCount() > 0) {
                    $message = "Customer activated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Customer not found or already active.";
                    $messageType = 'error';
                }
                break;
                
            case 'delete':
                // Check if customer has any orders before deleting
                $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
                $stmt->execute([$customerId]);
                $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['order_count'];
                
                if ($orderCount > 0) {
                    $message = "Cannot delete customer. Customer has {$orderCount} order(s). Please deactivate instead.";
                    $messageType = 'error';
                } else {
                    // Safe to delete - no orders associated
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'customer'");
                    $stmt->execute([$customerId]);
                    if ($stmt->rowCount() > 0) {
                        $message = "Customer deleted successfully!";
                        $messageType = 'success';
                    } else {
                        $message = "Customer not found or could not be deleted.";
                        $messageType = 'error';
                    }
                }
                break;
                
            default:
                $message = "Invalid action.";
                $messageType = 'error';
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $messageType = 'error';
    }
    
    // Redirect to avoid resubmission on refresh
    $redirectUrl = 'admin-customers.php';
    if (isset($_GET['page'])) {
        $redirectUrl .= '?page=' . intval($_GET['page']);
    }
    if (!empty($message)) {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'msg=' . urlencode($message) . '&type=' . $messageType;
    }
    header("Location: $redirectUrl");
    exit();
}

// Display messages from redirect
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = sanitizeInput($_GET['msg']);
    $messageType = sanitizeInput($_GET['type']);
}

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$searchCondition = '';
$searchParams = [];

if (!empty($searchTerm)) {
    $searchCondition = " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%{$searchTerm}%";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Check if is_active column exists
$columnExists = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    $columnExists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $columnExists = false;
}

// Build query based on whether is_active column exists
if ($columnExists) {
    $query = "SELECT *, is_active as active_status FROM users WHERE user_type = 'customer'" . $searchCondition . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE user_type = 'customer'" . $searchCondition;
} else {
    $query = "SELECT *, TRUE as active_status FROM users WHERE user_type = 'customer'" . $searchCondition . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE user_type = 'customer'" . $searchCondition;
}

try {
    // Get customers
    $stmt = $pdo->prepare($query);
    $paramIndex = 1;
    foreach ($searchParams as $param) {
        $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
    }
    $stmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total count
    $stmt = $pdo->prepare($countQuery);
    $paramIndex = 1;
    foreach ($searchParams as $param) {
        $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
    }
    $stmt->execute();
    $totalCustomers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalCustomers / $limit);

} catch (PDOException $e) {
    $message = "Error retrieving customers: " . $e->getMessage();
    $messageType = 'error';
    $customers = [];
    $totalCustomers = 0;
    $totalPages = 0;
}

// Function to get order count for a customer
function getCustomerOrderCount($pdo, $customerId) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        return 0;
    }
}
?>

<h1>Manage Customers</h1>

<?php if (!empty($message)): ?>
    <div class="<?php echo $messageType; ?>-message">
        <p><?php echo htmlspecialchars($message); ?></p>
    </div>
<?php endif; ?>

<?php if (!$columnExists): ?>
    <div class="warning-message">
        <p><strong>Warning:</strong> The 'is_active' column is missing from the database. Some functionality may be limited.</p>
        <p>Run this SQL query to fix: <code>ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER user_type;</code></p>
    </div>
<?php endif; ?>

<div class="admin-content">
    <!-- Search Form -->
    <div class="search-section">
        <form method="GET" action="admin-customers.php" class="search-form">
            <input type="text" name="search" placeholder="Search by username, email, or name..." 
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="search-input">
            <button type="submit" class="btn-search">Search</button>
            <?php if (!empty($searchTerm)): ?>
                <a href="admin-customers.php" class="btn-clear">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <h2>
        Customers List 
        (<?php echo $totalCustomers; ?> total<?php echo !empty($searchTerm) ? ', filtered results' : ''; ?>)
    </h2>
    
    <?php if (empty($customers)): ?>
        <p><?php echo !empty($searchTerm) ? 'No customers found matching your search.' : 'No customers found.'; ?></p>
    <?php else: ?>
        <div class="table-container" style="padding: 0 8px;">
            <table class="admin-table" style="margin: 0 auto;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <?php $orderCount = getCustomerOrderCount($pdo, $customer['id']); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($customer['id']); ?></td>
                            <td><?php echo htmlspecialchars($customer['username']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td>
                                <?php 
                                $fullName = trim($customer['first_name'] . ' ' . $customer['last_name']);
                                echo htmlspecialchars($fullName ?: 'N/A'); 
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $customer['active_status'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $customer['active_status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                echo $customer['created_at'] ? date('M j, Y', strtotime($customer['created_at'])) : 'N/A'; 
                                ?>
                            </td>
                            <td>
                                <span class="order-count"><?php echo $orderCount; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="user-details.php?id=<?php echo $customer['id']; ?>" 
                                       class="btn-view" title="View Customer Details"><i class="fas fa-eye"></i> View</a>
                                    
                                    <?php if ($columnExists): ?>
                                        <?php if ($customer['active_status']): ?>
                                            <a href="admin-customers.php?action=deactivate&id=<?php echo $customer['id']; ?>&page=<?php echo $page; ?>" 
                                               class="btn-deactivate" title="Deactivate Customer" data-confirm="Deactivate this customer?">
                                               <i class="fas fa-user-slash"></i> Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a href="admin-customers.php?action=activate&id=<?php echo $customer['id']; ?>&page=<?php echo $page; ?>" 
                                               class="btn-activate" title="Activate Customer" data-confirm="Activate this customer?">
                                               <i class="fas fa-user-check"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="admin-customers.php?action=delete&id=<?php echo $customer['id']; ?>&page=<?php echo $page; ?>" 
                                           class="btn-delete" title="Delete Customer" data-confirm="Delete this customer? This cannot be undone.">
                                           <i class="fas fa-trash"></i> Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="btn-disabled" title="Database column missing">Action unavailable</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $baseUrl = 'admin-customers.php?';
                if (!empty($_GET['search'])) {
                    $baseUrl .= 'search=' . urlencode($_GET['search']) . '&';
                }
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?php echo $baseUrl; ?>page=<?php echo $page - 1; ?>" class="page-link">« Previous</a>
                <?php endif; ?>
                
                <?php
                // Show pagination numbers with ellipsis for large page counts
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1): ?>
                    <a href="<?php echo $baseUrl; ?>page=1" class="page-link">1</a>
                    <?php if ($startPage > 2): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="<?php echo $baseUrl; ?>page=<?php echo $i; ?>" 
                       class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span class="page-ellipsis">...</span>
                    <?php endif; ?>
                    <a href="<?php echo $baseUrl; ?>page=<?php echo $totalPages; ?>" class="page-link"><?php echo $totalPages; ?></a>
                <?php endif; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo $baseUrl; ?>page=<?php echo $page + 1; ?>" class="page-link">Next »</a>
                <?php endif; ?>
            </div>
            
            <div class="pagination-info">
                Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalCustomers); ?> 
                of <?php echo $totalCustomers; ?> customers
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Add confirmation for bulk actions if needed in the future
document.addEventListener('DOMContentLoaded', function() {
    // Add any additional JavaScript functionality here
    console.log('Admin Customers page loaded');
});
</script>

<?php require_once '../includes/footer.php'; ?>