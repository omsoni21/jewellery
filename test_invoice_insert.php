<?php

/**
 * Test Invoice Creation to Find Exact Error
 */

require_once 'config/database.php';

$db = getDBConnection();

echo "Testing invoice item insert...\n\n";

// Test data
$invoiceId = 999999; // Fake ID for testing
$item = [
  'product_id' => 1,
  'item_name' => 'Test Gold Ring',
  'metal_type' => 'gold',
  'purity' => '22K',
  'quantity' => 1,
  'gross_weight' => 5.500,
  'net_weight' => 5.038,
  'wastage_percent' => 0,
  'wastage_weight' => 0,
  'total_weight' => 5.500,
  'rate_per_gram' => 13535.41,
  'metal_amount' => 68264.37,
  'making_charge_type' => 'per_gram',
  'making_charge_rate' => 250,
  'making_charge_amount' => 1375.00,
  'item_total' => 69639.37
];

echo "Item Data:\n";
print_r($item);

echo "\nPreparing INSERT statement...\n";

try {
  // Count columns
  $columns = "invoice_id, product_id, item_name, metal_type, purity, quantity, gross_weight, net_weight, wastage_percent, wastage_weight, total_weight, rate_per_gram, metal_amount, making_charge_type, making_charge_rate, making_charge_amount, item_total";
  $columnCount = count(explode(',', $columns));
  echo "Number of columns: $columnCount\n";

  $stmt = $db->prepare("INSERT INTO invoice_items ($columns) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

  echo "Executing with values...\n";

  $stmt->execute([
    $invoiceId,
    $item['product_id'],
    $item['item_name'],
    $item['metal_type'],
    $item['purity'],
    $item['quantity'],
    $item['gross_weight'],
    $item['net_weight'],
    $item['wastage_percent'],
    $item['wastage_weight'],
    $item['total_weight'],
    $item['rate_per_gram'],
    $item['metal_amount'],
    $item['making_charge_type'],
    $item['making_charge_rate'],
    $item['making_charge_amount'],
    $item['item_total']
  ]);

  echo "✅ SUCCESS! Invoice item inserted.\n";
} catch (PDOException $e) {
  echo "❌ ERROR: " . $e->getMessage() . "\n";
  echo "\nError Code: " . $e->getCode() . "\n";
}
