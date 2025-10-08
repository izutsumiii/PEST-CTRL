<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// spacer below fixed header
echo '<div style="height:40px"></div>';

requireLogin();

$transactionId = $_GET['transaction_id'] ?? null;

if (!$transactionId) {
    header("Location: multi-seller-checkout.php");
    exit();
}

// Get payment transaction details
$stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE id = ?");
$stmt->execute([$transactionId]);
$paymentTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paymentTransaction) {
    header("Location: multi-seller-checkout.php");
    exit();
}

// Note: Skipping status update because the 'status' column may not exist in payment_transactions

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_transaction_id = ?");
$stmt->execute([$transactionId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_url
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id IN (SELECT id FROM orders WHERE payment_transaction_id = ?)");
$stmt->execute([$transactionId]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group items by seller
$groupedItems = [];
foreach ($orderItems as $item) {
    $sellerId = $item['seller_id'];
    if (!isset($groupedItems[$sellerId])) {
        $groupedItems[$sellerId] = [
            'seller_name' => 'Seller ' . (string)$sellerId,
            'items' => []
        ];
    }
    $groupedItems[$sellerId]['items'][] = $item;
}

// Calculate totals
$grandTotal = 0;
foreach ($groupedItems as $sellerId => $sellerGroup) {
    $subtotal = 0;
    foreach ($sellerGroup['items'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $groupedItems[$sellerId]['subtotal'] = $subtotal;
    $grandTotal += $subtotal;
}

?>

<style>
body { background: #130325 !important; min-height: 100vh; }
.success-container { max-width: 800px; margin: 0 auto; padding: 20px; }
h1 { color: var(--primary-light); text-align: center; margin: 20px 0; font-size: 2rem; }
.success-section { background: var(--primary-dark); border: 1px solid var(--accent-yellow); padding: 40px; border-radius: 8px; text-align: center; margin-bottom: 30px; }
.success-icon { color: #28a745; font-size: 4rem; margin-bottom: 20px; }
.success-title { color: var(--primary-light); font-size: 1.8rem; margin-bottom: 10px; }
.success-message { color: var(--primary-light); opacity: 0.9; margin-bottom: 20px; }
.order-details { background: var(--primary-dark); border: 1px solid var(--accent-yellow); padding: 30px; border-radius: 8px; margin-bottom: 30px; }
.order-details h3 { color: var(--accent-yellow); margin-bottom: 20px; }
.seller-group { margin-bottom: 20px; padding: 15px; border: 1px solid rgba(255, 215, 54, 0.3); border-radius: 8px; background: rgba(255, 215, 54, 0.05); }
.seller-header { color: var(--accent-yellow); font-weight: 600; margin-bottom: 10px; }
.order-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255, 215, 54, 0.2); }
.order-item:last-child { border-bottom: none; }
.item-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 15px; }
.item-details { flex-grow: 1; }
.item-details h4 { color: var(--primary-light); margin: 0 0 5px 0; font-size: 16px; }
.item-details p { color: #ffffff; margin: 2px 0; font-size: 14px; }
.item-total { color: #ffffff; font-weight: bold; font-size: 16px; }
.grand-total { text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--accent-yellow); }
.grand-total strong { color: var(--accent-yellow); font-size: 1.5rem; }
.actions { display: flex; gap: 15px; justify-content: center; }
.btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s; }
.btn-primary { background: #FFD736; color: #130325; }
.btn-primary:hover { background: #e6c230; transform: translateY(-2px); }
.btn-secondary { background: #6c757d; color: #ffffff; }
.btn-secondary:hover { background: #5a6268; }
</style>

<main>
<div class="success-container">
    <h1>Payment Successful!</h1>
    
    <div class="success-section">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="success-title">Thank You for Your Order!</div>
        <div class="success-message">
            Your payment has been processed successfully. You will receive an email confirmation shortly.
        </div>
        <div style="color: var(--primary-light); font-size: 1.1rem;">
            Transaction ID: <strong><?php echo htmlspecialchars($transactionId); ?></strong>
        </div>
    </div>
    
    <div class="order-details">
        <h3><i class="fas fa-shopping-cart"></i> Order Summary</h3>
        
        <?php foreach ($groupedItems as $sellerId => $sellerGroup): ?>
            <div class="seller-group">
                <div class="seller-header">
                    <i class="fas fa-store"></i> <?php echo htmlspecialchars($sellerGroup['seller_name']); ?>
                </div>
                
                <?php foreach ($sellerGroup['items'] as $item): ?>
                    <div class="order-item">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'default-image.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                             class="item-image"
                             onerror="this.src='default-image.jpg'">
                        <div class="item-details">
                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                            <p>Price: ₱<?php echo number_format($item['price'], 2); ?></p>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        <div class="item-total">
                            ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div style="text-align: right; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255, 215, 54, 0.3);">
                    <strong style="color: var(--accent-yellow);">Subtotal: ₱<?php echo number_format($sellerGroup['subtotal'], 2); ?></strong>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="grand-total">
            <strong>Grand Total: ₱<?php echo number_format($grandTotal, 2); ?></strong>
        </div>
    </div>
    
    <div class="actions">
        <a href="user-dashboard.php" class="btn btn-primary">
            <i class="fas fa-tachometer-alt"></i> View Dashboard
        </a>
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-shopping-bag"></i> Continue Shopping
        </a>
    </div>
</div>
</main>

<?php require_once 'includes/footer.php'; ?>
