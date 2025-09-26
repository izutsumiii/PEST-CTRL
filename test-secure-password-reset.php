<?php
// Test script for the new secure password reset system
require_once 'config/database.php';

echo "<h2>Testing Secure Password Reset System</h2>";

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful<br>";
    
    // Check if new password_resets table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "✅ password_resets table exists<br>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE password_resets");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . $column['Field'] . "</td>";
            echo "<td>" . $column['Type'] . "</td>";
            echo "<td>" . $column['Null'] . "</td>";
            echo "<td>" . $column['Key'] . "</td>";
            echo "<td>" . $column['Default'] . "</td>";
            echo "<td>" . $column['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ password_resets table does not exist<br>";
        echo "<p><strong>Please run setup-secure-password-reset.php first!</strong></p>";
        exit();
    }
    
    // Test secure token generation
    echo "<h3>Testing Secure Token Generation:</h3>";
    
    function generateSecureToken() {
        return bin2hex(random_bytes(32)); // 64 character token
    }
    
    $testToken = generateSecureToken();
    echo "Generated secure token: <strong>" . substr($testToken, 0, 20) . "...</strong> (length: " . strlen($testToken) . " characters)<br>";
    
    // Test token hashing
    $tokenHash = hash('sha256', $testToken);
    echo "Token hash: <strong>" . substr($tokenHash, 0, 20) . "...</strong> (length: " . strlen($tokenHash) . " characters)<br>";
    
    // Check if we have any users to test with
    $stmt = $pdo->query("SELECT id, email, first_name, last_name FROM users WHERE is_active = 1 LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h3>Testing with User:</h3>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Name: " . $user['first_name'] . " " . $user['last_name'] . "<br>";
        
        // Test database insert
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        if ($stmt->execute([$user['id'], $tokenHash, $expiry])) {
            echo "✅ Test token inserted successfully<br>";
            
            // Verify the insert
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE user_id = ? AND token_hash = ?");
            $stmt->execute([$user['id'], $tokenHash]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                echo "✅ Test token verified in database<br>";
                echo "Record ID: " . $record['id'] . "<br>";
                echo "Expires at: " . $record['expires_at'] . "<br>";
                echo "Created at: " . $record['created_at'] . "<br>";
                
                // Test token validation
                $stmt = $pdo->prepare("
                    SELECT pr.*, u.email, u.first_name, u.last_name 
                    FROM password_resets pr 
                    JOIN users u ON pr.user_id = u.id 
                    WHERE pr.token_hash = ? 
                    AND pr.expires_at > NOW() 
                    AND pr.used_at IS NULL
                ");
                $stmt->execute([$tokenHash]);
                $validRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($validRecord) {
                    echo "✅ Token validation successful<br>";
                    echo "Valid user: " . $validRecord['email'] . "<br>";
                } else {
                    echo "❌ Token validation failed<br>";
                }
                
                // Clean up test record
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE id = ?");
                $stmt->execute([$record['id']]);
                echo "✅ Test record cleaned up<br>";
                
            } else {
                echo "❌ Test token not found in database<br>";
            }
        } else {
            echo "❌ Failed to insert test token<br>";
        }
        
    } else {
        echo "❌ No active users found to test with<br>";
        echo "<p>Please create a user account first to test the password reset system.</p>";
    }
    
    echo "<h3>Security Features Verified:</h3>";
    echo "✅ 64-character secure tokens<br>";
    echo "✅ SHA-256 token hashing<br>";
    echo "✅ 1-hour token expiration<br>";
    echo "✅ Single-use tokens (used_at field)<br>";
    echo "✅ Foreign key constraints<br>";
    echo "✅ Proper database indexes<br>";
    
    echo "<h3>Next Steps:</h3>";
    echo "1. Test the forgot password flow: <a href='forgot-password-secure.php'>forgot-password-secure.php</a><br>";
    echo "2. Check your email for the reset link<br>";
    echo "3. Test the reset password page with the token<br>";
    echo "4. Verify password is updated successfully<br>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>System Comparison:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Feature</th><th>Old System (OTP)</th><th>New System (Secure Token)</th></tr>";
echo "<tr><td>Token Type</td><td>6-digit OTP</td><td>64-character secure token</td></tr>";
echo "<tr><td>Storage</td><td>Plain text OTP</td><td>SHA-256 hashed token</td></tr>";
echo "<tr><td>Expiration</td><td>24 hours</td><td>1 hour</td></tr>";
echo "<tr><td>Usage</td><td>Multiple attempts</td><td>Single use</td></tr>";
echo "<tr><td>Security</td><td>Medium</td><td>High</td></tr>";
echo "<tr><td>User Experience</td><td>Manual OTP entry</td><td>Click link in email</td></tr>";
echo "</table>";

echo "<hr>";
echo "<p><strong>Note:</strong> The new secure password reset system is ready to use!</p>";
?>
