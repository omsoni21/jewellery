<?php
require_once 'config/database.php';
$db = getDBConnection();
$stmt = $db->query("SELECT * FROM users");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
