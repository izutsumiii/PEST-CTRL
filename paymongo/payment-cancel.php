<?php
require_once '../includes/header.php';
?>

<main style="background: #130325; min-height: 100vh; padding: 20px; margin: 0;">
    <div class="cancel-container">
        <div class="cancel-card">
            <div class="cancel-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            
            <h1 class="cancel-title">Payment Cancelled</h1>
            
            <p class="cancel-message">
                Your payment was cancelled. No charges have been made to your account. 
                You can try again or choose a different payment method.
            </p>
            
            <div class="action-buttons">
                <a href="../checkout.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Checkout
                </a>
                <a href="../cart.php" class="btn btn-secondary">
                    <i class="fas fa-shopping-cart"></i>
                    View Cart
                </a>
            </div>
        </div>
    </div>
</main>

<style>
/* Payment Cancel Page Specific Styles - Scoped to avoid header conflicts */
.payment-cancel-page .cancel-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    background: #130325;
    margin: 0;
    padding: 40px 20px;
}

.payment-cancel-page .cancel-card {
    background: white;
    border: 2px solid #dc3545;
    border-radius: 20px;
    padding: 50px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 500px;
    width: 90%;
}

.payment-cancel-page .cancel-icon {
    font-size: 4rem;
    color: #ff6b6b;
    margin-bottom: 20px;
}

.payment-cancel-page .cancel-title {
    font-size: 1.8rem;
    font-weight: 300;
    color: #333;
    margin-bottom: 15px;
}

.payment-cancel-page .cancel-message {
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
    font-weight: 300;
    font-size: 0.95rem;
}

.payment-cancel-page .action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.payment-cancel-page .btn {
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

.payment-cancel-page .btn-primary {
    background: #dc3545;
    color: white;
    font-weight: 500;
    font-size: 0.9rem;
}

.payment-cancel-page .btn-primary:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.payment-cancel-page .btn-secondary {
    background: var(--primary-dark);
    color: white;
    border: 1px solid #dc3545;
    font-weight: 500;
    font-size: 0.9rem;
}

.payment-cancel-page .btn-secondary:hover {
    background: #1a0220;
    color: white;
}

@media (max-width: 768px) {
    .payment-cancel-page .action-buttons {
        flex-direction: column;
    }
    
    .payment-cancel-page .cancel-card {
        padding: 30px 20px;
    }
}
</style>

<script>
// Add body class for specific styling
document.body.classList.add('payment-cancel-page');
</script>

<?php require_once '../includes/footer.php'; ?>
