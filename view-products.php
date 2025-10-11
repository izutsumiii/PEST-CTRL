<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

// Get all products for the seller
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.seller_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$allProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle product status toggle
if (isset($_GET['toggle_status'])) {
    $productId = intval($_GET['toggle_status']);
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch();
    
    if ($product) {
        $newStatus = $product['status'] == 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $productId])) {
            $statusText = $newStatus == 'active' ? 'activated' : 'deactivated';
            $_SESSION['product_message'] = ['type' => 'success', 'text' => "Product $statusText successfully!"];
        }
    }
    header("Location: view-products.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch();
    
    if ($product) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE product_id = ?");
            $stmt->execute([$productId]);
            $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
            $stmt->execute([$productId]);
            $orderItemsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($cartCount > 0 || $orderItemsCount > 0) {
                $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$productId]);
                
                if ($cartCount > 0) {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
                    $stmt->execute([$productId]);
                }
                
                $pdo->commit();
                $_SESSION['product_message'] = ['type' => 'success', 'text' => 'Product deactivated successfully.'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                
                $pdo->commit();
                $_SESSION['product_message'] = ['type' => 'success', 'text' => 'Product deleted successfully!'];
            }
            
        } catch (Exception $e) {
            $pdo->rollback();
            $_SESSION['product_message'] = ['type' => 'error', 'text' => 'Error deleting product.'];
        }
    }
    header("Location: view-products.php");
    exit();
}
?>

<style>
html, body { background:#130325 !important; margin:0; padding:0; }
main { background:transparent !important; margin-left: 120px !important; padding: 15px 30px 60px 30px !important; min-height: calc(100vh - 60px) !important; transition: margin-left 0.3s ease; margin-top: -35px !important; }
main.sidebar-collapsed { margin-left: 0px !important; }

.notification-toast {
    position: fixed;
    top: 100px;
    right: 20px;
    max-width: 400px;
    background: #1a0a2e;
    border: 1px solid rgba(255,215,54,0.5);
    border-left: 4px solid #FFD736;
    border-radius: 10px;
    padding: 16px 20px;
    color: #F9F9F9;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    z-index: 10000;
    animation: slideInRight 0.3s ease;
}

.notification-toast.success { border-left-color: #28a745; }
.notification-toast.error { border-left-color: #dc3545; }

@keyframes slideInRight {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.products-container {
    max-width: 1600px;
    margin: 0 auto;
}

.products-container h1 {
    color: #F9F9F9 !important;
    font-family: var(--font-primary) !important;
    font-size: 24px !important;
    font-weight: 700 !important;
    text-align: left !important;
    margin: 0 0 15px 0 !important;
    padding-left: 20px !important;
    background: none !important;
    text-shadow: none !important;
}

.products-container > p {
    color: #ffffff;
    text-align: center;
    opacity: 0.95;
    margin: 0 0 30px 0;
}

.search-card {
    background: #1a0a2e;
    border: 1px solid #2d1b4e;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.search-wrapper {
    display: flex;
    gap: 12px;
    align-items: center;
}

.search-input-group {
    display: flex;
    gap: 8px;
    flex: 1;
}

.search-bar {
    flex: 1;
    padding: 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,215,54,0.3);
    border-radius: 8px;
    color: #F9F9F9;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-bar:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 3px rgba(255,215,54,0.2);
    background: rgba(255,255,255,0.15);
}

.search-bar::placeholder {
    color: rgba(249,249,249,0.5);
}

.search-btn {
    padding: 12px 16px;
    background: #FFD736;
    color: #130325;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-btn:hover {
    background: #e6c230;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,215,54,0.4);
}

.filter-dropdown {
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

.filter-dropdown:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 3px rgba(255,215,54,0.2);
}

.filter-dropdown option {
    background: #1a0a2e;
    color: #F9F9F9;
}

.table-wrapper {
    background: #1a0a2e;
    border: 1px solid #2d1b4e;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table thead {
    background: rgba(255,215,54,0.1);
    border-bottom: 2px solid #FFD736;
}

.products-table th {
    padding: 14px 12px;
    text-align: left;
    color: #FFD736;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.products-table td {
    padding: 14px 12px;
    color: #F9F9F9;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.products-table tbody tr {
    transition: all 0.2s ease;
}

.products-table tbody tr:hover {
    background: rgba(255,215,54,0.05);
}

.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #FFD736;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.status-active {
    background: rgba(40,167,69,0.2);
    color: #28a745;
    border: 1px solid #28a745;
}

.status-inactive {
    background: rgba(220,53,69,0.2);
    color: #dc3545;
    border: 1px solid #dc3545;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.action-btn {
    width: 100%;
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.btn-edit {
    background: #007bff;
    color: white;
}

.btn-edit:hover {
    background: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.4);
}

.btn-toggle {
    background: #28a745;
    color: white;
}

.btn-toggle:hover {
    background: #1e7e34;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.4);
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.4);
}

.no-products {
    background: #1a0a2e;
    border: 1px solid #2d1b4e;
    color: #F9F9F9;
    border-radius: 12px;
    padding: 60px 20px;
    text-align: center;
}

.no-products i {
    font-size: 64px;
    color: #FFD736;
    margin-bottom: 20px;
    display: block;
}

@media (max-width: 768px) {
    main { padding: 15px 15px 60px 15px !important; }
    .search-wrapper { flex-direction: column; }
    .filter-dropdown { width: 100%; }
    .products-table { font-size: 12px; }
    .action-buttons { flex-direction: row; }
}
</style>

<main>
<div class="products-container">
    <?php if (isset($_SESSION['product_message'])): ?>
        <div class="notification-toast <?php echo $_SESSION['product_message']['type']; ?>">
            <?php echo htmlspecialchars($_SESSION['product_message']['text']); ?>
        </div>
        <?php unset($_SESSION['product_message']); ?>
    <?php endif; ?>

    <h1>View Products</h1>

    <div class="search-card">
        <div class="search-wrapper">
            <div class="search-input-group">
                <input type="text" class="search-bar" id="productSearch" placeholder="Search by name, category, or ID..." onkeyup="filterProducts()">
                <button class="search-btn" onclick="filterProducts()" title="Search">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <select class="filter-dropdown" id="statusFilter" onchange="filterProducts()">
                <option value="all">All Products</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
            </select>
        </div>
    </div>

    <?php if (empty($allProducts)): ?>
        <div class="no-products">
            <i class="fas fa-box-open"></i>
            <p style="font-size: 18px; margin: 0;">No products found.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProducts as $product): ?>
                        <tr data-status="<?php echo htmlspecialchars($product['status'] ?? 'inactive'); ?>">
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image"
                                     onerror="this.src='assets/uploads/tempo_image.jpg'">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td>
                                <span class="status-badge <?php echo ($product['status'] == 'active') ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo ucfirst($product['status'] ?? 'inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?toggle_status=<?php echo $product['id']; ?>" 
                                       class="action-btn btn-toggle"
                                       onclick="return confirm('Are you sure you want to <?php echo ($product['status'] == 'active') ? 'deactivate' : 'activate'; ?> this product?')">
                                        <i class="fas fa-<?php echo ($product['status'] == 'active') ? 'pause' : 'play'; ?>"></i>
                                        <?php echo ($product['status'] == 'active') ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $product['id']; ?>" 
                                       class="action-btn btn-delete"
                                       onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</main>

<script>
function filterProducts() {
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    const productRows = document.querySelectorAll('.products-table tbody tr');
    
    productRows.forEach(row => {
        const productId = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
        const productName = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
        const productCategory = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
        const productStatus = row.getAttribute('data-status') || 'inactive';
        
        const matchesSearch = productId.includes(searchTerm) || 
                             productName.includes(searchTerm) || 
                             productCategory.includes(searchTerm);
        
        let matchesStatus = true;
        if (statusFilter === 'active') {
            matchesStatus = productStatus === 'active';
        } else if (statusFilter === 'inactive') {
            matchesStatus = productStatus === 'inactive';
        }
        
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const toast = document.querySelector('.notification-toast');
    if (toast) {
        setTimeout(function() {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 500);
        }, 4000);
    }

    const main = document.querySelector('main');
    const sidebar = document.getElementById('sellerSidebar');
    
    function updateMainMargin() {
        if (sidebar && sidebar.classList.contains('collapsed')) {
            main.classList.add('sidebar-collapsed');
        } else {
            main.classList.remove('sidebar-collapsed');
        }
    }
    
    updateMainMargin();
    
    const observer = new MutationObserver(updateMainMargin);
    if (sidebar) {
        observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    }
});
</script>

