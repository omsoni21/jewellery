<?php
require_once 'config/database.php';
$db = getDBConnection();
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$db->query("UPDATE users SET password_hash = '$hash' WHERE username = 'admin'");
echo "Password updated for admin!";
