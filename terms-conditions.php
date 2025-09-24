<?php
// Include functions file
require_once 'includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include header
require_once 'includes/header.php';
?>

<div class="legal-page-container">
    <div class="legal-header">
        <h1><i class="fas fa-file-contract"></i> Terms & Conditions</h1>
        <div class="legal-subtitle">PEST-CTRL Professional Pest Control Solutions</div>
        <div class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></div>
    </div>

    <div class="legal-content">
        <div class="important-notice">
            <h3><i class="fas fa-exclamation-triangle"></i> Important Notice</h3>
            <p>These Terms & Conditions govern your use of PEST-CTRL and the purchase of pest control products. By accessing our website and making purchases, you agree to comply with these terms and all applicable laws and regulations regarding pesticide use.</p>
        </div>

        <section class="terms-section">
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing and using the PEST-CTRL website ("Service"), you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
            
            <h3>1.1 Age Requirement</h3>
            <ul>
                <li>You must be at least 18 years old to create an account and purchase products</li>
                <li>If you are under 18, you must have explicit parental or guardian consent</li>
                <li>Some products may have additional age restrictions as required by law</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>2. Product Information & Restrictions</h2>
            
            <h3>2.1 Pesticide Products</h3>
            <ul>
                <li>All pesticide products sold are for professional or licensed applicator use unless otherwise specified</li>
                <li>Customers must verify local licensing requirements before purchasing restricted-use pesticides</li>
                <li>Product availability may be restricted based on your geographic location and local regulations</li>
                <li>We reserve the right to request proof of licensing or certification before processing orders</li>
            </ul>

            <h3>2.2 Product Safety & Liability</h3>
            <ul>
                <li>All products must be used strictly according to manufacturer label instructions</li>
                <li>PEST-CTRL is not responsible for improper use of products purchased through our platform</li>
                <li>Customers assume full responsibility for safe storage, handling, and application of all products</li>
                <li>Environmental and health impacts from product misuse are the sole responsibility of the purchaser</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>3. Account Registration & Responsibilities</h2>
            
            <h3>3.1 Account Creation</h3>
            <ul>
                <li>You must provide accurate and complete information during registration</li>
                <li>You are responsible for maintaining the confidentiality of your account credentials</li>
                <li>You must notify us immediately of any unauthorized use of your account</li>
                <li>One person may not maintain multiple accounts</li>
            </ul>

            <h3>3.2 Account Types</h3>
            <ul>
                <li><strong>Customer Accounts:</strong> For end-users purchasing pest control products</li>
                <li><strong>Seller/Supplier Accounts:</strong> For businesses selling products through our platform</li>
                <li>Different account types have different privileges and responsibilities</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>4. Orders & Payments</h2>
            
            <h3>4.1 Order Processing</h3>
            <ul>
                <li>All orders are subject to availability and acceptance</li>
                <li>We reserve the right to refuse or cancel any order at our discretion</li>
                <li>Orders for restricted products may require additional verification</li>
                <li>Pricing is subject to change without notice</li>
            </ul>

            <h3>4.2 Payment Terms</h3>
            <ul>
                <li>Payment is due at the time of order placement</li>
                <li>We accept major credit cards, PayPal, and other approved payment methods</li>
                <li>All transactions are processed in USD unless otherwise specified</li>
                <li>Sales tax will be applied where required by law</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>5. Shipping & Delivery</h2>
            
            <h3>5.1 Shipping Restrictions</h3>
            <ul>
                <li>Certain products may have shipping restrictions due to hazardous material classifications</li>
                <li>Some products can only be shipped to licensed facilities</li>
                <li>International shipping may be restricted for certain pesticide products</li>
                <li>Additional fees may apply for hazardous material shipping</li>
            </ul>

            <h3>5.2 Delivery & Risk of Loss</h3>
            <ul>
                <li>Risk of loss passes to the buyer upon delivery to the shipping carrier</li>
                <li>Delivery times are estimates and not guarantees</li>
                <li>Signature confirmation may be required for certain products</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>6. Returns & Refunds</h2>
            
            <h3>6.1 Return Policy</h3>
            <ul>
                <li>Pesticide products cannot be returned once opened due to safety and regulatory concerns</li>
                <li>Unopened products may be returned within 30 days in original packaging</li>
                <li>Custom or special-order items are non-returnable</li>
                <li>Return shipping costs are the responsibility of the customer unless the return is due to our error</li>
            </ul>

            <h3>6.2 Damaged or Defective Products</h3>
            <ul>
                <li>Report damaged or defective products within 48 hours of delivery</li>
                <li>Photo documentation may be required for damage claims</li>
                <li>We will replace or refund defective products at our discretion</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>7. Intellectual Property</h2>
            <ul>
                <li>All content on PEST-CTRL is protected by copyright and other intellectual property laws</li>
                <li>You may not reproduce, distribute, or create derivative works without written permission</li>
                <li>Product names and trademarks belong to their respective owners</li>
                <li>User-generated content may be used by PEST-CTRL for promotional purposes</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>8. Prohibited Uses</h2>
            <p>You agree not to use our service:</p>
            <ul>
                <li>For any unlawful purpose or to solicit others to perform unlawful acts</li>
                <li>To violate any international, federal, provincial, or state regulations or laws</li>
                <li>To transmit or procure harmful computer code, viruses, or other malicious software</li>
                <li>To purchase products for illegal pest control activities</li>
                <li>To resell restricted-use pesticides without proper licensing</li>
                <li>To interfere with the security or proper functioning of the website</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>9. Disclaimers & Limitation of Liability</h2>
            
            <h3>9.1 Service Disclaimer</h3>
            <ul>
                <li>Our service is provided "as is" without warranties of any kind</li>
                <li>We do not warrant that the service will be uninterrupted or error-free</li>
                <li>Product information is provided by manufacturers and suppliers</li>
                <li>We make no guarantees about the effectiveness of pest control products</li>
            </ul>

            <h3>9.2 Limitation of Liability</h3>
            <ul>
                <li>PEST-CTRL shall not be liable for any indirect, incidental, or consequential damages</li>
                <li>Our total liability shall not exceed the amount paid for the product or service</li>
                <li>We are not responsible for crop damage, environmental impact, or health issues resulting from product use</li>
                <li>Users assume all risks associated with pesticide use</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>10. Regulatory Compliance</h2>
            <ul>
                <li>Customers must comply with all federal, state, and local pesticide regulations</li>
                <li>EPA registration numbers must be verified before use</li>
                <li>Proper disposal of containers and unused products is the customer's responsibility</li>
                <li>Record-keeping requirements for pesticide use must be maintained by the customer</li>
                <li>We may report suspicious purchases to appropriate regulatory authorities</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>11. Privacy & Data Protection</h2>
            <ul>
                <li>Our Privacy Policy governs the collection and use of your personal information</li>
                <li>We may share information with regulatory authorities as required by law</li>
                <li>Purchase records may be maintained for regulatory compliance</li>
                <li>Marketing communications can be opted out of at any time</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>12. Termination</h2>
            <ul>
                <li>We may terminate or suspend accounts that violate these terms</li>
                <li>You may close your account at any time</li>
                <li>Termination does not affect pending orders or obligations</li>
                <li>Certain provisions of these terms survive termination</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>13. Modifications to Terms</h2>
            <ul>
                <li>We reserve the right to modify these terms at any time</li>
                <li>Changes will be posted on this page with an updated date</li>
                <li>Continued use of the service constitutes acceptance of modified terms</li>
                <li>Major changes may be communicated via email</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>14. Governing Law & Dispute Resolution</h2>
            <ul>
                <li>These terms are governed by the laws of [Your State/Country]</li>
                <li>Disputes will be resolved through binding arbitration</li>
                <li>Legal actions must be brought within one year of the cause of action</li>
                <li>If any provision is found unenforceable, the remainder shall remain in effect</li>
            </ul>
        </section>

        <section class="terms-section">
            <h2>15. Contact Information</h2>
            <p>For questions about these Terms & Conditions, contact us:</p>
            <div class="contact-info">
                <p><i class="fas fa-envelope"></i> <strong>Email:</strong> legal@pest-ctrl.com</p>
                <p><i class="fas fa-phone"></i> <strong>Phone:</strong> 1-800-PEST-CTRL</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong> [Your Business Address]</p>
            </div>
        </section>

        <div class="acknowledgment-box">
            <h3><i class="fas fa-handshake"></i> Acknowledgment</h3>
            <p>By creating an account and using our services, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions. You also acknowledge the serious nature of pest control products and your responsibility to use them safely and legally.</p>
        </div>
    </div>

    <div class="legal-footer">
        <p><a href="register.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Registration</a></p>
        <p class="copyright">© <?php echo date('Y'); ?> PEST-CTRL. All rights reserved.</p>
    </div>
</div>

<style>
/* Legal Page Styles */
:root {
    --primary-dark: #130325;
    --primary-light: #F9F9F9;
    --accent-yellow: #FFD736;
    --accent-green: #28a745;
    --accent-red: #dc3545;
    --border-secondary: rgba(249, 249, 249, 0.3);
    --shadow-dark: rgba(0, 0, 0, 0.3);
    --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

body {
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.9) 100%);
    min-height: 100vh;
    margin: 0;
    font-family: var(--font-primary);
    color: var(--primary-light);
}

.site-header {
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.9) 100%) !important;
}

.legal-page-container {
    max-width: 900px;
    margin: 20px auto;
    padding: 30px;
    background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
    border-radius: 15px;
    border: 1px solid var(--border-secondary);
    box-shadow: 0 10px 40px var(--shadow-dark);
}

.legal-header {
    text-align: center;
    margin-bottom: 40px;
    border-bottom: 2px solid var(--accent-yellow);
    padding-bottom: 20px;
}

.legal-header h1 {
    color: var(--accent-yellow);
    font-size: 32px;
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.legal-subtitle {
    font-size: 18px;
    color: rgba(249, 249, 249, 0.8);
    margin-bottom: 5px;
}

.last-updated {
    font-size: 14px;
    color: rgba(249, 249, 249, 0.6);
    font-style: italic;
}

.important-notice {
    background: rgba(255, 215, 54, 0.1);
    border: 2px solid rgba(255, 215, 54, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
}

.important-notice h3 {
    color: var(--accent-yellow);
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.legal-content {
    line-height: 1.6;
}

.terms-section {
    margin-bottom: 35px;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(249, 249, 249, 0.1);
}

.terms-section:last-child {
    border-bottom: none;
}

.terms-section h2 {
    color: var(--accent-yellow);
    font-size: 22px;
    margin: 0 0 15px 0;
}

.terms-section h3 {
    color: rgba(255, 215, 54, 0.9);
    font-size: 18px;
    margin: 20px 0 10px 0;
}

.terms-section p {
    margin-bottom: 15px;
    color: rgba(249, 249, 249, 0.9);
}

.terms-section ul {
    padding-left: 20px;
    margin-bottom: 15px;
}

.terms-section li {
    margin-bottom: 8px;
    color: rgba(249, 249, 249, 0.8);
}

.contact-info {
    background: rgba(249, 249, 249, 0.05);
    border-radius: 8px;
    padding: 15px;
    margin-top: 15px;
}

.contact-info p {
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-info i {
    color: var(--accent-yellow);
    width: 20px;
}

.acknowledgment-box {
    background: rgba(40, 167, 69, 0.1);
    border: 2px solid rgba(40, 167, 69, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
}

.acknowledgment-box h3 {
    color: var(--accent-green);
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.legal-footer {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid rgba(249, 249, 249, 0.2);
}

.back-link {
    color: var(--accent-yellow);
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.back-link:hover {
    color: var(--primary-light);
    text-decoration: underline;
}

.copyright {
    color: rgba(249, 249, 249, 0.6);
    font-size: 14px;
    margin: 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .legal-page-container {
        margin: 10px;
        padding: 20px;
    }
    
    .legal-header h1 {
        font-size: 24px;
        flex-direction: column;
        gap: 8px;
    }
    
    .legal-subtitle {
        font-size: 16px;
    }
    
    .terms-section h2 {
        font-size: 20px;
    }
    
    .terms-section h3 {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .legal-header h1 {
        font-size: 20px;
    }
    
    .legal-subtitle {
        font-size: 14px;
    }
    
    .terms-section ul {
        padding-left: 15px;
    }
    
    .contact-info p {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
