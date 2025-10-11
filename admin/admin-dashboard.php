
<?php
require_once 'includes/admin_header.php';

// Simple admin check - ensure user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Ensure PDO connection is available
if (!isset($pdo)) {
    require_once '../config/database.php'; // Adjust path if needed
}

if (isset($_POST['update_settings'])) {
    $gracePeriod = intval($_POST['grace_period']);
    
    // Validate grace period (between 1 and 60 minutes)
    if ($gracePeriod < 1 || $gracePeriod > 60) {
        $error = "Grace period must be between 1 and 60 minutes.";
    } else {
        try {
            // Check if setting exists
            $stmt = $pdo->prepare("SELECT id FROM settings WHERE setting_key = 'order_grace_period'");
            $stmt->execute();
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing setting
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = 'order_grace_period'");
                $stmt->execute([$gracePeriod]);
            } else {
                // Insert new setting
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES ('order_grace_period', ?, NOW(), NOW())");
                $stmt->execute([$gracePeriod]);
            }
            
            $success = "Grace period updated successfully to {$gracePeriod} minutes.";
        } catch (PDOException $e) {
            $error = "Error updating settings: " . $e->getMessage();
        }
    }
}

// Get current grace period setting
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'order_grace_period'");
$stmt->execute();
$currentGracePeriod = $stmt->fetchColumn();

// Default to 5 minutes if not set
if (!$currentGracePeriod) {
    $currentGracePeriod = 5;
}

// Get stats for dashboard
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$totalProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'");
$stmt->execute();
$totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get pending sellers
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'seller' AND seller_status = 'pending'");
$stmt->execute();
$pendingSellers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get pending products for approval
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'pending'");
$stmt->execute();
$pendingProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get pending products list for display
$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name 
                       FROM products p 
                       JOIN users u ON p.seller_id = u.id 
                       WHERE p.status = 'pending' 
                       ORDER BY p.created_at DESC 
                       LIMIT 5");
$stmt->execute();
$pendingProductsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all admin categories (seller_id IS NULL)
$stmt = $pdo->prepare("SELECT c1.*, c2.name as parent_name 
                       FROM categories c1 
                       LEFT JOIN categories c2 ON c1.parent_id = c2.id 
                       WHERE c1.seller_id IS NULL 
                       ORDER BY c1.parent_id, c1.name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get parent categories for dropdown (admin categories only)
$stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND seller_id IS NULL ORDER BY name");
$stmt->execute();
$parentCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Add notification script function
function addNotificationScript() {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hide success messages after 4 seconds
            var successMsg = document.querySelector('.success-message');
            if (successMsg) {
                setTimeout(function() {
                    successMsg.style.display = 'none';
                }, 4000);
            }
            
            // Hide error messages after 4 seconds
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
    <form id="deleteCategoryForm" method="GET" action="admin-dashboard.php">
      <input type="hidden" name="delete_category" id="delete_category_id">
      <p id="deleteCategoryName" style="margin-bottom:18px;"></p>
      <button type="submit" class="btn" style="background:#d32f2f;">Delete</button>
      <button type="button" class="btn" onclick="closeDeleteModal()" style="margin-left:10px;background:#888;">Cancel</button>
    </form>
  </div>
</div>

<?php if (isset($success)): ?>
    <div class="success-message"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error-message"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stats">
    <div class="stat-card">
        <h3>Total Users</h3>
        <p><?php echo $totalUsers; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Products</h3>
        <p><?php echo $totalProducts; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Orders</h3>
        <p><?php echo $totalOrders; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Revenue</h3>
        <p>₱<?php echo number_format($totalRevenue, 2); ?></p>
    </div>
    <div class="stat-card">
        <h3>Pending Sellers</h3>
        <p><?php echo $pendingSellers; ?></p>
    </div>
    <div class="stat-card">
        <h3>Pending Products</h3>
        <p><?php echo $pendingProducts; ?></p>
    </div>
    <div class="stat-card">
        <h3>Active Sellers</h3>
        <p><?php 
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE user_type = 'seller' AND seller_status = 'approved'");
        $stmt->execute();
        $activeSellers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo $activeSellers; 
        ?></p>
    </div>
    <div class="stat-card">
        <h3>Active Products</h3>
        <p><?php 
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
        $stmt->execute();
        $activeProducts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo $activeProducts; 
        ?></p>
    </div>
    <div class="stat-card">
        <h3>Completed Orders</h3>
        <p><?php 
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'delivered'");
        $stmt->execute();
        $completedOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo $completedOrders; 
        ?></p>
    </div>
</div>

<div class="admin-sections">
    <!-- CATEGORY MANAGEMENT SECTION -->
    <div class="section category-management-section">
        <!-- <h2>Category Management</h2> -->
        <!-- <p class="section-description">Manage global product categories available to all sellers</p>
         -->

<!-- <div class="add-category-form">
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
</div> -->

<!-- <div class="categories-list">
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
    <?php endif; ?>
</div> -->
    </div>
    

   

    <?php if (empty($pendingProductsList)): ?>
        <!-- <p>No pending product approvals.</p> -->
    <?php else: ?>
        <div class="section">
            <h2>Pending Product Approvals</h2>
            <p>Products waiting for admin approval</p>
        </div>
        <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingProductsList as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['first_name'] . ' ' . $product['last_name']); ?></td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td>
                                <a href="admin-products.php?action=approve&id=<?php echo $product['id']; ?>">Approve</a>
                                <a href="admin-products.php?action=reject&id=<?php echo $product['id']; ?>">Reject</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="admin-products.php">View All Pending Products</a>
        <?php endif; ?>
    </div>
</div>

<!-- SETTINGS SECTION -->
<div class="page-header" style="margin-top: 50px;">
    <h1>System Settings</h1>
    <p>Configure platform-wide settings</p>
</div>

<div class="settings-container">
    <form method="POST" action="">
        <div class="setting-group">
            <h3>Order Grace Period</h3>
            <label for="grace_period">Customer Cancellation Grace Period:</label>
            
            <div class="input-group">
                <input type="number" 
                       id="grace_period" 
                       name="grace_period" 
                       value="<?php echo $currentGracePeriod; ?>" 
                       min="1" 
                       max="60" 
                       required>
                <span class="input-suffix">minutes</span>
            </div>
            
            <div class="current-value">
                <strong>Current Setting:</strong> <?php echo $currentGracePeriod; ?> minutes
            </div>
            
            <div class="setting-description">
                This is the time period after an order is placed during which:
                <ul>
                    <li>Customers have priority to cancel their orders</li>
                    <li>Sellers cannot process orders (orders remain in "pending" status)</li>
                    <li>This protects customers from immediate processing and allows cancellation flexibility</li>
                </ul>
            </div>
            
            <div class="warning-box">
                <strong>Important:</strong> Changing this setting will affect all new orders going forward. 
                Existing orders will continue to use the grace period that was active when they were placed.
            </div>
        </div>
        
        <button type="submit" name="update_settings" class="btn">Update Settings</button>
    </form>
    
    <div class="setting-group" style="margin-top: 30px;">
        <h3>How Grace Period Works</h3>
        <div class="setting-description">
            <ol>
                <li><strong>Order Placed:</strong> Customer places an order (status: "pending")</li>
                <li><strong>Grace Period Active:</strong> For the set duration, sellers cannot process the order</li>
                <li><strong>Customer Priority:</strong> During this time, customers can cancel without seller intervention</li>
                <li><strong>Grace Period Ends:</strong> Sellers can now process the order (change to "processing")</li>
                <li><strong>Order Locked:</strong> Once processing starts, customer cancellation requires seller approval</li>
            </ol>
        </div>
    </div>
</div>

<style>
  /* Stats Grid Layout - 3x3 */
  .stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(3, 1fr);
    gap: 20px;
    margin: 30px 0;
    padding: 20px;
  }

  .stat-card {
    background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
    border: 2px solid #d0e6d0;
    border-radius: 12px;
    padding: 25px 20px;
    text-align: center;
    box-shadow: 0 4px 16px rgba(76,175,80,0.1);
    transition: all 0.3s ease;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(76,175,80,0.2);
    border-color: #43a047;
  }

  .stat-card h3 {
    color: #2e7d32;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .stat-card p {
    color: #1b5e20;
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  }

  /* Responsive Design */
  @media (max-width: 1200px) {
    .stats {
      grid-template-columns: repeat(2, 1fr);
      grid-template-rows: repeat(5, 1fr);
    }
  }

  @media (max-width: 768px) {
    .stats {
      grid-template-columns: 1fr;
      grid-template-rows: repeat(9, 1fr);
      gap: 15px;
    }
    
    .stat-card {
      min-height: 100px;
      padding: 20px 15px;
    }
    
    .stat-card h3 {
      font-size: 14px;
    }
    
    .stat-card p {
      font-size: 24px;
    }
  }

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
    padding: 14px 16px !important;
    font-size: 16px;
    background: rgba(255, 215, 54, 0.12) !important;
    border: none !important;
    position: relative;
}

.parent-label:hover {
    background: rgba(255, 215, 54, 0.2) !important;
}

.parent-name {
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #FFD736 !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.toggle-children {
    margin-left: auto;
    cursor: pointer;
    color: #FFD736;
    transition: transform 0.3s ease;
    display: inline-flex;
    align-items: center;
    padding: 4px;
}

.toggle-children:hover {
    color: #fff;
}

.toggle-children.rotated {
    transform: rotate(-90deg);
}

.child-categories {
    padding-left: 24px;
    background: rgba(0, 0, 0, 0.2);
    max-height: 300px;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.child-categories.collapsed {
    max-height: 0;
    padding: 0;
}

.child-label {
    padding: 10px 16px !important;
    margin: 4px 0 !important;
    border-left: 2px solid rgba(255, 215, 54, 0.3);
}

.child-name {
    font-size: 14px !important;
    color: #E0E0E0 !important;
    font-weight: 500 !important;
}
/* Scrollable Categories List */
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

/* Table wrapper with scroll */
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

/* Custom Scrollbar */
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

/* Firefox scrollbar */
.categories-table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: #43a047 #f1f1f1;
}

/* Responsive */
@media (max-width: 768px) {
    .categories-table-wrapper {
        max-height: 300px;
    }
}
.category-checkbox-label {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    margin: 2px 0;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid transparent;
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

.category-checkbox {
    width: 20px;
    height: 20px;
    margin-right: 12px;
    cursor: pointer;
    accent-color: #FFD736;
    flex-shrink: 0;
}

.category-name {
    color: #F9F9F9;
    font-size: 15px;
    font-weight: 500;
    letter-spacing: 0.3px;
    flex: 1;
}

.category-checkbox:checked + .category-name {
    font-weight: 700;
    color: #FFD736;
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
 /* Uniform Container Styles */
    .admin-sections .section,
    .category-management-section,
    .categories-list,
    .add-category-form,
    .settings-container,
    .setting-group {
        background: #f4f8fb;
        border-radius: 14px;
        box-shadow: 0 4px 16px rgba(44,62,80,0.08);
        padding: 32px 28px;
        margin-bottom: 32px;
        border: 1px solid #cfd8dc;
    }

    .settings-container {
        max-width: 900px;
        margin: 40px auto;
        padding: 40px 36px 36px 36px;
    }

    .setting-group {
        margin-bottom: 40px;
        padding: 32px 28px;
    }
    .admin-sections table,
.categories-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-top: 15px;
}

.admin-sections table thead,
.categories-table thead {
    background: linear-gradient(135deg, #e8f5e8 0%, #d0e6d0 100%);
}

.admin-sections table th,
.admin-sections table td,
.categories-table th,
.categories-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #d0e6d0;
    color: #333;
}

.admin-sections table th,
.categories-table th {
    color: #2e7d32;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.admin-sections table tbody tr:hover,
.categories-table tbody tr:hover {
    background: #f8fff8;
    transition: background 0.2s ease;
}

.admin-sections table tbody tr:last-child td,
.categories-table tbody tr:last-child td {
    border-bottom: none;
}

/* Table Action Links */
.admin-sections table a,
.action-links a {
    color: #1976d2;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
    margin: 0 4px;
}

.admin-sections table a:hover,
.action-links a:hover {
    color: #0d47a1;
    text-decoration: underline;
}

.action-links .delete-link,
.admin-sections table a[href*="delete"]:not([href*="admin-products"]) {
    color: #d32f2f;
}

.action-links .delete-link:hover,
.admin-sections table a[href*="delete"]:hover {
    color: #b71c1c;
}

/* Section Containers */
.admin-sections .section {
    background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 16px rgba(76,175,80,0.1);
}

.admin-sections .section h2 {
    color: #2e7d32;
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: 600;
    border-bottom: 2px solid #43a047;
    padding-bottom: 10px;
}

.admin-sections .section p {
    color: #388e3c;
    margin-bottom: 15px;
}

.admin-sections .section > a {
    display: inline-block;
    margin-top: 15px;
    color: #1976d2;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.admin-sections .section > a:hover {
    color: #0d47a1;
    text-decoration: underline;
}

/* Responsive Table Design */
@media (max-width: 768px) {
    .admin-sections table,
    .categories-table {
        font-size: 13px;
    }
    
    .admin-sections table th,
    .admin-sections table td,
    .categories-table th,
    .categories-table td {
        padding: 10px 8px;
    }
}

@media (max-width: 480px) {
    .admin-sections table,
    .categories-table {
        font-size: 11px;
    }
    
    .admin-sections table th,
    .admin-sections table td,
    .categories-table th,
    .categories-table td {
        padding: 8px 6px;
    }
    
    .admin-sections .section {
        padding: 15px;
    }
}
.category-management-section {
    background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 16px rgba(76,175,80,0.1);
}

.section-description {
    color: #388e3c;
    margin-bottom: 20px;
    font-size: 14px;
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

.form-group textarea {
    min-height: 80px;
    resize: vertical;
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

.categories-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.categories-table thead {
    background: linear-gradient(135deg, #e8f5e8 0%, #d0e6d0 100%);
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
}

.categories-table tbody tr:hover {
    background: #f8fff8;
}

.action-links a {
    color: #1976d2;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}

.action-links a:hover {
    color: #0d47a1;
    text-decoration: underline;
}

.action-links .delete-link {
    color: #d32f2f;
}

.action-links .delete-link:hover {
    color: #b71c1c;
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

.settings-container {
    max-width: 800px;
    margin: 32px auto;
    background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
    padding: 38px 32px 32px 32px;
    border-radius: 18px;
    box-shadow: 0 8px 32px rgba(76,175,80,0.10);
}

.setting-group {
    margin-bottom: 36px;
    padding: 28px 22px 22px 22px;
    border: 1px solid #d0e6d0;
    border-radius: 12px;
    background: linear-gradient(135deg, #f9f9f9 0%, #e8f5e8 100%);
}

.setting-group h3 {
    color: #2e7d32;
    margin-top: 0;
    margin-bottom: 18px;
    font-size: 22px;
}

.input-group {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 12px;
}

.input-suffix {
    color: #434ca0ff;
    font-weight: 600;
}

.current-value {
    background: linear-gradient(90deg, #e8f5e8 0%, #f8fff8 100%);
    padding: 12px;
    border-radius: 7px;
    margin-top: 12px;
    border-left: 5px solid #434ca0ff;
}

.warning-box {
    background: linear-gradient(90deg, #fff3cd 0%, #fffde7 100%);
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 16px;
    border-radius: 7px;
    margin-top: 18px;
}

.setting-description {
    color: #388e3c;
    margin-top: 10px;
    line-height: 1.6;
}

.btn {
    background: linear-gradient(135deg, #434ca0ff 0%, #434ca0a9 100%);
    color: white;
    padding: 14px 32px;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 18px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 8px 24px rgba(67,160,71,0.18);
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

</script>
<?php addNotificationScript(); ?>
