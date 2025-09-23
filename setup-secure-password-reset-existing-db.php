<?php
// Setup script for secure password reset system - Existing Database Version
require_once 'config/database.php';

echo "<h2>Setting up Secure Password Reset System (Existing Database)</h2>";

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful<br>";
    echo "✅ Using existing database: <strong>" . $pdo->query("SELECT DATABASE()")->fetchColumn() . "</strong><br>";
    
    // Check if password_resets table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_resets'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "⚠️ password_resets table already exists<br>";
        echo "Dropping existing table to create new secure version...<br>";
        $pdo->exec("DROP TABLE password_resets");
        echo "✅ Dropped old password_resets table<br>";
    }
    
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
    
    // Check if users table exists and has required columns
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->fetch();
    
    if ($usersTableExists) {
        echo "✅ users table exists<br>";
        
        // Check for required columns
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = ['id', 'email', 'password', 'first_name', 'last_name'];
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "✅ users table has all required columns<br>";
        } else {
            echo "⚠️ users table missing columns: " . implode(', ', $missingColumns) . "<br>";
            echo "Please add these columns to your users table.<br>";
        }
        
        // Check for optional columns and add if missing
        $optionalColumns = [
            'is_active' => "ADD COLUMN is_active TINYINT(1) DEFAULT 1",
            'user_type' => "ADD COLUMN user_type ENUM('customer', 'seller', 'admin') DEFAULT 'customer'",
            'seller_status' => "ADD COLUMN seller_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'",
            'remember_token' => "ADD COLUMN remember_token VARCHAR(255) NULL"
        ];
        
        foreach ($optionalColumns as $column => $sql) {
            if (!in_array($column, $columns)) {
                try {
                    $pdo->exec("ALTER TABLE users $sql");
                    echo "✅ Added missing column: $column<br>";
                } catch (PDOException $e) {
                    echo "⚠️ Could not add column $column: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "✅ Column $column already exists<br>";
            }
        }
        
    } else {
        echo "❌ users table does not exist<br>";
        echo "Please create a users table first with the required columns.<br>";
    }
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE password_resets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>New password_resets Table Structure:</h3>";
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
    echo "2. Test with: <a href='forgot-password-secure.php'>forgot-password-secure.php</a><br>";
    echo "3. Tokens will be 64+ characters long and cryptographically secure<br>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This script has updated your existing database to use secure tokens instead of OTPs.</p>";
echo "<p><strong>Database:</strong> " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "</p>";
?>
