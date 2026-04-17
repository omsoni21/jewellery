<?php

/**
 * Generate 250-300 realistic invoices for jewellery billing system
 */

require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

echo "Starting invoice generation...\n\n";

// Get all customers
$stmt = $db->query("SELECT id, customer_code, business_name, state FROM customers ORDER BY id");
$customers = $stmt->fetchAll();

if (empty($customers)) {
  die("Error: No customers found in database. Please add customers first.\n");
}

echo "Found " . count($customers) . " customers\n";

// Get all products
$stmt = $db->query("SELECT id, product_code, name, metal_type, purity, category_id FROM products WHERE is_active = 1");
$products = $stmt->fetchAll();

echo "Found " . count($products) . " products\n\n";

// If no products exist, we'll use hardcoded jewellery items
if (empty($products)) {
  echo "No products found. Using default jewellery items...\n";
  $defaultItems = [
    ['name' => 'Gold Ring 22K', 'metal_type' => 'gold', 'purity' => '22K'],
    ['name' => 'Gold Chain 22K', 'metal_type' => 'gold', 'purity' => '22K'],
    ['name' => 'Gold Bangle 22K', 'metal_type' => 'gold', 'purity' => '22K'],
    ['name' => 'Gold Necklace 22K', 'metal_type' => 'gold', 'purity' => '22K'],
    ['name' => 'Gold Earrings 22K', 'metal_type' => 'gold', 'purity' => '22K'],
    ['name' => 'Gold Pendant 22K', 'metal_type' => 'gold', 'purity' => '22K'],
    ['name' => 'Silver Anklet 925', 'metal_type' => 'silver', 'purity' => '925'],
    ['name' => 'Silver Chain 925', 'metal_type' => 'silver', 'purity' => '925'],
    ['name' => 'Silver Bracelet 925', 'metal_type' => 'silver', 'purity' => '925'],
  ];
}

// Get current metal rates
$stmt = $db->query("SELECT metal_type, purity, rate_per_gram FROM metal_rates ORDER BY rate_date DESC LIMIT 10");
$rates = $stmt->fetchAll();

$metalRates = [];
foreach ($rates as $rate) {
  $metalRates[$rate['metal_type']][$rate['purity']] = $rate['rate_per_gram'];
}

// Default rates if not found
if (empty($metalRates['gold']['22K'])) {
  $metalRates['gold']['22K'] = 5850.00;
  $metalRates['gold']['24K'] = 6200.00;
  $metalRates['gold']['18K'] = 4680.00;
  $metalRates['silver']['925'] = 78.50;
  $metalRates['silver']['999'] = 82.00;
}

echo "Metal Rates:\n";
foreach ($metalRates as $metal => $purities) {
  foreach ($purities as $purity => $rate) {
    echo "  " . ucfirst($metal) . " $purity: ₹$rate/gram\n";
  }
}
echo "\n";

// Jewellery item templates with typical weight ranges
$itemTemplates = [
  'gold' => [
    '22K' => [
      ['name' => 'Gold Ring', 'min_weight' => 3.0, 'max_weight' => 8.0, 'mc_rate' => 350],
      ['name' => 'Gold Chain', 'min_weight' => 8.0, 'max_weight' => 25.0, 'mc_rate' => 280],
      ['name' => 'Gold Bangle', 'min_weight' => 15.0, 'max_weight' => 35.0, 'mc_rate' => 320],
      ['name' => 'Gold Necklace', 'min_weight' => 20.0, 'max_weight' => 60.0, 'mc_rate' => 450],
      ['name' => 'Gold Earrings', 'min_weight' => 2.5, 'max_weight' => 10.0, 'mc_rate' => 380],
      ['name' => 'Gold Pendant', 'min_weight' => 3.0, 'max_weight' => 12.0, 'mc_rate' => 340],
      ['name' => 'Gold Bracelet', 'min_weight' => 10.0, 'max_weight' => 25.0, 'mc_rate' => 300],
      ['name' => 'Gold Mangalsutra', 'min_weight' => 15.0, 'max_weight' => 30.0, 'mc_rate' => 400],
      ['name' => 'Gold Nose Pin', 'min_weight' => 1.0, 'max_weight' => 3.0, 'mc_rate' => 450],
      ['name' => 'Gold Anklet', 'min_weight' => 20.0, 'max_weight' => 45.0, 'mc_rate' => 280],
    ],
    '18K' => [
      ['name' => '18K Gold Ring', 'min_weight' => 2.5, 'max_weight' => 6.0, 'mc_rate' => 400],
      ['name' => '18K Gold Chain', 'min_weight' => 6.0, 'max_weight' => 18.0, 'mc_rate' => 320],
      ['name' => '18K Gold Earrings', 'min_weight' => 2.0, 'max_weight' => 8.0, 'mc_rate' => 420],
      ['name' => '18K Gold Pendant', 'min_weight' => 2.5, 'max_weight' => 10.0, 'mc_rate' => 380],
    ],
    '24K' => [
      ['name' => '24K Gold Coin 10g', 'min_weight' => 10.0, 'max_weight' => 10.0, 'mc_rate' => 150],
      ['name' => '24K Gold Coin 20g', 'min_weight' => 20.0, 'max_weight' => 20.0, 'mc_rate' => 150],
      ['name' => '24K Gold Bar 50g', 'min_weight' => 50.0, 'max_weight' => 50.0, 'mc_rate' => 100],
    ],
  ],
  'silver' => [
    '925' => [
      ['name' => 'Silver Anklet', 'min_weight' => 25.0, 'max_weight' => 50.0, 'mc_rate' => 45],
      ['name' => 'Silver Chain', 'min_weight' => 15.0, 'max_weight' => 35.0, 'mc_rate' => 35],
      ['name' => 'Silver Bracelet', 'min_weight' => 20.0, 'max_weight' => 40.0, 'mc_rate' => 40],
      ['name' => 'Silver Ring', 'min_weight' => 5.0, 'max_weight' => 12.0, 'mc_rate' => 50],
      ['name' => 'Silver Earrings', 'min_weight' => 4.0, 'max_weight' => 10.0, 'mc_rate' => 48],
      ['name' => 'Silver Pendant', 'min_weight' => 6.0, 'max_weight' => 15.0, 'mc_rate' => 42],
      ['name' => 'Silver Toe Ring', 'min_weight' => 3.0, 'max_weight' => 6.0, 'mc_rate' => 55],
    ],
    '999' => [
      ['name' => 'Silver Coin 25g', 'min_weight' => 25.0, 'max_weight' => 25.0, 'mc_rate' => 20],
      ['name' => 'Silver Coin 50g', 'min_weight' => 50.0, 'max_weight' => 50.0, 'mc_rate' => 20],
      ['name' => 'Silver Bar 100g', 'min_weight' => 100.0, 'max_weight' => 100.0, 'mc_rate' => 15],
    ],
  ],
];

// Generate invoices
$targetInvoices = 250;
$invoicesCreated = 0;
$startDate = strtotime('-180 days'); // Last 6 months

echo "Generating $targetInvoices invoices...\n\n";

try {
  $db->beginTransaction();

  for ($i = 0; $i < $targetInvoices; $i++) {
    // Random customer
    $customer = $customers[array_rand($customers)];

    // Random date in last 6 months
    $randomDays = rand(0, 180);
    $invoiceDate = date('Y-m-d', strtotime("-$randomDays days", time()));
    $dueDate = date('Y-m-d', strtotime("+30 days", strtotime($invoiceDate)));

    // Generate invoice number
    $invoiceNo = 'INV' . str_pad(1000 + $i, 6, '0', STR_PAD_LEFT);

    // Determine if same state (for CGST/SGST) or different (IGST)
    $companyState = 'Maharashtra'; // Default company state
    $isIntraState = ($customer['state'] == $companyState);

    // Generate 1-5 items per invoice
    $numItems = rand(1, 5);
    $subtotal = 0;
    $totalMetalAmount = 0;
    $totalMakingAmount = 0;
    $invoiceItems = [];

    for ($j = 0; $j < $numItems; $j++) {
      // Random metal type and purity
      $metalType = array_rand($itemTemplates);
      $purity = array_rand($itemTemplates[$metalType]);

      // Get item template
      $templates = $itemTemplates[$metalType][$purity];
      $template = $templates[array_rand($templates)];

      // Calculate weight
      $weight = round(rand($template['min_weight'] * 100, $template['max_weight'] * 100) / 100, 3);
      $quantity = rand(1, 3);

      // Get rate
      $ratePerGram = $metalRates[$metalType][$purity];

      // Calculate amounts
      $netWeight = $weight; // Simplified - in real system would consider wastage
      $metalAmount = $weight * $ratePerGram;

      // Making charge (per gram or fixed)
      $mcType = (rand(0, 1) == 0) ? 'per_gram' : 'fixed';
      $mcRate = $template['mc_rate'];
      $mcAmount = ($mcType === 'per_gram') ? ($weight * $mcRate) : ($quantity * $mcRate);

      $itemTotal = $metalAmount + $mcAmount;

      $totalMetalAmount += $metalAmount;
      $totalMakingAmount += $mcAmount;
      $subtotal += $itemTotal;

      $invoiceItems[] = [
        'product_id' => !empty($products) ? $products[array_rand($products)]['id'] : null,
        'item_name' => $template['name'],
        'metal_type' => $metalType,
        'purity' => $purity,
        'quantity' => $quantity,
        'gross_weight' => $weight,
        'net_weight' => $netWeight,
        'wastage_percent' => 0,
        'wastage_weight' => 0,
        'total_weight' => $weight,
        'rate_per_gram' => $ratePerGram,
        'metal_amount' => $metalAmount,
        'making_charge_type' => $mcType,
        'making_charge_rate' => $mcRate,
        'making_charge_amount' => $mcAmount,
        'item_total' => $itemTotal,
      ];
    }

    // Calculate GST
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

    $totalAmount = $subtotal + $totalGST;

    // Payment logic (70% paid, 20% partial, 10% pending)
    $paymentRand = rand(1, 100);
    if ($paymentRand <= 70) {
      // Fully paid
      $paidAmount = $totalAmount;
      $paymentStatus = 'paid';
    } elseif ($paymentRand <= 90) {
      // Partially paid (50-80%)
      $paidAmount = round($totalAmount * rand(50, 80) / 100, 2);
      $paymentStatus = 'partial';
    } else {
      // Pending
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
      1, // Admin user
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
        $item['wastage_percent'],
        $item['wastage_weight'],
        $item['total_weight'],
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
      $paymentMethods = ['cash', 'bank', 'upi', 'cheque'];
      $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

      $referenceNo = '';
      if ($paymentMethod == 'bank') {
        $referenceNo = 'TXN' . rand(100000, 999999);
      } elseif ($paymentMethod == 'upi') {
        $referenceNo = 'UPI' . rand(100000, 999999);
      } elseif ($paymentMethod == 'cheque') {
        $referenceNo = 'CHQ' . rand(100000, 999999);
      }

      $stmt = $db->prepare("INSERT INTO payments 
                (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

      $stmt->execute([
        $customer['id'],
        $invoiceId,
        $invoiceDate,
        $paidAmount,
        $paymentMethod,
        $referenceNo,
        '',
        1,
      ]);
    }

    // Update customer balance
    $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?");
    $stmt->execute([$balanceAmount, $customer['id']]);

    // Insert ledger entry
    $stmt = $db->prepare("INSERT INTO customer_ledger 
            (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
            VALUES (?, ?, 'invoice', ?, ?, ?, 0, ?, ?)");

    $stmt->execute([
      $customer['id'],
      $invoiceDate,
      $invoiceId,
      $invoiceNo,
      $totalAmount,
      $totalAmount, // Simplified - should calculate running balance
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
        $totalAmount - $paidAmount,
        '',
      ]);
    }

    $invoicesCreated++;

    if ($invoicesCreated % 50 == 0) {
      echo "Created $invoicesCreated invoices...\n";
    }
  }

  $db->commit();
  echo "\n✓ Successfully created $invoicesCreated invoices!\n";

  // Show summary
  $stmt = $db->query("SELECT COUNT(*) as total FROM invoices");
  $totalInvoices = $stmt->fetch()['total'];

  $stmt = $db->query("SELECT payment_status, COUNT(*) as count FROM invoices GROUP BY payment_status");
  $statusCounts = $stmt->fetchAll();

  $stmt = $db->query("SELECT SUM(total_amount) as total_sales FROM invoices");
  $totalSales = $stmt->fetch()['total_sales'];

  echo "\n=== SUMMARY ===\n";
  echo "Total Invoices: $totalInvoices\n";
  echo "Total Sales: ₹" . number_format($totalSales, 2) . "\n\n";

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
