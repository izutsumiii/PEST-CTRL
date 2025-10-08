<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'paymongo/config.php';

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

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_transaction_id = ?");
$stmt->execute([$transactionId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT oi.*, p.name AS product_name, p.image_url
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

// Prepare items for PayMongo
$paymongoItems = [];
foreach ($orderItems as $item) {
    $paymongoItems[] = [
        'name' => $item['product_name'],
        'quantity' => (int)$item['quantity'],
        'price' => (float)$item['price'],
        'description' => $item['product_name'],
        'seller_name' => 'Seller ' . (string)($item['seller_id'] ?? '')
    ];
}

// Determine payment method types based on selection
$paymentMethod = $paymentTransaction['payment_method'];
$paymentMethodTypes = [];
if ($paymentMethod === 'paymongo') {
    $paymentMethodTypes = ['card'];
} else if ($paymentMethod === 'gcash') {
    $paymentMethodTypes = ['gcash'];
} else if ($paymentMethod === 'paymaya') {
    $paymentMethodTypes = ['paymaya'];
} else {
    $paymentMethodTypes = ['card'];
}

// Prepare checkout data
$checkoutData = [
    'amount' => $grandTotal,
    'currency' => 'PHP',
    'payment_method_types' => $paymentMethodTypes,
    'send_email_receipt' => true,
    'show_description' => false,
    'show_line_items' => true,
    'receipt_email' => $paymentTransaction['customer_email'],
    'success_url' => PAYMENT_SUCCESS_URL . '?transaction_id=' . $transactionId,
    'cancel_url' => PAYMENT_CANCEL_URL,
    'order_id' => 'multi_seller_order_' . $transactionId,
    'customer_email' => $paymentTransaction['customer_email'],
    'customer_name' => $paymentTransaction['customer_name'],
    'customer_phone' => $paymentTransaction['customer_phone'],
    'items' => $paymongoItems,
    'transaction_id' => $transactionId
];

?>

<style>
body { background: #130325 !important; min-height: 100vh; }
.paymongo-container { max-width: 800px; margin: 0 auto; padding: 20px; }
h1 { color: var(--primary-light); text-align: center; margin: 20px 0; font-size: 2rem; }
.loading-section { background: var(--primary-dark); border: 1px solid var(--accent-yellow); padding: 40px; border-radius: 8px; text-align: center; }
.loading-spinner { border: 4px solid rgba(255, 215, 54, 0.3); border-top: 4px solid #FFD736; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.loading-text { color: var(--primary-light); font-size: 1.2rem; margin-bottom: 10px; }
.loading-subtext { color: var(--primary-light); opacity: 0.8; }
.error-section { background: var(--primary-dark); border: 1px solid #dc3545; padding: 40px; border-radius: 8px; text-align: center; }
.error-text { color: #dc3545; font-size: 1.2rem; margin-bottom: 20px; }
.btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s; }
.btn-primary { background: #FFD736; color: #130325; }
.btn-primary:hover { background: #e6c230; transform: translateY(-2px); }
.btn-secondary { background: #6c757d; color: #ffffff; }
.btn-secondary:hover { background: #5a6268; }
</style>

<main>
<div class="paymongo-container">
    <h1>Processing Payment</h1>
    
    <div id="loadingSection" class="loading-section">
        <div class="loading-spinner"></div>
        <div class="loading-text">Redirecting to PayMongo...</div>
        <div class="loading-subtext">Please wait while we prepare your payment</div>
    </div>
    
    <div id="errorSection" class="error-section" style="display: none;">
        <div class="error-text">Payment Error</div>
        <p id="errorMessage"></p>
        <a href="multi-seller-checkout.php" class="btn btn-secondary">Back to Checkout</a>
    </div>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkoutData = <?php echo json_encode($checkoutData); ?>;
    
    // Create PayMongo checkout session
    createPayMongoCheckout(checkoutData);
});

async function createPayMongoCheckout(checkoutData) {
    try {
        console.log('Creating PayMongo checkout session:', checkoutData);
        
        const response = await fetch('paymongo/multi-seller-checkout-session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(checkoutData)
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const result = await response.json();
        console.log('PayMongo API response:', result);
        
        if (result.success) {
            // Store payment data in session storage
            const paymentData = {
                checkout_session_id: result.checkout_session_id,
                amount: checkoutData.amount,
                payment_method: checkoutData.payment_method_types[0],
                customer_email: checkoutData.customer_email,
                customer_name: checkoutData.customer_name,
                order_id: checkoutData.order_id,
                transaction_id: checkoutData.transaction_id,
                items: checkoutData.items
            };
            sessionStorage.setItem('paymentData', JSON.stringify(paymentData));
            
            // Redirect to PayMongo checkout page
            window.location.href = result.checkout_url;
        } else {
            showError(result.error || 'Failed to create checkout session. Please try again.');
        }
        
    } catch (error) {
        console.error('Payment error:', error);
        showError('Payment error: ' + error.message);
    }
}

function showError(message) {
    document.getElementById('loadingSection').style.display = 'none';
    document.getElementById('errorSection').style.display = 'block';
    document.getElementById('errorMessage').textContent = message;
}
</script>

<?php require_once 'includes/footer.php'; ?>
