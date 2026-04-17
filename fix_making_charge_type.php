<?php

/**
 * Database Fix Script
 * 1. Add 'per_piece' to making_charge_type ENUM
 * 2. Ensure quantity column exists
 */

require_once __DIR__ . '/includes/functions.php';

try {
  $db = getDBConnection();

  echo "=== Database Fix ===\n\n";

  // 1. Fix making_charge_type ENUM
  echo "1. Fixing making_charge_type column...\n";
  $sql1 = "ALTER TABLE invoice_items 
             MODIFY COLUMN making_charge_type ENUM('per_gram', 'per_piece', 'fixed') DEFAULT 'per_gram'";
  $db->exec($sql1);
  echo "   ✅ making_charge_type updated to: 'per_gram', 'per_piece', 'fixed'\n\n";

  // 2. Check if quantity column exists
  echo "2. Checking quantity column...\n";
  $checkSql = "SHOW COLUMNS FROM invoice_items LIKE 'quantity'";
  $stmt = $db->query($checkSql);
  $columnExists = $stmt->fetch();

  if ($columnExists) {
    echo "   ✅ quantity column already exists\n";
  } else {
    echo "   ⚠️ quantity column not found, adding...\n";
    $sql2 = "ALTER TABLE invoice_items 
                 ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER purity";
    $db->exec($sql2);
    echo "   ✅ quantity column added\n";
  }

  echo "\n=== All fixes applied successfully! ===\n";
  echo "You can now use MC Type: Per Piece without errors.\n";
} catch (Exception $e) {
  echo "❌ ERROR: " . $e->getMessage() . "\n";
  echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
