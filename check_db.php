<?php

/**
 * Quick Database Check
 */

require_once __DIR__ . '/includes/functions.php';

try {
  $db = getDBConnection();

  echo "<h2>Database Status Check</h2>";

  // Check making_charge_type column
  echo "<h3>making_charge_type Column:</h3>";
  $stmt = $db->query("SHOW COLUMNS FROM invoice_items LIKE 'making_charge_type'");
  $column = $stmt->fetch();

  if ($column) {
    echo "<pre>";
    print_r($column);
    echo "</pre>";

    if (strpos($column['Type'], 'per_piece') !== false) {
      echo "<p style='color:green; font-size:20px;'>✅ 'per_piece' is in ENUM - OK!</p>";
    } else {
      echo "<p style='color:red; font-size:20px;'>❌ 'per_piece' is NOT in ENUM - Need to fix!</p>";
      echo "<p>Current ENUM: <code>" . $column['Type'] . "</code></p>";
      echo "<p><strong>Running fix now...</strong></p>";

      $db->exec("ALTER TABLE invoice_items 
                       MODIFY COLUMN making_charge_type ENUM('per_gram', 'per_piece', 'fixed') DEFAULT 'per_gram'");

      echo "<p style='color:green; font-size:20px;'>✅ Fixed! Refresh this page to verify.</p>";
    }
  }

  // Check quantity column
  echo "<hr><h3>quantity Column:</h3>";
  $stmt = $db->query("SHOW COLUMNS FROM invoice_items LIKE 'quantity'");
  $column = $stmt->fetch();

  if ($column) {
    echo "<p style='color:green; font-size:20px;'>✅ quantity column exists - OK!</p>";
    echo "<pre>";
    print_r($column);
    echo "</pre>";
  } else {
    echo "<p style='color:red; font-size:20px;'>❌ quantity column missing - Adding now...</p>";
    $db->exec("ALTER TABLE invoice_items ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER purity");
    echo "<p style='color:green; font-size:20px;'>✅ quantity column added!</p>";
  }

  echo "<hr>";
  echo "<p><a href='database_fix.php'>Run Full Fix</a> | <a href='billing/create.php'>Go to Billing</a></p>";
} catch (Exception $e) {
  echo "<p style='color:red;'><strong>ERROR:</strong> " . $e->getMessage() . "</p>";
}
