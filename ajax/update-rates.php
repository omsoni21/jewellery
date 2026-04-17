<?php

/**
 * AJAX: Update Metal Rates
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'update_rates') {
  echo json_encode(['success' => false, 'message' => 'Invalid request']);
  exit;
}

try {
  $db = getDBConnection();

  $rateDate = $_POST['rate_date'] ?? date('Y-m-d');

  $rates = [
    ['metal_type' => 'gold', 'purity' => '24K', 'rate' => floatval($_POST['gold_24k'] ?? 0)],
    ['metal_type' => 'gold', 'purity' => '22K', 'rate' => floatval($_POST['gold_22k'] ?? 0)],
    ['metal_type' => 'gold', 'purity' => '18K', 'rate' => floatval($_POST['gold_18k'] ?? 0)],
    ['metal_type' => 'gold', 'purity' => '14K', 'rate' => floatval($_POST['gold_14k'] ?? 0)],
    ['metal_type' => 'silver', 'purity' => '999', 'rate' => floatval($_POST['silver_999'] ?? 0)],
    ['metal_type' => 'silver', 'purity' => '925', 'rate' => floatval($_POST['silver_925'] ?? 0)],
  ];

  $stmt = $db->prepare("INSERT INTO metal_rates (metal_type, purity, rate_per_gram, rate_date, created_by) 
                          VALUES (?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE rate_per_gram = VALUES(rate_per_gram), created_by = VALUES(created_by)");

  $updated = 0;
  foreach ($rates as $rate) {
    if ($rate['rate'] > 0) {
      $stmt->execute([$rate['metal_type'], $rate['purity'], $rate['rate'], $rateDate, $_SESSION['user_id']]);
      $updated++;
    }
  }

  if ($updated > 0) {
    logActivity('rates_updated', "Updated $rates metal rates for $rateDate from billing page");
    echo json_encode([
      'success' => true,
      'message' => "Successfully updated $updated rate(s)",
      'updated' => $updated
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'No valid rates provided']);
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
