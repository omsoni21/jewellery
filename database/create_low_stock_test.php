<?php

/**
 * Create Low Stock Test Data to Demonstrate Alerts
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "=== CREATING LOW STOCK TEST DATA ===\n\n";

try {
  $db->beginTransaction();

  // Select random products and reduce their stock below minimum
  $stmt = $db->query("SELECT p.id, p.name, p.minimum_stock, p.minimum_weight, s.quantity, s.net_weight 
                        FROM products p 
                        LEFT JOIN stock s ON p.id = s.product_id 
                        WHERE p.is_active = 1 
                        ORDER BY RAND() 
                        LIMIT 15");
  $products = $stmt->fetchAll();

  $updated = 0;
  foreach ($products as $product) {
    // Set stock to 50% of minimum (clearly below threshold)
    $newQty = max(0, floor($product['minimum_stock'] * 0.5));
    $newWeight = max(0, $product['minimum_weight'] * 0.4);

    // Update or insert stock
    if ($product['quantity'] !== null) {
      $stmt = $db->prepare("UPDATE stock SET quantity = ?, gross_weight = ?, net_weight = ? WHERE product_id = ?");
      $stmt->execute([$newQty, $newWeight + 2, $newWeight, $product['id']]);
    } else {
      $stmt = $db->prepare("INSERT INTO stock (product_id, quantity, gross_weight, net_weight, wastage_weight) VALUES (?, ?, ?, ?, 0)");
      $stmt->execute([$product['id'], $newQty, $newWeight + 2, $newWeight]);
    }

    $updated++;
    echo "✓ {$product['name']}: {$product['quantity']}→{$newQty} pcs, " .
      number_format($product['net_weight'] ?? 0, 3) . "g→" . number_format($newWeight, 3) . "g\n";
  }

  $db->commit();

  echo "\n✅ Successfully created low stock for $updated products!\n";
  echo "Now visit the stock page to see the alerts.\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "❌ Error: " . $e->getMessage() . "\n";
}
