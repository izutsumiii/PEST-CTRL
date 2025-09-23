<?php
// Enhanced test script to check database and create sample data
require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful<br>";
    
    // Check if password_resets table exists
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
        echo "<h3>Creating password_resets table...</h3>";
        
        $createTableSQL = "
        CREATE TABLE password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            otp VARCHAR(6) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_token (token),
            INDEX idx_expires (expires_at)
        )";
        
        $pdo->exec($createTableSQL);
        echo "✅ password_resets table created successfully<br>";
    }
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->fetch();
    
    if ($usersTableExists) {
        echo "✅ users table exists<br>";
        
        // Show sample users
        $stmt = $pdo->query("SELECT id, email, first_name, last_name FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Users:</h3>";
        if (count($users) > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Email</th><th>First Name</th><th>Last Name</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . $user['first_name'] . "</td>";
                echo "<td>" . $user['last_name'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "No users found in database.<br>";
            echo "<h3>Creating sample user for testing...</h3>";
            
            $sampleUserSQL = "
            INSERT INTO users (email, password, first_name, last_name, created_at) 
            VALUES ('test@example.com', ?, 'Test', 'User', NOW())
            ";
            
            $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare($sampleUserSQL);
            if ($stmt->execute([$hashedPassword])) {
                echo "✅ Sample user created: test@example.com / password123<br>";
            } else {
                echo "❌ Failed to create sample user<br>";
            }
        }
    } else {
        echo "❌ users table does not exist<br>";
        echo "<h3>Creating users table...</h3>";
        
        $createUsersTableSQL = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($createUsersTableSQL);
        echo "✅ users table created successfully<br>";
        
        // Add sample user
        $sampleUserSQL = "
        INSERT INTO users (email, password, first_name, last_name) 
        VALUES ('test@example.com', ?, 'Test', 'User')
        ";
        
        $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare($sampleUserSQL);
        if ($stmt->execute([$hashedPassword])) {
            echo "✅ Sample user created: test@example.com / password123<br>";
        }
    }
    
    // Test OTP generation
    echo "<h3>Testing OTP Generation:</h3>";
    function generateOTP($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    $testOTP = generateOTP(6);
    echo "Generated OTP: <strong>$testOTP</strong><br>";
    
    // Test database insert
    $testEmail = 'test@example.com';
    $testToken = bin2hex(random_bytes(32));
    $testExpiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, otp, expires_at) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$testEmail, $testToken, $testOTP, $testExpiry])) {
        echo "✅ Test OTP inserted into database successfully<br>";
        
        // Verify the insert
        $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND otp = ?");
        $stmt->execute([$testEmail, $testOTP]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "✅ Test OTP verified in database<br>";
            echo "Record ID: " . $record['id'] . "<br>";
            echo "Expires at: " . $record['expires_at'] . "<br>";
        } else {
            echo "❌ Test OTP not found in database<br>";
        }
    } else {
        echo "❌ Failed to insert test OTP<br>";
    }
    
    echo "<h3>Current Password Reset Records:</h3>";
    $stmt = $pdo->query("SELECT * FROM password_resets ORDER BY created_at DESC LIMIT 5");
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($records) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Email</th><th>OTP</th><th>Expires At</th><th>Created At</th></tr>";
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td>" . $record['id'] . "</td>";
            echo "<td>" . $record['email'] . "</td>";
            echo "<td>" . $record['otp'] . "</td>";
            echo "<td>" . $record['expires_at'] . "</td>";
            echo "<td>" . $record['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No password reset records found.<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "1. Test the forgot password flow with email: <strong>test@example.com</strong><br>";
echo "2. Check the debug OTP display on the forgot password page<br>";
echo "3. Verify OTP verification works<br>";
echo "4. Test the complete flow: Email → OTP → Reset Password<br>";
?>