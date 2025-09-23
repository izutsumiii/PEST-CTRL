<?php
require_once '../config/database.php';
require_once 'includes/admin_header.php';

requireAdmin();

$message = '';
$error = '';

// Handle product actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitizeInput($_GET['action']);
    $productId = intval($_GET['id']);
    
    $validActions = ['approve', 'reject', 'suspend', 'delete', 'reactivate'];
    
    if (in_array($action, $validActions)) {
        try {
            // First check if product exists
            $stmt = $pdo->prepare("SELECT p.*, u.username as seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                switch ($action) {
                    case 'approve':
                        $stmt = $pdo->prepare("UPDATE products SET status = 'active', updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$productId]);
                        $message = "Product '{$product['name']}' approved successfully!";
                        
                        // Log admin action
                        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, timestamp) VALUES (?, 'product_approve', ?, NOW())");
                        $stmt->execute([$_SESSION['user_id'], "Approved product ID: {$productId} - {$product['name']}"]);
                        break;
                        
                    case 'reject':
                        $stmt = $pdo->prepare("UPDATE products SET status = 'rejected', updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$productId]);
                        $message = "Product '{$product['name']}' rejected successfully!";
                        
                        // Log admin action
                        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, timestamp) VALUES (?, 'product_reject', ?, NOW())");
                        $stmt->execute([$_SESSION['user_id'], "Rejected product ID: {$productId} - {$product['name']}"]);
                        break;
                        
                    case 'suspend':
                        $stmt = $pdo->prepare("UPDATE products SET status = 'suspended', updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$productId]);
                        $message = "Product '{$product['name']}' suspended successfully!";
                        
                        // Log admin action
                        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, timestamp) VALUES (?, 'product_suspend', ?, NOW())");
                        $stmt->execute([$_SESSION['user_id'], "Suspended product ID: {$productId} - {$product['name']}"]);
                        break;
                        
                    case 'reactivate':
                        $stmt = $pdo->prepare("UPDATE products SET status = 'active', updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$productId]);
                        $message = "Product '{$product['name']}' reactivated successfully!";
                        
                        // Log admin action
                        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, timestamp) VALUES (?, 'product_reactivate', ?, NOW())");
                        $stmt->execute([$_SESSION['user_id'], "Reactivated product ID: {$productId} - {$product['name']}"]);
                        break;
                        
                    case 'delete':
                        // Check if product has orders first
                        $stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = ?");
                        $stmt->execute([$productId]);
                        $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['order_count'];
                        
                        if ($orderCount > 0) {
                            $error = "Cannot delete product '{$product['name']}' because it has existing orders. Consider suspending it instead.";
                        } else {
                            // Delete product images first (if you have image management)
                            $stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
                            $stmt->execute([$productId]);
                            
                            // Delete the product
                            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                            $stmt->execute([$productId]);
                            $message = "Product '{$product['name']}' deleted successfully!";
                            
                            // Log admin action
                            $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, timestamp) VALUES (?, 'product_delete', ?, NOW())");
                            $stmt->execute([$_SESSION['user_id'], "Deleted product ID: {$productId} - {$product['name']}"]);
                        }
                        break;
                }
                
            } else {
                $error = "Product not found.";
            }
        } catch (Exception $e) {
            $error = "An error occurred while processing the action: " . $e->getMessage();
        }
    } else {
        $error = "Invalid action specified.";
    }
    
    // Redirect to prevent resubmission on refresh
    $redirectUrl = "admin-products.php";
    if (isset($_GET['status'])) {
        $redirectUrl .= "?status=" . urlencode($_GET['status']);
    }
    if (isset($_GET['page'])) {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "page=" . intval($_GET['page']);
    }
    
    $_SESSION['admin_message'] = $message;
    $_SESSION['admin_error'] = $error;
    
    header("Location: $redirectUrl");
    exit();
}

// Handle admin notes update
if (isset($_POST['update_notes'])) {
    $productId = intval($_POST['product_id']);
    $adminNotes = sanitizeInput($_POST['admin_notes']);
    
    try {
        $stmt = $pdo->prepare("UPDATE products SET admin_notes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$adminNotes, $productId]);
        
        // Log admin action
        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, timestamp) VALUES (?, 'product_notes_update', ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], "Updated notes for product ID: {$productId}"]);
        
        $_SESSION['admin_message'] = "Admin notes updated successfully!";
    } catch (Exception $e) {
        $_SESSION['admin_error'] = "Error updating notes: " . $e->getMessage();
    }
    
    header("Location: admin-products.php" . (isset($_GET['status']) ? "?status=" . urlencode($_GET['status']) : ""));
    exit();
}

// Display stored messages
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}
if (isset($_SESSION['admin_error'])) {
    $error = $_SESSION['admin_error'];
    unset($_SESSION['admin_error']);
}

// Get products with filter
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on filters
$query = "SELECT p.*, u.username as seller_name, u.email as seller_email, c.name as category_name 
          FROM products p 
          JOIN users u ON p.seller_id = u.id 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];
$countParams = [];

if ($statusFilter !== 'all') {
    $query .= " AND p.status = ?";
    $params[] = $statusFilter;
    $countParams[] = $statusFilter;
}

if (!empty($searchTerm)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR u.username LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
}

$query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Get products
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key + 1, $value, $paramType);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM products p JOIN users u ON p.seller_id = u.id WHERE 1=1";
if ($statusFilter !== 'all') {
    $countQuery .= " AND p.status = ?";
}
if (!empty($searchTerm)) {
    $countQuery .= " AND (p.name LIKE ? OR p.description LIKE ? OR u.username LIKE ?)";
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalProducts / $limit);

// Get statistics
$stats = [];
$statusList = ['pending', 'active', 'rejected', 'suspended'];
foreach ($statusList as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE status = ?");
    $stmt->execute([$status]);
    $stats[$status] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}
?>

<style>
.admin-header {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #3498db;
}

.stat-card.pending { border-left-color: #f39c12; }
.stat-card.active { border-left-color: #27ae60; }
.stat-card.rejected { border-left-color: #e74c3c; }
.stat-card.suspended { border-left-color: #95a5a6; }

.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #2c3e50;
}

.stat-label {
    color: #7f8c8d;
    text-transform: uppercase;
    font-size: 0.9em;
}

.admin-filters {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filter-row {
    display: flex;
    gap: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.status-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.status-filters a {
    padding: 8px 15px;
    background: #ecf0f1;
    color: #2c3e50;
    text-decoration: none;
    border-radius: 20px;
    transition: all 0.3s ease;
    font-size: 14px;
}

.status-filters a:hover {
    background: #d5dbdb;
}

.status-filters a.active {
    background: #3498db;
    color: white;
}

.search-box {
    display: flex;
    gap: 10px;
}

.search-box input {
    padding: 8px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 200px;
}

.search-box button {
    padding: 8px 15px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.admin-table {
    width: 100%;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-collapse: collapse;
}

.admin-table th {
    background: #34495e;
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.admin-table td {
    padding: 15px;
    border-bottom: 1px solid #ecf0f1;
    vertical-align: top;
}

.admin-table tr:hover {
    background: #f8f9fa;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-active { background: #d4edda; color: #155724; }
.status-rejected { background: #f8d7da; color: #721c24; }
.status-suspended { background: #e2e3e5; color: #383d41; }

.action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.action-buttons a, .action-buttons button {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-view { background: #17a2b8; color: white; }
.btn-approve { background: #28a745; color: white; }
.btn-reject { background: #dc3545; color: white; }
.btn-suspend { background: #6c757d; color: white; }
.btn-reactivate { background: #28a745; color: white; }
.btn-delete { background: #dc3545; color: white; }
.btn-notes { background: #007bff; color: white; }

.btn-view:hover { background: #138496; }
.btn-approve:hover { background: #218838; }
.btn-reject:hover { background: #c82333; }
.btn-suspend:hover { background: #5a6268; }
.btn-reactivate:hover { background: #218838; }
.btn-delete:hover { background: #c82333; }
.btn-notes:hover { background: #0056b3; }

.success-message, .error-message {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    font-weight: 600;
}

.success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}

.pagination a {
    padding: 8px 12px;
    background: white;
    color: #3498db;
    text-decoration: none;
    border-radius: 5px;
    border: 1px solid #ddd;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #3498db;
    color: white;
}

.pagination a.active {
    background: #3498db;
    color: white;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.close {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    resize: vertical;
    font-family: inherit;
}

.form-group button {
    background: #3498db;
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}

.form-group button:hover {
    background: #2980b9;
}

@media (max-width: 768px) {
    .admin-stats {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .admin-table {
        font-size: 14px;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 10px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="admin-header">
    <h1><i class="fas fa-box"></i> Product Management</h1>
    <p>Manage and moderate products from sellers</p>
</div>

<!-- Statistics Cards -->
<div class="admin-stats">
    <div class="stat-card pending">
        <div class="stat-number"><?php echo $stats['pending']; ?></div>
        <div class="stat-label">Pending Review</div>
    </div>
    <div class="stat-card active">
        <div class="stat-number"><?php echo $stats['active']; ?></div>
        <div class="stat-label">Active Products</div>
    </div>
    <div class="stat-card rejected">
        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
        <div class="stat-label">Rejected</div>
    </div>
    <div class="stat-card suspended">
        <div class="stat-number"><?php echo $stats['suspended']; ?></div>
        <div class="stat-label">Suspended</div>
    </div>
</div>

<!-- Display Messages -->
<?php if (!empty($message)): ?>
    <div class="success-message">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="error-message">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-filters">
    <div class="filter-row">
        <div>
            <strong>Filter by Status:</strong>
            <div class="status-filters">
                <a href="admin-products.php?<?php echo !empty($searchTerm) ? "search=" . urlencode($searchTerm) . "&" : ""; ?>status=all" class="<?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All (<?php echo array_sum($stats); ?>)</a>
                <a href="admin-products.php?<?php echo !empty($searchTerm) ? "search=" . urlencode($searchTerm) . "&" : ""; ?>status=pending" class="<?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending (<?php echo $stats['pending']; ?>)</a>
                <a href="admin-products.php?<?php echo !empty($searchTerm) ? "search=" . urlencode($searchTerm) . "&" : ""; ?>status=active" class="<?php echo $statusFilter === 'active' ? 'active' : ''; ?>">Active (<?php echo $stats['active']; ?>)</a>
                <a href="admin-products.php?<?php echo !empty($searchTerm) ? "search=" . urlencode($searchTerm) . "&" : ""; ?>status=rejected" class="<?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">Rejected (<?php echo $stats['rejected']; ?>)</a>
                <a href="admin-products.php?<?php echo !empty($searchTerm) ? "search=" . urlencode($searchTerm) . "&" : ""; ?>status=suspended" class="<?php echo $statusFilter === 'suspended' ? 'active' : ''; ?>">Suspended (<?php echo $stats['suspended']; ?>)</a>
            </div>
        </div>
        
        <div>
            <strong>Search Products:</strong>
            <form method="GET" class="search-box">
                <?php if ($statusFilter !== 'all'): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                <?php endif; ?>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search products, sellers...">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($searchTerm)): ?>
                    <a href="admin-products.php?<?php echo $statusFilter !== 'all' ? "status=" . urlencode($statusFilter) : ""; ?>" class="btn-clear">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="admin-content">
    <h2>Products List (<?php echo $totalProducts; ?> total)</h2>
    
    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 40px; background: white; border-radius: 10px;">
            <i class="fas fa-box-open" style="font-size: 48px; color: #bdc3c7; margin-bottom: 15px;"></i>
            <h3 style="color: #7f8c8d;">No products found</h3>
            <p style="color: #bdc3c7;">
                <?php if (!empty($searchTerm)): ?>
                    Try adjusting your search terms or filters.
                <?php else: ?>
                    Products will appear here once sellers start adding them.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product Info</th>
                    <th>Seller</th>
                    <th>Price & Stock</th>
                    <th>Status</th>
                    <th>Dates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong>#<?php echo $product['id']; ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong><br>
                            <small style="color: #7f8c8d;">
                                Category: <?php echo htmlspecialchars($product['category_name'] ?: 'N/A'); ?>
                            </small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($product['seller_name']); ?></strong><br>
                            <small style="color: #7f8c8d;"><?php echo htmlspecialchars($product['seller_email']); ?></small>
                        </td>
                        <td>
                            <strong>₱<?php echo number_format($product['price'], 2); ?></strong><br>
                            <small style="color: #7f8c8d;">Stock: <?php echo $product['stock_quantity']; ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $product['status']; ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </span>
                        </td>
                        <td>
                            <small>
                                Created: <?php echo date('M j, Y', strtotime($product['created_at'])); ?><br>
                                <?php if (!empty($product['updated_at']) && $product['updated_at'] !== '0000-00-00 00:00:00'): ?>
                                    Updated: <?php echo date('M j, Y', strtotime($product['updated_at'])); ?>
                                <?php else: ?>
                                    <span style="color: #bdc3c7;">Not updated</span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="../product-detail.php?id=<?php echo $product['id']; ?>" target="_blank" class="btn-view" title="View Product">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                
                                <?php if ($product['status'] === 'pending'): ?>
                                    <a href="admin-products.php?action=approve&id=<?php echo $product['id']; ?>&<?php echo http_build_query(['status' => $statusFilter, 'page' => $page, 'search' => $searchTerm]); ?>" 
                                       class="btn-approve" title="Approve Product"
                                       onclick="return confirm('Are you sure you want to approve this product?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="admin-products.php?action=reject&id=<?php echo $product['id']; ?>&<?php echo http_build_query(['status' => $statusFilter, 'page' => $page, 'search' => $searchTerm]); ?>" 
                                       class="btn-reject" title="Reject Product"
                                       onclick="return confirm('Are you sure you want to reject this product?')">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($product['status'] === 'active'): ?>
                                    <a href="admin-products.php?action=suspend&id=<?php echo $product['id']; ?>&<?php echo http_build_query(['status' => $statusFilter, 'page' => $page, 'search' => $searchTerm]); ?>" 
                                       class="btn-suspend" title="Suspend Product"
                                       onclick="return confirm('Are you sure you want to suspend this product?')">
                                        <i class="fas fa-pause"></i> Suspend
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (in_array($product['status'], ['rejected', 'suspended'])): ?>
                                    <a href="admin-products.php?action=reactivate&id=<?php echo $product['id']; ?>&<?php echo http_build_query(['status' => $statusFilter, 'page' => $page, 'search' => $searchTerm]); ?>" 
                                       class="btn-reactivate" title="Reactivate Product"
                                       onclick="return confirm('Are you sure you want to reactivate this product?')">
                                        <i class="fas fa-play"></i> Reactivate
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" class="btn-notes" title="Edit Admin Notes"
                                        onclick="openNotesModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['admin_notes'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-sticky-note"></i> Notes
                                </button>
                                
                                <a href="admin-products.php?action=delete&id=<?php echo $product['id']; ?>&<?php echo http_build_query(['status' => $statusFilter, 'page' => $page, 'search' => $searchTerm]); ?>" 
                                   class="btn-delete" title="Delete Product"
                                   onclick="return confirm('Are you sure you want to permanently delete this product? This action cannot be undone!')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $baseUrl = "admin-products.php?" . http_build_query(['status' => $statusFilter, 'search' => $searchTerm]);
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php 
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="<?php echo $baseUrl; ?>&page=<?php echo $i; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="<?php echo $baseUrl; ?>&page=<?php echo $page + 1; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Notes Modal -->
<div id="notesModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeNotesModal()">&times;</span>
        <h2><i class="fas fa-sticky-note"></i> Admin Notes</h2>
        <form method="POST" action="">
            <input type="hidden" id="modalProductId" name="product_id" value="">
            
            <div class="form-group">
                <label for="admin_notes">Internal Notes:</label>
                <textarea id="admin_notes" name="admin_notes" rows="5" 
                          placeholder="Add internal notes about this product for admin reference..."></textarea>
                <small style="color: #7f8c8d; font-style: italic;">
                    These notes are only visible to administrators and are used for internal tracking.
                </small>
            </div>
            
            <button type="submit" name="update_notes">
                <i class="fas fa-save"></i> Save Notes
            </button>
        </form>
    </div>
</div>

<script>
// Update sort function
function updateSort(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page'); // Reset to first page when sorting
    window.location.href = url.toString();
}

function openNotesModal(productId, currentNotes) {
    document.getElementById('modalProductId').value = productId;
    document.getElementById('admin_notes').value = currentNotes || '';
    document.getElementById('notesModal').style.display = 'block';
    document.getElementById('admin_notes').focus();
}

function closeNotesModal() {
    document.getElementById('notesModal').style.display = 'none';
    document.getElementById('admin_notes').value = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('notesModal');
    if (event.target === modal) {
        closeNotesModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeNotesModal();
    }
});

// Auto-resize textarea
document.getElementById('admin_notes').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

// Confirmation for sensitive actions
document.querySelectorAll('.btn-delete, .btn-reject').forEach(function(button) {
    button.addEventListener('click', function(e) {
        const action = this.classList.contains('btn-delete') ? 'delete' : 'reject';
        const confirmText = action === 'delete' ? 
            'Are you sure you want to permanently delete this product? This action cannot be undone!' :
            'Are you sure you want to reject this product?';
        
        if (!confirm(confirmText)) {
            e.preventDefault();
        }
    });
});

// Show loading state for actions
document.querySelectorAll('.action-buttons a').forEach(function(button) {
    button.addEventListener('click', function() {
        if (this.classList.contains('btn-delete') || 
            this.classList.contains('btn-approve') || 
            this.classList.contains('btn-reject') || 
            this.classList.contains('btn-suspend') || 
            this.classList.contains('btn-reactivate')) {
            
            // Add loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            this.style.pointerEvents = 'none';
            
            // Reset after 5 seconds in case of error
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.pointerEvents = 'auto';
            }, 5000);
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>