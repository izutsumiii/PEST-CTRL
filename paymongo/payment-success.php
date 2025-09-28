<?php
require_once '../includes/header.php';
?>

<main style="background: #130325; min-height: 100vh; padding: 20px; margin: 0;">
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="success-title">Payment Successful!</h1>
            
            <p class="success-message">
                Thank you for your purchase! Your payment has been processed successfully 
                and you will receive a confirmation email shortly.
            </p>
            
            <div class="payment-details" id="paymentDetails">
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value" id="transactionId">Loading...</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value" id="amount">Loading...</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value" id="paymentMethod">Loading...</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value" id="paymentDate">Loading...</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i>
                    Continue Shopping
                </a>
                <a href="../user-dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-user"></i>
                    View Orders
                </a>
            </div>
        </div>
    </div>
</main>

<style>
/* Payment Success Page Specific Styles - Scoped to avoid header conflicts */
.payment-success-page .success-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    background: #130325;
    margin: 0;
    padding: 40px 20px;
}

.payment-success-page .success-card {
    background: white;
    border: 2px solid var(--accent-yellow);
    border-radius: 20px;
    padding: 50px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 500px;
    width: 90%;
}

.payment-success-page .success-icon {
    font-size: 4rem;
    color: #2ed573;
    margin-bottom: 20px;
}

.payment-success-page .success-title {
    font-size: 1.8rem;
    font-weight: 300;
    color: var(--primary-dark);
    margin-bottom: 15px;
}

.payment-success-page .success-message {
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
    font-weight: 300;
    font-size: 0.95rem;
}

.payment-success-page .payment-details {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: left;
}

.payment-success-page .detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.payment-success-page .detail-row:last-child {
    margin-bottom: 0;
}

.payment-success-page .detail-label {
    font-weight: 500;
    color: #333;
    font-size: 0.9rem;
}

.payment-success-page .detail-value {
    color: var(--accent-yellow);
    font-weight: 600;
    font-size: 0.9rem;
}

.payment-success-page .action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.payment-success-page .btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    font-size: 1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.payment-success-page .btn-primary {
    background: #e6c230 !important;
    color: var(--primary-dark);
    font-weight: 500;
    font-size: 0.9rem;
}

.payment-success-page .btn-primary:hover {
    background: #e6c230;
    transform: translateY(-2px);
}

.payment-success-page .btn.btn-secondary {
    background:  #1a0220 !important;
    color: white !important;
    border: 1px solid var(--primary-dark) !important;
    font-weight: 500;
    font-size: 0.9rem;
}

.payment-success-page .btn.btn-secondary:hover {
    background: #1a0220 !important;
    color: white !important;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .payment-success-page .action-buttons {
        flex-direction: column;
    }
    
    .payment-success-page .success-card {
        padding: 30px 20px;
    }
}
</style>

<script>
// Add body class for specific styling
document.body.classList.add('payment-success-page');

// Get payment details from URL parameters
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

// Get payment data from session storage (fallback)
function getPaymentDataFromStorage() {
    try {
        const paymentData = sessionStorage.getItem('paymentData');
        return paymentData ? JSON.parse(paymentData) : null;
    } catch (e) {
        console.error('Error parsing payment data from storage:', e);
        return null;
    }
}

// Format amount with currency
function formatAmount(amount) {
    if (!amount || amount === 'N/A') return 'N/A';
    const numAmount = parseFloat(amount);
    if (isNaN(numAmount)) return 'N/A';
    return '₱' + numAmount.toFixed(2);
}

// Format payment method name
function formatPaymentMethod(method) {
    if (!method || method === 'N/A') return 'N/A';
    const methodMap = {
        'card': 'Credit/Debit Card',
        'gcash': 'GCash',
        'grab_pay': 'GrabPay',
        'paypal': 'PayPal'
    };
    return methodMap[method] || method;
}

// Fetch payment details from PayMongo API
async function fetchPaymentDetails(checkoutSessionId) {
    try {
        const response = await fetch('get-payment-details.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                checkout_session_id: checkoutSessionId
            })
        });
        
        if (response.ok) {
            const data = await response.json();
            return data;
        } else {
            console.error('Failed to fetch payment details:', response.statusText);
            return null;
        }
    } catch (error) {
        console.error('Error fetching payment details:', error);
        return null;
    }
}

// Display payment details
document.addEventListener('DOMContentLoaded', async function() {
    // Try to get data from URL parameters first
    let transactionId = getUrlParameter('payment_intent_id') || 
                       getUrlParameter('checkout_session_id') || 
                       getUrlParameter('session_id') ||
                       getUrlParameter('id');
    
    let amount = getUrlParameter('amount') || getUrlParameter('total');
    let paymentMethod = getUrlParameter('payment_method') || getUrlParameter('method');
    
    // If no data from URL, try to get from session storage
    if (!transactionId || transactionId === 'N/A') {
        const paymentData = getPaymentDataFromStorage();
        if (paymentData) {
            transactionId = paymentData.checkout_session_id || paymentData.payment_intent_id || 'N/A';
            amount = paymentData.amount || 'N/A';
            paymentMethod = paymentData.payment_method || 'N/A';
        }
    }
    
    // If we have a checkout session ID, try to fetch real payment details from PayMongo
    if (transactionId && transactionId !== 'N/A' && !transactionId.startsWith('TXN-')) {
        console.log('Fetching payment details for session:', transactionId);
        const paymentDetails = await fetchPaymentDetails(transactionId);
        
        if (paymentDetails && paymentDetails.success) {
            amount = paymentDetails.amount || amount;
            paymentMethod = paymentDetails.payment_method || paymentMethod;
            console.log('Retrieved payment details:', paymentDetails);
        }
    }
    
    // If still no transaction ID, generate a temporary one
    if (!transactionId || transactionId === 'N/A') {
        transactionId = 'TXN-' + Date.now();
    }
    
    // Format the date
    const date = new Date().toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    // Update the display
    document.getElementById('transactionId').textContent = transactionId;
    document.getElementById('amount').textContent = formatAmount(amount);
    document.getElementById('paymentMethod').textContent = formatPaymentMethod(paymentMethod);
    document.getElementById('paymentDate').textContent = date;

    // Clear any cart data from localStorage if exists
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem('paymentTestCart');
        localStorage.removeItem('cart');
    }
    
    // Clear payment data from session storage
    if (typeof sessionStorage !== 'undefined') {
        sessionStorage.removeItem('paymentData');
    }
    
    // Log the data for debugging
    console.log('Payment Success Data:', {
        transactionId: transactionId,
        amount: amount,
        paymentMethod: paymentMethod,
        urlParams: window.location.search
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
