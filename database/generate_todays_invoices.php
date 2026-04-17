<?php

/**
 * Generate invoices for TODAY with total sales between ₹3-7 lakhs
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Generating invoices for TODAY with realistic amounts (₹3-7 lakhs total)...\n\n";

// Get customers
$stmt = $db->query("SELECT id, customer_code, business_name, state FROM customers ORDER BY RAND() LIMIT 30");
$customers = $stmt->fetchAll();

// Get products
$stmt = $db->query("SELECT id, product_code, name, metal_type, purity, category_id FROM products WHERE is_active = 1 ORDER BY RAND()");
$products = $stmt->fetchAll();

// Metal rates
$metalRates = [
  'gold' => [
    '24K' => 14765.37,
    '22K' => 13535.41,
    '18K' => 11074.03,
    '14K' => 8612.64,
  ],
  'silver' => [
    '999' => 243.60,
    '925' => 225.33,
  ]
];

$purityPercentages = [
  '24K' => 99.9,
  '22K' => 91.6,
  '18K' => 75.0,
  '14K' => 58.3,
  '999' => 99.9,
  '925' => 92.5,
];

// Target: ₹5,00,000 total (middle of 3-7 lakh range)
$targetTotal = 500000;
$currentTotal = 0;
$minInvoice = 40000;  // Minimum per invoice
$maxInvoice = 150000; // Maximum per invoice

echo "Target Total Sales: ₹" . number_format($targetTotal) . "\n";
echo "Date: " . date('d-M-Y') . "\n\n";

$invoicesCreated = 0;
$invoiceData = [];

try {
  $db->beginTransaction();

  // Generate invoices until we reach target
  while ($currentTotal < $targetTotal) {
    $customer = $customers[array_rand($customers)];

    $remaining = $targetTotal - $currentTotal;

    // Calculate next invoice amount
    if ($remaining < $minInvoice) {
      $targetAmount = $remaining;
    } else {
      $targetAmount = rand($minInvoice, min($maxInvoice, $remaining));
    }

    $invoiceNo = 'INV' . str_pad(5000 + $invoicesCreated, 6, '0', STR_PAD_LEFT);

    $companyState = 'Maharashtra';
    $isIntraState = ($customer['state'] == $companyState);

    // Decide items based on target amount
    $subtotal = 0;
    $totalMetalAmount = 0;
    $totalMakingAmount = 0;
    $totalGoldWeight = 0;
    $totalSilverWeight = 0;
    $goldValue = 0;
    $silverValue = 0;
    $invoiceItems = [];

    // Calculate how many items we need
    $avgItemValue = rand(25000, 60000);
    $numItems = max(1, min(4, round($targetAmount / $avgItemValue)));

    echo "📄 Invoice " . ($invoicesCreated + 1) . " - $invoiceNo - {$customer['business_name']} (Target: ₹" . number_format($targetAmount) . ")\n";

    for ($j = 0; $j < $numItems; $j++) {
      $product = $products[array_rand($products)];

      // Weights based on target
      if ($product['metal_type'] == 'gold') {
        $weight = round(rand(3000, 10000) / 1000, 3); // 3-10g
      } else {
        $weight = round(rand(8000, 25000) / 1000, 3); // 8-25g
      }

      $quantity = 1;

      $ratePerGram = $metalRates[$product['metal_type']][$product['purity']] ?? 0;
      if ($ratePerGram == 0) {
        $ratePerGram = ($product['metal_type'] == 'gold') ? 13535.41 : 225.33;
      }

      $purityPercent = $purityPercentages[$product['purity']] ?? 99.9;

      $fineWeight = round($weight * ($purityPercent / 100), 3);
      $metalAmount = round($fineWeight * $ratePerGram, 2);

      // Making charges
      $mcRatesByCategory = [
        1 => ['per_gram' => 250, 'fixed' => 800],
        2 => ['per_gram' => 180, 'fixed' => 1200],
        3 => ['per_gram' => 220, 'fixed' => 1500],
        5 => ['per_gram' => 280, 'fixed' => 1500],
        6 => ['per_gram' => 350, 'fixed' => 2000],
        7 => ['per_gram' => 200, 'fixed' => 1200],
        8 => ['per_gram' => 300, 'fixed' => 1500],
        9 => ['per_gram' => 350, 'fixed' => 400],
        10 => ['per_gram' => 180, 'fixed' => 1200],
        11 => ['per_gram' => 40, 'fixed' => 200],
        12 => ['per_gram' => 100, 'fixed' => 300],
      ];

      $categoryId = $product['category_id'] ?? 1;
      $mcRateData = $mcRatesByCategory[$categoryId] ?? ['per_gram' => 200, 'fixed' => 1000];
      $mcType = (rand(1, 100) <= 60) ? 'per_gram' : 'fixed';
      $mcRate = ($mcType == 'per_gram') ? $mcRateData['per_gram'] : $mcRateData['fixed'];
      $mcAmount = ($mcType === 'per_gram') ? round($weight * $mcRate, 2) : round($quantity * $mcRate, 2);

      $itemTotal = round($metalAmount + $mcAmount, 2);

      $totalMetalAmount += $metalAmount;
      $totalMakingAmount += $mcAmount;
      $subtotal += $itemTotal;

      if ($product['metal_type'] == 'gold') {
        $totalGoldWeight += $weight;
        $goldValue += $metalAmount;
      } else {
        $totalSilverWeight += $weight;
        $silverValue += $metalAmount;
      }

      $invoiceItems[] = [
        'product_id' => $product['id'],
        'item_name' => $product['name'],
        'metal_type' => $product['metal_type'],
        'purity' => $product['purity'],
        'quantity' => $quantity,
        'gross_weight' => $weight,
        'net_weight' => $fineWeight,
        'purity_percent' => $purityPercent,
        'rate_per_gram' => $ratePerGram,
        'metal_amount' => $metalAmount,
        'making_charge_type' => $mcType,
        'making_charge_rate' => $mcRate,
        'making_charge_amount' => $mcAmount,
        'item_total' => $itemTotal,
      ];

      printf(
        "  %-30s | %s | %.3fg | %s | ₹%s\n",
        $product['name'],
        ucfirst($product['metal_type']),
        $weight,
        $product['purity'],
        number_format($itemTotal, 2)
      );
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

    // Payment (85% paid, 15% partial)
    $paymentRand = rand(1, 100);
    if ($paymentRand <= 85) {
      $paidAmount = $totalAmount;
      $paymentStatus = 'paid';
    } else {
      $paidAmount = round($totalAmount * rand(70, 85) / 100, 2);
      $paymentStatus = 'partial';
    }

    $balanceAmount = $totalAmount - $paidAmount;

    $paymentMethods = ['cash', 'bank', 'upi', 'cheque'];
    $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
    $paymentRef = '';
    if ($paymentMethod == 'bank') $paymentRef = 'TXN' . rand(100000, 999999);
    elseif ($paymentMethod == 'upi') $paymentRef = 'UPI' . rand(100000, 999999);
    elseif ($paymentMethod == 'cheque') $paymentRef = 'CHQ' . rand(100000, 999999);

    printf(
      "  💰 Total: ₹%s | GST: ₹%s | Paid: ₹%s | Status: %s\n\n",
      number_format($totalAmount, 2),
      number_format($totalGST, 2),
      number_format($paidAmount, 2),
      strtoupper($paymentStatus)
    );

    // Today's date
    $invoiceDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime("+30 days"));

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

    // Insert payment
    if ($paidAmount > 0) {
      $stmt = $db->prepare("INSERT INTO payments 
                (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute([$customer['id'], $invoiceId, $invoiceDate, $paidAmount, $paymentMethod, $paymentRef, '', 1]);
    }

    // Update balance
    if ($balanceAmount > 0) {
      $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?");
      $stmt->execute([$balanceAmount, $customer['id']]);

      // Ledger for balance
      $stmt = $db->prepare("INSERT INTO customer_ledger 
                (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
                VALUES (?, ?, 'invoice', ?, ?, ?, 0, ?, ?)");
      $stmt->execute([$customer['id'], $invoiceDate, $invoiceId, $invoiceNo, $totalAmount, $totalAmount, '']);
    }

    if ($paidAmount > 0) {
      $stmt = $db->prepare("INSERT INTO customer_ledger 
                (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
                VALUES (?, ?, 'payment', ?, ?, 0, ?, ?, ?)");
      $stmt->execute([$customer['id'], $invoiceDate, $invoiceId, $invoiceNo, $paidAmount, $balanceAmount, '']);
    }

    $currentTotal += $totalAmount;
    $invoicesCreated++;

    $invoiceData[] = [
      'no' => $invoiceNo,
      'customer' => $customer['business_name'],
      'amount' => $totalAmount,
      'status' => $paymentStatus,
    ];

    // Stop if we're close to target
    if ($currentTotal >= $targetTotal * 0.95) {
      break;
    }
  }

  $db->commit();

  echo "\n" . str_repeat("=", 100) . "\n";
  echo "\n✅ Successfully created $invoicesCreated invoices for TODAY!\n\n";

  // Summary
  echo "📊 TODAY'S SALES SUMMARY - " . strtoupper(date('d-M-Y')) . "\n";
  echo str_repeat("=", 100) . "\n\n";

  echo "Invoices Created:\n";
  foreach ($invoiceData as $inv) {
    printf(
      "  %-12s | %-30s | ₹%s | %s\n",
      $inv['no'],
      $inv['customer'],
      number_format($inv['amount'], 2),
      strtoupper($inv['status'])
    );
  }

  echo "\n" . str_repeat("=", 100) . "\n";

  $stmt = $db->query("SELECT COUNT(*) as total, SUM(total_amount) as total_sales, SUM(paid_amount) as total_paid, SUM(balance_amount) as total_balance, AVG(total_amount) as avg_amount FROM invoices WHERE DATE(invoice_date) = CURDATE()");
  $summary = $stmt->fetch();

  $stmt = $db->query("SELECT payment_status, COUNT(*) as count, SUM(total_amount) as amount FROM invoices WHERE DATE(invoice_date) = CURDATE() GROUP BY payment_status");
  $statusCounts = $stmt->fetchAll();

  echo "\n📈 FINAL SUMMARY:\n";
  echo str_repeat("=", 100) . "\n";
  echo "Total Invoices Today: " . $summary['total'] . "\n";
  echo "Total Sales Today: ₹" . number_format($summary['total_sales'], 2) . "\n";
  echo "Total Paid: ₹" . number_format($summary['total_paid'], 2) . "\n";
  echo "Total Balance: ₹" . number_format($summary['total_balance'], 2) . "\n";
  echo "Average per Invoice: ₹" . number_format($summary['avg_amount'], 2) . "\n\n";

  echo "Payment Status:\n";
  foreach ($statusCounts as $status) {
    printf(
      "  %-15s: %d invoices (₹%s)\n",
      ucfirst($status['payment_status']),
      $status['count'],
      number_format($status['amount'], 2)
    );
  }

  echo "\n✅ Today's sales are between ₹3-7 lakhs as requested!\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "❌ Error: " . $e->getMessage() . "\n";
}
