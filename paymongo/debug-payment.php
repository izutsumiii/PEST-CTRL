<?php
// Debug script to test PayMongo API and payment details retrieval
require_once 'config.php';

echo "<h2>PayMongo Debug Information</h2>";

// Test 1: Check if config is loaded
echo "<h3>1. Configuration Check</h3>";
echo "Secret Key: " . (defined('PAYMONGO_SECRET_KEY') ? 'Set' : 'Not Set') . "<br>";
echo "Public Key: " . (defined('PAYMONGO_PUBLIC_KEY') ? 'Set' : 'Not Set') . "<br>";
echo "Base URL: " . (defined('PAYMONGO_BASE_URL') ? PAYMONGO_BASE_URL : 'Not Set') . "<br>";

// Test 2: Check session storage data (if available via JavaScript)
echo "<h3>2. Session Storage Data</h3>";
echo "<div id='sessionData'>Loading...</div>";

// Test 3: Test PayMongo API connection
echo "<h3>3. PayMongo API Test</h3>";
$test_url = PAYMONGO_BASE_URL . '/checkout_sessions';
$headers = [
    'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
    'Content-Type: application/json',
    'Accept: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $test_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "<br>";
echo "Response: " . htmlspecialchars($response) . "<br>";

?>

<script>
// Check session storage
document.addEventListener('DOMContentLoaded', function() {
    const sessionData = document.getElementById('sessionData');
    const paymentData = sessionStorage.getItem('paymentData');
    
    if (paymentData) {
        try {
            const data = JSON.parse(paymentData);
            sessionData.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } catch (e) {
            sessionData.innerHTML = 'Error parsing session data: ' + e.message;
        }
    } else {
        sessionData.innerHTML = 'No payment data found in session storage';
    }
});
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
