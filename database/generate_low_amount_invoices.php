<?php

/**
 * Generate 15 invoices with realistic LOW amounts (₹50,000 - ₹3,00,000 range)
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Generating invoices with realistic amounts...\n\n";

// Get customers
$stmt = $db->query("SELECT id, customer_code, business_name, state FROM customers ORDER BY id LIMIT 50");
$customers = $stmt->fetchAll();

// Get products
$stmt = $db->query("SELECT id, product_code, name, metal_type, purity, category_id FROM products WHERE is_active = 1 ORDER BY name");
$products = $stmt->fetchAll();

// Get metal rates
$stmt = $db->query("SELECT metal_type, purity, rate_per_gram FROM metal_rates WHERE rate_date = CURDATE()");
$rates = $stmt->fetchAll();

$metalRates = [];
foreach ($rates as $rate) {
  $metalRates[$rate['metal_type']][$rate['purity']] = $rate['rate_per_gram'];
}

if (empty($metalRates['gold']['22K'])) {
  $metalRates['gold']['24K'] = 14765.37;
  $metalRates['gold']['22K'] = 13535.41;
  $metalRates['gold']['18K'] = 11074.03;
  $metalRates['silver']['999'] = 243.60;
  $metalRates['silver']['925'] = 225.33;
}

echo "Metal Rates:\n";
foreach ($metalRates as $metal => $purities) {
  foreach ($purities as $purity => $rate) {
    echo "  " . ucfirst($metal) . " $purity: ₹" . number_format($rate, 2) . "/gram\n";
  }
}
echo "\n";

// Purity percentages
$purityPercentages = [
  '24K' => 99.9,
  '22K' => 91.6,
  '18K' => 75.0,
  '14K' => 58.3,
  '999' => 99.9,
  '925' => 92.5,
];

// MC rates by category (lower values)
$mcRatesByCategory = [
  1 => ['per_gram' => 250, 'fixed' => 800],    // Rings
  2 => ['per_gram' => 180, 'fixed' => 1200],   // Chains
  3 => ['per_gram' => 220, 'fixed' => 1500],   // Bangles
  5 => ['per_gram' => 280, 'fixed' => 1500],   // Earrings
  6 => ['per_gram' => 350, 'fixed' => 2000],   // Necklaces
  7 => ['per_gram' => 200, 'fixed' => 1200],   // Bracelets
  8 => ['per_gram' => 300, 'fixed' => 1500],   // Mangalsutra
  9 => ['per_gram' => 350, 'fixed' => 400],    // Nose Pin
  10 => ['per_gram' => 180, 'fixed' => 1200],  // Anklet
  11 => ['per_gram' => 40, 'fixed' => 200],    // Toe Ring
  12 => ['per_gram' => 100, 'fixed' => 300],   // Coins/Bars
];

$totalInvoices = 15;
$invoicesCreated = 0;

echo "Generating $totalInvoices invoices (₹50,000 - ₹3,00,000 range)...\n";
echo str_repeat("=", 120) . "\n\n";

try {
  $db->beginTransaction();

  for ($i = 0; $i < $totalInvoices; $i++) {
    $customer = $customers[array_rand($customers)];

    $daysAgo = rand(0, 30);
    $invoiceDate = date('Y-m-d', strtotime("-$daysAgo days"));
    $dueDate = date('Y-m-d', strtotime("+30 days", strtotime($invoiceDate)));
    $invoiceNo = 'INV' . str_pad(4000 + $i, 6, '0', STR_PAD_LEFT);

    $companyState = 'Maharashtra';
    $isIntraState = ($customer['state'] == $companyState);

    // 1-2 items per invoice (lower items = lower amount)
    $numItems = rand(1, 2);
    $subtotal = 0;
    $totalMetalAmount = 0;
    $totalMakingAmount = 0;
    $totalGoldWeight = 0;
    $totalSilverWeight = 0;
    $goldValue = 0;
    $silverValue = 0;
    $invoiceItems = [];

    echo "📄 Invoice " . ($i + 1) . " - $invoiceNo - {$customer['business_name']}\n";

    for ($j = 0; $j < $numItems; $j++) {
      $product = $products[array_rand($products)];

      // LOWER weights: Gold 2-8g, Silver 5-20g
      if ($product['metal_type'] == 'gold') {
        $weight = round(rand(2000, 8000) / 1000, 3); // 2-8 grams
      } else {
        $weight = round(rand(5000, 20000) / 1000, 3); // 5-20 grams
      }

      $quantity = 1; // Always 1 piece to keep amounts low

      $ratePerGram = $metalRates[$product['metal_type']][$product['purity']] ?? 0;
      if ($ratePerGram == 0) {
        $ratePerGram = ($product['metal_type'] == 'gold') ? 13535.41 : 225.33;
      }

      $purityPercent = $purityPercentages[$product['purity']] ?? 99.9;

      // 15% chance of custom purity
      if (rand(1, 100) <= 15) {
        $customPurities = [
          ['label' => '20K', 'percent' => 83.3],
          ['label' => '916', 'percent' => 91.6],
          ['label' => '750', 'percent' => 75.0],
        ];
        $customPurity = $customPurities[array_rand($customPurities)];
        $purityDisplay = $customPurity['label'];
        $purityPercent = $customPurity['percent'];
      } else {
        $purityDisplay = $product['purity'];
      }

      $fineWeight = round($weight * ($purityPercent / 100), 3);
      $metalAmount = round($fineWeight * $ratePerGram, 2);

      // Making charges
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
        'purity' => $purityDisplay,
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
        "  %-30s | %s | %.3fg | %s (%.1f%%) | Fine: %.3fg | Metal: ₹%s | MC: ₹%s | Total: ₹%s\n",
        $product['name'],
        ucfirst($product['metal_type']),
        $weight,
        $purityDisplay,
        $purityPercent,
        $fineWeight,
        number_format($metalAmount, 2),
        number_format($mcAmount, 2),
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

    // Small adjustments (or none)
    $oldGoldValue = (rand(0, 10) < 1) ? round(rand(2000, 10000), 2) : 0; // 10% chance
    $silverReturn = 0;
    $discount = (rand(0, 10) < 2) ? round(rand(500, 2000), 2) : 0; // 20% chance
    $otherCharges = 0;

    $totalAmount = $subtotal + $totalGST - $oldGoldValue - $silverReturn - $discount + $otherCharges;

    // Payment (80% paid, 15% partial, 5% pending)
    $paymentRand = rand(1, 100);
    if ($paymentRand <= 80) {
      $paidAmount = $totalAmount;
      $paymentStatus = 'paid';
    } elseif ($paymentRand <= 95) {
      $paidAmount = round($totalAmount * rand(60, 80) / 100, 2);
      $paymentStatus = 'partial';
    } else {
      $paidAmount = 0;
      $paymentStatus = 'pending';
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
      $discount,
      $subtotal,
      $cgst,
      $sgst,
      $igst,
      $totalAmount,
      $paidAmount,
      $balanceAmount,
      $paymentStatus,
      "Old Gold: ₹$oldGoldValue, Silver Return: ₹$silverReturn, Other: ₹$otherCharges",
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
    $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?");
    $stmt->execute([$balanceAmount, $customer['id']]);

    // Ledger
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

    $invoicesCreated++;
  }

  $db->commit();

  echo "\n" . str_repeat("=", 120) . "\n";
  echo "\n✅ Successfully created $invoicesCreated invoices!\n\n";

  // Summary
  $stmt = $db->query("SELECT COUNT(*) as total FROM invoices");
  $totalInvoices = $stmt->fetch()['total'];

  $stmt = $db->query("SELECT SUM(total_amount) as total_sales, SUM(paid_amount) as total_paid, SUM(balance_amount) as total_balance, AVG(total_amount) as avg_amount FROM invoices");
  $summary = $stmt->fetch();

  $stmt = $db->query("SELECT payment_status, COUNT(*) as count FROM invoices GROUP BY payment_status");
  $statusCounts = $stmt->fetchAll();

  echo "📊 INVOICE SUMMARY:\n";
  echo "Total Invoices: $totalInvoices\n";
  echo "Total Sales: ₹" . number_format($summary['total_sales'], 2) . "\n";
  echo "Total Paid: ₹" . number_format($summary['total_paid'], 2) . "\n";
  echo "Total Balance: ₹" . number_format($summary['total_balance'], 2) . "\n";
  echo "Average per Invoice: ₹" . number_format($summary['avg_amount'], 2) . "\n\n";

  echo "Payment Status:\n";
  foreach ($statusCounts as $status) {
    echo "  " . ucfirst($status['payment_status']) . ": " . $status['count'] . " invoices\n";
  }

  echo "\n✅ Realistic invoices created with lower amounts!\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "❌ Error: " . $e->getMessage() . "\n";
}
