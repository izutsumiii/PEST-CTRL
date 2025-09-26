<?php
// Simple email test script
require_once 'PHPMailer/PHPMailer/src/Exception.php';
require_once 'PHPMailer/PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h2>Email Configuration Test</h2>";

if (isset($_POST['test_email'])) {
    $testEmail = $_POST['test_email'];
    
    $mail = new PHPMailer(true);
    
    try {
        echo "Testing email configuration...<br>";
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'jhongujol1299@gmail.com';
        $mail->Password = 'ljdo ohkv pehx idkv'; // App password
        $mail->SMTPSecure = "ssl";
        $mail->Port = 465;
        
        echo "✅ SMTP configuration set<br>";
        
        // Recipients
        $mail->setFrom('jhongujol1299@gmail.com', 'E-Commerce Store');
        $mail->addAddress($testEmail, 'Test User');
        
        echo "✅ Recipients set<br>";
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Test Email - E-Commerce Store';
        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h1 style='color: #007bff;'>Email Test Successful!</h1>
            <p>This is a test email to verify that your email configuration is working correctly.</p>
            <p>If you received this email, your password reset system should work properly.</p>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        </div>";
        
        $mail->send();
        echo "✅ Email sent successfully to: $testEmail<br>";
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>Success!</strong> Email was sent successfully. Check your inbox.";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "❌ Email sending failed<br>";
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Mailer Error:</strong> " . $mail->ErrorInfo;
        echo "</div>";
        
        echo "<h3>Common Solutions:</h3>";
        echo "<ul>";
        echo "<li>Check if Gmail app password is correct</li>";
        echo "<li>Make sure 2-factor authentication is enabled on Gmail</li>";
        echo "<li>Verify the app password is for 'Mail' application</li>";
        echo "<li>Check if your server allows outbound SMTP connections</li>";
        echo "</ul>";
    }
} else {
    echo "<p>Enter an email address to test the email configuration:</p>";
    echo "<form method='POST'>";
    echo "<input type='email' name='test_email' placeholder='your-email@example.com' required style='padding: 10px; width: 300px; margin: 10px;'>";
    echo "<br>";
    echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test Email</button>";
    echo "</form>";
}

echo "<hr>";
echo "<h3>Current Email Configuration:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> smtp.gmail.com</li>";
echo "<li><strong>SMTP Port:</strong> 465</li>";
echo "<li><strong>Security:</strong> SSL</li>";
echo "<li><strong>Username:</strong> jhongujol1299@gmail.com</li>";
echo "<li><strong>Password:</strong> [App Password]</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test email configuration with this script</li>";
echo "<li>If email works, test the debug forgot password page</li>";
echo "<li>If email doesn't work, check Gmail app password settings</li>";
echo "</ol>";
?>
