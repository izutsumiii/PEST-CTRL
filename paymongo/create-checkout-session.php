<?php
/**
 * PayMongo Checkout Session Creation
 * This file creates a checkout session using PayMongo's API
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

// Get PayMongo Configuration
$PAYMONGO_CONFIG = getPayMongoConfig();

try {
    // Get JSON input
    $raw_input = file_get_contents('php://input');
    error_log("Raw input received: " . $raw_input);
    
    $input = json_decode($raw_input, true);
    
    if (!$input) {
        $json_error = json_last_error_msg();
        throw new Exception('Invalid JSON input: ' . $json_error);
    }
    
    error_log("Parsed input: " . print_r($input, true));
    
    // Validate required fields
    $required_fields = ['amount', 'currency', 'items'];
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
                'receipt_email' => $input['receipt_email'] ?? $input['customer_email'] ?? '',
                'success_url' => $input['success_url'] ?? $PAYMONGO_CONFIG['success_url'],
                'cancel_url' => $input['cancel_url'] ?? $PAYMONGO_CONFIG['cancel_url'],
                'payment_method_types' => $input['payment_method_types'] ?? ['card', 'gcash', 'grab_pay'],
                'line_items' => [],
                'metadata' => [
                    'order_id' => $input['order_id'] ?? 'order_' . time(),
                    'customer_email' => $input['customer_email'] ?? '',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]
        ]
    ];
    
    // Add line items
    foreach ($input['items'] as $item) {
        $desc = isset($item['description']) && trim($item['description']) !== ''
            ? $item['description']
            : ('Order item: ' . $item['name']);
        $checkout_data['data']['attributes']['line_items'][] = [
            'currency' => $input['currency'],
            'amount' => (int)($item['price'] * 100), // Convert to cents
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'description' => $desc
        ];
    }
    
    // Add customer information if provided
    if (isset($input['customer']) && !empty($input['customer']['email'])) {
        $checkout_data['data']['attributes']['billing'] = [
            'name' => trim(($input['customer']['first_name'] ?? '') . ' ' . ($input['customer']['last_name'] ?? '')),
            'email' => $input['customer']['email'],
            'phone' => $input['customer']['phone'] ?? null
        ];
        
        // Also add customer email to metadata for receipt
        $checkout_data['data']['attributes']['metadata']['customer_email'] = $input['customer']['email'];
        $checkout_data['data']['attributes']['metadata']['customer_name'] = trim(($input['customer']['first_name'] ?? '') . ' ' . ($input['customer']['last_name'] ?? ''));
    } elseif (!empty($input['customer_email'])) {
        // Fallback to customer_email if customer object is not properly structured
        $checkout_data['data']['attributes']['billing'] = [
            'name' => 'Customer',
            'email' => $input['customer_email'],
            'phone' => null
        ];
        
        $checkout_data['data']['attributes']['metadata']['customer_email'] = $input['customer_email'];
        $checkout_data['data']['attributes']['metadata']['customer_name'] = 'Customer';
    }
    
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
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'checkout_session_id' => $response_data['data']['id'],
        'checkout_url' => $response_data['data']['attributes']['checkout_url'],
        'data' => $response_data['data']
    ]);
    
} catch (Exception $e) {
    // Log error (you might want to log this to a file in production)
    error_log("Checkout Session Error: " . $e->getMessage());
    error_log("Checkout Session Error Trace: " . $e->getTraceAsString());
    
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
