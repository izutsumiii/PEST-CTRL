<?php
// Debug script to test token validation
require_once 'config/database.php';

echo "<h2>Debug Token Validation</h2>";

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful<br>";
    
    // Get a test user
    $stmt = $pdo->query("SELECT id, email, first_name, last_name FROM users WHERE is_active = 1 LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ No active users found<br>";
        exit();
    }
    
    echo "Testing with user: " . $user['email'] . " (ID: " . $user['id'] . ")<br>";
    
    // Generate a test token
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    echo "Generated token: " . substr($token, 0, 20) . "...<br>";
    echo "Token hash: " . substr($tokenHash, 0, 20) . "...<br>";
    echo "Expires at: $expiry<br>";
    
    // Clean up any existing tokens
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    echo "✅ Cleaned up existing tokens<br>";
    
    // Insert test token
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
    $result = $stmt->execute([$user['id'], $tokenHash, $expiry]);
    
    if ($result) {
        echo "✅ Token inserted successfully<br>";
        
        // Test 1: Simple token lookup
        echo "<h3>Test 1: Simple Token Lookup</h3>";
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ?");
        $stmt->execute([$tokenHash]);
        $simpleRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($simpleRecord) {
            echo "✅ Simple lookup successful<br>";
            echo "Record ID: " . $simpleRecord['id'] . "<br>";
            echo "User ID: " . $simpleRecord['user_id'] . "<br>";
            echo "Expires at: " . $simpleRecord['expires_at'] . "<br>";
            echo "Used at: " . ($simpleRecord['used_at'] ?? 'NULL') . "<br>";
        } else {
            echo "❌ Simple lookup failed<br>";
        }
        
        // Test 2: Check if user exists
        echo "<h3>Test 2: User Existence Check</h3>";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userRecord) {
            echo "✅ User exists<br>";
            echo "User email: " . $userRecord['email'] . "<br>";
            echo "User active: " . ($userRecord['is_active'] ?? 'NULL') . "<br>";
        } else {
            echo "❌ User not found<br>";
        }
        
        // Test 3: JOIN query (the problematic one)
        echo "<h3>Test 3: JOIN Query</h3>";
        $stmt = $pdo->prepare("
            SELECT pr.*, u.email, u.first_name, u.last_name 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token_hash = ? 
            AND pr.expires_at > NOW() 
            AND pr.used_at IS NULL
        ");
        $stmt->execute([$tokenHash]);
        $joinRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($joinRecord) {
            echo "✅ JOIN query successful<br>";
            echo "User email: " . $joinRecord['email'] . "<br>";
            echo "User name: " . $joinRecord['first_name'] . " " . $joinRecord['last_name'] . "<br>";
        } else {
            echo "❌ JOIN query failed<br>";
            
            // Debug the JOIN query step by step
            echo "<h4>Debugging JOIN Query:</h4>";
            
            // Check if token exists with expiration
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > NOW() AND used_at IS NULL");
            $stmt->execute([$tokenHash]);
            $tokenCheck = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tokenCheck) {
                echo "✅ Token exists and is valid<br>";
                
                // Check if JOIN works without conditions
                $stmt = $pdo->prepare("
                    SELECT pr.*, u.email, u.first_name, u.last_name 
                    FROM password_resets pr 
                    JOIN users u ON pr.user_id = u.id 
                    WHERE pr.token_hash = ?
                ");
                $stmt->execute([$tokenHash]);
                $simpleJoin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($simpleJoin) {
                    echo "✅ Simple JOIN works<br>";
                    echo "User email: " . $simpleJoin['email'] . "<br>";
                } else {
                    echo "❌ Simple JOIN failed<br>";
                }
            } else {
                echo "❌ Token validation conditions failed<br>";
                echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
                echo "Token expires: " . $expiry . "<br>";
            }
        }
        
        // Test 4: Alternative query without JOIN
        echo "<h3>Test 4: Alternative Query (No JOIN)</h3>";
        $stmt = $pdo->prepare("
            SELECT pr.* 
            FROM password_resets pr 
            WHERE pr.token_hash = ? 
            AND pr.expires_at > NOW() 
            AND pr.used_at IS NULL
        ");
        $stmt->execute([$tokenHash]);
        $altRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($altRecord) {
            echo "✅ Alternative query successful<br>";
            echo "User ID: " . $altRecord['user_id'] . "<br>";
            
            // Get user info separately
            $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
            $stmt->execute([$altRecord['user_id']]);
            $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userInfo) {
                echo "✅ User info retrieved separately<br>";
                echo "User email: " . $userInfo['email'] . "<br>";
            } else {
                echo "❌ Could not get user info separately<br>";
            }
        } else {
            echo "❌ Alternative query failed<br>";
        }
        
        // Clean up
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE id = ?");
        $stmt->execute([$simpleRecord['id']]);
        echo "✅ Test record cleaned up<br>";
        
    } else {
        echo "❌ Failed to insert test token<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Recommendations:</h3>";
echo "<ul>";
echo "<li>If JOIN query fails, we can use the alternative approach</li>";
echo "<li>Check if there are any foreign key constraint issues</li>";
echo "<li>Verify that the users table has the correct structure</li>";
echo "</ul>";
?>
