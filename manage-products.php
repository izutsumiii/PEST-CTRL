<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

// Handle product actions (add, edit, delete, toggle)
if (isset($_POST['add_product'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $categoryIds = isset($_POST['category_id']) ? $_POST['category_id'] : [];
    $categoryId = !empty($categoryIds) ? intval($categoryIds[0]) : 0;
    $stockQuantity = intval($_POST['stock_quantity']);
    
    $imageUrl = 'assets/uploads/tempo_image.jpg';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $imageUrl = $uploadFile;
            }
        }
    }
    
    if (addProduct($name, $description, $price, $categoryId, $userId, $stockQuantity, $imageUrl)) {
        $_SESSION['product_message'] = ['type' => 'success', 'text' => 'Product added successfully!'];
    } else {
        $_SESSION['product_message'] = ['type' => 'error', 'text' => 'Error adding product.'];
    }
    header("Location: manage-products.php");
    exit();
}

if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    header("Location: manage-products.php");
    exit();
}

if (isset($_GET['toggle_status'])) {
    $productId = intval($_GET['toggle_status']);
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $newStatus = $product['status'] == 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $productId])) {
            $statusText = $newStatus == 'active' ? 'activated' : 'deactivated';
            $_SESSION['product_message'] = ['type' => 'success', 'text' => "Product $statusText successfully!"];
        }
    }
    header("Location: manage-products.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE seller_id IS NULL ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeProducts = getSellerActiveProducts($userId);
$inactiveProducts = getSellerInactiveProducts($userId);
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
    margin: 0 0 40px 0;
}

.add-product-card {
    background: #1a0a2e;
    border: 1px solid #2d1b4e;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.add-product-card h2 {
    color: #FFD736;
    margin: 0 0 25px 0;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(255,215,54,0.3);
    font-size: 24px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 30px;
    margin-bottom: 25px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #FFD736;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,215,54,0.3);
    border-radius: 8px;
    color: #F9F9F9;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 3px rgba(255,215,54,0.2);
    background: rgba(255,255,255,0.15);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.upload-area {
    width: 100%;
    min-height: 280px;
    border: 2px dashed rgba(255,215,54,0.5);
    border-radius: 12px;
    background: rgba(255,255,255,0.05);
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-area:hover {
    border-color: #FFD736;
    background: rgba(255,215,54,0.1);
}

.upload-placeholder {
    text-align: center;
    color: #FFD736;
    padding: 20px;
}

.upload-placeholder svg {
    margin-bottom: 10px;
    opacity: 0.7;
}

.upload-placeholder p {
    margin: 8px 0 4px 0;
    font-weight: 600;
}

.upload-placeholder small {
    color: #bbb;
    font-size: 12px;
}

.image-preview {
    width: 100%;
    height: 280px;
    object-fit: cover;
    border-radius: 8px;
}

.category-selection-container {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid rgba(255,215,54,0.3);
    border-radius: 8px;
    padding: 12px;
    background: rgba(255,255,255,0.05);
}

.category-group {
    margin-bottom: 8px;
}

.parent-category {
    background: rgba(255,215,54,0.1);
    border-left: 3px solid #FFD736;
    border-radius: 6px;
}

.parent-label {
    padding: 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: background 0.2s ease;
}

.parent-label:hover {
    background: rgba(255,215,54,0.15);
}

.parent-name {
    color: #FFD736;
    font-weight: 700;
    font-size: 14px;
    text-transform: uppercase;
}

.toggle-children {
    color: #FFD736;
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
}

.toggle-children.rotated svg {
    transform: rotate(-90deg);
}

.child-categories {
    padding: 8px 0 8px 16px;
    background: rgba(0,0,0,0.2);
    max-height: 500px;
    overflow: hidden;
    transition: max-height 0.3s ease, padding 0.3s ease;
}

.child-categories.collapsed {
    max-height: 0;
    padding: 0 0 0 16px;
}

.child-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    margin: 4px 0;
    border-radius: 6px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.child-label:hover {
    background: rgba(255,215,54,0.1);
}

.child-label:has(.category-checkbox:checked) {
    background: rgba(255,215,54,0.15);
    border-left: 2px solid #FFD736;
}

.category-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #FFD736;
}

.child-name {
    color: #F9F9F9;
    font-size: 13px;
}

.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,215,54,0.2);
}

.form-actions button {
    flex: 1;
    padding: 14px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.form-actions button[type="submit"] {
    background: #FFD736;
    color: #130325;
}

.form-actions button[type="submit"]:hover:not(:disabled) {
    background: #e6c230;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255,215,54,0.4);
}

.form-actions button[type="submit"]:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn-reset {
    background: rgba(255,255,255,0.1);
    color: #F9F9F9;
    border: 1px solid rgba(255,255,255,0.3);
}

.btn-reset:hover {
    background: rgba(255,255,255,0.15);
}

.products-section {
    margin-top: 40px;
}

.products-section h2 {
    color: #FFD736;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid rgba(255,215,54,0.3);
    font-size: 22px;
}

.table-wrapper {
    background: #1a0a2e;
    border: 1px solid #2d1b4e;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    margin-bottom: 30px;
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

.product-thumbnail {
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

.products-table a {
    color: #FFD736;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.products-table a:hover {
    color: #fff;
}

.no-products {
    background: #1a0a2e;
    border: 1px solid #2d1b4e;
    color: #F9F9F9;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    opacity: 0.8;
}

@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    main { padding: 70px 15px 60px 15px !important; }
    .add-product-card { padding: 20px; }
    .form-row { grid-template-columns: 1fr; }
    .form-actions { flex-direction: column; }
    .products-table { font-size: 12px; }
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

    <h1>Product Management</h1>

    <div class="add-product-card">
        <h2>Add New Product</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-left">
                    <div class="form-group">
                        <label for="product_name">Product Name</label>
                        <input type="text" id="product_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_description">Description</label>
                        <textarea id="product_description" name="description" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price (₱)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Categories</label>
                        <div class="category-selection-container">
                            <?php if (empty($categories)): ?>
                                <p style="color: #dc3545; text-align: center; padding: 20px;">No categories available</p>
                            <?php else: ?>
                                <?php 
                                $groupedCats = [];
                                foreach ($categories as $cat) {
                                    if ($cat['parent_id'] === null) {
                                        $groupedCats[$cat['id']] = ['info' => $cat, 'children' => []];
                                    }
                                }
                                
                                foreach ($categories as $cat) {
                                    if ($cat['parent_id'] !== null && isset($groupedCats[$cat['parent_id']])) {
                                        $groupedCats[$cat['parent_id']]['children'][] = $cat;
                                    }
                                }
                                
                                foreach ($groupedCats as $parentId => $data): 
                                    $parent = $data['info'];
                                    $hasChildren = !empty($data['children']);
                                ?>
                                    <div class="category-group">
                                        <div class="parent-category">
                                            <div class="parent-label" onclick="toggleChildren(<?php echo $parent['id']; ?>, event)">
                                                <span class="parent-name"><?php echo htmlspecialchars($parent['name']); ?></span>
                                                <?php if ($hasChildren): ?>
                                                    <span class="toggle-children" id="toggle-<?php echo $parent['id']; ?>">
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                            <path d="M8 11L3 6h10l-5 5z"/>
                                                        </svg>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($hasChildren): ?>
                                            <div class="child-categories collapsed" id="children_<?php echo $parent['id']; ?>">
                                                <?php foreach ($data['children'] as $child): ?>
                                                    <label class="child-label">
                                                        <input type="checkbox" name="category_id[]" value="<?php echo $child['id']; ?>" class="category-checkbox">
                                                        <span class="child-name"><?php echo htmlspecialchars($child['name']); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-right">
                    <div class="form-group">
                        <label>Product Image</label>
                        <div class="upload-area" id="upload-area" onclick="document.getElementById('image').click()">
                            <div class="upload-placeholder" id="upload-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                <p>Click to upload</p>
                                <small>PNG, JPG, GIF up to 10MB</small>
                            </div>
                            <img id="image-preview" src="" alt="Preview" class="image-preview" style="display: none;">
                        </div>
                        <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(event)" style="display: none;">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="add_product" <?php echo empty($categories) ? 'disabled' : ''; ?>>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add Product
                </button>
                <button type="reset" class="btn-reset" onclick="resetForm()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Reset
                </button>
            </div>
        </form>
    </div>
    
    <div class="products-section">
        <h2>Active Products</h2>
        <?php if (empty($activeProducts)): ?>
            <div class="no-products">No active products found.</div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeProducts as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td><span class="status-badge status-active">Active</span></td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>">Edit</a> |
                                    <a href="manage-products.php?toggle_status=<?php echo $product['id']; ?>">Deactivate</a> |
                                    <a href="manage-products.php?delete=<?php echo $product['id']; ?>" onclick="return confirm('Delete this product?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <h2>Inactive Products</h2>
        <?php if (empty($inactiveProducts)): ?>
            <div class="no-products">No inactive products found.</div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inactiveProducts as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-thumbnail">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo $product['stock_quantity']; ?></td>
                                <td><span class="status-badge status-inactive">Inactive</span></td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>">Edit</a> |
                                    <a href="manage-products.php?toggle_status=<?php echo $product['id']; ?>">Activate</a> |
                                    <a href="manage-products.php?delete=<?php echo $product['id']; ?>" onclick="return confirm('Delete this product?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</main>

<script>
function toggleChildren(parentId, event) {
    event.stopPropagation();
    const childContainer = document.getElementById('children_' + parentId);
    const toggleIcon = document.getElementById('toggle-' + parentId);
    
    if (childContainer.classList.contains('collapsed')) {
        childContainer.classList.remove('collapsed');
        toggleIcon.classList.add('rotated');
    } else {
        childContainer.classList.add('collapsed');
        toggleIcon.classList.remove('rotated');
    }
}

function previewImage(event) {
    const reader = new FileReader();
    const imagePreview = document.getElementById('image-preview');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    
    reader.onload = function() {
        if (reader.readyState == 2) {
            imagePreview.src = reader.result;
            imagePreview.style.display = 'block';
            uploadPlaceholder.style.display = 'none';
        }
    }
    
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    }
}

function resetForm() {
    document.getElementById('image-preview').style.display = 'none';
    document.getElementById('upload-placeholder').style.display = 'flex';
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

    // Sidebar state handling
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

    // Form validation
    const form = document.querySelector('form[method="POST"]');
    const submitButton = form.querySelector('button[name="add_product"]');
    const productName = document.getElementById('product_name');
    const price = document.getElementById('price');
    const stockQuantity = document.getElementById('stock_quantity');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    
    function validateForm() {
        const nameValid = productName.value.trim() !== '';
        const priceValid = price.value.trim() !== '' && parseFloat(price.value) >= 0;
        const stockValid = stockQuantity.value.trim() !== '' && parseInt(stockQuantity.value) >= 0;
        
        let categorySelected = false;
        categoryCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                categorySelected = true;
            }
        });
        
        if (nameValid && priceValid && stockValid && categorySelected) {
            submitButton.disabled = false;
            submitButton.style.opacity = '1';
            submitButton.style.cursor = 'pointer';
        } else {
            submitButton.disabled = true;
            submitButton.style.opacity = '0.6';
            submitButton.style.cursor = 'not-allowed';
        }
    }
    
    validateForm();
    
    productName.addEventListener('input', validateForm);
    price.addEventListener('input', validateForm);
    stockQuantity.addEventListener('input', validateForm);
    
    categoryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', validateForm);
    });
});
</script>
