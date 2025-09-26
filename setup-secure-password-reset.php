<?php
// Setup script for secure password reset system
require_once 'config/database.php';

echo "<h2>Setting up Secure Password Reset System</h2>";

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful<br>";
    
    // Drop existing password_resets table if it exists
    $pdo->exec("DROP TABLE IF EXISTS password_resets");
    echo "✅ Dropped old password_resets table<br>";
    
    // Create new secure password_resets table
    $createTableSQL = "
    CREATE TABLE password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_token_hash (token_hash),
        INDEX idx_expires (expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($createTableSQL);
    echo "✅ Created new secure password_resets table<br>";
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE password_resets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>New Table Structure:</h3>";
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
    
    echo "<h3>Security Features:</h3>";
    echo "✅ Tokens are hashed with SHA-256 before storage<br>";
    echo "✅ Tokens expire after 1 hour<br>";
    echo "✅ Tokens are single-use (marked as used)<br>";
    echo "✅ Foreign key constraint ensures data integrity<br>";
    echo "✅ Indexes for fast lookups<br>";
    
    echo "<h3>Next Steps:</h3>";
    echo "1. The new secure password reset system is ready<br>";
    echo "2. Test with the updated forgot-password.php<br>";
    echo "3. Tokens will be 64+ characters long and cryptographically secure<br>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This script has updated your password reset system to use secure tokens instead of OTPs.</p>";
?>
