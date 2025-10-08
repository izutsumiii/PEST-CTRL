<?php
// Admin password hash tool (temporary). Remove this file in production.
// Usage:
//   http://localhost/PEST-CTRL-VER_1.6/PEST-CTRL-main/admin/includes/hash.php?p=NewStrongPass123!

header('Content-Type: text/plain; charset=utf-8');

$plain = isset($_GET['p']) && $_GET['p'] !== '' ? (string)$_GET['p'] : 'Lobotomized!746';

// Generate bcrypt hash
$hash = password_hash($plain, PASSWORD_DEFAULT);

echo "Plain:    {$plain}\n";
echo "Hash:     {$hash}\n";
echo "\nSQL example:\n";
echo "UPDATE users SET password = '" . addslashes($hash) . "' WHERE username = 'admin';\n";

// Optional: quick verify if you pass &verify=1 and &h=<hash>
if (isset($_GET['verify']) && isset($_GET['h'])) {
    $ok = password_verify($plain, (string)$_GET['h']);
    echo "\nVerify given hash with provided plain: " . ($ok ? 'MATCH' : 'NO MATCH') . "\n";
}
?>


