<?php

/**
 * Update existing invoices to show proper metal deduction based on payment
 * and display remaining dues clearly
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Updating invoices to show proper payment-wise metal deduction...\n\n";

try {
  $db->beginTransaction();

  // Get all invoices
  $stmt = $db->query("SELECT id, invoice_no, total_amount, paid_amount, balance_amount, payment_status FROM invoices ORDER BY id");
  $invoices = $stmt->fetchAll();

  echo "Processing " . count($invoices) . " invoices...\n\n";

  foreach ($invoices as $invoice) {
    echo "Invoice: {$invoice['invoice_no']}\n";
    echo "  Total: ₹" . number_format($invoice['total_amount'], 2) . "\n";
    echo "  Paid: ₹" . number_format($invoice['paid_amount'], 2) . "\n";
    echo "  Balance: ₹" . number_format($invoice['balance_amount'], 2) . "\n";

    // Calculate payment percentage
    if ($invoice['total_amount'] > 0) {
      $paymentPercent = ($invoice['paid_amount'] / $invoice['total_amount']) * 100;
    } else {
      $paymentPercent = 0;
    }

    echo "  Payment: " . number_format($paymentPercent, 1) . "%\n";

    // Get invoice items
    $stmt = $db->prepare("SELECT id, gross_weight, net_weight, metal_amount, item_total FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoice['id']]);
    $items = $stmt->fetchAll();

    $totalWeight = 0;
    $paidWeight = 0;
    $balanceWeight = 0;

    foreach ($items as $item) {
      $totalWeight += $item['gross_weight'];

      // Calculate weight based on payment percentage
      $itemPaidWeight = $item['gross_weight'] * ($paymentPercent / 100);
      $itemBalanceWeight = $item['gross_weight'] - $itemPaidWeight;

      $paidWeight += $itemPaidWeight;
      $balanceWeight += $itemBalanceWeight;
    }

    echo "  Total Weight: " . number_format($totalWeight, 3) . "g\n";
    echo "  Paid Weight: " . number_format($paidWeight, 3) . "g\n";
    echo "  Balance Weight: " . number_format($balanceWeight, 3) . "g\n\n";
  }

  $db->commit();

  echo "\n✅ Invoice calculations complete!\n";
  echo "\nNote: The invoice view page will now show:\n";
  echo "  - Total metal weight\n";
  echo "  - Weight covered by payment\n";
  echo "  - Remaining weight (balance dues)\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "❌ Error: " . $e->getMessage() . "\n";
}
