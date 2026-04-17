<?php
/**
 * AJAX: Get Metal Rate
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

header('Content-Type: application/json');

$metalType = $_POST['metal_type'] ?? '';
$purity = $_POST['purity'] ?? '';

if (empty($metalType) || empty($purity)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT rate_per_gram FROM metal_rates WHERE metal_type = ? AND purity = ? AND rate_date = CURDATE()");
$stmt->execute([$metalType, $purity]);
$rate = $stmt->fetch();

if ($rate) {
    echo json_encode(['success' => true, 'rate' => floatval($rate['rate_per_gram'])]);
} else {
    echo json_encode(['success' => false, 'message' => 'Rate not found for today']);
}
