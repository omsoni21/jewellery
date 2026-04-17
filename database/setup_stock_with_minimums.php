<?php

/**
 * Add minimum_stock column and populate stock data for all products
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "=== STOCK SYSTEM UPGRADE ===\n\n";

try {
  $db->beginTransaction();

  // Step 1: Add minimum_stock column to products table if not exists
  echo "Step 1: Adding minimum_stock column to products table...\n";

  try {
    $db->exec("ALTER TABLE products ADD COLUMN minimum_stock INT DEFAULT 5 AFTER is_active");
    echo "  ✓ Column 'minimum_stock' added\n";
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
      echo "  ✓ Column 'minimum_stock' already exists\n";
    } else {
      throw $e;
    }
  }

  // Add minimum_weight column
  try {
    $db->exec("ALTER TABLE products ADD COLUMN minimum_weight DECIMAL(10,3) DEFAULT 10.000 AFTER minimum_stock");
    echo "  ✓ Column 'minimum_weight' added\n";
  } catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
      echo "  ✓ Column 'minimum_weight' already exists\n";
    } else {
      throw $e;
    }
  }

  echo "\n";

  // Step 2: Set minimum stock levels based on category
  echo "Step 2: Setting minimum stock levels by category...\n";

  $categoryMinimums = [
    1 => ['qty' => 10, 'weight' => 20],   // Rings - high demand
    2 => ['qty' => 8, 'weight' => 30],    // Chains - high demand
    3 => ['qty' => 6, 'weight' => 25],    // Bangles
    4 => ['qty' => 5, 'weight' => 15],    // Necklaces
    5 => ['qty' => 10, 'weight' => 15],   // Earrings
    6 => ['qty' => 5, 'weight' => 20],    // Pendants
    7 => ['qty' => 5, 'weight' => 15],    // Bracelets
    8 => ['qty' => 5, 'weight' => 15],    // Mangalsutra
    9 => ['qty' => 10, 'weight' => 5],    // Nose Pin
    10 => ['qty' => 5, 'weight' => 20],   // Anklet
    11 => ['qty' => 15, 'weight' => 10],  // Toe Ring
    12 => ['qty' => 20, 'weight' => 50],  // Coins & Bars
  ];

  foreach ($categoryMinimums as $catId => $mins) {
    $stmt = $db->prepare("UPDATE products SET minimum_stock = ?, minimum_weight = ? WHERE category_id = ?");
    $stmt->execute([$mins['qty'], $mins['weight'], $catId]);
    echo "  ✓ Category $catId: Min Qty={$mins['qty']}, Min Weight={$mins['weight']}g\n";
  }

  echo "\n";

  // Step 3: Create stock records for all products
  echo "Step 3: Creating stock records for all products...\n";

  $stmt = $db->query("SELECT id, category_id, metal_type FROM products WHERE is_active = 1");
  $products = $stmt->fetchAll();

  $stockInserted = 0;
  $stockUpdated = 0;

  foreach ($products as $product) {
    // Check if stock record exists
    $stmt = $db->prepare("SELECT id FROM stock WHERE product_id = ?");
    $stmt->execute([$product['id']]);
    $existingStock = $stmt->fetch();

    if (!$existingStock) {
      // Set realistic initial stock based on category
      $category = $product['category_id'] ?? 1;
      $mins = $categoryMinimums[$category] ?? ['qty' => 5, 'weight' => 15];

      // Random stock between 50-200% of minimum
      $stockQty = rand($mins['qty'], $mins['qty'] * 2);
      $stockWeight = round(rand($mins['weight'] * 1000, $mins['weight'] * 2000) / 1000, 3);
      $grossWeight = round($stockWeight * 1.1, 3); // 10% wastage

      $stmt = $db->prepare("INSERT INTO stock (product_id, quantity, gross_weight, net_weight, wastage_weight, last_updated) VALUES (?, ?, ?, ?, 0, NOW())");
      $stmt->execute([$product['id'], $stockQty, $grossWeight, $stockWeight]);
      $stockInserted++;
    } else {
      $stockUpdated++;
    }
  }

  echo "  ✓ Stock records inserted: $stockInserted\n";
  echo "  ✓ Stock records updated: $stockUpdated\n";

  echo "\n";

  // Step 4: Show stock summary
  echo "Step 4: Stock Summary...\n\n";

  $stmt = $db->query("SELECT 
        p.metal_type,
        p.purity,
        COUNT(*) as product_count,
        SUM(s.quantity) as total_qty,
        SUM(s.net_weight) as total_weight,
        SUM(CASE WHEN s.quantity < p.minimum_stock OR s.net_weight < p.minimum_weight THEN 1 ELSE 0 END) as low_stock_count
    FROM products p
    LEFT JOIN stock s ON p.id = s.product_id
    WHERE p.is_active = 1
    GROUP BY p.metal_type, p.purity
    ORDER BY p.metal_type, p.purity");

  $summary = $stmt->fetchAll();

  printf("%-10s | %-8s | %8s | %8s | %12s | %10s\n", "Metal", "Purity", "Products", "Qty", "Weight (g)", "Low Stock");
  echo str_repeat("-", 80) . "\n";

  foreach ($summary as $row) {
    printf(
      "%-10s | %-8s | %8d | %8d | %12.3f | %10d\n",
      ucfirst($row['metal_type']),
      $row['purity'],
      $row['product_count'],
      $row['total_qty'],
      $row['total_weight'],
      $row['low_stock_count']
    );
  }

  echo "\n";

  // Step 5: Show low stock items
  echo "Step 5: Low Stock Items Alert...\n\n";

  $stmt = $db->query("SELECT 
        p.name,
        p.metal_type,
        p.purity,
        s.quantity,
        s.net_weight,
        p.minimum_stock,
        p.minimum_weight
    FROM products p
    JOIN stock s ON p.id = s.product_id
    WHERE s.quantity < p.minimum_stock OR s.net_weight < p.minimum_weight
    ORDER BY s.quantity ASC
    LIMIT 10");

  $lowStockItems = $stmt->fetchAll();

  if (empty($lowStockItems)) {
    echo "  ✓ All stock levels are good! No alerts.\n";
  } else {
    printf("%-35s | %-8s | %-6s | %5s / %5s | %5s / %5s\n", "Product", "Metal", "Purity", "Qty", "Min Qty", "Weight", "Min Wt");
    echo str_repeat("-", 100) . "\n";

    foreach ($lowStockItems as $item) {
      printf(
        "%-35s | %-8s | %-6s | %5d / %5d | %5.3f / %5.3f\n",
        $item['name'],
        ucfirst($item['metal_type']),
        $item['purity'],
        $item['quantity'],
        $item['minimum_stock'],
        $item['net_weight'],
        $item['minimum_weight']
      );
    }
  }

  $db->commit();

  echo "\n\n✅ Stock system upgraded successfully!\n";
  echo "\nFeatures:\n";
  echo "  ✓ Minimum stock quantity per product\n";
  echo "  ✓ Minimum stock weight per product\n";
  echo "  ✓ Category-based minimum thresholds\n";
  echo "  ✓ Stock records created for all products\n";
  echo "  ✓ Low stock alerts based on minimum levels\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "❌ Error: " . $e->getMessage() . "\n";
  echo "Trace: " . $e->getTraceAsString() . "\n";
}
