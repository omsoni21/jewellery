<?php

/**
 * Direct Database Fix - Run this ONCE
 */

// Database connection
$host = 'localhost';
$dbname = 'jewellery_db';
$username = 'root';
$password = '';  // Apna password yahan daalo agar hai toh

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  echo "<h2>Database Fix Running...</h2>";

  // Fix 1: Update making_charge_type ENUM
  echo "<p>1. Updating making_charge_type column...</p>";
  $pdo->exec("ALTER TABLE invoice_items 
                MODIFY COLUMN making_charge_type ENUM('per_gram', 'per_piece', 'fixed') DEFAULT 'per_gram'");
  echo "<p style='color:green;'>✅ making_charge_type updated!</p>";

  // Fix 2: Check quantity column
  echo "<p>2. Checking quantity column...</p>";
  $result = $pdo->query("SHOW COLUMNS FROM invoice_items LIKE 'quantity'")->fetchAll();

  if (empty($result)) {
    $pdo->exec("ALTER TABLE invoice_items 
                    ADD COLUMN quantity INT NOT NULL DEFAULT 1 AFTER purity");
    echo "<p style='color:orange;'>⚠️ quantity column was missing - Added!</p>";
  } else {
    echo "<p style='color:green;'>✅ quantity column exists!</p>";
  }

  echo "<hr>";
  echo "<h3 style='color:green;'>✅ All fixes applied successfully!</h3>";
  echo "<p>You can now close this page and use billing normally.</p>";
  echo "<p><a href='/jewellery/billing/create.php'>← Go to Billing</a></p>";
} catch (PDOException $e) {
  echo "<h3 style='color:red;'>❌ ERROR:</h3>";
  echo "<p><strong>" . $e->getMessage() . "</strong></p>";
  echo "<hr>";
  echo "<p><strong>Common Issues:</strong></p>";
  echo "<ul>";
  echo "<li>Database password galat hai - file mein line 9 pe password update karo</li>";
  echo "<li>Database name alag hai - line 7 check karo</li>";
  echo "<li>MySQL server running nahi hai</li>";
  echo "</ul>";
}
