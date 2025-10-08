<?php
/**
 * PayMongo Multi-Seller Checkout Session Creation
 * This file creates a checkout session for multi-seller orders using PayMongo's API
 */

// Enable CORS for frontend requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Include configuration
require_once 'config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Get PayMongo Configuration
$PAYMONGO_CONFIG = getPayMongoConfig();

try {
    // Get JSON input
    $raw_input = file_get_contents('php://input');
    error_log("Multi-seller raw input received: " . $raw_input);
    
    $input = json_decode($raw_input, true);
    
    if (!$input) {
        $json_error = json_last_error_msg();
        throw new Exception('Invalid JSON input: ' . $json_error);
    }
    
    error_log("Multi-seller parsed input: " . print_r($input, true));
    
    // Validate required fields
    $required_fields = ['amount', 'currency', 'items', 'customer_email'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Prepare checkout session data
    $checkout_data = [
        'data' => [
            'attributes' => [
                'send_email_receipt' => $input['send_email_receipt'] ?? true,
                'show_description' => false,
                'show_line_items' => $input['show_line_items'] ?? true,
                'receipt_email' => $input['customer_email'],
                'success_url' => $input['success_url'] ?? $PAYMONGO_CONFIG['success_url'],
                'cancel_url' => $input['cancel_url'] ?? $PAYMONGO_CONFIG['cancel_url'],
                'payment_method_types' => $input['payment_method_types'] ?? ['card'],
                'line_items' => [],
                'metadata' => [
                    'order_id' => $input['order_id'] ?? 'multi_seller_order_' . time(),
                    'customer_email' => $input['customer_email'],
                    'customer_name' => $input['customer_name'] ?? '',
                    'created_at' => date('Y-m-d H:i:s'),
                    'order_type' => 'multi_seller'
                ]
            ]
        ]
    ];
    
    // Add line items with seller information
    foreach ($input['items'] as $item) {
        $sellerInfo = isset($item['seller_name']) ? " (From: {$item['seller_name']})" : '';
        $desc = isset($item['description']) && trim($item['description']) !== ''
            ? $item['description'] . $sellerInfo
            : ('Order item: ' . $item['name'] . $sellerInfo);
            
        $checkout_data['data']['attributes']['line_items'][] = [
            'currency' => $input['currency'],
            'amount' => (int)($item['price'] * 100), // Convert to cents
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'description' => $desc
        ];
    }
    
    // Add customer information
    if (isset($input['customer']) && !empty($input['customer']['email'])) {
        $checkout_data['data']['attributes']['billing'] = [
            'name' => trim(($input['customer']['first_name'] ?? '') . ' ' . ($input['customer']['last_name'] ?? '')),
            'email' => $input['customer']['email'],
            'phone' => $input['customer']['phone'] ?? null
        ];
    } else {
        $checkout_data['data']['attributes']['billing'] = [
            'name' => $input['customer_name'] ?? 'Customer',
            'email' => $input['customer_email'],
            'phone' => $input['customer_phone'] ?? null
        ];
    }
    
    // Log the final checkout data being sent to PayMongo
    error_log("Multi-seller checkout data being sent to PayMongo: " . json_encode($checkout_data));
    
    // Create checkout session using cURL
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $PAYMONGO_CONFIG['base_url'] . '/checkout_sessions',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($checkout_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($PAYMONGO_CONFIG['secret_key'] . ':')
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    
    curl_close($ch);
    
    if ($curl_error) {
        throw new Exception("cURL Error: $curl_error");
    }
    
    $response_data = json_decode($response, true);
    
    if ($http_code >= 400) {
        $error_message = $response_data['errors'][0]['detail'] ?? 'Unknown error occurred';
        throw new Exception("PayMongo API Error: $error_message");
    }
    
    // Store payment session in database for tracking
    if (isset($input['transaction_id'])) {
        $stmt = $pdo->prepare("UPDATE payment_transactions SET paymongo_session_id = ? WHERE id = ?");
        $stmt->execute([$response_data['data']['id'], $input['transaction_id']]);
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'checkout_session_id' => $response_data['data']['id'],
        'checkout_url' => $response_data['data']['attributes']['checkout_url'],
        'data' => $response_data['data']
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Multi-seller Checkout Session Error: " . $e->getMessage());
    error_log("Multi-seller Checkout Session Error Trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
