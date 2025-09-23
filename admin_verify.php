<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['admin_code'] ?? '';

    if ($code === '987654') {
        // Correct code → Redirect to admin login
        header("Location: login_admin.php");
        exit();
    } else {
        $error = "Invalid security code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Verification</title>
    <link href="assets/css/pest-ctrl.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, var(--primary-dark) 0%, rgba(19, 3, 37, 0.95) 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-primary);
        }
        .verify-container {
            background: var(--primary-dark);
            padding: 28px 24px;
            border-radius: var(--radius-xl);
            box-shadow: 0 20px 40px var(--shadow-dark);
            text-align: center;
            width: 360px;
            color: var(--primary-light);
            border: 1px solid var(--border-secondary);
        }
        h2 {
            margin-bottom: 12px;
            color: var(--accent-yellow);
            font-weight: 800;
        }
        .subtitle { 
            color: var(--primary-light); 
            opacity: 0.8; 
            font-size: 14px; 
            margin-bottom: 16px; 
        }
        input[type="password"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 16px;
            border-radius: 10px;
            border: 1px solid var(--border-secondary);
            text-align: center;
            font-size: 22px;
            letter-spacing: 8px;
            background: var(--primary-light);
            color: var(--primary-dark);
            transition: all 0.3s ease;
        }
        input[type="password"]::placeholder {
            color: var(--primary-dark);
            opacity: 0.5;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent-yellow);
            box-shadow: 0 0 0 3px rgba(255, 215, 54, 0.15);
        }
        .btn {
            border: none;
            padding: 10px 22px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.25s ease;
        }
        .btn-verify {
            background: var(--accent-yellow);
            color: var(--primary-dark);
        }
        .btn-verify:hover { background: #e6c230; transform: translateY(-2px); }
        .btn-cancel { 
            background: rgba(249, 249, 249, 0.1);
            color: var(--primary-light);
            border: 1px solid var(--border-secondary);
            margin-left: 8px;
        }
        .btn-cancel:hover { background: rgba(249, 249, 249, 0.2); transform: translateY(-2px); }
        .error {
            color: #dc3545;
            margin-bottom: 12px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <h2>Admin Access</h2>
        <div class="subtitle">Enter the 6-digit admin code to proceed</div>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <input type="password" name="admin_code" maxlength="6" placeholder="Enter 6-digit code" required>
            <br>
            <button type="submit" class="btn btn-verify">Verify</button>
            <a href="index.php" class="btn btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>
