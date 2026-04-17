<?php

/**
 * Print Invoice Page - Updated with all columns and company details
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$invoiceId = intval($_GET['id'] ?? 0);

if (!$invoiceId) {
    die('Invalid invoice ID');
}

$db = getDBConnection();

// Get invoice details
$stmt = $db->prepare("SELECT i.*, c.*, i.id as invoice_id 
                      FROM invoices i 
                      JOIN customers c ON i.customer_id = c.id 
                      WHERE i.id = ?");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    die('Invoice not found');
}

// Get invoice items
$stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoiceId]);
$items = $stmt->fetchAll();

// Calculate purity percent for each item
foreach ($items as &$item) {
    if ($item['gross_weight'] > 0) {
        $item['purity_percent'] = ($item['net_weight'] / $item['gross_weight']) * 100;
    } else {
        $item['purity_percent'] = 99.9;
    }
}

// Company details
$companyName = 'Tirupati Balaji Jewellers';
$companyAddress = 'Bagodar, Giridih';
$companyState = 'Jharkhand';
$companyPincode = '825322';

// Update database
$stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
$company = $stmt->fetch();

if ($company) {
    $stmt = $db->prepare("UPDATE company_settings SET company_name = ?, address_line1 = ?, city = ?, state = ?, pincode = ? WHERE id = ?");
    $stmt->execute([$companyName, $companyAddress, 'Giridih', $companyState, $companyPincode, $company['id']]);
} else {
    $stmt = $db->prepare("INSERT INTO company_settings (company_name, address_line1, city, state, pincode) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$companyName, $companyAddress, 'Giridih', $companyState, $companyPincode]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice['invoice_no']; ?> - <?php echo $companyName; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            background: white;
        }

        .invoice-container {
            max-width: 1000px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }

        .invoice-header {
            border-bottom: 3px solid #d4af37;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .company-name {
            color: #d4af37;
            font-size: 26px;
            font-weight: bold;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
        }

        .table th {
            background-color: #2c3e50;
            color: white;
            font-size: 10px;
            padding: 6px 3px;
        }

        .table td {
            font-size: 10px;
            padding: 6px 3px;
        }

        .metal-summary {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 15px 0;
        }

        .paid-section {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }

        .balance-section {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
        }

        .totals-table td {
            border: none;
        }

        .grand-total {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .invoice-container {
                border: none;
            }
        }
    </style>
</head>

<body>
    <div class="text-center mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            <i class="bi bi-printer"></i> Print Invoice
        </button>
        <a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $invoiceId; ?>" class="btn btn-secondary btn-lg">
            Back to Invoice
        </a>
    </div>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="row">
                <div class="col-6">
                    <div class="company-name"><?php echo $companyName; ?></div>
                    <p class="mb-1"><?php echo $companyAddress; ?></p>
                    <p class="mb-1"><?php echo 'Giridih, ' . $companyState . ' - ' . $companyPincode; ?></p>
                    <?php if (!empty($company['gst_number'])): ?>
                        <p class="mb-1"><strong>GST:</strong> <?php echo $company['gst_number']; ?></p>
                    <?php endif; ?>
                    <?php if (!empty($company['phone'])): ?>
                        <p class="mb-0"><strong>Phone:</strong> <?php echo $company['phone']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-6 text-end">
                    <div class="invoice-title">TAX INVOICE</div>
                    <p class="mb-1"><strong>Invoice #:</strong> <?php echo $invoice['invoice_no']; ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($invoice['invoice_date']); ?></p>
                    <p class="mb-0"><strong>Due Date:</strong> <?php echo formatDate($invoice['due_date']); ?></p>
                </div>
            </div>
        </div>

        <!-- Bill To -->
        <div class="row mb-3">
            <div class="col-6">
                <h6 class="text-primary">Bill To:</h6>
                <h5><?php echo $invoice['business_name']; ?></h5>
                <p class="mb-1"><?php echo $invoice['contact_person'] ?? ''; ?></p>
                <p class="mb-1"><?php echo ($invoice['city'] ?? '') . ', ' . ($invoice['state'] ?? '') . ' - ' . ($invoice['pincode'] ?? ''); ?></p>
                <?php if ($invoice['gst_number']): ?>
                    <p class="mb-0"><strong>GST:</strong> <?php echo $invoice['gst_number']; ?></p>
                <?php endif; ?>
            </div>
            <div class="col-6 text-end">
                <h6 class="text-primary">Payment Status</h6>
                <?php if ($invoice['payment_status'] === 'paid'): ?>
                    <span class="badge bg-success fs-6">PAID</span>
                <?php elseif ($invoice['payment_status'] === 'partial'): ?>
                    <span class="badge bg-warning fs-6">PARTIALLY PAID</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6">PENDING</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th width="30" class="text-center">#</th>
                    <th width="150">Item Name</th>
                    <th width="70">Metal</th>
                    <th width="40" class="text-center">Qty</th>
                    <th width="80">Weight(g)</th>
                    <th width="100">Purity</th>
                    <th width="70">Fine Wt</th>
                    <th width="90">Rate/g</th>
                    <th width="100">Metal Amt</th>
                    <th width="70">MC Type</th>
                    <th width="80">MC Rate</th>
                    <th width="90">MC Amt</th>
                    <th width="100">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalGoldWeight = 0;
                $totalSilverWeight = 0;
                $totalGoldValue = 0;
                $totalSilverValue = 0;
                $totalMetalAmount = 0;
                $totalMakingAmount = 0;

                foreach ($items as $index => $item):
                    if ($item['metal_type'] == 'gold') {
                        $totalGoldWeight += $item['gross_weight'];
                        $totalGoldValue += $item['metal_amount'];
                    } else {
                        $totalSilverWeight += $item['gross_weight'];
                        $totalSilverValue += $item['metal_amount'];
                    }
                    $totalMetalAmount += $item['metal_amount'];
                    $totalMakingAmount += $item['making_charge_amount'];
                ?>
                    <tr>
                        <td class="text-center"><?php echo $index + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                        <td><?php echo ucfirst($item['metal_type']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end"><?php echo number_format($item['gross_weight'], 3); ?></td>
                        <td>
                            <?php echo $item['purity']; ?><br>
                            <small>(<?php echo number_format($item['purity_percent'], 1); ?>%)</small>
                        </td>
                        <td class="text-end"><?php echo number_format($item['net_weight'], 3); ?></td>
                        <td class="text-end"><?php echo formatCurrency($item['rate_per_gram']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($item['metal_amount']); ?></td>
                        <td class="text-center"><?php echo ucfirst(str_replace('_', ' ', $item['making_charge_type'])); ?></td>
                        <td class="text-end"><?php echo formatCurrency($item['making_charge_rate']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($item['making_charge_amount']); ?></td>
                        <td class="text-end"><strong><?php echo formatCurrency($item['item_total']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Metal Summary -->
        <div class="metal-summary">
            <div class="row">
                <div class="col-6">
                    <strong>Metal Summary:</strong><br>
                    Gold: <?php echo number_format($totalGoldWeight, 3); ?>g (₹<?php echo number_format($totalGoldValue, 2); ?>)<br>
                    Silver: <?php echo number_format($totalSilverWeight, 3); ?>g (₹<?php echo number_format($totalSilverValue, 2); ?>)
                </div>
                <div class="col-6 text-end">
                    <strong>Calculation:</strong><br>
                    Metal Amount: ₹<?php echo number_format($totalMetalAmount, 2); ?><br>
                    Making Charges: ₹<?php echo number_format($totalMakingAmount, 2); ?>
                </div>
            </div>
        </div>

        <!-- Payment-wise Metal Deduction -->
        <?php
        $paymentPercentage = ($invoice['total_amount'] > 0) ? ($invoice['paid_amount'] / $invoice['total_amount']) * 100 : 0;
        $paidGoldWeight = $totalGoldWeight * ($paymentPercentage / 100);
        $paidSilverWeight = $totalSilverWeight * ($paymentPercentage / 100);
        $balanceGoldWeight = $totalGoldWeight - $paidGoldWeight;
        $balanceSilverWeight = $totalSilverWeight - $paidSilverWeight;
        ?>

        <?php if ($invoice['balance_amount'] > 0): ?>
            <div class="row">
                <div class="col-6">
                    <div class="paid-section">
                        <strong>✓ Paid Metal (<?php echo number_format($paymentPercentage, 1); ?>%)</strong><br>
                        Gold: <?php echo number_format($paidGoldWeight, 3); ?>g | Silver: <?php echo number_format($paidSilverWeight, 3); ?>g<br>
                        Amount Paid: ₹<?php echo number_format($invoice['paid_amount'], 2); ?>
                    </div>
                </div>
                <div class="col-6">
                    <div class="balance-section">
                        <strong>⚠ Balance Metal (Due)</strong><br>
                        Gold: <?php echo number_format($balanceGoldWeight, 3); ?>g | Silver: <?php echo number_format($balanceSilverWeight, 3); ?>g<br>
                        Balance Due: ₹<?php echo number_format($invoice['balance_amount'], 2); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Totals -->
        <div class="row">
            <div class="col-6">
                <?php if ($invoice['notes']): ?>
                    <h6>Notes:</h6>
                    <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                <?php endif; ?>
            </div>
            <div class="col-6">
                <table class="table totals-table">
                    <tr>
                        <td class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end" width="150"><?php echo formatCurrency($invoice['subtotal']); ?></td>
                    </tr>
                    <?php if ($invoice['cgst_amount'] > 0): ?>
                        <tr>
                            <td class="text-end">CGST:</td>
                            <td class="text-end"><?php echo formatCurrency($invoice['cgst_amount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoice['sgst_amount'] > 0): ?>
                        <tr>
                            <td class="text-end">SGST:</td>
                            <td class="text-end"><?php echo formatCurrency($invoice['sgst_amount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoice['igst_amount'] > 0): ?>
                        <tr>
                            <td class="text-end">IGST:</td>
                            <td class="text-end"><?php echo formatCurrency($invoice['igst_amount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="grand-total">
                        <td class="text-end"><strong>Total Amount:</strong></td>
                        <td class="text-end"><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                    </tr>
                    <?php if ($invoice['paid_amount'] > 0): ?>
                        <tr>
                            <td class="text-end">Paid:</td>
                            <td class="text-end"><?php echo formatCurrency($invoice['paid_amount']); ?></td>
                        </tr>
                        <tr class="text-danger">
                            <td class="text-end"><strong>Balance:</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($invoice['balance_amount']); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Signatures -->
        <div class="row mt-5 pt-4">
            <div class="col-6">
                <p style="border-top: 1px solid #333; display: inline-block; padding-top: 5px;">Customer Signature</p>
            </div>
            <div class="col-6 text-end">
                <p style="border-top: 1px solid #333; display: inline-block; padding-top: 5px;"><?php echo $companyName; ?></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-3 pt-2 border-top">
            <small class="text-muted">Thank you for your business! - <?php echo $companyName; ?></small>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>

</html>