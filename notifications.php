<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

requireLogin();

$userId = $_SESSION['user_id'];

// Pull recent order status updates for this user
$stmt = $pdo->prepare("SELECT o.id as order_id, o.status, o.created_at, o.updated_at,
                             COALESCE(pt.payment_status, '') as payment_status
                        FROM orders o
                        LEFT JOIN payment_transactions pt ON pt.id = o.payment_transaction_id
                        WHERE o.user_id = ?
                        ORDER BY o.updated_at DESC, o.created_at DESC
                        LIMIT 100");
$stmt->execute([$userId]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON mode for header popup
if (isset($_GET['as']) && $_GET['as'] === 'json') {
    header('Content-Type: application/json');
    $items = [];
    foreach ($events as $e) {
        $items[] = [
            'order_id' => (int)$e['order_id'],
            'status' => (string)$e['status'],
            'payment_status' => (string)$e['payment_status'],
            'updated_at' => $e['updated_at'] ?: $e['created_at'],
            'updated_at_human' => date('M d, Y h:i A', strtotime($e['updated_at'] ?: $e['created_at']))
        ];
    }
    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

function statusBadge($status) {
    $status = strtolower((string)$status);
    $map = [
        'pending' => ['Pending', '#ffc107', '#130325'],
        'processing' => ['Processing', '#0dcaf0', '#130325'],
        'shipped' => ['Shipped', '#17a2b8', '#ffffff'],
        'delivered' => ['Delivered', '#28a745', '#ffffff'],
        'cancelled' => ['Cancelled', '#dc3545', '#ffffff'],
        'refunded' => ['Refunded', '#6c757d', '#ffffff'],
    ];
    $label = ucfirst($status);
    $bg = '#6c757d';
    $fg = '#ffffff';
    if (isset($map[$status])) { [$label,$bg,$fg] = $map[$status]; }
    return '<span class="badge" style="background:'.$bg.';color:'.$fg.';padding:4px 8px;border-radius:12px;font-weight:600;font-size:12px;">'.$label.'</span>';
}
?>

<style>
  html, body { background:#130325 !important; }
</style>
<main style="background:#130325; min-height:100vh; padding: 80px 0 60px 0;">
  <div style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
    <h1 style="color:#ffffff; text-align:center; margin:0 0 30px 0;">Notifications</h1>

    <?php if (empty($events)): ?>
      <div style="background:#1a0a2e; border:1px solid #2d1b4e; color:#F9F9F9; border-radius:8px; padding:20px; text-align:center;">No notifications yet.</div>
    <?php else: ?>
      <div class="notif-list" style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($events as $e): ?>
          <a href="user-dashboard.php#order-<?php echo (int)$e['order_id']; ?>" class="notif-item" style="text-decoration:none;">
            <div style="display:flex; gap:12px; align-items:center; background:#1a0a2e; border:1px solid rgba(255,215,54,0.25); padding:14px; border-radius:10px;">
              <div style="width:36px; height:36px; display:flex; align-items:center; justify-content:center; background:rgba(255,215,54,0.15); color:#FFD736; border-radius:8px;">
                <i class="fas fa-bell"></i>
              </div>
              <div style="flex:1;">
                <div style="color:#F9F9F9; font-weight:700;">Order #<?php echo (int)$e['order_id']; ?> update</div>
                <div style="color:#F9F9F9; opacity:0.9; font-size:0.9rem;">
                  Status: <?php echo statusBadge($e['status']); ?>
                  <?php if (!empty($e['payment_status'])): ?>
                    <span style="margin-left:8px; opacity:0.9;">Payment: <?php echo htmlspecialchars($e['payment_status']); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div style="color:#F9F9F9; opacity:0.8; font-size:0.85rem; white-space:nowrap;">
                <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($e['updated_at'] ?: $e['created_at']))); ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php require_once 'includes/footer.php'; ?>


