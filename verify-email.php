<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$token = isset($_GET['token']) ? sanitizeInput($_GET['token']) : '';

if (empty($token)) {
    $error = "Invalid verification link.";
} else {
    // Check if token exists and is valid
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Verify email
        $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, verification_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $success = "Email verified successfully! You can now login to your account.";
    } else {
        $error = "Invalid or expired verification link.";
    }
}
?>

<h1>Email Verification</h1>

<?php if (isset($success)): ?>
    <div class="success-message">
        <?php echo $success; ?>
    </div>
    <p><a href="login.php">Login to your account</a></p>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error-message">
        <?php echo $error; ?>
    </div>
    <p><a href="register.php">Register for a new account</a></p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>