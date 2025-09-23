<?php
require_once 'config/database.php';

echo "<h2>Testing Time Issue</h2>";

try {
    // Test current time
    echo "PHP Current Time: " . date('Y-m-d H:i:s') . "<br>";
    
    // Test database time
    $stmt = $pdo->query("SELECT NOW() as db_time");
    $dbTime = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Database Time: " . $dbTime['db_time'] . "<br>";
    
    // Test timezone
    $stmt = $pdo->query("SELECT @@time_zone as timezone");
    $timezone = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Database Timezone: " . $timezone['timezone'] . "<br>";
    
    // Test a simple token query
    $stmt = $pdo->query("SELECT * FROM password_resets ORDER BY created_at DESC LIMIT 1");
    $latestToken = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($latestToken) {
        echo "<h3>Latest Token Record:</h3>";
        echo "ID: " . $latestToken['id'] . "<br>";
        echo "User ID: " . $latestToken['user_id'] . "<br>";
        echo "Expires at: " . $latestToken['expires_at'] . "<br>";
        echo "Created at: " . $latestToken['created_at'] . "<br>";
        echo "Used at: " . ($latestToken['used_at'] ?? 'NULL') . "<br>";
        
        // Test if token is expired
        $stmt = $pdo->prepare("SELECT expires_at > NOW() as is_valid FROM password_resets WHERE id = ?");
        $stmt->execute([$latestToken['id']]);
        $isValid = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Is token valid (expires_at > NOW()): " . ($isValid['is_valid'] ? 'YES' : 'NO') . "<br>";
        
        // Test the exact query that's failing
        $stmt = $pdo->prepare("
            SELECT * FROM password_resets 
            WHERE token_hash = ? 
            AND expires_at > NOW() 
            AND used_at IS NULL
        ");
        $stmt->execute([$latestToken['token_hash']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "✅ Token validation query works!<br>";
        } else {
            echo "❌ Token validation query fails<br>";
            
            // Debug each condition
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ?");
            $stmt->execute([$latestToken['token_hash']]);
            $tokenExists = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Token exists: " . ($tokenExists ? 'YES' : 'NO') . "<br>";
            
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND expires_at > NOW()");
            $stmt->execute([$latestToken['token_hash']]);
            $notExpired = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Token not expired: " . ($notExpired ? 'YES' : 'NO') . "<br>";
            
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL");
            $stmt->execute([$latestToken['token_hash']]);
            $notUsed = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Token not used: " . ($notUsed ? 'YES' : 'NO') . "<br>";
        }
    } else {
        echo "No tokens found in database<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}
?>
