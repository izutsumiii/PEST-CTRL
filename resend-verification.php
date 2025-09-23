<?php
require_once 'includes/header.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$email = isset($_GET['email']) ? sanitizeInput($_GET['email']) : '';

if (isset($_POST['resend_verification'])) {
    $email = sanitizeInput($_POST['email']);
    
    // Check if user exists and is not verified
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND email_verified = FALSE");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Generate new verification token
        $verificationToken = bin2hex(random_bytes(32));
        
        // Update token in database
        $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
        $stmt->execute([$verificationToken, $user['id']]);
        
        // Send verification email
        sendVerificationEmail($email, $verificationToken, $user['first_name']);
        
        $success = "Verification email has been sent. Please check your inbox.";
    } else {
        $error = "No unverified account found with this email address or the account is already verified.";
    }
}

function sendVerificationEmail($email, $token, $name) {
    $verificationLink = "http://" . $_SERVER['HTTP_HOST'] . "/verify-email.php?token=" . $token;
    
    $subject = "Verify Your Email Address";
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <h2>Hello $name,</h2>
        <p>You requested a new verification email. Please click the link below to verify your email address:</p>
        <p><a href='$verificationLink'>Verify Email Address</a></p>
        <p>If you didn't request this, please ignore this email.</p>
        <br>
        <p>Best regards,<br>E-Commerce Store Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@ecommerce.example.com" . "\r\n";
    
    mail($email, $subject, $message, $headers);
}
?>

<h1>Resend Verification Email</h1>

<?php if (isset($success)): ?>
    <div class="success-message">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error-message">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div class="form-group">
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        <small>Enter your email address to receive a new verification link.</small>
    </div>
    
    <button type="submit" name="resend_verification">Resend Verification Email</button>
</form>

<p><a href="login.php">Back to Login</a></p>

<?php require_once 'includes/footer.php'; ?>