<?php
require_once '../config/database.php';
require_once 'includes/admin_header.php';

requireAdmin();

$userId = $_SESSION['user_id'];

// Handle seller actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = sanitizeInput($_GET['action']);
    $sellerId = intval($_GET['id']);
    
    $validActions = ['approve', 'reject', 'suspend', 'ban', 'verify_documents', 'unverify_documents'];
    
    if (in_array($action, $validActions)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'seller'");
        $stmt->execute([$sellerId]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seller) {
            switch ($action) {
                case 'approve':
                    $stmt = $pdo->prepare("UPDATE users SET seller_status = 'approved' WHERE id = ?");
                    $stmt->execute([$sellerId]);
                    $message = "Seller approved successfully!";
                    break;
                    
                case 'reject':
                    $stmt = $pdo->prepare("UPDATE users SET seller_status = 'rejected' WHERE id = ?");
                    $stmt->execute([$sellerId]);
                    $message = "Seller rejected successfully!";
                    break;
                    
                case 'suspend':
                    $stmt = $pdo->prepare("UPDATE users SET seller_status = 'suspended' WHERE id = ?");
                    $stmt->execute([$sellerId]);
                    $message = "Seller suspended successfully!";
                    break;
                    
                case 'ban':
                    $stmt = $pdo->prepare("UPDATE users SET seller_status = 'banned' WHERE id = ?");
                    $stmt->execute([$sellerId]);
                    $message = "Seller banned successfully!";
                    break;
                    
                case 'verify_documents':
                    $stmt = $pdo->prepare("UPDATE users SET document_verified = TRUE WHERE id = ?");
                    $stmt->execute([$sellerId]);
                    $message = "Documents verified successfully!";
                    break;
                    
                case 'unverify_documents':
                    $stmt = $pdo->prepare("UPDATE users SET document_verified = FALSE WHERE id = ?");
                    $stmt->execute([$sellerId]);
                    $message = "Documents verification removed!";
                    break;
            }
            
            if (isset($message)) {
                echo "<p class='success-message'>$message</p>";
            }
        } else {
            echo "<p class='error-message'>Seller not found.</p>";
        }
    }
}

// Handle document upload
if (isset($_POST['upload_document'])) {
    $sellerId = intval($_POST['seller_id']);
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'assets/documents/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['document']['name']);
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['document']['tmp_name'], $uploadFile)) {
            $stmt = $pdo->prepare("UPDATE users SET document_path = ? WHERE id = ?");
            $stmt->execute([$uploadFile, $sellerId]);
            echo "<p class='success-message'>Document uploaded successfully!</p>";
        } else {
            echo "<p class='error-message'>Error uploading document.</p>";
        }
    }
}

// Get sellers with filter
$statusFilter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : 'all';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query based on filters
$query = "SELECT * FROM users WHERE user_type = 'seller'";
$params = [];
$countParams = [];

if ($statusFilter !== 'all') {
    $query .= " AND seller_status = ?";
    $params[] = $statusFilter;
    $countParams[] = $statusFilter;
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
// Add limit and offset as integers to parameters array
$params[] = $limit;
$params[] = $offset;

// Get sellers
$stmt = $pdo->prepare($query);

// Bind parameters with proper types
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key + 1, $value, $paramType);
}

$stmt->execute();
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM users WHERE user_type = 'seller'";
if ($statusFilter !== 'all') {
    $countQuery .= " AND seller_status = ?";
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalSellers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalSellers / $limit);
?>

<!-- Rest of your HTML/PHP code remains the same -->
<h1>Manage Sellers</h1>

<div class="admin-filters">
    <h2>Filter by Status</h2>
    <div class="status-filters">
        <a href="admin-sellers.php?status=all" class="<?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
        <a href="admin-sellers.php?status=pending" class="<?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="admin-sellers.php?status=approved" class="<?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">Approved</a>
        <a href="admin-sellers.php?status=rejected" class="<?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
        <a href="admin-sellers.php?status=suspended" class="<?php echo $statusFilter === 'suspended' ? 'active' : ''; ?>">Suspended</a>
        <a href="admin-sellers.php?status=banned" class="<?php echo $statusFilter === 'banned' ? 'active' : ''; ?>">Banned</a>
    </div>
</div>

<div class="admin-content">
    <h2>Sellers List (<?php echo $totalSellers; ?> total)</h2>
    
    <?php if (empty($sellers)): ?>
        <p>No sellers found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Documents</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sellers as $seller): ?>
                    <tr>
                        <td><?php echo $seller['id']; ?></td>
                        <td><?php echo $seller['username']; ?></td>
                        <td><?php echo $seller['email']; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $seller['seller_status']; ?>">
                                <?php echo ucfirst($seller['seller_status']); ?>
                            </span>
                            <?php if ($seller['document_verified']): ?>
                                <br><small class="verified">Documents Verified</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($seller['document_path']): ?>
                                <a href="<?php echo $seller['document_path']; ?>" target="_blank">View Document</a>
                            <?php else: ?>
                                No document
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($seller['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($seller['seller_status'] === 'pending'): ?>
                                    <a href="admin-sellers.php?action=approve&id=<?php echo $seller['id']; ?>" class="btn-approve">Approve</a>
                                    <a href="admin-sellers.php?action=reject&id=<?php echo $seller['id']; ?>" class="btn-reject">Reject</a>
                                <?php endif; ?>
                                
                                <?php if ($seller['seller_status'] === 'approved'): ?>
                                    <a href="admin-sellers.php?action=suspend&id=<?php echo $seller['id']; ?>" class="btn-suspend">Suspend</a>
                                <?php endif; ?>
                                
                                <?php if (in_array($seller['seller_status'], ['approved', 'suspended'])): ?>
                                    <a href="admin-sellers.php?action=ban&id=<?php echo $seller['id']; ?>" class="btn-ban">Ban</a>
                                <?php endif; ?>
                                
                                <?php if ($seller['document_path'] && !$seller['document_verified']): ?>
                                    <a href="admin-sellers.php?action=verify_documents&id=<?php echo $seller['id']; ?>" class="btn-verify">Verify Docs</a>
                                <?php endif; ?>
                                
                                <?php if ($seller['document_verified']): ?>
                                    <a href="admin-sellers.php?action=unverify_documents&id=<?php echo $seller['id']; ?>" class="btn-unverify">Unverify Docs</a>
                                <?php endif; ?>
                                
                                <button onclick="openDocumentModal(<?php echo $seller['id']; ?>)" class="btn-upload">Upload Doc</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="admin-sellers.php?status=<?php echo $statusFilter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="admin-sellers.php?status=<?php echo $statusFilter; ?>&page=<?php echo $i; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="admin-sellers.php?status=<?php echo $statusFilter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Document Upload Modal -->
<div id="documentModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDocumentModal()">&times;</span>
        <h2>Upload Document</h2>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" id="modalSellerId" name="seller_id" value="">
            
            <div class="form-group">
                <label for="document">Select Document:</label>
                <input type="file" id="document" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
            </div>
            
            <button type="submit" name="upload_document">Upload Document</button>
        </form>
    </div>
</div>

<script>
function openDocumentModal(sellerId) {
    document.getElementById('modalSellerId').value = sellerId;
    document.getElementById('documentModal').style.display = 'block';
}

function closeDocumentModal() {
    document.getElementById('documentModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('documentModal');
    if (event.target === modal) {
        closeDocumentModal();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>