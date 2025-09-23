<?php
require_once 'includes/seller_header.php';
require_once 'config/database.php';

requireSeller();

$userId = $_SESSION['user_id'];

// Handle category actions (add, edit, delete)
if (isset($_POST['add_category'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $parentId = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    
    $stmt = $pdo->prepare("INSERT INTO categories (name, description, parent_id, seller_id) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$name, $description, $parentId, $userId]);
    
    if ($result) {
        echo "<p class='success-message'>Category added successfully!</p>";
    } else {
        echo "<p class='error-message'>Error adding category. Please try again.</p>";
    }
}

if (isset($_POST['update_category'])) {
    $categoryId = intval($_POST['category_id']);
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $parentId = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    
    // Check if category belongs to the seller
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND seller_id = ?");
    $stmt->execute([$categoryId, $userId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, parent_id = ? WHERE id = ?");
        $result = $stmt->execute([$name, $description, $parentId, $categoryId]);
        
        if ($result) {
            echo "<p class='success-message'>Category updated successfully!</p>";
        } else {
            echo "<p class='error-message'>Error updating category. Please try again.</p>";
        }
    } else {
        echo "<p class='error-message'>Error: You don't have permission to update this category.</p>";
    }
}

if (isset($_GET['delete'])) {
    $categoryId = intval($_GET['delete']);
    
    // Check if category belongs to the seller
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND seller_id = ?");
    $stmt->execute([$categoryId, $userId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        // Check if category has subcategories
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
        $stmt->execute([$categoryId]);
        $subcategoryCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Check if category has products
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
            
            if ($result) {
                echo "<p class='success-message'>Category deleted successfully!</p>";
            } else {
                echo "<p class='error-message'>Error deleting category. Please try again.</p>";
            }
        }
    } else {
        echo "<p class='error-message'>Error: You don't have permission to delete this category.</p>";
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

// Get parent categories for dropdown (only categories without parents)
$stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND seller_id = ? ORDER BY name");
$stmt->execute([$userId]);
$parentCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
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
                    // Group categories by parent
                    $groupedCategories = [];
                    foreach ($categories as $category) {
                        $parentId = $category['parent_id'] ? $category['parent_id'] : 0;
                        if (!isset($groupedCategories[$parentId])) {
                            $groupedCategories[$parentId] = [];
                        }
                        $groupedCategories[$parentId][] = $category;
                    }
                    
                    // Function to display categories recursively
                    function displayCategories($categories, $parentId = 0, $level = 0) {
                        if (!isset($categories[$parentId])) return;
                        
                        foreach ($categories[$parentId] as $category) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
                            echo '<tr>';
                            echo '<td>' . $indent . $category['name'] . '</td>';
                            echo '<td>' . ($category['description'] ? $category['description'] : 'N/A') . '</td>';
                            echo '<td>' . ($category['parent_name'] ? $category['parent_name'] : 'None') . '</td>';
                            echo '<td>';
                            echo '<a href="edit-category.php?id=' . $category['id'] . '">Edit</a> | ';
                            echo '<a href="manage-categories.php?delete=' . $category['id'] . '" onclick="return confirm(\'Are you sure you want to delete this category?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                            
                            // Display subcategories
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

<?php require_once 'includes/footer.php'; ?>