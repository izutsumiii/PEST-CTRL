<?php
require_once 'config/database.php';

try {
    // Create order_status_history table
    $sql = "CREATE TABLE IF NOT EXISTS order_status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        status VARCHAR(50) NOT NULL,
        notes TEXT,
        updated_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (updated_by) REFERENCES users(id)
    )";
    
    $pdo->exec($sql);
    echo "Table order_status_history created successfully!<br>";
    
    // Check if the table exists now
    $result = $pdo->query("SHOW TABLES LIKE 'order_status_history'");
    if ($result->rowCount() > 0) {
        echo "The order_status_history table is ready to use.";
    } else {
        echo "Failed to create order_status_history table.";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>