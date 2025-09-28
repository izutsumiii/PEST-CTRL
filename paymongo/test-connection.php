<?php
/**
 * Simple test endpoint to check if PayMongo integration is working
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Include configuration
    require_once 'config.php';
    
    // Test basic configuration
    $config = getPayMongoConfig();
    
    echo json_encode([
        'success' => true,
        'message' => 'PayMongo configuration loaded successfully',
        'config' => [
            'has_secret_key' => !empty($config['secret_key']),
            'has_public_key' => !empty($config['public_key']),
            'base_url' => $config['base_url'],
            'success_url' => $config['success_url'],
            'cancel_url' => $config['cancel_url'],
            'currency' => $config['currency']
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
