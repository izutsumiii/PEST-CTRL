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
        <h1><i class="fas fa-shield-alt"></i> Privacy Policy</h1>
        <div class="legal-subtitle">PEST-CTRL Professional Pest Control Solutions</div>
        <div class="last-updated">Last Updated: <?php echo date('F j, Y'); ?></div>
    </div>

    <div class="legal-content">
        <div class="important-notice">
            <h3><i class="fas fa-user-shield"></i> Your Privacy Matters</h3>
            <p>At PEST-CTRL, we are committed to protecting your privacy and personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services. Due to the regulated nature of pest control products, some information collection is required by law.</p>
        </div>

        <section class="privacy-section">
            <h2>1. Information We Collect</h2>
            
            <h3>1.1 Personal Information</h3>
            <p>We collect personal information that you voluntarily provide when:</p>
            <ul>
                <li><strong>Creating an Account:</strong> Name, email, username, password, address, phone number</li>
                <li><strong>Making Purchases:</strong> Billing information, shipping address, payment details</li>
                <li><strong>Professional Verification:</strong> License numbers, certification details, business information</li>
                <li><strong>Customer Support:</strong> Communication records, support tickets, feedback</li>
                <li><strong>Marketing:</strong> Subscription preferences, interests, product preferences</li>
            </ul>

            <h3>1.2 Automatically Collected Information</h3>
            <ul>
                <li><strong>Usage Data:</strong> IP address, browser type, device information, operating system</li>
                <li><strong>Website Activity:</strong> Pages visited, time spent, clicks, search queries</li>
                <li><strong>Cookies & Tracking:</strong> Session data, preferences, shopping cart contents</li>
                <li><strong>Location Data:</strong> General geographic location for shipping and regulatory compliance</li>
            </ul>

            <h3>1.3 Third-Party Information</h3>
            <ul>
                <li><strong>Payment Processors:</strong> Transaction verification and fraud prevention data</li>
                <li><strong>Shipping Partners:</strong> Delivery confirmation and tracking information</li>
                <li><strong>Regulatory Databases:</strong> License verification and compliance checks</li>
                <li><strong>Social Media:</strong> Information from connected social accounts (if applicable)</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>2. How We Use Your Information</h2>
            
            <h3>2.1 Primary Uses</h3>
            <ul>
                <li><strong>Account Management:</strong> Creating and maintaining your account</li>
                <li><strong>Order Processing:</strong> Processing payments, fulfilling orders, shipping products</li>
                <li><strong>Customer Service:</strong> Responding to inquiries, resolving issues, providing support</li>
                <li><strong>Product Delivery:</strong> Coordinating shipping and delivery of purchased products</li>
            </ul>

            <h3>2.2 Legal & Regulatory Compliance</h3>
            <ul>
                <li><strong>License Verification:</strong> Confirming authorization to purchase restricted products</li>
                <li><strong>Age Verification:</strong> Ensuring compliance with age requirements</li>
                <li><strong>Regulatory Reporting:</strong> Reporting to EPA, state agencies as required by law</li>
                <li><strong>Record Keeping:</strong> Maintaining purchase records for regulatory audits</li>
                <li><strong>Safety Monitoring:</strong> Tracking product usage for safety compliance</li>
            </ul>

            <h3>2.3 Business Operations</h3>
            <ul>
                <li><strong>Website Improvement:</strong> Analyzing usage to enhance user experience</li>
                <li><strong>Product Development:</strong> Understanding customer needs and preferences</li>
                <li><strong>Marketing:</strong> Sending promotional emails, product recommendations</li>
                <li><strong>Security:</strong> Protecting against fraud, unauthorized access, and abuse</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>3. Information Sharing & Disclosure</h2>
            
            <h3>3.1 We Share Information With:</h3>
            <ul>
                <li><strong>Service Providers:</strong> Payment processors, shipping companies, IT services</li>
                <li><strong>Business Partners:</strong> Manufacturers, suppliers, authorized distributors</li>
                <li><strong>Regulatory Agencies:</strong> EPA, state pesticide agencies, law enforcement (when required)</li>
                <li><strong>Legal Compliance:</strong> Courts, attorneys, regulatory bodies (as legally required)</li>
            </ul>

            <h3>3.2 We Do NOT Share Information For:</h3>
            <ul>
                <li>Sale to marketing companies or data brokers</li>
                <li>Unsolicited commercial purposes</li>
                <li>Sharing with competitors without consent</li>
                <li>Personal use by employees or contractors</li>
            </ul>

            <h3>3.3 Business Transfers</h3>
            <p>In the event of a merger, acquisition, or sale of assets, your information may be transferred to the acquiring entity, subject to the same privacy protections.</p>
        </section>

        <section class="privacy-section">
            <h2>4. Data Security & Protection</h2>
            
            <h3>4.1 Security Measures</h3>
            <ul>
                <li><strong>Encryption:</strong> SSL/TLS encryption for data transmission</li>
                <li><strong>Secure Storage:</strong> Encrypted databases with restricted access</li>
                <li><strong>Access Controls:</strong> Role-based permissions and multi-factor authentication</li>
                <li><strong>Regular Audits:</strong> Security assessments and vulnerability testing</li>
                <li><strong>Employee Training:</strong> Privacy and security awareness programs</li>
            </ul>

            <h3>4.2 Payment Security</h3>
            <ul>
                <li>PCI DSS compliant payment processing</li>
                <li>No storage of full credit card numbers</li>
                <li>Tokenization for recurring payments</li>
                <li>Fraud detection and prevention systems</li>
            </ul>

            <h3>4.3 Data Breach Response</h3>
            <ul>
                <li>Immediate containment and assessment procedures</li>
                <li>Notification to affected users within 72 hours</li>
                <li>Cooperation with law enforcement and regulatory agencies</li>
                <li>Remediation and prevention measures</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>5. Your Privacy Rights</h2>
            
            <h3>5.1 Access & Control</h3>
            <ul>
                <li><strong>Access:</strong> Request copies of your personal information</li>
                <li><strong>Correction:</strong> Update or correct inaccurate information</li>
                <li><strong>Deletion:</strong> Request deletion of your data (subject to legal requirements)</li>
                <li><strong>Portability:</strong> Receive your data in a structured format</li>
                <li><strong>Restriction:</strong> Limit processing of your information</li>
            </ul>

            <h3>5.2 Marketing Preferences</h3>
            <ul>
                <li>Opt-out of marketing emails at any time</li>
                <li>Customize communication preferences</li>
                <li>Unsubscribe from promotional materials</li>
                <li>Control cookie and tracking preferences</li>
            </ul>

            <h3>5.3 Account Management</h3>
            <ul>
                <li>Update personal information in your account settings</li>
                <li>Change password and security settings</li>
                <li>View order history and purchase records</li>
                <li>Close your account (subject to regulatory retention requirements)</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>6. Cookies & Tracking Technologies</h2>
            
            <h3>6.1 Types of Cookies We Use</h3>
            <ul>
                <li><strong>Essential Cookies:</strong> Required for website functionality and security</li>
                <li><strong>Performance Cookies:</strong> Analytics to improve website performance</li>
                <li><strong>Functional Cookies:</strong> Remember your preferences and settings</li>
                <li><strong>Marketing Cookies:</strong> Deliver relevant advertisements and content</li>
            </ul>

            <h3>6.2 Cookie Management</h3>
            <ul>
                <li>Configure cookie preferences in your browser settings</li>
                <li>Use our cookie consent manager to customize preferences</li>
                <li>Note: Disabling essential cookies may affect website functionality</li>
                <li>Third-party cookies from service providers may be present</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>7. Data Retention</h2>
            
            <h3>7.1 Retention Periods</h3>
            <ul>
                <li><strong>Account Information:</strong> Retained while account is active plus 3 years</li>
                <li><strong>Purchase Records:</strong> 7 years (regulatory requirement for pesticide sales)</li>
                <li><strong>License Information:</strong> 5 years after license expiration</li>
                <li><strong>Marketing Data:</strong> Until you unsubscribe or request deletion</li>
                <li><strong>Website Analytics:</strong> 2 years for performance optimization</li>
            </ul>

            <h3>7.2 Legal Requirements</h3>
            <ul>
                <li>EPA requires pesticide sale records for 2 years minimum</li>
                <li>State regulations may require longer retention periods</li>
                <li>Tax and accounting records retained for 7 years</li>
                <li>Legal disputes may extend retention periods</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>8. International Data Transfers</h2>
            <ul>
                <li>Data may be processed in countries outside your residence</li>
                <li>We ensure adequate protection through appropriate safeguards</li>
                <li>Standard Contractual Clauses or adequacy decisions govern transfers</li>
                <li>You consent to international transfers by using our services</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>9. Children's Privacy</h2>
            <ul>
                <li>Our services are not intended for children under 18 years of age</li>
                <li>We do not knowingly collect information from children</li>
                <li>Parents should monitor their children's online activities</li>
                <li>If we discover we have collected information from a child, we will delete it immediately</li>
                <li>Contact us if you believe your child has provided information to us</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>10. State-Specific Privacy Rights</h2>
            
            <h3>10.1 California Residents (CCPA/CPRA)</h3>
            <ul>
                <li><strong>Right to Know:</strong> Categories and specific pieces of personal information collected</li>
                <li><strong>Right to Delete:</strong> Request deletion of personal information</li>
                <li><strong>Right to Correct:</strong> Correct inaccurate personal information</li>
                <li><strong>Right to Opt-Out:</strong> Sale or sharing of personal information</li>
                <li><strong>Right to Limit:</strong> Use of sensitive personal information</li>
                <li><strong>Non-Discrimination:</strong> Equal service regardless of privacy choices</li>
            </ul>

            <h3>10.2 European Union (GDPR)</h3>
            <ul>
                <li>Legal basis for processing: Consent, contract performance, legal compliance</li>
                <li>Right to withdraw consent at any time</li>
                <li>Right to object to processing for marketing purposes</li>
                <li>Right to lodge complaints with supervisory authorities</li>
                <li>Data Protection Officer contact: dpo@pest-ctrl.com</li>
            </ul>

            <h3>10.3 Other State Laws</h3>
            <ul>
                <li>Virginia Consumer Data Protection Act (VCDPA)</li>
                <li>Colorado Privacy Act (CPA)</li>
                <li>Connecticut Data Privacy Act (CTDPA)</li>
                <li>Additional state laws may apply based on your location</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>11. Third-Party Services & Links</h2>
            
            <h3>11.1 Integrated Services</h3>
            <ul>
                <li><strong>Payment Processors:</strong> PayPal, Stripe, credit card companies</li>
                <li><strong>Shipping Partners:</strong> FedEx, UPS, USPS tracking and delivery</li>
                <li><strong>Analytics:</strong> Google Analytics, website performance tools</li>
                <li><strong>Customer Support:</strong> Live chat, helpdesk platforms</li>
                <li><strong>Email Services:</strong> Marketing automation and transactional emails</li>
            </ul>

            <h3>11.2 External Links</h3>
            <ul>
                <li>Our website may contain links to third-party websites</li>
                <li>We are not responsible for the privacy practices of other sites</li>
                <li>Review privacy policies of linked websites before providing information</li>
                <li>Third-party services have their own terms and privacy policies</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>12. Marketing & Communications</h2>
            
            <h3>12.1 Email Marketing</h3>
            <ul>
                <li>Product announcements and new arrivals</li>
                <li>Special offers and promotional discounts</li>
                <li>Educational content about pest control</li>
                <li>Safety alerts and product recalls</li>
                <li>Industry news and regulatory updates</li>
            </ul>

            <h3>12.2 Communication Preferences</h3>
            <ul>
                <li>Choose frequency and types of communications</li>
                <li>Separate preferences for promotional vs. transactional emails</li>
                <li>SMS/text message opt-in for order updates</li>
                <li>Push notifications for mobile app users</li>
            </ul>

            <h3>12.3 Opt-Out Options</h3>
            <ul>
                <li>Unsubscribe links in all marketing emails</li>
                <li>Account settings to manage preferences</li>
                <li>Contact customer service for assistance</li>
                <li>Note: You cannot opt-out of transactional/service emails</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>13. Regulatory Compliance & Reporting</h2>
            
            <h3>13.1 Pesticide Regulations</h3>
            <ul>
                <li>Federal Insecticide, Fungicide, and Rodenticide Act (FIFRA) compliance</li>
                <li>EPA pesticide registration and usage reporting</li>
                <li>State pesticide licensing and certification verification</li>
                <li>Restricted Use Pesticide (RUP) purchase tracking</li>
                <li>Hazardous material shipping documentation</li>
            </ul>

            <h3>13.2 Required Disclosures</h3>
            <ul>
                <li>Suspicious purchase patterns to regulatory authorities</li>
                <li>Large quantity purchases requiring additional scrutiny</li>
                <li>License violations or expired certifications</li>
                <li>Safety incidents or product misuse reports</li>
                <li>Law enforcement requests with valid legal process</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>14. Data Subject Requests</h2>
            
            <h3>14.1 How to Submit Requests</h3>
            <ul>
                <li><strong>Online:</strong> Privacy request form on our website</li>
                <li><strong>Email:</strong> privacy@pest-ctrl.com</li>
                <li><strong>Phone:</strong> 1-800-PEST-CTRL (privacy department)</li>
                <li><strong>Mail:</strong> PEST-CTRL Privacy Team, [Business Address]</li>
            </ul>

            <h3>14.2 Request Processing</h3>
            <ul>
                <li>Identity verification required for all requests</li>
                <li>Response within 30 days (may extend to 60 days if complex)</li>
                <li>No charge for reasonable requests</li>
                <li>Excessive or repetitive requests may incur fees</li>
                <li>Some information may be retained for legal compliance</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>15. Privacy Policy Updates</h2>
            <ul>
                <li>We may update this policy to reflect changes in practices or regulations</li>
                <li>Material changes will be prominently posted on our website</li>
                <li>Email notification for significant changes affecting your rights</li>
                <li>Continued use of services constitutes acceptance of updates</li>
                <li>Previous versions available upon request</li>
            </ul>
        </section>

        <section class="privacy-section">
            <h2>16. Contact Information</h2>
            <p>For privacy-related questions, concerns, or requests:</p>
            <div class="contact-info">
                <p><i class="fas fa-envelope"></i> <strong>Privacy Team:</strong> privacy@pest-ctrl.com</p>
                <p><i class="fas fa-shield-alt"></i> <strong>Data Protection Officer:</strong> dpo@pest-ctrl.com</p>
                <p><i class="fas fa-phone"></i> <strong>Privacy Hotline:</strong> 1-800-PEST-CTRL (ext. 777)</p>
                <p><i class="fas fa-map-marker-alt"></i> <strong>Mailing Address:</strong></p>
                <div class="address-block">
                    PEST-CTRL Privacy Team<br>
                    [Your Business Address]<br>
                    [City, State ZIP Code]<br>
                    [Country]
                </div>
            </div>
        </section>

        <div class="acknowledgment-box">
            <h3><i class="fas fa-user-check"></i> Your Consent</h3>
            <p>By creating an account and using PEST-CTRL services, you acknowledge that you have read, understood, and consent to the collection, use, and disclosure of your personal information as described in this Privacy Policy. You understand that some information collection and sharing is required by law due to the regulated nature of pest control products.</p>
            <p><strong>Special Note:</strong> Due to the hazardous nature of pesticide products, certain information must be retained for regulatory compliance even if you request deletion. We will clearly explain any limitations when processing your privacy requests.</p>
        </div>
    </div>

    <div class="legal-footer">
        <p><a href="register.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Registration</a></p>
        <p><a href="terms-conditions.php" class="related-link"><i class="fas fa-file-contract"></i> View Terms & Conditions</a></p>
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
    --accent-blue: #007bff;
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
    border-bottom: 2px solid var(--accent-blue);
    padding-bottom: 20px;
}

.legal-header h1 {
    color: var(--accent-blue);
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
    background: rgba(0, 123, 255, 0.1);
    border: 2px solid rgba(0, 123, 255, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
}

.important-notice h3 {
    color: var(--accent-blue);
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.legal-content {
    line-height: 1.6;
}

.privacy-section {
    margin-bottom: 35px;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(249, 249, 249, 0.1);
}

.privacy-section:last-child {
    border-bottom: none;
}

.privacy-section h2 {
    color: var(--accent-blue);
    font-size: 22px;
    margin: 0 0 15px 0;
}

.privacy-section h3 {
    color: rgba(0, 123, 255, 0.9);
    font-size: 18px;
    margin: 20px 0 10px 0;
}

.privacy-section p {
    margin-bottom: 15px;
    color: rgba(249, 249, 249, 0.9);
}

.privacy-section ul {
    padding-left: 20px;
    margin-bottom: 15px;
}

.privacy-section li {
    margin-bottom: 8px;
    color: rgba(249, 249, 249, 0.8);
}

.privacy-section li strong {
    color: var(--accent-yellow);
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
    color: var(--accent-blue);
    width: 20px;
}

.address-block {
    margin-left: 30px;
    line-height: 1.4;
    color: rgba(249, 249, 249, 0.8);
}

.acknowledgment-box {
    background: rgba(0, 123, 255, 0.1);
    border: 2px solid rgba(0, 123, 255, 0.3);
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
}

.acknowledgment-box h3 {
    color: var(--accent-blue);
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

.back-link, .related-link {
    color: var(--accent-blue);
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0 15px 10px 15px;
}

.back-link:hover, .related-link:hover {
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
    
    .privacy-section h2 {
        font-size: 20px;
    }
    
    .privacy-section h3 {
        font-size: 16px;
    }
    
    .legal-footer .back-link,
    .legal-footer .related-link {
        display: block;
        margin: 10px 0;
    }
}

@media (max-width: 480px) {
    .legal-header h1 {
        font-size: 20px;
    }
    
    .legal-subtitle {
        font-size: 14px;
    }
    
    .privacy-section ul {
        padding-left: 15px;
    }
    
    .contact-info p {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .address-block {
        margin-left: 0;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
