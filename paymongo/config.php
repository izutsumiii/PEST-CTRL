<?php
/**
 * PayMongo Configuration File
 * Update these settings with your actual PayMongo credentials and ngrok URL
 */

// PayMongo API Configuration
define('PAYMONGO_SECRET_KEY', 'your_paymongo_secret_key_here'); // Replace with your actual secret key
define('PAYMONGO_PUBLIC_KEY', 'your_paymongo_public_key_here'); // Replace with your actual public key
define('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1');

// IMPORTANT: Replace this with your actual ngrok URL
// Example: https://abc123.ngrok-free.app
define('NGROK_BASE_URL', 'https://nonfragilely-marked-wilfredo.ngrok-free.dev');

// Payment URLs
define('PAYMENT_SUCCESS_URL', NGROK_BASE_URL . '/PEST-CTRL_VER.1.3/paymongo/payment-success.php');
define('PAYMENT_CANCEL_URL', NGROK_BASE_URL . '/PEST-CTRL_VER.1.3/paymongo/payment-cancel.php');

// Local URLs for testing (use these when testing locally)
define('LOCAL_SUCCESS_URL', 'http://localhost/PEST-CTRL_VER.1.3/paymongo/payment-success.php');
define('LOCAL_CANCEL_URL', 'http://localhost/PEST-CTRL_VER.1.3/paymongo/payment-cancel.php');

// Webhook Configuration (optional)
define('WEBHOOK_SECRET', 'whsk_your_webhook_secret_here'); // Replace with your webhook secret

// Currency Settings
define('DEFAULT_CURRENCY', 'PHP');

// Payment Method Types
define('PAYMENT_METHOD_TYPES', ['card', 'gcash', 'grab_pay']);

// Email Settings
define('SEND_EMAIL_RECEIPT', true);
define('SHOW_LINE_ITEMS', true);
define('SHOW_DESCRIPTION', false);

/**
 * Get PayMongo Configuration Array
 */
function getPayMongoConfig() {
    return [
        'secret_key' => PAYMONGO_SECRET_KEY,
        'public_key' => PAYMONGO_PUBLIC_KEY,
        'base_url' => PAYMONGO_BASE_URL,
        'success_url' => PAYMENT_SUCCESS_URL,
        'cancel_url' => PAYMENT_CANCEL_URL,
        'webhook_secret' => WEBHOOK_SECRET,
        'currency' => DEFAULT_CURRENCY,
        'payment_method_types' => PAYMENT_METHOD_TYPES,
        'send_email_receipt' => SEND_EMAIL_RECEIPT,
        'show_line_items' => SHOW_LINE_ITEMS,
        'show_description' => SHOW_DESCRIPTION
    ];
}

/**
 * Instructions for Setup:
 * 
 * 1. Get your PayMongo API keys from https://dashboard.paymongo.com/
 * 2. Replace the placeholder keys above with your actual keys
 * 3. Start ngrok: ngrok http 80 (or your local port)
 * 4. Copy your ngrok URL and replace NGROK_BASE_URL above
 * 5. Test with sandbox keys first before going live
 * 
 * Example ngrok URL: https://abc123def456.ngrok-free.app
 */
?>
