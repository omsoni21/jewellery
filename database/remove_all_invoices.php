<?php

/**
 * Remove all invoices and related data
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Starting invoice removal...\n\n";

try {
  $db->beginTransaction();

  // Count records before deletion
  $stmt = $db->query("SELECT COUNT(*) as count FROM invoices");
  $invoiceCount = $stmt->fetch()['count'];

  $stmt = $db->query("SELECT COUNT(*) as count FROM invoice_items");
  $itemsCount = $stmt->fetch()['count'];

  $stmt = $db->query("SELECT COUNT(*) as count FROM payments");
  $paymentsCount = $stmt->fetch()['count'];

  $stmt = $db->query("SELECT COUNT(*) as count FROM customer_ledger");
  $ledgerCount = $stmt->fetch()['count'];

  echo "Records to be deleted:\n";
  echo "  Invoices: $invoiceCount\n";
  echo "  Invoice Items: $itemsCount\n";
  echo "  Payments: $paymentsCount\n";
  echo "  Ledger Entries: $ledgerCount\n\n";

  if ($invoiceCount == 0) {
    echo "No invoices found. Nothing to delete.\n";
    $db->rollBack();
    exit;
  }

  // Delete in correct order (respecting foreign keys)

  // 1. Delete invoice items
  $stmt = $db->exec("DELETE FROM invoice_items");
  echo "✓ Deleted invoice items\n";

  // 2. Delete payments related to invoices
  $stmt = $db->exec("DELETE FROM payments WHERE invoice_id IS NOT NULL");
  echo "✓ Deleted payments\n";

  // 3. Delete customer ledger entries
  $stmt = $db->exec("DELETE FROM customer_ledger");
  echo "✓ Deleted ledger entries\n";

  // 4. Delete invoices
  $stmt = $db->exec("DELETE FROM invoices");
  echo "✓ Deleted invoices\n";

  // 5. Reset customer balances to opening balance
  $stmt = $db->exec("UPDATE customers SET current_balance = opening_balance");
  echo "✓ Reset customer balances\n";

  $db->commit();

  echo "\n✓ All invoices and related data have been successfully removed!\n";
  echo "\nSummary:\n";
  echo "  Invoices deleted: $invoiceCount\n";
  echo "  Invoice items deleted: $itemsCount\n";
  echo "  Payments deleted: $paymentsCount\n";
  echo "  Ledger entries deleted: $ledgerCount\n";
  echo "  Customer balances reset to opening balance\n";

  echo "\nDone!\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "Error: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
