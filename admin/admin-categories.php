<?php
require_once 'includes/admin_header.php';
requireAdmin();

// Ensure PDO connection is available
if (!isset($pdo)) {
    require_once '../config/database.php';
}
// Handle emergency bypass
if (isset($_POST['emergency_bypass'])) {
    try {
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = '0' WHERE setting_key = 'maintenance_enabled'");
        $stmt->execute();
        
        $success = "🚨 EMERGENCY BYPASS ACTIVATED! Maintenance mode has been immediately disabled. System is now online.";
        
        // Refresh the settings
        $maintenanceEnabled = '0';
        $isMaintenanceActive = false;
    } catch (PDOException $e) {
        $error = "Error during emergency bypass: " . $e->getMessage();
    }
}

// Handle maintenance mode updates
if (isset($_POST['update_maintenance'])) {
    $enabled = isset($_POST['maintenance_enabled']) ? '1' : '0';
    
    // Convert MM/DD/YYYY format to database format
    $startDatetime = DateTime::createFromFormat('m/d/Y h:i A', $_POST['maintenance_start']);
    $endDatetime = DateTime::createFromFormat('m/d/Y h:i A', $_POST['maintenance_end']);
    
    if (!$startDatetime || !$endDatetime) {
        $error = "Invalid date format. Please use MM/DD/YYYY HH:MM AM/PM format.";
    } else {
        $startFormatted = $startDatetime->format('Y-m-d H:i:s');
        $endFormatted = $endDatetime->format('Y-m-d H:i:s');
        $message = sanitizeInput($_POST['maintenance_message']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                   VALUES (?, ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            $stmt->execute(['maintenance_enabled', $enabled]);
            $stmt->execute(['maintenance_start', $startFormatted]);
            $stmt->execute(['maintenance_end', $endFormatted]);
            $stmt->execute(['maintenance_message', $message]);
            
            $success = "Maintenance mode settings updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating maintenance settings: " . $e->getMessage();
        }
    }
}
// Handle maintenance mode updates
if (isset($_POST['update_maintenance'])) {
    $enabled = isset($_POST['maintenance_enabled']) ? '1' : '0';
    $startDatetime = $_POST['maintenance_start'];
    $endDatetime = $_POST['maintenance_end'];
    $message = sanitizeInput($_POST['maintenance_message']);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                               VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $stmt->execute(['maintenance_enabled', $enabled]);
        $stmt->execute(['maintenance_start', $startDatetime]);
        $stmt->execute(['maintenance_end', $endDatetime]);
        $stmt->execute(['maintenance_message', $message]);
        
        $success = "Maintenance mode settings updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating maintenance settings: " . $e->getMessage();
    }
}

// Get current maintenance settings
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM system_settings 
                      WHERE setting_key IN ('maintenance_enabled', 'maintenance_start', 'maintenance_end', 'maintenance_message')");
$stmt->execute();
$maintenanceSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Set defaults if not exists
$maintenanceEnabled = $maintenanceSettings['maintenance_enabled'] ?? '0';
$maintenanceStart = $maintenanceSettings['maintenance_start'] ?? date('Y-m-d H:i:s');
$maintenanceEnd = $maintenanceSettings['maintenance_end'] ?? date('Y-m-d H:i:s', strtotime('+1 hour'));
$maintenanceMessage = $maintenanceSettings['maintenance_message'] ?? 'System is currently under maintenance. Please check back later.';

// Check if maintenance is currently active
$isMaintenanceActive = false;
if ($maintenanceEnabled == '1') {
    date_default_timezone_set('Asia/Manila');
    $current = new DateTime('now');
    $start = new DateTime($maintenanceStart);
    $end = new DateTime($maintenanceEnd);
    $isMaintenanceActive = ($current >= $start && $current <= $end);
}
/* ---------------------------
   CATEGORY MANAGEMENT LOGIC
----------------------------*/

// Handle category actions (add, edit, delete)
if (isset($_POST['add_category'])) {
    if (!isset($_POST['name']) || empty(trim($_POST['name']))) {
        $error = "Category name is required.";
    } else {
        $name = sanitizeInput($_POST['name']);
        $parentId = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND seller_id IS NULL");
        $stmt->execute([$name]);
        $exists = $stmt->fetchColumn();

        if ($exists > 0) {
            $error = "A category with the name '{$name}' already exists. Please choose a different name.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id, seller_id) VALUES (?, ?, NULL)");
            try {
                $result = $stmt->execute([$name, $parentId]);
                if ($result) {
                    if ($parentId) {
                        $success = "Subcategory '{$name}' added successfully! All sellers can now use this category.";
                    } else {
                        $success = "Category '{$name}' added successfully! All sellers can now use this category.";
                    }
                } else {
                    $error = "Error adding category. Please try again.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

if (isset($_POST['update_category'])) {
    $categoryId = intval($_POST['category_id']);
    $name = sanitizeInput($_POST['name']);
    $parentId = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : NULL;
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND seller_id IS NULL");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, parent_id = ? WHERE id = ?");
        $result = $stmt->execute([$name, $parentId, $categoryId]);
        if ($result) {
            $success = "Category updated successfully!";
        } else {
            $error = "Error updating category.";
        }
    } else {
        $error = "Category not found or cannot be edited.";
    }
}

if (isset($_GET['delete_category'])) {
    $categoryId = intval($_GET['delete_category']);
    
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND seller_id IS NULL");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $productCount = $stmt->fetchColumn();
        
        if ($productCount > 0) {
            $error = "Cannot delete category '{$category['name']}' because it has {$productCount} product(s) associated with it. Please reassign or delete those products first.";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $stmt->execute([$categoryId]);
            $subcategoryCount = $stmt->fetchColumn();
            
            if ($subcategoryCount > 0) {
                $error = "Cannot delete category '{$category['name']}' because it has {$subcategoryCount} subcategory(ies). Please delete subcategories first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $result = $stmt->execute([$categoryId]);
                $success = $result ? "Category deleted successfully!" : "Error deleting category.";
            }
        }
    }
}

// Get all admin categories
$stmt = $pdo->prepare("SELECT c1.*, c2.name as parent_name 
                      FROM categories c1 
                      LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                      WHERE c1.seller_id IS NULL 
                      ORDER BY c1.parent_id, c1.name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get parent categories for dropdown
$stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND seller_id IS NULL ORDER BY name");
$stmt->execute();
$parentCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

function addNotificationScript() {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var successMsg = document.querySelector('.success-message');
            if (successMsg) {
                setTimeout(function() {
                    successMsg.style.display = 'none';
                }, 4000);
            }
            
            var errorMsg = document.querySelector('.error-message');
            if (errorMsg) {
                setTimeout(function() {
                    errorMsg.style.display = 'none';
                }, 4000);
            }
        });
    </script>";
}
?>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:9999;align-items:center;justify-content:center;">
  <div class="modal-content" style="background:#fff;padding:32px 28px;border-radius:14px;max-width:400px;margin:auto;box-shadow:0 4px 16px rgba(44,62,80,0.18);position:relative;">
    <span class="close" onclick="closeEditModal()" style="position:absolute;top:12px;right:18px;font-size:22px;cursor:pointer;">&times;</span>
    <h3>Edit Category</h3>
    <form method="POST" action="">
      <input type="hidden" id="edit_category_id" name="category_id">
      
      <div class="form-group">
        <label for="edit_name">Category Name: <span style="color: #d32f2f;">*</span></label>
        <input type="text" id="edit_name" name="name" required placeholder="Enter category name">
      </div>
      
      <div class="form-group">
        <label for="edit_parent_id">Parent Category (Optional):</label>
        <select id="edit_parent_id" name="parent_id">
          <option value="">-- None (Top-Level Category) --</option>
          <?php foreach ($parentCategories as $parent): ?>
            <option value="<?php echo $parent['id']; ?>">
              <?php echo htmlspecialchars($parent['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <small class="form-help">Leave as "None" to make it a main category</small>
      </div>
      
      <button type="submit" name="update_category" class="btn-primary">Update Category</button>
    </form>
  </div>
</div>

<!-- Delete Category Modal -->
<div id="deleteCategoryModal" class="modal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.3);z-index:9999;align-items:center;justify-content:center;">
  <div class="modal-content" style="background:#fff;padding:32px 28px;border-radius:14px;max-width:400px;margin:auto;box-shadow:0 4px 16px rgba(44,62,80,0.18);position:relative;">
    <span class="close" onclick="closeDeleteModal()" style="position:absolute;top:12px;right:18px;font-size:22px;cursor:pointer;">&times;</span>
    <h3>Delete Category</h3>
    <form id="deleteCategoryForm" method="GET" action="admin-categories.php">
      <input type="hidden" name="delete_category" id="delete_category_id">
      <p id="deleteCategoryName" style="margin-bottom:18px;"></p>
      <button type="submit" class="btn" style="background:#d32f2f;">Delete</button>
      <button type="button" class="btn" onclick="closeDeleteModal()" style="margin-left:10px;background:#888;">Cancel</button>
    </form>
  </div>
</div>

<div class="page-header">
    <h1>Category Management</h1>
    <p>Manage global product categories available to all sellers</p>
</div>

<?php if (isset($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
<?php endif; ?>

<div class="category-management-section">
    <div class="add-category-form">
        <h3>Add New Category</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Category Name: <span style="color: #d32f2f;">*</span></label>
                <input type="text" id="name" name="name" required placeholder="Enter category name">
            </div>
            
            <div class="form-group">
                <label for="parent_id">Parent Category (Optional):</label>
                <select id="parent_id" name="parent_id">
                    <option value="">-- None (Top-Level Category) --</option>
                    <?php foreach ($parentCategories as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>">
                            <?php echo htmlspecialchars($parent['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-help">Leave as "None" to create a main category, or select a parent to create a subcategory</small>
            </div>
            
            <button type="submit" name="add_category" class="btn-primary">Add Category</button>
        </form>
    </div>

    <div class="categories-list">
        <h3>All Platform Categories</h3>
        <?php if (empty($categories)): ?>
            <p>No categories found. Add your first category above.</p>
        <?php else: ?>
            <div class="categories-table-wrapper">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>Name</th>
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
                                $hasChildren = isset($categories[$category['id']]) && !empty($categories[$category['id']]);
                                
                                echo '<tr>';
                                echo '<td>' . $indent . htmlspecialchars($category['name']) . '</td>';
                                echo '<td>' . htmlspecialchars($category['parent_name'] ?: 'None') . '</td>';
                                echo '<td class="action-links">';
                                echo '<a href="#" class="edit-link" style="color:#1976d2;font-weight:600;padding:4px 10px;border-radius:5px;background:#e3eafc;margin-right:6px;transition:background 0.2s;" onclick="openEditModal(' . $category['id'] . ', \'' . addslashes($category['name']) . '\', ' . ($category['parent_id'] ? $category['parent_id'] : 'null') . '); return false;">Edit</a>';
                                
                                if ($hasChildren) {
                                    echo '<a href="#" class="delete-link disabled" style="color:#999;font-weight:600;padding:4px 10px;border-radius:5px;background:#f5f5f5;cursor:not-allowed;" title="Cannot delete category with subcategories">Delete</a>';
                                } else {
                                    echo '<a href="#" class="delete-link" style="color:#d32f2f;font-weight:600;padding:4px 10px;border-radius:5px;background:#fdeaea;transition:background 0.2s;" onclick="openDeleteModal(' . $category['id'] . ', \'' . addslashes($category['name']) . '\'); return false;">Delete</a>';
                                }
                                
                                echo '</td>';
                                echo '</tr>';
                                displayCategories($categories, $category['id'], $level + 1);
                            }
                        }
                        
                        displayCategories($groupedCategories);
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Maintenance Mode Control Panel -->
<!-- Maintenance Mode Control Panel -->
<div class="maintenance-control-panel" style="background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%); padding: 30px; border-radius: 12px; margin-top: 30px; box-shadow: 0 4px 16px rgba(220,53,69,0.15); border: 2px solid #dc3545;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div>
            <h3 style="color: #dc3545; margin: 0; font-size: 22px;">🔧 Maintenance Mode Control</h3>
            <p style="color: #666; margin: 5px 0 0 0; font-size: 14px;">Schedule system-wide maintenance to disable seller and customer access</p>
        </div>
        <div style="padding: 10px 20px; border-radius: 8px; font-weight: 600; <?php echo $isMaintenanceActive ? 'background: #dc3545; color: white;' : 'background: #28a745; color: white;'; ?>">
            <?php echo $isMaintenanceActive ? '🔴 MAINTENANCE ACTIVE' : '🟢 SYSTEM ONLINE'; ?>
        </div>
    </div>
    
    <?php if ($isMaintenanceActive): ?>
    <div style="background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #dc3545;">
        <strong style="color: #dc3545;">⚠️ WARNING:</strong> 
        <span style="color: #333;">All seller and customer pages are currently DISABLED. Only admins can access the system.</span>
    </div>
    
    <!-- Emergency Bypass Button -->
    <form method="POST" action="" style="margin-bottom: 20px;">
        <button type="submit" name="emergency_bypass" class="btn" 
                style="background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; width: 100%; font-size: 16px; padding: 14px; font-weight: 700;"
                onclick="return confirm('⚠️ Are you sure you want to IMMEDIATELY disable maintenance mode?\n\nThis will restore normal system operation right now, regardless of the scheduled end time.');">
            🚨 EMERGENCY BYPASS - Deactivate Maintenance NOW
        </button>
    </form>
    <?php endif; ?>
    
    <form method="POST" action="" style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <div class="form-group">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="maintenance_enabled" value="1" 
                       <?php echo $maintenanceEnabled == '1' ? 'checked' : ''; ?> 
                       style="width: 20px; height: 20px; margin-right: 10px; cursor: pointer;">
                <span style="font-weight: 600; color: #dc3545; font-size: 16px;">Enable Maintenance Mode</span>
            </label>
            <small class="form-help" style="margin-left: 30px;">When enabled and within the scheduled time, all non-admin users will be blocked</small>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            <div class="form-group">
                <label>🕐 Start Date & Time:</label>
                <input type="text" name="maintenance_start" 
                       value="<?php echo date('m/d/Y h:i A', strtotime($maintenanceStart)); ?>" 
                       placeholder="MM/DD/YYYY HH:MM AM/PM"
                       required>
                <small class="form-help">Format: MM/DD/YYYY HH:MM AM/PM</small>
            </div>
            
            <div class="form-group">
                <label>🕐 End Date & Time:</label>
                <input type="text" name="maintenance_end" 
                       value="<?php echo date('m/d/Y h:i A', strtotime($maintenanceEnd)); ?>" 
                       placeholder="MM/DD/YYYY HH:MM AM/PM"
                       required>
                <small class="form-help">Format: MM/DD/YYYY HH:MM AM/PM</small>
            </div>
        </div>
        
        <div class="form-group" style="margin-top: 20px;">
            <label>📝 Maintenance Message:</label>
            <textarea name="maintenance_message" rows="3" style="resize: vertical;" required><?php echo htmlspecialchars($maintenanceMessage); ?></textarea>
            <small class="form-help">This message will be displayed to users during maintenance</small>
        </div>
        
        <div style="margin-top: 25px; padding-top: 25px; border-top: 2px solid #f0f0f0;">
            <button type="submit" name="update_maintenance" class="btn-primary" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); font-size: 16px; padding: 14px 32px;">
                💾 Save Maintenance Settings
            </button>
        </div>
    </form>
    
    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 6px; border: 1px solid #ffc107;">
        <strong style="color: #856404;">ℹ️ How it works:</strong>
        <ul style="margin: 10px 0 0 20px; color: #856404;">
            <li>Enable the checkbox and set your start/end times in MM/DD/YYYY format</li>
            <li>During the scheduled period, sellers and customers will see the maintenance page</li>
            <li>Use the EMERGENCY BYPASS button to immediately disable maintenance</li>
            <li>Admins can still access all pages normally</li>
        </ul>
    </div>
</div>
<style>
/* All the CSS from your original file */
.categories-list {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.categories-list h3 {
    color: #2e7d32;
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 18px;
}

.categories-table-wrapper {
    max-height: 400px;
    overflow-y: auto;
    overflow-x: hidden;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.categories-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0;
}

.categories-table thead {
    background: linear-gradient(135deg, #e8f5e8 0%, #d0e6d0 100%);
    position: sticky;
    top: 0;
    z-index: 10;
}

.categories-table th,
.categories-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #d0e6d0;
}

.categories-table th {
    color: #2e7d32;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.categories-table tbody tr:hover {
    background: #f8fff8;
}

.categories-table-wrapper::-webkit-scrollbar {
    width: 8px;
}

.categories-table-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.categories-table-wrapper::-webkit-scrollbar-thumb {
    background: #43a047;
    border-radius: 10px;
}

.categories-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #2e7d32;
}

.categories-table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: #43a047 #f1f1f1;
}

.category-management-section {
    background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 16px rgba(76,175,80,0.1);
}

.add-category-form {
    background: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.add-category-form h3 {
    color: #2e7d32;
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 18px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #388e3c;
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    max-width: 500px;
    padding: 10px;
    border: 2px solid #b2dfdb;
    border-radius: 6px;
    font-size: 14px;
    background: #f8fff8;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #43a047;
    box-shadow: 0 0 0 2px rgba(67,160,71,0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #43a047 0%, #2e7d32 100%);
    color: white;
    padding: 12px 28px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67,160,71,0.3);
}

.btn {
    padding: 12px 28px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    transition: all 0.3s ease;
    color: white;
}

.success-message {
    background: linear-gradient(90deg, #d4edda 0%, #e8f5e8 100%);
    color: #155724;
    padding: 15px 20px;
    border-radius: 6px;
    border: 1px solid #c3e6cb;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
}

.error-message {
    background: linear-gradient(90deg, #f8d7da 0%, #ffeaea 100%);
    color: #721c24;
    padding: 15px 20px;
    border-radius: 6px;
    border: 1px solid #f5c6cb;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
}

.action-links a {
    text-decoration: none;
    margin: 0 4px;
}

@media (max-width: 768px) {
    .categories-table-wrapper {
        max-height: 300px;
    }
}
/* Fix text colors */
.page-header h1,
.page-header p,
.category-management-section h2,
.category-management-section p,
.add-category-form h3,
.categories-list h3,
.categories-list p {
    color: #d5d81aff !important;
}

.form-group label {
    color: #a0a130ff !important;
}

.form-help,
.form-group small {
    color: #666 !important;
    font-size: 13px;
}

.categories-table td {
    color: #333 !important;
}

.modal-content h3 {
    color: #2e7d32 !important;
    margin-bottom: 20px;
}

.modal-content p {
    color: #333 !important;
}

.modal-content label {
    color: #388e3c !important;
}

/* Ensure all text elements have proper colors */
body,
.category-management-section,
.add-category-form,
.categories-list {
    color: #333;
}
/* Hide maintenance control by default */
.maintenance-control-panel {
    display: none;
}
</style>

<script>
function openEditModal(id, name, parentId) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_parent_id').value = parentId;
    document.getElementById('editCategoryModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
}

function openDeleteModal(id, name) {
    document.getElementById('delete_category_id').value = id;
    document.getElementById('deleteCategoryName').innerHTML = 'Are you sure you want to delete <strong>' + name + '</strong>?';
    document.getElementById('deleteCategoryModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteCategoryModal').style.display = 'none';
}
// Maintenance Mode Security
let clickCount = 0;
let clickTimer = null;
let passwordVerified = false;

document.querySelector('button[name="add_category"]').addEventListener('click', function(e) {
    clickCount++;
    
    // Clear existing timer
    if (clickTimer) {
        clearTimeout(clickTimer);
    }
    
    // If 10 clicks reached and not yet verified
    if (clickCount >= 10 && !passwordVerified) {
        e.preventDefault();
        showPasswordModal();
        clickCount = 0;
        return;
    }
    
    // Reset after 2 seconds of no clicks
    clickTimer = setTimeout(function() {
        clickCount = 0;
    }, 2000);
});

function showPasswordModal() {
    const modalHTML = `
        <div id="passwordModal" style="display:flex;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;">
            <div style="background:#fff;padding:32px 28px;border-radius:14px;max-width:400px;width:90%;box-shadow:0 4px 16px rgba(44,62,80,0.18);position:relative;">
                <h3 style="color:#dc3545;margin-top:0;">🔐 Admin Access Required</h3>
                <p style="color:#666;margin-bottom:20px;">Enter password to access Maintenance Mode Control</p>
                <input type="password" id="maintenancePassword" placeholder="Enter password" style="width:100%;padding:12px;border:2px solid #ddd;border-radius:6px;font-size:14px;margin-bottom:15px;">
                <div id="passwordError" style="color:#d32f2f;font-size:13px;margin-bottom:10px;display:none;">❌ Incorrect password</div>
                <button onclick="verifyPassword()" style="background:#dc3545;color:white;padding:12px 24px;border:none;border-radius:6px;cursor:pointer;font-weight:600;width:100%;margin-bottom:10px;">Submit</button>
                <button onclick="closePasswordModal()" style="background:#888;color:white;padding:12px 24px;border:none;border-radius:6px;cursor:pointer;font-weight:600;width:100%;">Cancel</button>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.getElementById('maintenancePassword').focus();
    
    // Allow Enter key to submit
    document.getElementById('maintenancePassword').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            verifyPassword();
        }
    });
}

function verifyPassword() {
    const password = document.getElementById('maintenancePassword').value;
    const correctPassword = 'hamhamTheHamster';
    
    if (password === correctPassword) {
        passwordVerified = true;
        closePasswordModal();
        showMaintenanceControl();
    } else {
        document.getElementById('passwordError').style.display = 'block';
        document.getElementById('maintenancePassword').value = '';
        document.getElementById('maintenancePassword').focus();
    }
}

function closePasswordModal() {
    const modal = document.getElementById('passwordModal');
    if (modal) {
        modal.remove();
    }
}

function showMaintenanceControl() {
    document.querySelector('.maintenance-control-panel').style.display = 'block';
    document.querySelector('.maintenance-control-panel').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php 
addNotificationScript();

?>
