<?php
/**
 * Get Payment Details from PayMongo API
 * This file retrieves payment details using the checkout session ID
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['checkout_session_id'])) {
        throw new Exception('Missing checkout_session_id');
    }
    
    $checkoutSessionId = $input['checkout_session_id'];
    
    // Fetch checkout session details from PayMongo
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $PAYMONGO_CONFIG['base_url'] . '/checkout_sessions/' . $checkoutSessionId,
        CURLOPT_RETURNTRANSFER => true,
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
    
    // Extract payment details
    $checkoutSession = $response_data['data'];
    $attributes = $checkoutSession['attributes'];
    
    // Calculate total amount from line items
    $totalAmount = 0;
    if (isset($attributes['line_items']) && is_array($attributes['line_items'])) {
        foreach ($attributes['line_items'] as $item) {
            $totalAmount += $item['amount'] * $item['quantity'];
        }
    }
    
    // Convert from cents to actual amount
    $totalAmount = $totalAmount / 100;
    
    // Get payment method from the checkout session
    $paymentMethod = 'Unknown';
    if (isset($attributes['payment_method_types']) && is_array($attributes['payment_method_types'])) {
        $paymentMethod = $attributes['payment_method_types'][0];
    }
    
    // Log the response for debugging
    error_log("PayMongo API Response: " . json_encode($response));
    error_log("Extracted amount: " . $totalAmount);
    error_log("Extracted payment method: " . $paymentMethod);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'checkout_session_id' => $checkoutSession['id'],
        'amount' => $totalAmount,
        'currency' => $attributes['currency'] ?? 'PHP',
        'payment_method' => $paymentMethod,
        'status' => $attributes['status'] ?? 'unknown',
        'created_at' => $attributes['created_at'] ?? date('Y-m-d H:i:s'),
        'line_items' => $attributes['line_items'] ?? [],
        'billing' => $attributes['billing'] ?? null
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Get Payment Details Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
