<?php
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
$stmt->execute([$hash]);

echo "Password reset successfully!\n";
echo "New hash: " . $hash . "\n";
echo "You can now login with: admin / admin123\n";
?>
