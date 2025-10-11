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
    
    // Verify the product belongs to the seller
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch();
    
    if ($product) {
        $newStatus = $product['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $productId])) {
            $_SESSION['product_message'] = ['type' => 'success', 'text' => 'Product status updated successfully!'];
        } else {
            $_SESSION['product_message'] = ['type' => 'error', 'text' => 'Error updating product status.'];
        }
    }
    header("Location: view-products.php");
    exit();
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    
    // Verify the product belongs to the seller
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch();
    
    if ($product) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$productId])) {
            $_SESSION['product_message'] = ['type' => 'success', 'text' => 'Product deleted successfully!'];
        } else {
            $_SESSION['product_message'] = ['type' => 'error', 'text' => 'Error deleting product.'];
        }
    }
    header("Location: view-products.php");
    exit();
}
?>

<style>
html, body { background:#130325 !important; margin:0; padding:0; }
main { background:transparent !important; margin-left: 120px !important; padding: 20px 30px 60px 30px !important; min-height: calc(100vh - 60px) !important; transition: margin-left 0.3s ease; margin-top: -20px !important; }
main.sidebar-collapsed { margin-left: 0px !important; }
h1 { color:#F9F9F9 !important; font-family:var(--font-primary) !important; font-size:24px !important; font-weight:700 !important; text-align:left !important; margin:0 0 15px 0 !important; padding-left:20px !important; background:none !important; text-shadow:none !important; }

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
    margin-top: -20px !important;
}

/* Search bar styling */
.search-container {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.search-bar {
    width: 100%;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 6px;
    color: #130325;
    font-size: 16px;
    transition: all 0.3s ease;
}

.search-bar:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 3px rgba(255, 215, 54, 0.3);
}

.search-bar::placeholder {
    color: #666;
}

/* Products table styling */
.table-wrapper {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.products-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.products-table thead {
    background: rgba(255, 255, 255, 0.1);
    position: sticky;
    top: 0;
    z-index: 10;
}

.products-table th {
    padding: 12px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #FFD736;
    border-bottom: 2px solid rgba(255,255,255,0.2);
}

.products-table td {
    padding: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    color: #F9F9F9;
}

.products-table tbody tr {
    background: rgba(255, 255, 255, 0.03);
    transition: all 0.15s ease-in-out;
}

.products-table tbody tr:hover {
    background: #1a0a2e !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.products-table tbody tr:hover td, 
.products-table tbody tr:hover a, 
.products-table tbody tr:hover span { 
    color: #F9F9F9 !important; 
}

/* Status badges */
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid #28a745;
}

.status-inactive {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid #dc3545;
}

/* Action buttons */
.action-btn {
    padding: 6px 12px;
    margin: 2px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-edit {
    background: #FFD736;
    color: #130325;
}

.btn-edit:hover {
    background: #e6c230;
    transform: translateY(-1px);
}

.btn-toggle {
    background: #17a2b8;
    color: white;
}

.btn-toggle:hover {
    background: #138496;
    transform: translateY(-1px);
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.no-products {
    text-align: center;
    color: #F9F9F9;
    padding: 40px;
    font-style: italic;
}

@media (max-width: 768px) {
    main { padding: 70px 15px 60px 15px !important; }
    .products-table { font-size: 12px; }
    .action-btn { padding: 4px 8px; font-size: 10px; }
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

    <!-- Search Bar -->
    <div class="search-container">
        <input type="text" class="search-bar" id="productSearch" placeholder="Search products by name, description, category, or ID..." onkeyup="filterProducts()">
    </div>

    <?php if (empty($allProducts)): ?>
        <div class="no-products">No products found.</div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProducts as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image"
                                     onerror="this.src='assets/uploads/tempo_image.jpg'">
                            </td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td>
                                <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="action-btn btn-edit">Edit</a>
                                <a href="?toggle_status=<?php echo $product['id']; ?>" 
                                   class="action-btn btn-toggle"
                                   onclick="return confirm('Are you sure you want to <?php echo $product['is_active'] ? 'deactivate' : 'activate'; ?> this product?')">
                                    <?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <a href="?delete=<?php echo $product['id']; ?>" 
                                   class="action-btn btn-delete"
                                   onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                    Delete
                                </a>
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
// Search functionality
function filterProducts() {
    const searchTerm = document.getElementById('productSearch').value.toLowerCase();
    const productRows = document.querySelectorAll('.products-table tbody tr');
    
    productRows.forEach(row => {
        const productId = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
        const productName = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
        const productDesc = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
        const productCategory = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
        
        if (productId.includes(searchTerm) || 
            productName.includes(searchTerm) || 
            productDesc.includes(searchTerm) || 
            productCategory.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Handle sidebar state changes for proper margin adjustment
document.addEventListener('DOMContentLoaded', function() {
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
    
    const hamburgerBtn = document.querySelector('.header-hamburger');
    if (hamburgerBtn) {
        hamburgerBtn.addEventListener('click', function() {
            setTimeout(updateMainMargin, 100);
        });
    }
});
</script>

</body>
</html>
