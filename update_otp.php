<?php
session_start();

header('Content-Type: application/json');

// Handle different actions
if (isset($_POST['action']) && $_POST['action'] === 'generate_new') {
    // Generate new OTP and update session
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $_SESSION['registration_otp'] = sprintf('%06d', mt_rand(100000, 999999));
        echo json_encode(['status' => 'success', 'message' => 'New OTP generated', 'otp' => $_SESSION['registration_otp']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    }
} elseif (isset($_POST['new_otp']) && isset($_POST['csrf_token'])) {
    // Legacy: Update OTP with provided value
    if ($_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $_SESSION['registration_otp'] = $_POST['new_otp'];
        echo json_encode(['status' => 'success', 'message' => 'OTP updated']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid token']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
}
?>