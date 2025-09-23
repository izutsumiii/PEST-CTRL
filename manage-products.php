<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

/* ---------------------------
   CATEGORY MANAGEMENT LOGIC
----------------------------*/

// Handle category actions (add, edit, delete)
if (isset($_POST['add_category'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $parentId = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    
    $stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id, seller_id) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$name, $description, $parentId, $userId]);
    
    echo $result 
        ? "<p class='success-message'>Category added successfully!</p>" 
        : "<p class='error-message'>Error adding category. Please try again.</p>";
}

if (isset($_POST['update_category'])) {
    $categoryId = intval($_POST['category_id']);
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $parentId = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND seller_id = ?");
    $stmt->execute([$categoryId, $userId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, parent_id = ? WHERE id = ?");
        $result = $stmt->execute([$name, $description, $parentId, $categoryId]);
        echo $result 
            ? "<p class='success-message'>Category updated successfully!</p>" 
            : "<p class='error-message'>Error updating category. Please try again.</p>";
    } else {
        echo "<p class='error-message'>Error: You don't have permission to update this category.</p>";
    }
}

if (isset($_GET['delete_category'])) {
    $categoryId = intval($_GET['delete_category']);
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND seller_id = ?");
    $stmt->execute([$categoryId, $userId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
        $stmt->execute([$categoryId]);
        $subcategoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $productCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($subcategoryCount > 0) {
            echo "<p class='error-message'>Cannot delete category. It has subcategories. Please delete or move them first.</p>";
        } elseif ($productCount > 0) {
            echo "<p class='error-message'>Cannot delete category. It has products. Please move or delete them first.</p>";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$categoryId]);
            echo $result 
                ? "<p class='success-message'>Category deleted successfully!</p>" 
                : "<p class='error-message'>Error deleting category. Please try again.</p>";
        }
    } else {
        echo "<p class='error-message'>Error: You don't have permission to delete this category.</p>";
    }
}

/* ---------------------------
   PRODUCT MANAGEMENT LOGIC
----------------------------*/

// Handle product actions (add, edit, delete, toggle)
if (isset($_POST['add_product'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $categoryId = intval($_POST['category_id']);
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

// Get seller's categories
$stmt = $pdo->prepare("SELECT c1.*, c2.name as parent_name 
                      FROM categories c1 
                      LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                      WHERE c1.seller_id = ? 
                      ORDER BY c1.parent_id, c1.name");
$stmt->execute([$userId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get parent categories for dropdown
$stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND seller_id = ? ORDER BY name");
$stmt->execute([$userId]);
$parentCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get seller's products
$activeProducts = getSellerActiveProducts($userId);
$inactiveProducts = getSellerInactiveProducts($userId);
?>

<h1>Manage Categories</h1>
<div class="category-management">
    <div class="add-category-form">
        <h2>Add New Category</h2>
        <form method="POST" action="">
            <div>
                <label for="name">Category Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div>
                <label for="description">Description:</label>
                <textarea id="description" name="description"></textarea>
            </div>
            
            <div>
                <label for="parent_id">Parent Category (optional):</label>
                <select id="parent_id" name="parent_id">
                    <option value="">No Parent (Top Level)</option>
                    <?php foreach ($parentCategories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="add_category">Add Category</button>
        </form>
    </div>
    
    <div class="categories-list">
        <h2>Your Categories</h2>
        <?php if (empty($categories)): ?>
            <p>No categories found.</p>
        <?php else: ?>
            <table class="categories-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Parent Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $groupedCategories = [];
                    foreach ($categories as $category) {
                        $parentId = $category['parent_id'] ? $category['parent_id'] : 0;
                        $groupedCategories[$parentId][] = $category;
                    }
                    
                    function displayCategories($categories, $parentId = 0, $level = 0) {
                        if (!isset($categories[$parentId])) return;
                        foreach ($categories[$parentId] as $category) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                            echo '<tr>';
                            echo '<td>' . $indent . htmlspecialchars($category['name']) . '</td>';
                            echo '<td>' . htmlspecialchars($category['description'] ?: 'N/A') . '</td>';
                            echo '<td>' . htmlspecialchars($category['parent_name'] ?: 'None') . '</td>';
                            echo '<td>';
                            echo '<a href="edit-category.php?id=' . $category['id'] . '">Edit</a> | ';
                            echo '<a href="manage-products.php?delete_category=' . $category['id'] . '" onclick="return confirm(\'Are you sure you want to delete this category?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                            displayCategories($categories, $category['id'], $level + 1);
                        }
                    }
                    
                    displayCategories($groupedCategories);
                    ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<hr style="margin: 30px 0;">

<h1>Manage Products</h1>

<div class="product-management">
    <div class="add-product-form">
        <h2>Add New Product</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <div>
                <label for="product_name">Product Name:</label>
                <input type="text" id="product_name" name="name" required>
            </div>
            
            <div>
                <label for="product_description">Description:</label>
                <textarea id="product_description" name="description" required></textarea>
            </div>
            
            <div>
                <label for="price">Price:</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>
            
            <div>
                <label for="category_id">Category:</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="stock_quantity">Stock Quantity:</label>
                <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
            </div>
            
            <div>
                <label for="image">Product Image (optional):</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small class="form-help">If no image is uploaded, a default image will be used.</small>
            </div>
            
            <button type="submit" name="add_product">Add Product</button>
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
body {
    background: linear-gradient(135deg, #130325 0%, #1a0a2e 100%);
    min-height: 100vh;
    color: #F9F9F9;
    font-size: 0.9em;
}

h1 {
    color: #F9F9F9;
    font-size: 1.8em;
    margin-bottom: 30px;
    text-align: center;
}

h2 {
    color: #F9F9F9;
    font-size: 1.4em;
    margin-bottom: 20px;
}

h3 {
    color: #F9F9F9;
    font-size: 1.2em;
    margin-bottom: 15px;
}

/* Category Management Styles */
.category-management {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.add-category-form,
.add-product-form {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    color: #F9F9F9;
}

.add-category-form h2,
.add-product-form h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #F9F9F9;
    font-size: 1.5em;
    border-bottom: 2px solid #FFD736;
    padding-bottom: 10px;
}

.add-category-form div,
.add-product-form div {
    margin-bottom: 15px;
}

.add-category-form label,
.add-product-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #F9F9F9;
}

.add-category-form input,
.add-category-form select,
.add-category-form textarea,
.add-product-form input,
.add-product-form select,
.add-product-form textarea {
    width: 100%;
    max-width: 400px;
    padding: 10px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s ease;
    background: rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
}

.add-category-form input:focus,
.add-category-form select:focus,
.add-category-form textarea:focus,
.add-product-form input:focus,
.add-product-form select:focus,
.add-product-form textarea:focus {
    outline: none;
    border-color: #FFD736;
    box-shadow: 0 0 0 2px rgba(255, 215, 54, 0.25);
}

.add-category-form textarea,
.add-product-form textarea {
    height: 80px;
    resize: vertical;
}

.add-category-form button,
.add-product-form button {
    background: #FFD736;
    color: #130325;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.add-category-form button:hover,
.add-product-form button:hover {
    background: #e6c230;
}

.form-help {
    display: block;
    margin-top: 5px;
    font-size: 12px;
    color: #6c757d;
    font-style: italic;
}

/* Categories List */
.categories-list {
    margin-bottom: 40px;
}

.categories-list h2 {
    color: #495057;
    margin-bottom: 20px;
    font-size: 1.5em;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
}

.categories-table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    color: #F9F9F9;
}

.categories-table thead {
    background: linear-gradient(135deg, #130325, #1a0a2e);
    color: #F9F9F9;
}

.categories-table th,
.categories-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
}

.categories-table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.categories-table tbody tr:hover {
    background: rgba(255, 215, 54, 0.1);
}

.categories-table tbody tr:last-child td {
    border-bottom: none;
}

/* Product Management Styles */
.product-management {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.products-list h2 {
    color: #495057;
    margin-bottom: 20px;
    margin-top: 30px;
    font-size: 1.5em;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
}

.products-list table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    margin-bottom: 30px;
    backdrop-filter: blur(10px);
    color: #F9F9F9;
}

.products-list table thead {
    background: linear-gradient(135deg, #130325, #1a0a2e);
    color: #F9F9F9;
}

.products-list table th,
.products-list table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: #F9F9F9;
}

.products-list table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

.products-list table tbody tr:hover {
    background: rgba(255, 215, 54, 0.1);
}

.products-list table tbody tr:last-child td {
    border-bottom: none;
}

/* Status Badges */
.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Action Links */
.categories-table a,
.products-list a {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.categories-table a:hover,
.products-list a:hover {
    color: #0056b3;
    text-decoration: underline;
}

/* Message Styles */
.success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
    padding: 12px 16px;
    margin: 15px 0;
    font-weight: 500;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
    padding: 12px 16px;
    margin: 15px 0;
    font-weight: 500;
}

.warning-message {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 12px 16px;
    margin: 15px 0;
    font-weight: 500;
}

/* Page Header */
h1 {
    color: #495057;
    font-size: 2.2em;
    margin-bottom: 30px;
    text-align: center;
    font-weight: 700;
}

hr {
    border: none;
    height: 2px;
    background: linear-gradient(90deg, #007bff, #28a745);
    margin: 40px 0;
    border-radius: 1px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .category-management,
    .product-management {
        padding: 10px;
    }
    
    .add-category-form,
    .add-product-form {
        padding: 20px 15px;
    }
    
    .add-category-form input,
    .add-category-form select,
    .add-category-form textarea,
    .add-product-form input,
    .add-product-form select,
    .add-product-form textarea {
        max-width: 100%;
    }
    
    .categories-table,
    .products-list table {
        font-size: 14px;
    }
    
    .categories-table th,
    .categories-table td,
    .products-list table th,
    .products-list table td {
        padding: 8px 10px;
    }
    
    h1 {
        font-size: 1.8em;
    }
}

@media (max-width: 480px) {
    .categories-table,
    .products-list table {
        font-size: 12px;
    }
    
    .categories-table th,
    .categories-table td,
    .products-list table th,
    .products-list table td {
        padding: 6px 8px;
    }
    
    .status-badge {
        font-size: 9px;
        padding: 3px 6px;
    }
    
    .add-category-form button,
    .add-product-form button {
        width: 100%;
        padding: 14px;
    }
}


</style>

<?php require_once 'includes/footer.php'; ?>