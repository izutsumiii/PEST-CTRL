<?php
session_start();
require_once 'config/database.php';

// Clear remember me token from database if user is logged in
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Clear remember me cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Destroy session
session_destroy();

// Redirect to home page
header("Location: index.php");
exit();
?>