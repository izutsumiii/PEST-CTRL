<?php
require_once 'includes/header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: manage-products.php");
    exit();
}

$productId = intval($_GET['id']);

// Check if product belongs to the seller
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
$stmt->execute([$productId, $userId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<p class='error-message'>Product not found or you don't have permission to edit it.</p>";
    echo "<a href='manage-products.php' class='back-button'>Back to Products</a>";
    require_once 'includes/footer.php';
    exit();
}

// Handle form submission
if (isset($_POST['update_product'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $categoryId = intval($_POST['category_id']);
    $stockQuantity = intval($_POST['stock_quantity']);
    $status = sanitizeInput($_POST['status']);
    
    // Validate that the category belongs to the seller
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ? AND seller_id = ?");
    $stmt->execute([$categoryId, $userId]);
    $categoryExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoryExists) {
        echo "<p class='error-message'>Invalid category selected.</p>";
    } else {
        if (updateProduct($productId, $name, $description, $price, $categoryId, $stockQuantity, $status)) {
            echo "<p class='success-message'>Product updated successfully!</p>";
            // Use JavaScript redirect instead of header refresh to avoid issues
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'manage-products.php';
                    }, 2000);
                  </script>";
        } else {
            echo "<p class='error-message'>Error updating product. Please try again.</p>";
        }
    }
}

// Get categories for dropdown
$stmt = $pdo->prepare("SELECT * FROM categories WHERE seller_id = ? ORDER BY name");
$stmt->execute([$userId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if seller has categories
if (empty($categories)) {
    echo "<div class='warning-message'>
            <p>You need to create at least one category before editing products.</p>
            <a href='manage-products.php'>Back to Products</a>
          </div>";
    require_once 'includes/footer.php';
    exit();
}
?>

<h1>Edit Product</h1>

<form method="POST" action="">
    <div>
        <label for="name">Product Name:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    
    <div>
        <label for="description">Description:</label>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    </div>
    
    <div>
        <label for="price">Price:</label>
        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    
    <div>
        <label for="category_id">Category:</label>
        <select id="category_id" name="category_id" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                        <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div>
        <label for="stock_quantity">Stock Quantity:</label>
        <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo htmlspecialchars($product['stock_quantity'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    
    <div>
        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
    </div>
    
    <div class="form-buttons">
        <button type="submit" name="update_product">Update Product</button>
        <a href="manage-products.php" class="back-button" onclick="return confirmNavigation();">Cancel</a>
    </div>
</form>

<script>
function confirmNavigation() {
    // Check if form has been modified
    var form = document.querySelector('form');
    var formData = new FormData(form);
    var hasChanges = false;
    
    // Simple check - you can enhance this to compare with original values
    var inputs = form.querySelectorAll('input, textarea, select');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].name !== 'update_product' && inputs[i].value.trim() !== '') {
            // You could store original values and compare here
        }
    }
    
    // Ask for confirmation if there might be unsaved changes
    if (document.querySelector('input[name="name"]').value !== '<?php echo addslashes($product['name']); ?>') {
        return confirm('You have unsaved changes. Are you sure you want to leave?');
    }
    
    return true;
}

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>

<style>
.form-buttons {
    margin-top: 20px;
}

.back-button {
    display: inline-block;
    padding: 10px 15px;
    background-color: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-left: 10px;
}

.back-button:hover {
    background-color: #545b62;
}

.warning-message {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
}

.error-message, .success-message {
    padding: 10px;
    border-radius: 5px;
    margin: 10px 0;
}

.error-message {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.success-message {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
</style>

<?php require_once 'includes/footer.php'; ?>