<?php

/**
 * Generate 15 invoices with exact format including purity %, fine weight, MC types, adjustments, payments
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Starting invoice generation with detailed format...\n\n";

// Get all customers
$stmt = $db->query("SELECT id, customer_code, business_name, state FROM customers ORDER BY id LIMIT 50");
$customers = $stmt->fetchAll();

if (empty($customers)) {
  die("Error: No customers found. Please add customers first.\n");
}

echo "Using " . count($customers) . " customers\n";

// Get current metal rates
$stmt = $db->query("SELECT metal_type, purity, rate_per_gram FROM metal_rates WHERE rate_date = CURDATE()");
$rates = $stmt->fetchAll();

$metalRates = [];
foreach ($rates as $rate) {
  $metalRates[$rate['metal_type']][$rate['purity']] = $rate['rate_per_gram'];
}

// Default rates if not found
if (empty($metalRates['gold']['24K'])) {
  $metalRates['gold']['24K'] = 14765.37;
  $metalRates['gold']['22K'] = 13306.08;
  $metalRates['gold']['18K'] = 10886.40;
  $metalRates['silver']['999'] = 229.06;
  $metalRates['silver']['925'] = 211.88;
}

echo "Current Metal Rates:\n";
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

// Custom purity options (realistic jewellery purities used in Indian market)
$customPurities = [
  ['label' => '20K', 'percent' => 83.3],
  ['label' => '16K', 'percent' => 66.6],
  ['label' => '21K', 'percent' => 87.5],
  ['label' => '19K', 'percent' => 79.2],
  ['label' => '835', 'percent' => 83.5], // European standard
  ['label' => '916', 'percent' => 91.6], // BIS hallmark 22K
  ['label' => '750', 'percent' => 75.0], // BIS hallmark 18K
  ['label' => '585', 'percent' => 58.5], // BIS hallmark 14K
  ['label' => '375', 'percent' => 37.5], // 9K gold
  ['label' => '950', 'percent' => 95.0], // Platinum standard (sometimes used)
];

// Item templates with realistic data
$itemTemplates = [
  'gold' => [
    '24K' => [
      ['name' => 'Gold Coin 10g', 'weight' => 10.000, 'mc_type' => 'per_gram', 'mc_rate' => 150.00],
      ['name' => 'Gold Coin 20g', 'weight' => 20.000, 'mc_type' => 'per_gram', 'mc_rate' => 150.00],
      ['name' => 'Gold Bar 50g', 'weight' => 50.000, 'mc_type' => 'fixed', 'mc_rate' => 500.00],
    ],
    '22K' => [
      ['name' => 'Gold Ring', 'weight' => 5.500, 'mc_type' => 'per_gram', 'mc_rate' => 350.00],
      ['name' => 'Gold Chain', 'weight' => 15.200, 'mc_type' => 'per_gram', 'mc_rate' => 280.00],
      ['name' => 'Gold Bangle', 'weight' => 25.800, 'mc_type' => 'per_gram', 'mc_rate' => 320.00],
      ['name' => 'Gold Necklace', 'weight' => 35.500, 'mc_type' => 'per_gram', 'mc_rate' => 450.00],
      ['name' => 'Gold Earrings', 'weight' => 6.800, 'mc_type' => 'per_gram', 'mc_rate' => 380.00],
      ['name' => 'Gold Pendant', 'weight' => 8.200, 'mc_type' => 'per_gram', 'mc_rate' => 340.00],
      ['name' => 'Gold Bracelet', 'weight' => 18.500, 'mc_type' => 'per_gram', 'mc_rate' => 300.00],
      ['name' => 'Gold Mangalsutra', 'weight' => 22.300, 'mc_type' => 'per_gram', 'mc_rate' => 400.00],
    ],
    '18K' => [
      ['name' => '18K Gold Ring', 'weight' => 4.200, 'mc_type' => 'per_gram', 'mc_rate' => 400.00],
      ['name' => '18K Gold Chain', 'weight' => 12.500, 'mc_type' => 'per_gram', 'mc_rate' => 320.00],
      ['name' => '18K Gold Earrings', 'weight' => 5.800, 'mc_type' => 'fixed', 'mc_rate' => 2500.00],
      ['name' => '18K Gold Pendant', 'weight' => 7.300, 'mc_type' => 'per_gram', 'mc_rate' => 380.00],
    ],
  ],
  'silver' => [
    '925' => [
      ['name' => 'Silver Anklet', 'weight' => 35.000, 'mc_type' => 'per_gram', 'mc_rate' => 45.00],
      ['name' => 'Silver Chain', 'weight' => 25.500, 'mc_type' => 'per_gram', 'mc_rate' => 35.00],
      ['name' => 'Silver Bracelet', 'weight' => 30.200, 'mc_type' => 'per_gram', 'mc_rate' => 40.00],
      ['name' => 'Silver Ring', 'weight' => 8.500, 'mc_type' => 'fixed', 'mc_rate' => 500.00],
      ['name' => 'Silver Earrings', 'weight' => 7.200, 'mc_type' => 'per_gram', 'mc_rate' => 48.00],
    ],
    '999' => [
      ['name' => 'Silver Coin 25g', 'weight' => 25.000, 'mc_type' => 'fixed', 'mc_rate' => 200.00],
      ['name' => 'Silver Coin 50g', 'weight' => 50.000, 'mc_type' => 'fixed', 'mc_rate' => 300.00],
      ['name' => 'Silver Bar 100g', 'weight' => 100.000, 'mc_type' => 'fixed', 'mc_rate' => 500.00],
    ],
  ],
];

// Generate 15 invoices
$totalInvoices = 15;
$invoicesCreated = 0;

echo "Generating $totalInvoices invoices with detailed format...\n\n";

try {
  $db->beginTransaction();

  for ($i = 0; $i < $totalInvoices; $i++) {
    // Random customer
    $customer = $customers[array_rand($customers)];

    // Invoice date (last 30 days)
    $daysAgo = rand(0, 30);
    $invoiceDate = date('Y-m-d', strtotime("-$daysAgo days"));
    $dueDate = date('Y-m-d', strtotime("+30 days", strtotime($invoiceDate)));

    // Invoice number
    $invoiceNo = 'INV' . str_pad(2000 + $i, 6, '0', STR_PAD_LEFT);

    // Check if same state for GST
    $companyState = 'Maharashtra';
    $isIntraState = ($customer['state'] == $companyState);

    // Generate 1-3 items per invoice
    $numItems = rand(1, 3);
    $subtotal = 0;
    $totalMetalAmount = 0;
    $totalMakingAmount = 0;
    $totalGoldWeight = 0;
    $totalSilverWeight = 0;
    $goldValue = 0;
    $silverValue = 0;
    $invoiceItems = [];

    echo "Invoice " . ($i + 1) . " - $invoiceNo - {$customer['business_name']}\n";
    echo str_repeat("-", 120) . "\n";
    printf(
      "%-3s | %-20s | %-8s | %-4s | %-10s | %-12s | %-10s | %-12s | %-12s | %-10s | %-10s | %-12s | %-12s\n",
      '#',
      'Item Name',
      'Metal',
      'Qty',
      'Weight(g)',
      'Purity',
      'Fine Wt',
      'Rate/g',
      'Metal Amt',
      'MC Type',
      'MC Rate',
      'MC Amt',
      'Total'
    );
    echo str_repeat("-", 120) . "\n";

    for ($j = 0; $j < $numItems; $j++) {
      // Select random metal and purity
      $metalType = (rand(0, 10) < 8) ? 'gold' : 'silver'; // 80% gold

      // 30% chance of custom purity, 70% standard purity
      $useCustomPurity = (rand(1, 100) <= 30);

      if ($useCustomPurity) {
        // Use custom purity
        $customPurity = $customPurities[array_rand($customPurities)];
        $purity = $customPurity['label'];
        $purityPercent = $customPurity['percent'];

        // For custom purity, select appropriate item template based on metal type
        $purityOptions = array_keys($itemTemplates[$metalType]);
        $templatePurity = $purityOptions[array_rand($purityOptions)];
        $templates = $itemTemplates[$metalType][$templatePurity];
      } else {
        // Use standard purity
        $purityOptions = array_keys($itemTemplates[$metalType]);
        $purity = $purityOptions[array_rand($purityOptions)];
        $purityPercent = $purityPercentages[$purity];
        $templates = $itemTemplates[$metalType][$purity];
      }

      // Get item template
      $template = $templates[array_rand($templates)];

      // Add some variation to weight (±10%)
      $variation = rand(-10, 10) / 100;
      $weight = round($template['weight'] * (1 + $variation), 3);
      $quantity = rand(1, 3);

      // Get rate and purity percentage
      $ratePerGram = $metalRates[$metalType][$purity] ?? $metalRates[$metalType][array_keys($metalRates[$metalType])[0]];

      // Calculate fine weight (using purity percentage)
      $fineWeight = round($weight * ($purityPercent / 100), 3);

      // Calculate metal amount (using fine weight)
      $metalAmount = round($fineWeight * $ratePerGram, 2);

      // Making charges
      $mcType = $template['mc_type'];
      $mcRate = $template['mc_rate'];
      $mcAmount = ($mcType === 'per_gram') ? round($weight * $mcRate, 2) : round($quantity * $mcRate, 2);

      // Item total
      $itemTotal = round($metalAmount + $mcAmount, 2);

      // Accumulate totals
      $totalMetalAmount += $metalAmount;
      $totalMakingAmount += $mcAmount;
      $subtotal += $itemTotal;

      if ($metalType == 'gold') {
        $totalGoldWeight += $weight;
        $goldValue += $metalAmount;
      } else {
        $totalSilverWeight += $weight;
        $silverValue += $metalAmount;
      }

      $invoiceItems[] = [
        'item_name' => $template['name'],
        'metal_type' => $metalType,
        'purity' => $purity,
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

      // Print item row
      printf(
        "%-3d | %-20s | %-8s | %-4d | %-10.3f | %-12s | %-10.3f | %-12.2f | %-12.2f | %-10s | %-10.2f | %-12.2f | %-12.2f\n",
        $j + 1,
        $template['name'],
        ucfirst($metalType),
        $quantity,
        $weight,
        "$purity ($purityPercent%)",
        $fineWeight,
        $ratePerGram,
        $metalAmount,
        ucfirst(str_replace('_', ' ', $mcType)),
        $mcRate,
        $mcAmount,
        $itemTotal
      );
    }

    echo str_repeat("-", 120) . "\n";

    // Metal Summary
    echo "\n Metal Summary:\n";
    echo "  Gold: " . number_format($totalGoldWeight, 3) . "g | Value: ₹" . number_format($goldValue, 2) . "\n";
    echo "  Silver: " . number_format($totalSilverWeight, 3) . "g | Value: ₹" . number_format($silverValue, 2) . "\n";

    // GST Calculation
    $metalGST = $totalMetalAmount * 0.03; // 3% on metal
    $makingGST = $totalMakingAmount * 0.05; // 5% on making charges
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

    // Adjustments (random)
    $oldGoldValue = (rand(0, 10) < 2) ? round(rand(5000, 50000), 2) : 0; // 20% chance
    $silverReturn = (rand(0, 10) < 1) ? round(rand(1000, 10000), 2) : 0; // 10% chance
    $discount = (rand(0, 10) < 3) ? round(rand(500, 5000), 2) : 0; // 30% chance
    $otherCharges = (rand(0, 10) < 2) ? round(rand(200, 2000), 2) : 0; // 20% chance

    $adjustments = $oldGoldValue + $silverReturn + $discount + $otherCharges;

    echo "\n Adjustments:\n";
    echo "  Old Gold Value: ₹" . number_format($oldGoldValue, 2) . "\n";
    echo "  Silver Return: ₹" . number_format($silverReturn, 2) . "\n";
    echo "  Discount: ₹" . number_format($discount, 2) . "\n";
    echo "  Other Charges: ₹" . number_format($otherCharges, 2) . "\n";

    // Final calculation
    $totalAmount = $subtotal + $totalGST - $oldGoldValue - $silverReturn - $discount + $otherCharges;

    echo "\n GST: ₹" . number_format($totalGST, 2);
    if ($isIntraState) {
      echo " (CGST: ₹" . number_format($cgst, 2) . " + SGST: ₹" . number_format($sgst, 2) . ")";
    } else {
      echo " (IGST: ₹" . number_format($igst, 2) . ")";
    }
    echo "\n";

    // Payment logic
    $paymentRand = rand(1, 100);
    if ($paymentRand <= 60) {
      // Fully paid
      $paidAmount = $totalAmount;
      $paymentStatus = 'paid';
    } elseif ($paymentRand <= 85) {
      // Partially paid
      $paidAmount = round($totalAmount * rand(50, 80) / 100, 2);
      $paymentStatus = 'partial';
    } else {
      // Pending
      $paidAmount = 0;
      $paymentStatus = 'pending';
    }

    $balanceAmount = $totalAmount - $paidAmount;

    $paymentMethods = ['cash', 'bank', 'upi', 'cheque'];
    $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

    $paymentRef = '';
    if ($paymentMethod == 'bank') {
      $paymentRef = 'TXN' . rand(100000, 999999);
    } elseif ($paymentMethod == 'upi') {
      $paymentRef = 'UPI' . rand(100000, 999999);
    } elseif ($paymentMethod == 'cheque') {
      $paymentRef = 'CHQ' . rand(100000, 999999);
    }

    echo "\n Payment:\n";
    echo "  Paid Amount: ₹" . number_format($paidAmount, 2) . "\n";
    echo "  Mode: " . ucfirst($paymentMethod) . "\n";
    echo "  Ref No: " . ($paymentRef ?: 'N/A') . "\n";
    echo "  Balance: ₹" . number_format($balanceAmount, 2) . "\n";
    echo "  Status: " . ucfirst($paymentStatus) . "\n";
    echo "\n";

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
      1, // Admin user
    ]);

    $invoiceId = $db->lastInsertId();

    // Insert invoice items
    $stmt = $db->prepare("INSERT INTO invoice_items 
            (invoice_id, item_name, metal_type, purity, quantity, gross_weight, net_weight, 
             wastage_percent, wastage_weight, total_weight, rate_per_gram, metal_amount,
             making_charge_type, making_charge_rate, making_charge_amount, item_total) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($invoiceItems as $item) {
      $stmt->execute([
        $invoiceId,
        $item['item_name'],
        $item['metal_type'],
        $item['purity'],
        $item['quantity'],
        $item['gross_weight'],
        $item['net_weight'],
        0, // wastage_percent
        0, // wastage_weight
        $item['gross_weight'], // total_weight
        $item['rate_per_gram'],
        $item['metal_amount'],
        $item['making_charge_type'],
        $item['making_charge_rate'],
        $item['making_charge_amount'],
        $item['item_total'],
      ]);
    }

    // Insert payment if paid or partial
    if ($paidAmount > 0) {
      $stmt = $db->prepare("INSERT INTO payments 
                (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

      $stmt->execute([
        $customer['id'],
        $invoiceId,
        $invoiceDate,
        $paidAmount,
        $paymentMethod,
        $paymentRef,
        '',
        1,
      ]);
    }

    // Update customer balance
    $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?");
    $stmt->execute([$balanceAmount, $customer['id']]);

    // Insert ledger entries
    $stmt = $db->prepare("INSERT INTO customer_ledger 
            (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
            VALUES (?, ?, 'invoice', ?, ?, ?, 0, ?, ?)");

    $stmt->execute([
      $customer['id'],
      $invoiceDate,
      $invoiceId,
      $invoiceNo,
      $totalAmount,
      $totalAmount,
      '',
    ]);

    if ($paidAmount > 0) {
      $stmt = $db->prepare("INSERT INTO customer_ledger 
                (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
                VALUES (?, ?, 'payment', ?, ?, 0, ?, ?, ?)");

      $stmt->execute([
        $customer['id'],
        $invoiceDate,
        $invoiceId,
        $invoiceNo,
        $paidAmount,
        $balanceAmount,
        '',
      ]);
    }

    $invoicesCreated++;
    echo "\n" . str_repeat("=", 120) . "\n\n";
  }

  $db->commit();

  echo "\n✓ Successfully created $invoicesCreated invoices!\n\n";

  // Summary
  $stmt = $db->query("SELECT COUNT(*) as total FROM invoices");
  $totalInvoices = $stmt->fetch()['total'];

  $stmt = $db->query("SELECT SUM(total_amount) as total_sales, SUM(paid_amount) as total_paid, SUM(balance_amount) as total_balance FROM invoices");
  $summary = $stmt->fetch();

  $stmt = $db->query("SELECT payment_status, COUNT(*) as count FROM invoices GROUP BY payment_status");
  $statusCounts = $stmt->fetchAll();

  echo "=== INVOICE SUMMARY ===\n";
  echo "Total Invoices: $totalInvoices\n";
  echo "Total Sales: ₹" . number_format($summary['total_sales'], 2) . "\n";
  echo "Total Paid: ₹" . number_format($summary['total_paid'], 2) . "\n";
  echo "Total Balance: ₹" . number_format($summary['total_balance'], 2) . "\n\n";

  echo "Payment Status:\n";
  foreach ($statusCounts as $status) {
    echo "  " . ucfirst($status['payment_status']) . ": " . $status['count'] . " invoices\n";
  }

  echo "\nDone!\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "Error: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
