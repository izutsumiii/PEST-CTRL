<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Load user profile for auto-fill
$userProfile = null;
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $userProfile = null;
}

// Get cart items grouped by seller
// Support selected-only checkout via query param ?selected=1,2,3
$selectedIds = [];
if (!empty($_GET['selected'])) {
    $selectedIds = array_values(array_filter(array_map('intval', explode(',', $_GET['selected']))));
}

$groupedItems = getCartItemsGroupedBySeller();
if (!empty($selectedIds)) {
    // Filter items to selected product IDs only, recompute subtotals and item counts
    foreach ($groupedItems as $sid => &$sg) {
        $sg['items'] = array_values(array_filter($sg['items'], function($it) use ($selectedIds) {
            return in_array((int)$it['product_id'], $selectedIds, true);
        }));
        $sg['subtotal'] = 0;
        $sg['item_count'] = 0;
        foreach ($sg['items'] as $it) {
            $sg['subtotal'] += $it['price'] * $it['quantity'];
            $sg['item_count'] += $it['quantity'];
        }
        if (empty($sg['items'])) {
            unset($groupedItems[$sid]);
        }
    }
    unset($sg);
}

// Recompute grand total from filtered groups
$grandTotal = 0;
foreach ($groupedItems as $sg) { $grandTotal += $sg['subtotal']; }

// Handle form submission
$errors = [];
$success = false;

// Handle remove item via AJAX (before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_item') {
    header('Content-Type: application/json');
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }
    $productId = (int)($_POST['product_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error removing item']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? '';
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    
    // Validation
    if (empty($shippingAddress)) {
        $errors[] = 'Shipping address is required';
    }
    if (empty($paymentMethod)) {
        $errors[] = 'Payment method is required';
    }
    
    if (empty($errors)) {
        // Process multi-seller checkout
        if (method_exists($pdo, 'query')) { ensureAutoIncrementPrimary('payment_transactions'); ensureAutoIncrementPrimary('orders'); }
        $result = processMultiSellerCheckout($shippingAddress, $paymentMethod, $customerName, $customerEmail, $customerPhone);
        
        // If this is an AJAX request, return JSON instead of redirecting
        $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
        if ($isAjax) {
            header('Content-Type: application/json');
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'transaction_id' => $result['payment_transaction_id'],
                    'redirect_url' => 'multi-seller-payment.php?transaction_id=' . $result['payment_transaction_id']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
            exit();
        }

        if ($result['success']) {
            // Standard redirect fallback
            $_SESSION['checkout_success'] = $result;
            header("Location: multi-seller-payment.php?transaction_id=" . $result['payment_transaction_id']);
            exit();
        } else {
            $errors[] = $result['message'];
        }
    }
}

// If cart is empty, redirect to cart page BEFORE sending any output
if (empty($groupedItems)) {
    header("Location: cart.php");
    exit();
}

// Include header only after all potential redirects/headers
require_once 'includes/header.php';
?>

<main style="background: #130325; min-height: 100vh; padding: 20px;">
<div class="checkout-container">
    <h1>Checkout</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="checkout-content">
        <div class="order-summary">
            <h2>Order Summary</h2>

            <div class="order-items" id="order-items-container">
                <?php foreach ($groupedItems as $sellerId => $sellerGroup): ?>
                    <div class="seller-group" data-seller-id="<?php echo $sellerId; ?>">
                        <div class="seller-header">
                            <h3>
                                <i class="fas fa-store"></i>
                                Seller: <?php echo htmlspecialchars($sellerGroup['seller_display_name']); ?>
                            </h3>
                            <div class="seller-total">
                                Subtotal: ₱<?php echo number_format($sellerGroup['subtotal'], 2); ?>
                            </div>
                        </div>

                        <?php foreach ($sellerGroup['items'] as $item): ?>
                            <div class="order-item" data-product-id="<?php echo $item['product_id']; ?>">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="item-image">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Price: ₱<?php echo number_format($item['price'], 2); ?></p>
                                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="item-total">
                                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                                <button class="remove-item-btn" 
                                        data-product-id="<?php echo $item['product_id']; ?>"
                                        title="Remove item">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-total" id="order-total">
                <strong>Grand Total: ₱<?php echo number_format($grandTotal, 2); ?></strong>
            </div>
        </div>

        <div class="checkout-form" id="checkout-form">
            <h2>Billing & Shipping Information</h2>

            <?php if ($userProfile): ?>
                <div class="alert alert-info">
                    <strong>Information Auto-filled:</strong> Your profile information has been automatically filled. 
                    <button type="button" id="useProfileBtn" class="btn btn-secondary" style="margin-left:10px;">Use My Profile</button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="ms-checkout-form">
                <div class="form-group">
                    <label for="customer_name">Full Name</label>
                    <input type="text" id="customer_name" name="customer_name"
                           value="<?php echo htmlspecialchars($_POST['customer_name'] ?? (trim(($userProfile['first_name'] ?? '') . ' ' . ($userProfile['last_name'] ?? '')))); ?>">
                </div>

                <div class="form-group">
                    <label for="customer_email">Email Address</label>
                    <input type="email" id="customer_email" name="customer_email"
                           value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ($userProfile['email'] ?? '')); ?>">
                </div>

                <div class="form-group">
                    <label for="customer_phone">Phone Number</label>
                    <input type="tel" id="customer_phone" name="customer_phone"
                           value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ($userProfile['phone'] ?? '')); ?>">
                </div>

                <div class="form-group">
                    <label for="shipping_address">Shipping Address *</label>
                    <textarea id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($_POST['shipping_address'] ?? ($userProfile['address'] ?? '')); ?></textarea>
                </div>

                <div class="form-group payment-method-group">
                    <label for="payment_method" class="payment-method-label">Payment Method *</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Please select a payment method</option>
                        <option value="paymongo" <?php echo ($_POST['payment_method'] ?? '') === 'paymongo' ? 'selected' : ''; ?>>Debit/Credit Card</option>
                        <option value="gcash" <?php echo ($_POST['payment_method'] ?? '') === 'gcash' ? 'selected' : ''; ?>>GCash</option>
                        <option value="paymaya" <?php echo ($_POST['payment_method'] ?? '') === 'paymaya' ? 'selected' : ''; ?>>PayMaya</option>
                    </select>
                    <small class="payment-method-note">Choose your preferred payment method to continue</small>
                </div>

                <div class="form-actions">
                    <a href="cart.php" class="btn btn-secondary">Back to Cart</a>
                    <button type="button" id="reviewOrderBtn" class="btn btn-primary">
                        <i class="fas fa-eye"></i> Review Order
                    </button>
                    <button type="button" id="openPaymentModal" class="btn btn-primary" style="display:none;">
                        Checkout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</main>

<!-- Custom Remove Confirmation Popup -->
<div id="removeConfirmModal" class="remove-confirm-modal" style="display: none;">
    <div class="remove-confirm-content">
        <div class="remove-confirm-header">
            <h3>Remove Item</h3>
            <button class="close-btn" onclick="hideRemoveConfirm()">&times;</button>
        </div>
        <div class="remove-confirm-body">
            <p>Are you sure you want to remove this item from checkout?</p>
        </div>
        <div class="remove-confirm-footer">
            <button class="btn-cancel" onclick="hideRemoveConfirm()">Cancel</button>
            <button class="btn-confirm" onclick="confirmRemove()">Remove Item</button>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<style>
/* Custom Remove Confirmation Popup Styles */
.remove-confirm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remove-confirm-content {
    background: #1a0a2e;
    border: 2px solid #FFD736;
    border-radius: 12px;
    max-width: 400px;
    width: 90%;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    animation: popupSlideIn 0.3s ease-out;
}

.remove-confirm-header {
    background: #130325;
    padding: 20px;
    border-radius: 10px 10px 0 0;
    border-bottom: 1px solid #FFD736;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.remove-confirm-header h3 {
    color: #FFD736;
    margin: 0;
    font-size: 1.3rem;
    font-weight: 700;
}

.close-btn {
    background: none;
    border: none;
    color: #FFD736;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s;
}

.close-btn:hover {
    background: rgba(255, 215, 54, 0.2);
    transform: scale(1.1);
}

.remove-confirm-body {
    padding: 25px 20px;
    text-align: center;
}

.remove-confirm-body p {
    color: #F9F9F9;
    margin: 0;
    font-size: 1.1rem;
    line-height: 1.5;
}

.remove-confirm-footer {
    padding: 20px;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-cancel, .btn-confirm {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1rem;
}

.btn-cancel {
    background: #6c757d;
    color: #ffffff;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-confirm {
    background: #dc3545;
    color: #ffffff;
}

.btn-confirm:hover {
    background: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

@keyframes popupSlideIn {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(-50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Layout and visual styles copied from checkout.php */
body { background: #130325 !important; min-height: 100vh; }
.checkout-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
h1 { color: var(--primary-light); text-align: center; margin: 20px 0; font-size: 2rem; border-bottom: 3px solid var(--accent-yellow); padding-bottom: 10px; }
.alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; }
.alert-info { background-color: var(--primary-dark); border: 1px solid var(--accent-yellow); color: var(--accent-yellow); }
.alert-warning { background-color: var(--primary-dark); border: 1px solid #ffc107; color: #ffc107; }
.alert-error { background-color: var(--primary-dark); border: 1px solid #dc3545; color: #dc3545; }
.alert ul { margin: 0; padding-left: 20px; }
.checkout-content { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px; }
.order-summary { background-color: var(--primary-dark); border: 1px solid var(--accent-yellow); padding: 20px; border-radius: 8px; }
.order-items { margin: 20px 0; }
.order-item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid rgba(255, 215, 54, 0.3); position: relative; }
.order-item:last-child { border-bottom: none; }
.item-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; margin-right: 15px; }
.item-details { flex-grow: 1; }
.item-details h4 { margin: 0 0 5px 0; font-size: 16px; }
.item-details p { margin: 2px 0; color: #666; font-size: 14px; }
/* Make all price text white */
.item-details p, .review-item-info, .seller-total, .item-total, .order-total, .review-subtotal {
    color: #ffffff !important;
}
.item-total { font-weight: bold; font-size: 16px; margin-right: 10px; color: #ffffff !important; }
.remove-item-btn { background-color: #dc3545; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 16px; font-weight: bold; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; line-height: 1; }
.remove-item-btn:hover { background-color: #c82333; transform: scale(1.1); }
.remove-item-btn:disabled { background-color: #6c757d; cursor: not-allowed; transform: none; }
.order-total { border-top: 2px solid var(--accent-yellow); padding-top: 15px; font-size: 18px; text-align: right; color: #ffffff !important; }
.checkout-form { background-color: var(--primary-dark); border: 1px solid var(--accent-yellow); padding: 20px; border-radius: 8px; }
.checkout-form h2 { color: var(--primary-light); border-bottom: 2px solid var(--accent-yellow); padding-bottom: 10px; margin-bottom: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { color: var(--primary-light); font-weight: 500; margin-bottom: 5px; display: block; }
.form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; transition: border-color 0.3s; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--accent-yellow); box-shadow: 0 0 0 2px rgba(255, 215, 54, 0.25); }
#payment_method { background: var(--primary-dark); border: 1px solid #ced4da; color: var(--primary-light); font-size: 15px; padding: 10px 12px; border-radius: 4px; }
#payment_method:focus { border-color: var(--accent-yellow); box-shadow: none; }
#payment_method option { background: var(--primary-dark); color: var(--primary-light); padding: 8px; }
#payment_method.error { border-color: #dc3545; box-shadow: none; }
#payment_method.error:focus { border-color: #dc3545; box-shadow: none; }
.payment-method-group { background: transparent; border: 1px solid #ced4da; border-radius: 6px; padding: 16px; margin: 20px 0; }
.payment-method-label { font-size: 16px; font-weight: 600; color: var(--primary-light); margin-bottom: 8px; display: block; }
.payment-method-note { color: var(--primary-light); font-size: 13px; margin-top: 6px; display: block; opacity: 0.7; }
.form-actions { display: flex; justify-content: space-between; gap: 15px; margin-top: 30px; }
.btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; text-align: center; transition: background-color 0.3s; }
.btn-secondary { background-color: #6c757d; color: white; }
.btn-secondary:hover { background-color: #5a6268; }
.btn-primary { background-color: #007bff; color: white; font-weight: 500; }
.btn-primary:hover { background-color: #0056b3; }
/* Seller grouping visuals matching checkout.php */
.seller-group { margin-bottom: 30px; border: 2px solid rgba(255, 215, 54, 0.3); border-radius: 8px; padding: 15px; background: rgba(255, 215, 54, 0.05); }
.seller-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; margin-bottom: 15px; border-bottom: 2px solid var(--accent-yellow); }
.seller-header h3 { color: var(--accent-yellow); margin: 0; font-size: 18px; display: flex; align-items: center; gap: 8px; }
.seller-total { color: #ffffff !important; font-weight: bold; font-size: 16px; }
.seller-group .order-item { border-bottom: 1px solid rgba(255, 215, 54, 0.2); }
.seller-group .order-item:last-child { border-bottom: none; }
@media (max-width: 768px) {
  .checkout-content { grid-template-columns: 1fr; gap: 20px; }
  .form-actions { flex-direction: column; }
  .order-item { flex-direction: column; text-align: center; padding: 20px 15px; }
  .item-image { margin: 0 0 10px 0; }
  .item-total { margin-right: 0; margin-top: 10px; }
  .remove-item-btn { position: absolute; top: 10px; right: 10px; }
}
</style>
<div id="paymentModal" class="review-modal" style="display:none;">
    <div class="review-modal-content" style="max-width:1000px; width:95%; height:85vh;">
        <div class="review-modal-header">
            <h2><i class="fas fa-credit-card"></i> Complete Your Payment</h2>
            <span class="close-modal" id="closePaymentModal">&times;</span>
        </div>
        <iframe id="paymentIframe" src="about:blank" style="width:100%; height: calc(85vh - 100px); border:0; border-radius:8px; background:#fff;"></iframe>
    </div>
</div>
<style>
/* Ensure review/payment modals overlay the whole page (same look as checkout) */
.review-modal { position: fixed; inset: 0; z-index: 100000; background: rgba(0,0,0,0.8); display: none; overflow-y: auto; }
.review-modal-content { background-color: var(--primary-dark); margin: 2% auto; padding: 30px; border: 2px solid var(--accent-yellow); border-radius: 12px; width: 90%; max-width: 900px; max-height: 90vh; overflow-y: auto; animation: slideDown 0.3s ease; }
.review-modal-header { display:flex; justify-content: space-between; align-items:center; margin-bottom:25px; padding-bottom:15px; border-bottom: 2px solid var(--accent-yellow); }
.review-modal-header h2 { color: var(--accent-yellow); margin: 0; }
.close-modal { color: var(--primary-light); font-size: 32px; font-weight: bold; cursor: pointer; transition: color 0.3s; line-height: 1; }
.close-modal:hover { color: var(--accent-yellow); }
.review-section h3 { color: var(--accent-yellow); margin-bottom: 15px; font-size: 20px; }
.review-info-grid { display:grid; grid-template-columns: repeat(2, 1fr); gap: 15px; background: rgba(255, 215, 54, 0.05); padding: 20px; border-radius: 8px; border: 1px solid rgba(255, 215, 54, 0.3); }
.review-info-item { color: var(--primary-light); }
.review-info-item strong { color: var(--accent-yellow); display:block; margin-bottom:5px; }
.review-seller-group { background: rgba(255, 215, 54, 0.05); border: 1px solid rgba(255, 215, 54, 0.3); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.review-seller-header { color: var(--accent-yellow); font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid rgba(255, 215, 54, 0.3); }
.review-item { display:flex; align-items:center; padding:12px 0; border-bottom:1px solid rgba(255, 215, 54, 0.2); color: var(--primary-light); }
.review-item:last-child { border-bottom: none; }
.review-item img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 15px; }
.review-item-details { flex-grow: 1; }
.review-item-name { font-weight: 500; margin-bottom: 5px; }
.review-item-info { font-size: 14px; opacity: 0.8; }
.review-subtotal { text-align: right; padding-top:10px; margin-top:10px; border-top:1px solid rgba(255, 215, 54, 0.3); color: #ffffff !important; font-weight: bold; }
.review-grand-total { text-align:right; font-size:24px; color: var(--accent-yellow); margin-top:20px; padding-top:20px; border-top:2px solid var(--accent-yellow); }
.review-actions { display:flex; gap:15px; justify-content:flex-end; margin-top:30px; }
.btn.btn-primary { background-color: #007bff; color:#fff; font-weight:500; }
.btn.btn-primary:hover { background-color: #0056b3; }
.btn.btn-secondary { background-color: #6c757d; color:#fff; }
.btn.btn-secondary:hover { background-color: #5a6268; }
@keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@media (max-width: 768px) { .review-modal-content { width:95%; padding:20px; margin:5% auto; } .review-info-grid { grid-template-columns: 1fr; } .review-actions { flex-direction: column; } .review-actions .btn { width:100%; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Review Order (reuse checkout modal styling/structure)
    const reviewBtn = document.getElementById('reviewOrderBtn');
    if (reviewBtn) {
        reviewBtn.addEventListener('click', function() {
            // Build modal similar to checkout.php (with customer info)
            const modal = document.createElement('div');
            modal.className = 'review-modal';
            modal.id = 'msReviewModal';

            // Read customer form fields
            const name = (document.getElementById('customer_name')?.value || '').trim();
            const email = (document.getElementById('customer_email')?.value || '').trim();
            const phone = (document.getElementById('customer_phone')?.value || '').trim();
            const address = (document.getElementById('shipping_address')?.value || '').trim();
            const paymentMethod = (document.getElementById('payment_method')?.value || '').trim();

            let sellerGroupsHtml = '';
            const itemsBySeller = <?php echo json_encode($groupedItems); ?>;
            for (const sid in itemsBySeller) {
                const sg = itemsBySeller[sid];
                const itemsHtml = sg.items.map(item => `
                    <div class="review-item">
                        <img src="${item.image_url || ''}" alt="${item.name}">
                        <div class="review-item-details">
                            <div class="review-item-name">${item.name}</div>
                            <div class="review-item-info">Qty: ${item.quantity} × ₱${parseFloat(item.price).toFixed(2)} = ₱${(item.quantity * item.price).toFixed(2)}</div>
                        </div>
                    </div>
                `).join('');
                sellerGroupsHtml += `
                    <div class="review-seller-group">
                        <div class="review-seller-header">
                            <i class="fas fa-store"></i> Seller: ${sg.seller_display_name}
                        </div>
                        ${itemsHtml}
                        <div class="review-subtotal">Subtotal: ₱${parseFloat(sg.subtotal).toFixed(2)}</div>
                    </div>
                `;
            }

            function pmLabel(method){
                const map = { card:'💳 Credit/Debit Card', gcash:'📱 GCash', grab_pay:'🚗 GrabPay', paymaya:'💳 PayMaya', billease:'🏦 Billease', cash_on_delivery:'💰 Cash on Delivery' };
                return map[method] || method;
            }

            modal.innerHTML = `
                <div class="review-modal-content">
                    <div class="review-modal-header">
                        <h2><i class=\"fas fa-clipboard-check\"></i> Review Your Order</h2>
                        <span class="close-modal">&times;</span>
                    </div>
                    <div class="review-section">
                        <h3><i class=\"fas fa-user\"></i> Customer Information</h3>
                        <div class="review-info-grid">
                            <div class="review-info-item"><strong>Name:</strong>${name}</div>
                            <div class="review-info-item"><strong>Email:</strong>${email}</div>
                            <div class="review-info-item"><strong>Phone:</strong>${phone}</div>
                            <div class="review-info-item"><strong>Payment Method:</strong>${pmLabel(paymentMethod)}</div>
                        </div>
                        <div class="review-info-grid" style="margin-top: 15px; grid-template-columns: 1fr;">
                            <div class="review-info-item"><strong>Shipping Address:</strong>${address}</div>
                        </div>
                    </div>
                    <div class="review-section">
                        <h3><i class=\"fas fa-shopping-cart\"></i> Order Items</h3>
                        ${sellerGroupsHtml}
                    </div>
                    <div class="review-grand-total">
                        <strong>Grand Total: ₱<?php echo number_format($grandTotal, 2); ?></strong>
                    </div>
                    <div class="review-actions">
                        <a href="cart.php" class="btn btn-secondary" id="msReviewBack">Back to Cart</a>
                        <button type="button" class="btn btn-primary" id="msReviewProceed">Checkout</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            const closeEls = [modal.querySelector('.close-modal'), modal.querySelector('#msReviewClose')];
            closeEls.forEach(el => el && el.addEventListener('click', () => { modal.remove(); document.body.style.overflow=''; }));
            modal.addEventListener('click', e => { if (e.target === modal) { modal.remove(); document.body.style.overflow=''; } });
            const proceedBtn = modal.querySelector('#msReviewProceed');
            if (proceedBtn) proceedBtn.addEventListener('click', () => {
                modal.remove();
                // Trigger payment popup (Checkout)
                openBtn.click();
            });
        });
    }
    const openBtn = document.getElementById('openPaymentModal');
    const form = document.getElementById('ms-checkout-form');
    const modal = document.getElementById('paymentModal');
    const closeBtn = document.getElementById('closePaymentModal');
    const iframe = document.getElementById('paymentIframe');

    function closeModal() {
        modal.style.display = 'none';
        iframe.src = 'about:blank';
        document.body.style.overflow = '';
    }

    if (openBtn && form) {
        openBtn.addEventListener('click', function() {
            const fd = new FormData(form);
            fd.append('checkout', '1');
            fd.append('ajax', '1');
            fetch('', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        // Redirect directly to payment URL (no overlay)
                        window.location.href = data.redirect_url;
                    } else {
                        alert(data.message || 'Failed to initialize payment');
                    }
                })
                .catch(() => alert('Failed to initialize payment'));
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
});
</script>
<script>
// Global variables for the remove confirmation
let currentProductId = null;
let currentRow = null;

function showRemoveConfirm(productId, row) {
    currentProductId = productId;
    currentRow = row;
    document.getElementById('removeConfirmModal').style.display = 'flex';
}

function hideRemoveConfirm() {
    document.getElementById('removeConfirmModal').style.display = 'none';
    currentProductId = null;
    currentRow = null;
}

function confirmRemove() {
    if (!currentProductId) return;
    
    const fd = new FormData();
    fd.append('action', 'remove_item');
    fd.append('product_id', currentProductId);
    
    fetch('', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Remove from DOM and optionally reload to refresh totals
                if (currentRow) currentRow.remove();
                location.reload();
            } else {
                alert(data.message || 'Failed to remove item');
            }
        })
        .catch(() => alert('Failed to remove item'))
        .finally(() => {
            hideRemoveConfirm();
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const removeButtons = document.querySelectorAll('.remove-item-btn');
    removeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const row = this.closest('.cart-item');
            showRemoveConfirm(productId, row);
        });
    });
    
    // Close modal when clicking outside
    document.getElementById('removeConfirmModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideRemoveConfirm();
        }
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const useBtn = document.getElementById('useProfileBtn');
    if (!useBtn) return;
    useBtn.addEventListener('click', function() {
        const profile = <?php echo json_encode($userProfile ?: []); ?>;
        if (!profile) return;
        const fullName = [profile.first_name || '', profile.last_name || ''].join(' ').trim();
        const f = {
            name: document.getElementById('customer_name'),
            email: document.getElementById('customer_email'),
            phone: document.getElementById('customer_phone'),
            addr: document.getElementById('shipping_address')
        };
        if (f.name && fullName) f.name.value = fullName;
        if (f.email && profile.email) f.email.value = profile.email;
        if (f.phone && profile.phone) f.phone.value = profile.phone;
        if (f.addr && profile.address) f.addr.value = profile.address;
    });
});
</script>
