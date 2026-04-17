<?php

/**
 * Generate Old Historical Invoices for All Customers
 * Creates invoices from past 6 months with varied payment statuses
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "=== GENERATING OLD HISTORICAL INVOICES ===\n\n";

// Get all active customers
$stmt = $db->query("SELECT id, customer_code, business_name, state FROM customers WHERE is_active = 1 ORDER BY RAND()");
$customers = $stmt->fetchAll();

echo "Total active customers: " . count($customers) . "\n\n";

// Get products
$stmt = $db->query("SELECT id, product_code, name, metal_type, purity, category_id FROM products WHERE is_active = 1");
$products = $stmt->fetchAll();

// Metal rates
$metalRates = [
  'gold' => ['24K' => 14765.37, '22K' => 13535.41, '18K' => 11074.03, '14K' => 8612.64],
  'silver' => ['999' => 243.60, '925' => 225.33]
];

$purityPercentages = ['24K' => 99.9, '22K' => 91.6, '18K' => 75.0, '14K' => 58.3, '999' => 99.9, '925' => 92.5];

$mcRatesByCategory = [
  1 => ['per_gram' => 250, 'fixed' => 800],
  2 => ['per_gram' => 180, 'fixed' => 1200],
  3 => ['per_gram' => 220, 'fixed' => 1500],
  5 => ['per_gram' => 280, 'fixed' => 1500],
  6 => ['per_gram' => 350, 'fixed' => 2000],
];

$totalInvoicesCreated = 0;
$targetInvoicesPerCustomer = [1, 2, 3]; // Each customer gets 1-3 old invoices

echo "Generating old invoices...\n";
echo str_repeat("=", 100) . "\n\n";

try {
  $db->beginTransaction();

  foreach ($customers as $customer) {
    // Each customer gets 1-3 historical invoices
    $numInvoices = $targetInvoicesPerCustomer[array_rand($targetInvoicesPerCustomer)];

    for ($i = 0; $i < $numInvoices; $i++) {
      // Random date in past 6 months
      $daysAgo = rand(30, 180);
      $invoiceDate = date('Y-m-d', strtotime("-$daysAgo days"));
      $dueDate = date('Y-m-d', strtotime("+30 days", strtotime($invoiceDate)));

      $invoiceNo = 'INV' . str_pad(3000 + $totalInvoicesCreated, 6, '0', STR_PAD_LEFT);

      $companyState = 'Maharashtra';
      $isIntraState = ($customer['state'] == $companyState);

      // 1-3 items per invoice
      $numItems = rand(1, 3);
      $subtotal = 0;
      $totalMetalAmount = 0;
      $totalMakingAmount = 0;
      $invoiceItems = [];

      for ($j = 0; $j < $numItems; $j++) {
        $product = $products[array_rand($products)];

        if ($product['metal_type'] == 'gold') {
          $weight = round(rand(3000, 12000) / 1000, 3);
        } else {
          $weight = round(rand(8000, 30000) / 1000, 3);
        }

        $quantity = 1;
        $ratePerGram = $metalRates[$product['metal_type']][$product['purity']] ?? 13535.41;
        $purityPercent = $purityPercentages[$product['purity']] ?? 99.9;

        $fineWeight = round($weight * ($purityPercent / 100), 3);
        $metalAmount = round($fineWeight * $ratePerGram, 2);

        $categoryId = $product['category_id'] ?? 1;
        $mcRateData = $mcRatesByCategory[$categoryId] ?? ['per_gram' => 200, 'fixed' => 1000];
        $mcType = (rand(1, 100) <= 60) ? 'per_gram' : 'fixed';
        $mcRate = ($mcType == 'per_gram') ? $mcRateData['per_gram'] : $mcRateData['fixed'];
        $mcAmount = ($mcType === 'per_gram') ? round($weight * $mcRate, 2) : round($quantity * $mcRate, 2);

        $itemTotal = round($metalAmount + $mcAmount, 2);

        $totalMetalAmount += $metalAmount;
        $totalMakingAmount += $mcAmount;
        $subtotal += $itemTotal;

        $invoiceItems[] = [
          'product_id' => $product['id'],
          'item_name' => $product['name'],
          'metal_type' => $product['metal_type'],
          'purity' => $product['purity'],
          'quantity' => $quantity,
          'gross_weight' => $weight,
          'net_weight' => $fineWeight,
          'rate_per_gram' => $ratePerGram,
          'metal_amount' => $metalAmount,
          'making_charge_type' => $mcType,
          'making_charge_rate' => $mcRate,
          'making_charge_amount' => $mcAmount,
          'item_total' => $itemTotal,
        ];
      }

      // GST
      $metalGST = $totalMetalAmount * 0.03;
      $makingGST = $totalMakingAmount * 0.05;
      $totalGST = $metalGST + $makingGST;

      if ($isIntraState) {
        $cgst = $totalGST / 2;
        $sgst = $totalGST / 2;
        $igst = 0;
      } else {
        $cgst = 0;
        $sgst = 0;
        $igst = $totalGST;
      }

      $totalAmount = $subtotal + $totalGST;

      // Payment status: 60% paid, 25% partial, 15% pending
      $paymentRand = rand(1, 100);
      if ($paymentRand <= 60) {
        $paidAmount = $totalAmount;
        $paymentStatus = 'paid';
      } elseif ($paymentRand <= 85) {
        $paidAmount = round($totalAmount * rand(50, 80) / 100, 2);
        $paymentStatus = 'partial';
      } else {
        $paidAmount = 0;
        $paymentStatus = 'pending';
      }

      $balanceAmount = $totalAmount - $paidAmount;

      // Insert invoice
      $stmt = $db->prepare("INSERT INTO invoices 
                (invoice_no, customer_id, invoice_date, due_date, subtotal, discount_amount, taxable_amount, 
                 cgst_amount, sgst_amount, igst_amount, total_amount, paid_amount, balance_amount, payment_status, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

      $stmt->execute([
        $invoiceNo,
        $customer['id'],
        $invoiceDate,
        $dueDate,
        $subtotal,
        0,
        $subtotal,
        $cgst,
        $sgst,
        $igst,
        $totalAmount,
        $paidAmount,
        $balanceAmount,
        $paymentStatus,
        '',
        1,
      ]);

      $invoiceId = $db->lastInsertId();

      // Insert items
      $stmt = $db->prepare("INSERT INTO invoice_items 
                (invoice_id, product_id, item_name, metal_type, purity, quantity, gross_weight, net_weight, 
                 wastage_percent, wastage_weight, total_weight, rate_per_gram, metal_amount,
                 making_charge_type, making_charge_rate, making_charge_amount, item_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

      foreach ($invoiceItems as $item) {
        $stmt->execute([
          $invoiceId,
          $item['product_id'],
          $item['item_name'],
          $item['metal_type'],
          $item['purity'],
          $item['quantity'],
          $item['gross_weight'],
          $item['net_weight'],
          0,
          0,
          $item['gross_weight'],
          $item['rate_per_gram'],
          $item['metal_amount'],
          $item['making_charge_type'],
          $item['making_charge_rate'],
          $item['making_charge_amount'],
          $item['item_total'],
        ]);
      }

      // Insert payment if paid
      if ($paidAmount > 0) {
        $paymentMethods = ['cash', 'bank', 'upi'];
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

        $stmt = $db->prepare("INSERT INTO payments 
                    (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$customer['id'], $invoiceId, $invoiceDate, $paidAmount, $paymentMethod, '', '', 1]);
      }

      // Update customer balance
      if ($balanceAmount > 0) {
        $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?");
        $stmt->execute([$balanceAmount, $customer['id']]);
      }

      // Ledger entries
      $stmt = $db->prepare("INSERT INTO customer_ledger 
                (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
                VALUES (?, ?, 'invoice', ?, ?, ?, 0, ?, ?)");
      $stmt->execute([$customer['id'], $invoiceDate, $invoiceId, $invoiceNo, $totalAmount, $totalAmount, '']);

      if ($paidAmount > 0) {
        $stmt = $db->prepare("INSERT INTO customer_ledger 
                    (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
                    VALUES (?, ?, 'payment', ?, ?, 0, ?, ?, ?)");
        $stmt->execute([$customer['id'], $invoiceDate, $invoiceId, $invoiceNo, $paidAmount, $balanceAmount, '']);
      }

      $totalInvoicesCreated++;

      // Progress indicator
      if ($totalInvoicesCreated % 50 == 0) {
        echo "✓ Created $totalInvoicesCreated invoices...\n";
      }
    }
  }

  $db->commit();

  echo "\n" . str_repeat("=", 100) . "\n";
  echo "\n✅ Successfully created $totalInvoicesCreated old invoices!\n\n";

  // Summary
  $stmt = $db->query("SELECT COUNT(*) as total, SUM(total_amount) as total_sales, SUM(paid_amount) as total_paid, SUM(balance_amount) as total_balance FROM invoices");
  $summary = $stmt->fetch();

  $stmt = $db->query("SELECT payment_status, COUNT(*) as count FROM invoices GROUP BY payment_status");
  $statusCounts = $stmt->fetchAll();

  echo "📊 INVOICE SUMMARY:\n";
  echo "Total Invoices: " . $summary['total'] . "\n";
  echo "Total Sales: ₹" . number_format($summary['total_sales'], 2) . "\n";
  echo "Total Paid: ₹" . number_format($summary['total_paid'], 2) . "\n";
  echo "Total Balance: ₹" . number_format($summary['total_balance'], 2) . "\n\n";

  echo "Payment Status:\n";
  foreach ($statusCounts as $status) {
    echo "  " . ucfirst($status['payment_status']) . ": " . $status['count'] . " invoices\n";
  }

  echo "\n✅ Old invoices added successfully! Check customer profiles to see complete history.\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "❌ Error: " . $e->getMessage() . "\n";
  echo "Trace: " . $e->getTraceAsString() . "\n";
}
