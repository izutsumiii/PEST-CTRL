<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// Prevent access without an email in session
if (empty($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit();
}

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// helper
function getTimeUntilResend() {
    if (!isset($_SESSION['last_otp_time'])) return 0;
    $elapsed = time() - $_SESSION['last_otp_time'];
    return max(0, 60 - $elapsed);
}

// Handle resend
if (isset($_POST['resend_otp'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid form submission.';
    } else {
        // Trigger resend by generating new OTP and storing it
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        try {
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? OR expires_at < NOW()");
            $stmt->execute([$_SESSION['reset_email']]);
        } catch (Exception $e) {}

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, otp, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['reset_email'], $token, $otp, $expiry]);
        $_SESSION['last_otp_time'] = time();

        // try send email (best effort)
        require_once 'PHPMailer/PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/PHPMailer/src/SMTP.php';
        require_once 'PHPMailer/PHPMailer/src/Exception.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'jhongujol1299@gmail.com';
            $mail->Password = 'ljdo ohkv pehx idkv';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->setFrom('jhongujol1299@gmail.com', 'E-Commerce Store');
            $mail->addAddress($_SESSION['reset_email']);
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP code';
            $mail->Body = "Your OTP is: <strong>$otp</strong>";
            $mail->send();
        } catch (Exception $e) {
            // ignore
        }

        $success = 'OTP resent. Check your email.';
    }
}

// Handle verification
if (isset($_POST['verify_otp'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid submission.';
    } else {
        $otp = trim($_POST['otp'] ?? '');
        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $error = 'Enter a valid 6-digit code.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$_SESSION['reset_email'], $otp]);
            $rec = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($rec) {
                // mark verified and redirect
                $_SESSION['verified_reset_email'] = $_SESSION['reset_email'];
                $_SESSION['reset_token'] = $rec['token'];
                unset($_SESSION['last_otp_time']);
                // delete used otp
                $d = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND otp = ?");
                $d->execute([$_SESSION['reset_email'], $otp]);
                header('Location: reset-password.php');
                exit();
            } else {
                $error = 'Invalid or expired code.';
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Enter OTP</h5>
        </div>
        <div class="card-body">
          <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
          <?php endif; ?>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
          <?php endif; ?>

          <p>Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

          <form method="post" class="mb-3">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
              <input name="otp" id="otp" maxlength="6" class="form-control form-control-lg text-center" placeholder="******" inputmode="numeric" required>
            </div>
            <div class="d-grid gap-2">
              <button name="verify_otp" class="btn btn-primary">Verify OTP</button>
            </div>
          </form>

          <form method="post" id="resend-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="d-flex justify-content-between align-items-center">
              <button name="resend_otp" class="btn btn-outline-secondary" <?php echo (isset($_SESSION['last_otp_time']) && (time() - $_SESSION['last_otp_time'] < 60)) ? 'disabled' : ''; ?>>Resend OTP</button>
              <small id="resend-timer"><?php echo getTimeUntilResend(); ?></small>
            </div>
          </form>

          <div class="mt-3 text-center"><a href="forgot-password.php">Change email</a></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const timer = document.getElementById('resend-timer');
  const resendBtn = document.querySelector('button[name="resend_otp"]');
  if (timer && parseInt(timer.textContent) > 0) {
    let s = parseInt(timer.textContent);
    const t = setInterval(()=>{
      s--;
      if (s<=0) { clearInterval(t); resendBtn.disabled=false; timer.textContent='0'; }
      else timer.textContent = s;
    },1000);
  }
})();
</script>

<?php require_once 'includes/footer.php'; ?>