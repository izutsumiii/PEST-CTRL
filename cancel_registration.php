<?php
session_start();

// Verify CSRF token and clear registration data
if (isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        // Clear temporary registration data
        unset($_SESSION['registration_otp']);
        unset($_SESSION['temp_user_data']);
        unset($_SESSION['otp_email']);
        
        echo json_encode(['status' => 'success', 'message' => 'Registration cancelled']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing token']);
}
?>
