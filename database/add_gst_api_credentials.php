<?php

/**
 * Add GST API credential columns to company_settings table
 */

require_once __DIR__ . '/../includes/functions.php';

$db = getDBConnection();

try {
  echo "Adding GST API credential columns to company_settings table...\n";

  // Check if columns already exist
  $stmt = $db->query("SHOW COLUMNS FROM company_settings LIKE 'gst_api_key'");
  if ($stmt->rowCount() == 0) {
    $db->exec("ALTER TABLE company_settings ADD COLUMN gst_api_key VARCHAR(255) DEFAULT NULL AFTER gst_rate");
    echo "✓ Added gst_api_key column\n";
  } else {
    echo "✓ gst_api_key column already exists\n";
  }

  $stmt = $db->query("SHOW COLUMNS FROM company_settings LIKE 'gst_api_secret'");
  if ($stmt->rowCount() == 0) {
    $db->exec("ALTER TABLE company_settings ADD COLUMN gst_api_secret VARCHAR(255) DEFAULT NULL AFTER gst_api_key");
    echo "✓ Added gst_api_secret column\n";
  } else {
    echo "✓ gst_api_secret column already exists\n";
  }

  $stmt = $db->query("SHOW COLUMNS FROM company_settings LIKE 'gst_username'");
  if ($stmt->rowCount() == 0) {
    $db->exec("ALTER TABLE company_settings ADD COLUMN gst_username VARCHAR(100) DEFAULT NULL AFTER gst_api_secret");
    echo "✓ Added gst_username column\n";
  } else {
    echo "✓ gst_username column already exists\n";
  }

  $stmt = $db->query("SHOW COLUMNS FROM company_settings LIKE 'gst_password'");
  if ($stmt->rowCount() == 0) {
    $db->exec("ALTER TABLE company_settings ADD COLUMN gst_password VARCHAR(255) DEFAULT NULL AFTER gst_username");
    echo "✓ Added gst_password column\n";
  } else {
    echo "✓ gst_password column already exists\n";
  }

  echo "\n✅ All GST API credential columns added successfully!\n";
  echo "You can now configure your GST API credentials in Settings → Company Settings\n";
} catch (PDOException $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
  exit(1);
}
