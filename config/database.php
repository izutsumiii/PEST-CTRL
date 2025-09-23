<?php
$host = 'localhost';
$dbname = 'ecommerce_dbb';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '" . date('P') . "'");
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
?>