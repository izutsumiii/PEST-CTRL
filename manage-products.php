<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

// Unify with seller dashboard theme
echo '<style>
body{background:#130325 !important;}
main{margin-left:240px;}
.section{background:rgba(255,255,255,0.1);padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.3);color:#F9F9F9;backdrop-filter:blur(10px)}
.admin-table, .products-table{width:100%;border-collapse:collapse;background:rgba(255,255,255,0.05);border-radius:10px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
.admin-table thead th, .products-table thead th{background:#34495e;color:#ffffff;text-align:left;padding:15px;font-weight:600;border-bottom:1px solid #2e3f50}
.admin-table td, .products-table td{padding:15px;border-bottom:1px solid rgba(255,255,255,0.1);color:#F9F9F9}
.admin-table tr:hover, .products-table tr:hover{background:#1a0a2e !important}
.btn{padding:8px 12px;border-radius:6px;font-weight:600;border:none;cursor:pointer}
.btn-primary{background:#FFD736;color:#130325}
.btn-primary:hover{filter:brightness(0.95)}
.btn-secondary{background:#6c757d;color:#fff}
</style>';

requireSeller();

$userId = $_SESSION['user_id'];

// Handle product actions (add, edit, delete, toggle)
if (isset($_POST['add_product'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $categoryIds = isset($_POST['category_id']) ? $_POST['category_id'] : [];
$categoryId = !empty($categoryIds) ? intval($categoryIds[0]) : 0; // Use first category for main category_id
    $stockQuantity = intval($_POST['stock_quantity']);
    
    // Handle image upload with default fallback
    $imageUrl = 'assets/uploads/tempo_image.jpg'; // Default image
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $fileType = $_FILES['image']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $uploadFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $imageUrl = $uploadFile;
            } else {
                echo "<p class='warning-message'>Image upload failed. Using default image.</p>";
            }
        } else {
            echo "<p class='warning-message'>Invalid file type. Please upload JPG, PNG, or GIF images only. Using default image.</p>";
        }
    }
    
    // Add product with automatic active status
    if (addProduct($name, $description, $price, $categoryId, $userId, $stockQuantity, $imageUrl)) {
        echo "<p class='success-message'>Product added successfully and is now live!</p>";
    } else {
        echo "<p class='error-message'>Error adding product. Please try again.</p>";
    }
}

if (isset($_POST['update_product'])) {
    $productId = intval($_POST['product_id']);
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $categoryId = intval($_POST['category_id']);
    $stockQuantity = intval($_POST['stock_quantity']);
    $status = sanitizeInput($_POST['status']);
    
    // Check if product belongs to the seller
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        if (updateProduct($productId, $name, $description, $price, $categoryId, $stockQuantity, $status)) {
            echo "<p class='success-message'>Product updated successfully!</p>";
        } else {
            echo "<p class='error-message'>Error updating product. Please try again.</p>";
        }
    } else {
        echo "<p class='error-message'>Error: You don't have permission to update this product.</p>";
    }
}

if (isset($_GET['delete'])) {
    $productId = intval($_GET['delete']);
    
    // Check if product belongs to the seller
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // First, check if product is in any carts
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart WHERE product_id = ?");
            $stmt->execute([$productId]);
            $cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Check if product has any order items
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
            $stmt->execute([$productId]);
            $orderItemsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($cartCount > 0 || $orderItemsCount > 0) {
                // Instead of deleting, just deactivate the product
                $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$productId]);
                
                if ($cartCount > 0) {
                    // Remove from all carts
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
                    $stmt->execute([$productId]);
                }
                
                $pdo->commit();
                echo "<p class='success-message'>Product deactivated and removed from carts (cannot be fully deleted due to order history).</p>";
            } else {
                // Safe to delete - no cart items or order history
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$productId]);
                
                $pdo->commit();
                echo "<p class='success-message'>Product deleted successfully!</p>";
            }
            
        } catch (Exception $e) {
            $pdo->rollback();
            echo "<p class='error-message'>Error deleting product: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='error-message'>Error: You don't have permission to delete this product.</p>";
    }
}

if (isset($_GET['toggle_status'])) {
    $productId = intval($_GET['toggle_status']);
    
    // Check if product belongs to the seller
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$productId, $userId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $newStatus = $product['status'] == 'active' ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $productId])) {
            $statusText = $newStatus == 'active' ? 'activated' : 'deactivated';
            echo "<p class='success-message'>Product $statusText successfully!</p>";
        } else {
            echo "<p class='error-message'>Error updating product status. Please try again.</p>";
        }
    } else {
        echo "<p class='error-message'>Error: You don't have permission to update this product.</p>";
    }
}

// Get all platform categories (admin-created categories with seller_id IS NULL)
$stmt = $pdo->prepare("SELECT * FROM categories WHERE seller_id IS NULL ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get seller's products
$activeProducts = getSellerActiveProducts($userId);
$inactiveProducts = getSellerInactiveProducts($userId);
?>

<h1>Manage Products</h1>

<div class="product-management">
    <div class="add-product-form">
    <h2>Add New Product</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-left">
                <div class="form-group">
                    <label for="product_name">Product Name:</label>
                    <input type="text" id="product_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="product_description">Description:</label>
                    <textarea id="product_description" name="description" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (₱):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity:</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                    </div>
                </div>
                
                    <div class="form-group">
                        <label for="category_id">Product Categories:</label>
                        <div class="category-selection-container">
                            <?php if (empty($categories)): ?>
                                <p class="no-categories">No categories available - Contact admin</p>
                            <?php else: ?>
                                <?php 
                                // Group categories by parent
                                $groupedCats = [];
                                $orphanChildren = [];
                                
                                // First pass: identify all parent categories
                                foreach ($categories as $cat) {
                                    if ($cat['parent_id'] === null) {
                                        $groupedCats[$cat['id']] = [
                                            'info' => $cat,
                                            'children' => []
                                        ];
                                    }
                                }
                                
                                // Second pass: assign children to their parents
                                foreach ($categories as $cat) {
                                    if ($cat['parent_id'] !== null) {
                                        if (isset($groupedCats[$cat['parent_id']])) {
                                            $groupedCats[$cat['parent_id']]['children'][] = $cat;
                                        } else {
                                            $orphanChildren[] = $cat;
                                        }
                                    }
                                }
                                
                                // Display grouped categories
                                foreach ($groupedCats as $parentId => $data): 
                                    $parent = $data['info'];
                                    $hasChildren = !empty($data['children']);
                                ?>
                                    <div class="category-group">
                                        <div class="parent-category">
                                            <div class="parent-label">
                                                <span class="category-name parent-name">
                                                    <?php echo htmlspecialchars($parent['name']); ?>
                                                </span>
                                                <?php if ($hasChildren): ?>
                                                    <span class="toggle-children" onclick="toggleChildren(<?php echo $parent['id']; ?>, event)">
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
                                                        <label class="category-checkbox-label child-label">
                                                            <input type="checkbox" 
                                                                name="category_id[]" 
                                                                value="<?php echo $child['id']; ?>" 
                                                                class="category-checkbox child-checkbox"
                                                                data-parent="<?php echo $parent['id']; ?>">
                                                            <span class="category-name child-name">
                                                                <?php echo htmlspecialchars($child['name']); ?>
                                                            </span>
                                                        </label>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (!empty($orphanChildren)): ?>
                                    <div class="category-group">
                                        <div class="orphan-categories-header">
                                            <span style="color: #FFD736; font-size: 14px; font-weight: 600;">Other Categories</span>
                                        </div>
                                        <?php foreach ($orphanChildren as $orphan): ?>
                                            <label class="category-checkbox-label child-label">
                                                <input type="checkbox" 
                                                    name="category_id[]" 
                                                    value="<?php echo $orphan['id']; ?>" 
                                                    class="category-checkbox">
                                                <span class="category-name child-name">
                                                    <?php echo htmlspecialchars($orphan['name']); ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($categories)): ?>
                            <small class="form-help" style="color: #dc3545;">No categories available. Please contact the administrator.</small>
                        <?php else: ?>
                            <small class="form-help">Click parent categories to expand/collapse. Select subcategories for your product.</small>
                        <?php endif; ?>
                    </div>
            </div>
            
            <div class="form-right">
                <div class="form-group">
                    <label for="image">Product Image:</label>
                    <div class="upload-area" id="upload-area">
                        <div class="upload-placeholder" id="upload-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <p>Click to upload or drag and drop</p>
                            <small>PNG, JPG, GIF up to 10MB</small>
                        </div>
                        <img id="image-preview" src="" alt="Preview" class="image-preview" style="display: none;">
                    </div>
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewImage(event)" style="display: none;">
                    <small class="form-help">If no image is uploaded, a default image will be used.</small>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" name="add_product" <?php echo empty($categories) ? 'disabled' : ''; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Add Product
            </button>
            <button type="reset" class="btn-reset" onclick="resetForm()">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                Reset Form
            </button>
        </div>
    </form>
</div>
    
    <div class="products-list">
        <h2>Your Active Products</h2>
        <?php if (empty($activeProducts)): ?>
            <p>No active products found.</p>
        <?php else: ?>
            <table>
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
                            <td>
                                <span class="status-badge status-active">Active</span>
                            </td>
                            <td>
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>">Edit</a> |
                                <a href="manage-products.php?toggle_status=<?php echo $product['id']; ?>">Deactivate</a> |
                                <a href="manage-products.php?delete=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h2>Your Inactive Products</h2>
        <?php if (empty($inactiveProducts)): ?>
            <p>No inactive products found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
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
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock_quantity']; ?></td>
                            <td>
                                <span class="status-badge status-inactive">Inactive</span>
                            </td>
                            <td>
                                <a href="edit-product.php?id=<?php echo $product['id']; ?>">Edit</a> |
                                <a href="manage-products.php?toggle_status=<?php echo $product['id']; ?>">Activate</a> |
                                <a href="manage-products.php?delete=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style> 
/* Category Selection Styling */
.category-selection-container {
    max-height: 400px;
    overflow-y: auto;
    border: 2px solid rgba(255, 215, 54, 0.3);
    border-radius: 12px;
    padding: 16px;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
}

.category-group {
    margin-bottom: 8px;
    border-radius: 8px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.03);
}

.parent-category {
    background: rgba(255, 215, 54, 0.12);
    border-left: 4px solid #FFD736;
}

.parent-label {
    padding: 14px 16px;
    font-size: 16px;
    background: rgba(255, 215, 54, 0.12);
    border: none;
    position: relative;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: background 0.3s ease;
}

.parent-label:hover {
    background: rgba(255, 215, 54, 0.2);
}

.parent-name {
    font-size: 16px;
    font-weight: 700;
    color: #FFD736;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex: 1;
}

.toggle-children {
    margin-left: auto;
    cursor: pointer;
    color: #FFD736;
    transition: transform 0.3s ease;
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 4px;
}

.toggle-children:hover {
    background: rgba(255, 215, 54, 0.2);
}

.toggle-children.rotated svg {
    transform: rotate(-90deg);
}

.child-categories {
    padding: 8px 0 8px 24px;
    background: rgba(0, 0, 0, 0.2);
    max-height: 500px;
    overflow: hidden;
    transition: max-height 0.4s ease, padding 0.4s ease;
    display: block !important; /* Force block display */
}

.child-categories.collapsed {
    max-height: 0;
    padding: 0 0 0 24px;
}

.child-label {
    padding: 10px 16px !important;
    margin: 4px 0 !important;
    border-left: 2px solid rgba(255, 215, 54, 0.3);
    display: grid !important; /* Change from flex to grid */
    grid-template-columns: 20px 1fr !important; /* Checkbox column + text column */
    gap: 12px !important;
    align-items: center !important;
}

.child-name {
    font-size: 14px !important;
    color: #E0E0E0 !important;
    font-weight: 500 !important;
    text-align: left !important;
    margin: 0 !important;
    padding: 0 !important;
    justify-self: start !important;
}

.category-checkbox-label {
    display: flex;
    align-items: center;
    justify-content: flex-start; /* This ensures left alignment */
    padding: 12px 14px;
    margin: 2px 0;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid transparent;
    text-align: left; /* Add this */
}
.category-checkbox-label:hover {
    background: rgba(255, 215, 54, 0.15);
    border-color: rgba(255, 215, 54, 0.4);
    transform: translateX(4px);
}

.category-checkbox-label:has(.category-checkbox:checked) {
    background: linear-gradient(135deg, rgba(255, 215, 54, 0.25), rgba(255, 215, 54, 0.15));
    border-color: #FFD736;
    box-shadow: 0 2px 8px rgba(255, 215, 54, 0.3);
}
.category-checkbox-label.child-label {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    text-align: left !important;
}
.category-checkbox {
    width: 20px !important;
    height: 20px !important;
    margin: 0 !important;
    cursor: pointer;
    accent-color: #FFD736;
    justify-self: start !important; /* Align to start of grid cell */
}

.category-name {
    color: #F9F9F9;
    font-size: 15px;
    font-weight: 500;
    letter-spacing: 0.3px;
    flex: 1;
    text-align: left; /* Ensure text is also left-aligned */
}

.category-checkbox:checked + .category-name {
    font-weight: 700;
    color: #FFD736;
}

.orphan-categories-header {
    padding: 12px 16px;
    background: rgba(255, 215, 54, 0.08);
    border-left: 4px solid rgba(255, 215, 54, 0.5);
    margin-bottom: 8px;
}

.no-categories {
    color: #dc3545;
    padding: 20px;
    text-align: center;
    font-style: italic;
}

/* Scrollbar styling */
.category-selection-container::-webkit-scrollbar {
    width: 10px;
}

.category-selection-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 5px;
}

.category-selection-container::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #FFD736, #f0c419);
    border-radius: 5px;
    border: 2px solid rgba(255, 255, 255, 0.1);
}

.category-selection-container::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #f0c419, #FFD736);
    box-shadow: 0 0 6px rgba(255, 215, 54, 0.5);
}
body {
    background: linear-gradient(135deg, #130325 0%, #1a0a2e 100%);
    min-height: 100vh;
    color: #F9F9F9;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

h1 {
    color: #F9F9F9;
    font-size: 2.2em;
    margin-bottom: 30px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 3px;
    border-bottom: 3px solid #FFD736;
    padding-bottom: 15px;
    font-weight: 700;
}

h2 {
    color: #FFD736;
    font-size: 1.6em;
    margin-bottom: 25px;
    text-transform: uppercase;
    letter-spacing: 2px;
    font-weight: 700;
}

/* Product Management Container */
.product-management {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 20px;
}

/* Add Product Form */
.add-product-form {
    background: rgba(255, 255, 255, 0.08);
    border: 2px solid rgba(255, 215, 54, 0.3);
    border-radius: 16px;
    padding: 35px;
    margin-bottom: 50px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.4);
    backdrop-filter: blur(15px);
}

.add-product-form h2 {
    margin-top: 0;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #FFD736;
}

/* Form Grid Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 40px;
    margin-bottom: 30px;
}

.form-left,
.form-right {
    display: flex;
    flex-direction: column;
}

/* Form Groups */
.form-group {
    margin-bottom: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    font-weight: 600;
    color: #FFD736;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px;
    border: 2px solid rgba(255, 215, 54, 0.3);
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 4px rgba(255, 215, 54, 0.2);
    background: rgba(255, 255, 255, 0.15);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

/* Upload Area */
.upload-area {
    width: 100%;
    min-height: 300px;
    border: 3px dashed rgba(255, 215, 54, 0.5);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
    position: relative;
}

.upload-area:hover {
    border-color: #FFD736;
    background: rgba(255, 215, 54, 0.1);
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 300px;
    color: #FFD736;
    text-align: center;
    padding: 20px;
}

.upload-placeholder svg {
    margin-bottom: 15px;
    opacity: 0.7;
}

.upload-placeholder p {
    font-size: 16px;
    font-weight: 600;
    margin: 10px 0 5px 0;
}

.upload-placeholder small {
    font-size: 13px;
    color: #bbb;
}

.image-preview {
    width: 100%;
    height: 300px;
    object-fit: cover;
    border-radius: 8px;
}

.form-help {
    display: block;
    margin-top: 8px;
    font-size: 12px;
    color: #bbb;
    font-style: italic;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 215, 54, 0.2);
}

.form-actions button {
    flex: 1;
    padding: 16px 32px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 700;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-actions button[type="submit"] {
    background: linear-gradient(135deg, #FFD736 0%, #f0c419 100%);
    color: #130325;
}

.form-actions button[type="submit"]:hover:not(:disabled) {
    background: linear-gradient(135deg, #f0c419 0%, #FFD736 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(255, 215, 54, 0.4);
}

.form-actions button[type="submit"]:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
    transform: none;
}

.btn-reset {
    background: rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-reset:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
}

/* Category Dropdown */
#category_id {
    background: rgba(255, 255, 255, 0.1) !important;
    color: #F9F9F9;
}

#category_id option {
    background: #1a0a2e;
    color: #F9F9F9;
}

/* Products List */
.products-list {
    margin-top: 50px;
}

.products-list h2 {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #FFD736;
}

.products-list table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    margin-bottom: 40px;
    backdrop-filter: blur(10px);
}

.products-list table thead {
    background: linear-gradient(135deg, #1a0a2e, #2d1b3d);
    color: #FFD736;
}

.products-list table th,
.products-list table td {
    padding: 16px 14px;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
}

.products-list table th {
    font-weight: 700;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 1px;
}

.products-list table tbody tr {
    transition: all 0.3s ease;
}

.products-list table tbody tr:hover {
    background: rgba(255, 215, 54, 0.15);
}

.products-list table tbody tr:last-child td {
    border-bottom: none;
}

/* Product Thumbnail */
.product-thumbnail {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #FFD736;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Status Badges */
.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.status-active {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border: 1px solid #28a745;
}

.status-inactive {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border: 1px solid #dc3545;
}

/* Action Links */
.products-list a {
    color: #FFD736;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    padding: 4px 8px;
    border-radius: 4px;
}

.products-list a:hover {
    color: #fff;
    background: rgba(255, 215, 54, 0.2);
}

/* Message Styles */
.success-message,
.error-message,
.warning-message {
    padding: 16px 24px;
    border-radius: 10px;
    margin: 20px 0;
    font-weight: 600;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2);
}

.success-message {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    color: #155724;
    border: 2px solid #28a745;
}

.error-message {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    color: #721c24;
    border: 2px solid #dc3545;
}

.warning-message {
    background: linear-gradient(135deg, #fff3cd, #ffeaa7);
    color: #856404;
    border: 2px solid #ffc107;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

@media (max-width: 768px) {
    .product-management {
        padding: 20px 10px;
    }
    
    .add-product-form {
        padding: 25px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .products-list table {
        font-size: 12px;
    }
    
    .products-list table th,
    .products-list table td {
        padding: 10px 8px;
    }
    
    .product-thumbnail {
        width: 50px;
        height: 50px;
    }
    
    h1 {
        font-size: 1.6em;
    }
}

@media (max-width: 480px) {
    .products-list table th,
    .products-list table td {
        padding: 8px 6px;
        font-size: 11px;
    }
    
    .product-thumbnail {
        width: 40px;
        height: 40px;
    }
    
    .status-badge {
        font-size: 9px;
        padding: 4px 8px;
    }
}
/* Multiple Select Styling */
.form-group select[multiple] {
    height: auto;
    min-height: 120px;
    padding: 8px;
}

.form-group select[multiple] option {
    padding: 8px 12px;
    margin: 2px 0;
    border-radius: 4px;
    cursor: pointer;
}

.form-group select[multiple] option:checked {
    background: linear-gradient(135deg, #FFD736 0%, #f0c419 100%);
    color: #130325;
    font-weight: 600;
}

.form-group select[multiple] option:hover {
    background: rgba(255, 215, 54, 0.3);
}
</style>
<script>
    // Form validation - enable/disable submit button
document.addEventListener('DOMContentLoaded', function() {
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
        
        // Check if at least one category is selected
        let categorySelected = false;
        categoryCheckboxes.forEach(function(checkbox) {
            if (checkbox.checked) {
                categorySelected = true;
            }
        });
        
        // Enable button only if all fields are valid
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
    
    // Initial validation on page load
    validateForm();
    
    // Add event listeners to all form inputs
    productName.addEventListener('input', validateForm);
    price.addEventListener('input', validateForm);
    stockQuantity.addEventListener('input', validateForm);
    
    categoryCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', validateForm);
    });
});
    // Toggle child categories visibility
function toggleChildren(parentId, event) {
    event.preventDefault();
    event.stopPropagation();
    
    const childContainer = document.getElementById('children_' + parentId);
    const toggleIcon = event.currentTarget;
    
    if (childContainer.classList.contains('collapsed')) {
        childContainer.classList.remove('collapsed');
        toggleIcon.classList.add('rotated');
    } else {
        childContainer.classList.add('collapsed');
        toggleIcon.classList.remove('rotated');
    }
}// Make parent labels clickable to toggle children
document.addEventListener('DOMContentLoaded', function() {
    const parentLabels = document.querySelectorAll('.parent-label');
    parentLabels.forEach(function(label) {
        label.addEventListener('click', function(e) {
            // Only toggle if clicking on the label itself or parent name, not the toggle button
            if (e.target === label || e.target.classList.contains('parent-name')) {
                const toggleBtn = label.querySelector('.toggle-children');
                if (toggleBtn) {
                    toggleBtn.click();
                }
            }
        });
    });
});
// Auto-check parent when child is checked
document.addEventListener('DOMContentLoaded', function() {
    const childCheckboxes = document.querySelectorAll('.child-checkbox');
    
    childCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const parentId = this.getAttribute('data-parent');
            const parentCheckbox = document.getElementById('parent_' + parentId);
            
            if (this.checked && parentCheckbox && !parentCheckbox.checked) {
                parentCheckbox.checked = true;
            }
        });
    });
    
    // Make parent labels clickable to toggle children
    const parentLabels = document.querySelectorAll('.parent-label');
    parentLabels.forEach(function(label) {
        label.addEventListener('click', function(e) {
            // Only toggle if clicking on the label itself, not checkbox or toggle button
            if (e.target === label || e.target.classList.contains('parent-name')) {
                const toggleBtn = label.querySelector('.toggle-children');
                if (toggleBtn) {
                    toggleBtn.click();
                }
            }
        });
    });
});
    // Toggle child categories visibility
function toggleChildren(parentId) {
    const childContainer = document.getElementById('children_' + parentId);
    const toggleIcon = event.target.closest('.toggle-children');
    
    if (childContainer.classList.contains('collapsed')) {
        childContainer.classList.remove('collapsed');
        toggleIcon.classList.remove('rotated');
    } else {
        childContainer.classList.add('collapsed');
        toggleIcon.classList.add('rotated');
    }
}

// Auto-check parent when child is checked
document.addEventListener('DOMContentLoaded', function() {
    const childCheckboxes = document.querySelectorAll('.child-checkbox');
    
    childCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Find parent checkbox and check it
                const categoryGroup = this.closest('.category-group');
                const parentCheckbox = categoryGroup.querySelector('.parent-checkbox');
                if (parentCheckbox && !parentCheckbox.checked) {
                    parentCheckbox.checked = true;
                }
            }
        });
    });
});
function previewImage(event) {
    var reader = new FileReader();
    var imagePreview = document.getElementById('image-preview');
    var previewContainer = document.getElementById('image-preview-container');
    
    reader.onload = function() {
        if (reader.readyState == 2) {
            imagePreview.src = reader.result;
            previewContainer.style.display = 'block';
        }
    }
    
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    } else {
        previewContainer.style.display = 'none';
    }
}

// Clear form and preview after submission
document.querySelector('form').addEventListener('submit', function(e) {
    setTimeout(function() {
        document.getElementById('image-preview-container').style.display = 'none';
    }, 100);
});


function previewImage(event) {
    var reader = new FileReader();
    var imagePreview = document.getElementById('image-preview');
    var uploadPlaceholder = document.getElementById('upload-placeholder');
    
    reader.onload = function() {
        if (reader.readyState == 2) {
            imagePreview.src = reader.result;
            imagePreview.style.display = 'block';
            uploadPlaceholder.style.display = 'none';
        }
    }
    
    if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
    } else {
        imagePreview.style.display = 'none';
        uploadPlaceholder.style.display = 'flex';
    }
}

// Click to upload
document.getElementById('upload-area').addEventListener('click', function() {
    document.getElementById('image').click();
});

// Drag and drop functionality
var uploadArea = document.getElementById('upload-area');

uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    uploadArea.style.borderColor = '#FFD736';
    uploadArea.style.background = 'rgba(255, 215, 54, 0.2)';
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    uploadArea.style.borderColor = 'rgba(255, 215, 54, 0.5)';
    uploadArea.style.background = 'rgba(255, 255, 255, 0.05)';
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    uploadArea.style.borderColor = 'rgba(255, 215, 54, 0.5)';
    uploadArea.style.background = 'rgba(255, 255, 255, 0.05)';
    
    var files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('image').files = files;
        previewImage({ target: { files: files } });
    }
});

function resetForm() {
    document.getElementById('image-preview').style.display = 'none';
    document.getElementById('upload-placeholder').style.display = 'flex';
}

// Clear form and preview after submission
document.querySelector('form').addEventListener('submit', function(e) {
    setTimeout(function() {
        resetForm();
    }, 100);
});

// Auto-dismiss notifications after 4 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.success-message, .error-message, .warning-message');
    
    messages.forEach(function(message) {
        // Add fade-out animation after 4 seconds
        setTimeout(function() {
            message.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            message.style.opacity = '0';
            message.style.transform = 'translateY(-20px)';
            
            // Remove from DOM after fade animation completes
            setTimeout(function() {
                message.remove();
            }, 500);
        }, 4000);
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>