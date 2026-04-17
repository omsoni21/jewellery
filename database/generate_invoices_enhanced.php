<?php

/**
 * Generate 15 invoices using products from database with detailed calculations and display
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Starting enhanced invoice generation with product database...\n\n";

// Get all customers
$stmt = $db->query("SELECT id, customer_code, business_name, state FROM customers ORDER BY id LIMIT 50");
$customers = $stmt->fetchAll();

// Get all products from database
$stmt = $db->query("SELECT id, product_code, name, metal_type, purity, category_id FROM products WHERE is_active = 1 ORDER BY name");
$products = $stmt->fetchAll();

echo "Found " . count($customers) . " customers\n";
echo "Found " . count($products) . " products in database\n\n";

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
  $metalRates['gold']['22K'] = 13535.41;
  $metalRates['gold']['18K'] = 11074.03;
  $metalRates['silver']['999'] = 243.60;
  $metalRates['silver']['925'] = 225.33;
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

// Custom purities
$customPurities = [
  ['label' => '20K', 'percent' => 83.3],
  ['label' => '21K', 'percent' => 87.5],
  ['label' => '916', 'percent' => 91.6],
  ['label' => '750', 'percent' => 75.0],
  ['label' => '585', 'percent' => 58.5],
  ['label' => '375', 'percent' => 37.5],
];

// Making charge rates by category
$mcRatesByCategory = [
  1 => ['per_gram' => 350, 'fixed' => 1500],   // Rings
  2 => ['per_gram' => 280, 'fixed' => 2000],   // Chains
  3 => ['per_gram' => 320, 'fixed' => 2500],   // Bangles
  5 => ['per_gram' => 380, 'fixed' => 2500],   // Earrings
  6 => ['per_gram' => 450, 'fixed' => 3000],   // Necklaces
  7 => ['per_gram' => 300, 'fixed' => 2000],   // Bracelets
  8 => ['per_gram' => 400, 'fixed' => 2500],   // Mangalsutra
  9 => ['per_gram' => 450, 'fixed' => 500],    // Nose Pin
  10 => ['per_gram' => 280, 'fixed' => 2000],  // Anklet
  11 => ['per_gram' => 50, 'fixed' => 300],    // Toe Ring
  12 => ['per_gram' => 150, 'fixed' => 500],   // Coins/Bars
];

// Generate 15 invoices
$totalInvoices = 15;
$invoicesCreated = 0;

echo "Generating $totalInvoices invoices with detailed calculations...\n";
echo str_repeat("=", 150) . "\n\n";

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
    $invoiceNo = 'INV' . str_pad(3000 + $i, 6, '0', STR_PAD_LEFT);

    // Check if same state for GST
    $companyState = 'Maharashtra';
    $isIntraState = ($customer['state'] == $companyState);

    // Generate 2-4 items per invoice
    $numItems = rand(2, 4);
    $subtotal = 0;
    $totalMetalAmount = 0;
    $totalMakingAmount = 0;
    $totalGoldWeight = 0;
    $totalSilverWeight = 0;
    $goldValue = 0;
    $silverValue = 0;
    $invoiceItems = [];

    echo "📄 Invoice " . ($i + 1) . " - $invoiceNo\n";
    echo "Customer: {$customer['business_name']} ({$customer['customer_code']})\n";
    echo "Date: " . date('d-m-Y', strtotime($invoiceDate)) . "\n";
    echo str_repeat("-", 150) . "\n";

    // Header
    printf(
      "%-4s | %-30s | %-8s | %-6s | %-10s | %-12s | %-10s | %-12s | %-12s | %-10s | %-10s | %-12s | %-12s\n",
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
    echo str_repeat("-", 150) . "\n";

    for ($j = 0; $j < $numItems; $j++) {
      // Random product from database
      $product = $products[array_rand($products)];

      // Add some variation to weight (±15%)
      $baseWeight = ($product['metal_type'] == 'gold') ? rand(3000, 50000) / 1000 : rand(5000, 100000) / 1000;
      $variation = rand(-15, 15) / 100;
      $weight = round($baseWeight * (1 + $variation), 3);
      $quantity = rand(1, 3);

      // Get rate
      $ratePerGram = $metalRates[$product['metal_type']][$product['purity']] ?? 0;
      if ($ratePerGram == 0) {
        $ratePerGram = ($product['metal_type'] == 'gold') ? 13535.41 : 225.33;
      }

      // Get purity percentage
      $purityPercent = $purityPercentages[$product['purity']] ?? 99.9;

      // 20% chance of custom purity
      if (rand(1, 100) <= 20) {
        $customPurity = $customPurities[array_rand($customPurities)];
        $purityDisplay = $customPurity['label'];
        $purityPercent = $customPurity['percent'];
      } else {
        $purityDisplay = $product['purity'];
      }

      // Calculate fine weight
      $fineWeight = round($weight * ($purityPercent / 100), 3);

      // Calculate metal amount (using fine weight)
      $metalAmount = round($fineWeight * $ratePerGram, 2);

      // Making charges
      $categoryId = $product['category_id'] ?? 1;
      $mcRateData = $mcRatesByCategory[$categoryId] ?? ['per_gram' => 300, 'fixed' => 2000];

      // Random MC type
      $mcType = (rand(1, 100) <= 70) ? 'per_gram' : 'fixed';
      $mcRate = ($mcType == 'per_gram') ? $mcRateData['per_gram'] : $mcRateData['fixed'];
      $mcAmount = ($mcType === 'per_gram') ? round($weight * $mcRate, 2) : round($quantity * $mcRate, 2);

      // Item total
      $itemTotal = round($metalAmount + $mcAmount, 2);

      // Accumulate totals
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

      // Print item row
      printf(
        "%-4d | %-30s | %-8s | %-6d | %-10.3f | %-12s | %-10.3f | %-12.2f | %-12.2f | %-10s | %-10.2f | %-12.2f | %-12.2f\n",
        $j + 1,
        $product['name'],
        ucfirst($product['metal_type']),
        $quantity,
        $weight,
        "$purityDisplay ($purityPercent%)",
        $fineWeight,
        $ratePerGram,
        $metalAmount,
        ucfirst(str_replace('_', ' ', $mcType)),
        $mcRate,
        $mcAmount,
        $itemTotal
      );
    }

    echo str_repeat("-", 150) . "\n";

    // Metal Summary
    echo "\n📊 METAL SUMMARY:\n";
    echo "  Gold: " . number_format($totalGoldWeight, 3) . "g | Value: ₹" . number_format($goldValue, 2) . "\n";
    echo "  Silver: " . number_format($totalSilverWeight, 3) . "g | Value: ₹" . number_format($silverValue, 2) . "\n";
    echo "  Total Metal Value: ₹" . number_format($totalMetalAmount, 2) . "\n";

    // Making Charges Summary
    echo "\n🔧 MAKING CHARGES:\n";
    echo "  Total Making Charges: ₹" . number_format($totalMakingAmount, 2) . "\n";

    // Subtotal
    echo "\n💰 SUBTOTAL (Metal + Making): ₹" . number_format($subtotal, 2) . "\n";

    // GST Calculation
    $metalGST = $totalMetalAmount * 0.03; // 3% on metal
    $makingGST = $totalMakingAmount * 0.05; // 5% on making charges
    $totalGST = $metalGST + $makingGST;

    if ($isIntraState) {
      $cgst = $totalGST / 2;
      $sgst = $totalGST / 2;
      $igst = 0;
      echo "\n📋 GST CALCULATION:\n";
      echo "  Metal GST (3%): ₹" . number_format($metalGST, 2) . "\n";
      echo "  Making GST (5%): ₹" . number_format($makingGST, 2) . "\n";
      echo "  Total GST: ₹" . number_format($totalGST, 2) . "\n";
      echo "    CGST (50%): ₹" . number_format($cgst, 2) . "\n";
      echo "    SGST (50%): ₹" . number_format($sgst, 2) . "\n";
    } else {
      $cgst = 0;
      $sgst = 0;
      $igst = $totalGST;
      echo "\n📋 GST CALCULATION:\n";
      echo "  Metal GST (3%): ₹" . number_format($metalGST, 2) . "\n";
      echo "  Making GST (5%): ₹" . number_format($makingGST, 2) . "\n";
      echo "  Total GST: ₹" . number_format($totalGST, 2) . "\n";
      echo "    IGST (100%): ₹" . number_format($igst, 2) . "\n";
    }

    // Adjustments
    $oldGoldValue = (rand(0, 10) < 2) ? round(rand(5000, 50000), 2) : 0;
    $silverReturn = (rand(0, 10) < 1) ? round(rand(1000, 10000), 2) : 0;
    $discount = (rand(0, 10) < 3) ? round(rand(500, 5000), 2) : 0;
    $otherCharges = (rand(0, 10) < 2) ? round(rand(200, 2000), 2) : 0;

    echo "\n🔄 ADJUSTMENTS:\n";
    echo "  Old Gold Value: -₹" . number_format($oldGoldValue, 2) . "\n";
    echo "  Silver Return: -₹" . number_format($silverReturn, 2) . "\n";
    echo "  Discount: -₹" . number_format($discount, 2) . "\n";
    echo "  Other Charges: +₹" . number_format($otherCharges, 2) . "\n";
    echo "  Total Adjustments: " . ($oldGoldValue + $silverReturn + $discount + $otherCharges > 0 ? "+" : "-") . "₹" . number_format($oldGoldValue + $silverReturn + $discount + $otherCharges, 2) . "\n";

    // Final calculation
    $totalAmount = $subtotal + $totalGST - $oldGoldValue - $silverReturn - $discount + $otherCharges;

    echo "\n💵 FINAL AMOUNT:\n";
    echo "  Subtotal: ₹" . number_format($subtotal, 2) . "\n";
    echo "  + GST: ₹" . number_format($totalGST, 2) . "\n";
    echo "  - Adjustments: ₹" . number_format($oldGoldValue + $silverReturn + $discount, 2) . "\n";
    echo "  + Other Charges: ₹" . number_format($otherCharges, 2) . "\n";
    echo "  = TOTAL: ₹" . number_format($totalAmount, 2) . "\n";

    // Payment logic
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

    echo "\n💳 PAYMENT:\n";
    echo "  Paid Amount: ₹" . number_format($paidAmount, 2) . "\n";
    echo "  Mode: " . ucfirst($paymentMethod) . "\n";
    echo "  Ref No: " . ($paymentRef ?: 'N/A') . "\n";
    echo "  Balance: ₹" . number_format($balanceAmount, 2) . "\n";
    echo "  Status: " . strtoupper($paymentStatus) . "\n";

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

    // Insert invoice items
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
    echo "\n" . str_repeat("=", 150) . "\n\n";
  }

  $db->commit();

  echo "\n✅ Successfully created $invoicesCreated invoices!\n\n";

  // Summary
  $stmt = $db->query("SELECT COUNT(*) as total FROM invoices");
  $totalInvoices = $stmt->fetch()['total'];

  $stmt = $db->query("SELECT SUM(total_amount) as total_sales, SUM(paid_amount) as total_paid, SUM(balance_amount) as total_balance FROM invoices");
  $summary = $stmt->fetch();

  $stmt = $db->query("SELECT payment_status, COUNT(*) as count FROM invoices GROUP BY payment_status");
  $statusCounts = $stmt->fetchAll();

  echo "📊 INVOICE SUMMARY:\n";
  echo "Total Invoices: $totalInvoices\n";
  echo "Total Sales: ₹" . number_format($summary['total_sales'], 2) . "\n";
  echo "Total Paid: ₹" . number_format($summary['total_paid'], 2) . "\n";
  echo "Total Balance: ₹" . number_format($summary['total_balance'], 2) . "\n\n";

  echo "Payment Status:\n";
  foreach ($statusCounts as $status) {
    echo "  " . ucfirst($status['payment_status']) . ": " . $status['count'] . " invoices\n";
  }

  echo "\n✅ All invoices created with detailed calculations and product database!\n";
} catch (Exception $e) {
  $db->rollBack();
  echo "❌ Error: " . $e->getMessage() . "\n";
  echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
