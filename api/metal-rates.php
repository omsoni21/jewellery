<?php

/**
 * API Metal Rates Endpoint
 * Returns metal rates for a specific date
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

try {
  $db = getDBConnection();
  $date = $_GET['date'] ?? date('Y-m-d');

  $stmt = $db->prepare("SELECT * FROM metal_rates WHERE rate_date = ? ORDER BY metal_type, purity");
  $stmt->execute([$date]);
  $rates = $stmt->fetchAll();

  echo json_encode($rates);
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Server error: ' . $e->getMessage()
  ]);
}
