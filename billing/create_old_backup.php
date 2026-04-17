<?php

/**
 * Create Invoice - Wholesale Optimized
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_BILLING]);

$pageTitle = 'Create Wholesale Invoice';
$db = getDBConnection();

// Get today's rates
$stmt = $db->prepare("SELECT metal_type, purity, rate_per_gram FROM metal_rates WHERE rate_date = CURDATE()");
$stmt->execute();
$todayRates = $stmt->fetchAll();

$rates = [];
foreach ($todayRates as $rate) {
    $rates[$rate['metal_type']][$rate['purity']] = $rate['rate_per_gram'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $customerId = intval($_POST['customer_id'] ?? 0);
        $invoiceDate = $_POST['invoice_date'] ?? date(DB_DATE_FORMAT);
        $dueDate = $_POST['due_date'] ?? date(DB_DATE_FORMAT, strtotime('+30 days'));
        $notes = sanitize($_POST['notes'] ?? '');

        // Calculate totals
        $subtotal = 0;
        $items = [];

        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (empty($item['item_name'])) continue;

                $grossWeight = floatval($item['gross_weight'] ?? 0);
                $netWeight = floatval($item['net_weight'] ?? 0);
                $wastagePercent = floatval($item['wastage_percent'] ?? 0);
                $wastageWeight = $netWeight * ($wastagePercent / 100);
                $totalWeight = $netWeight + $wastageWeight;
                $ratePerGram = floatval($item['rate_per_gram'] ?? 0);
                $metalAmount = $totalWeight * $ratePerGram;

                $makingChargeType = $item['making_charge_type'] ?? 'per_gram';
                $makingChargeRate = floatval($item['making_charge_rate'] ?? 0);
                $makingChargeAmount = $makingChargeType === 'per_gram' ? ($totalWeight * $makingChargeRate) : $makingChargeRate;

                $itemTotal = $metalAmount + $makingChargeAmount;
                $subtotal += $itemTotal;

                $items[] = [
                    'category_id' => intval($item['category_id'] ?? 0),
                    'item_name' => sanitize($item['item_name']),
                    'metal_type' => $item['metal_type'],
                    'purity' => $item['purity'],
                    'quantity' => intval($item['quantity'] ?? 1),
                    'gross_weight' => $grossWeight,
                    'net_weight' => $netWeight,
                    'wastage_percent' => $wastagePercent,
                    'wastage_weight' => $wastageWeight,
                    'total_weight' => $totalWeight,
                    'rate_per_gram' => $ratePerGram,
                    'metal_amount' => $metalAmount,
                    'making_charge_type' => $makingChargeType,
                    'making_charge_rate' => $makingChargeRate,
                    'making_charge_amount' => $makingChargeAmount,
                    'item_total' => $itemTotal
                ];
            }
        }

        $discountAmount = floatval($_POST['discount_amount'] ?? 0);
        $taxableAmount = $subtotal - $discountAmount;

        // Calculate GST based on metal type and making charges
        $totalMetalAmount = 0;
        $totalMakingAmount = 0;
        $totalGSTAmount = 0;
        $totalCGST = 0;
        $totalSGST = 0;
        $totalIGST = 0;

        foreach ($items as $item) {
            $totalMetalAmount += $item['metal_amount'];
            $totalMakingAmount += $item['making_charge_amount'];

            // GST on metal (3% for gold/silver)
            $metalGSTRate = ($item['metal_type'] === 'gold') ? GST_RATE_GOLD : GST_RATE_SILVER;
            $metalGST = ($item['metal_amount'] * $metalGSTRate / 100);

            // GST on making charges (5% as it's a service)
            $makingGST = ($item['making_charge_amount'] * GST_RATE_MAKING_CHARGES / 100);

            $itemGST = $metalGST + $makingGST;
            $totalGSTAmount += $itemGST;
        }

        // Split GST into CGST and SGST (half each for intra-state)
        $totalCGST = $totalGSTAmount / 2;
        $totalSGST = $totalGSTAmount / 2;
        $totalIGST = 0; // For inter-state, would be full amount

        $totalAmount = $taxableAmount + $totalGSTAmount;

        // Handle payment received
        $paidAmount = floatval($_POST['paid_amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $paymentReference = sanitize($_POST['payment_reference'] ?? '');
        $balanceAmount = $totalAmount - $paidAmount;

        // Determine payment status
        if ($paidAmount <= 0) {
            $paymentStatus = 'pending';
        } elseif ($paidAmount < $totalAmount) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'paid';
        }

        // Generate invoice number
        $invoiceNo = generateInvoiceNumber();

        // Insert invoice
        $stmt = $db->prepare("INSERT INTO invoices 
            (invoice_no, customer_id, invoice_date, due_date, subtotal, discount_amount, taxable_amount, 
             cgst_amount, sgst_amount, igst_amount, total_amount, paid_amount, balance_amount, payment_status, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $invoiceNo,
            $customerId,
            $invoiceDate,
            $dueDate,
            $subtotal,
            $discountAmount,
            $taxableAmount,
            $totalCGST,
            $totalSGST,
            $totalIGST,
            $totalAmount,
            $paidAmount,
            $balanceAmount,
            $paymentStatus,
            $notes,
            $_SESSION['user_id']
        ]);

        $invoiceId = $db->lastInsertId();

        // If payment received, insert payment record
        if ($paidAmount > 0) {
            $stmt = $db->prepare("INSERT INTO payments 
                (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $customerId,
                $invoiceId,
                $invoiceDate,
                $paidAmount,
                $paymentMethod,
                $paymentReference,
                "Payment against invoice $invoiceNo",
                $_SESSION['user_id']
            ]);
        }

        // Insert invoice items
        $stmt = $db->prepare("INSERT INTO invoice_items 
            (invoice_id, item_name, metal_type, purity, quantity, gross_weight, net_weight, 
             wastage_percent, wastage_weight, total_weight, rate_per_gram, metal_amount,
             making_charge_type, making_charge_rate, making_charge_amount, item_total) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($items as $item) {
            $stmt->execute([
                $invoiceId,
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
                $item['item_total']
            ]);
        }

        // Update customer balance with balance amount (after payment)
        $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?");
        $stmt->execute([$balanceAmount, $customerId]);

        // Add to customer ledger
        $stmt = $db->prepare("INSERT INTO customer_ledger 
            (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
            SELECT id, CURDATE(), 'invoice', ?, ?, ?, 0, current_balance, ? FROM customers WHERE id = ?");
        $stmt->execute([$invoiceId, $invoiceNo, $balanceAmount, "Invoice created. Paid: ₹" . number_format($paidAmount, 2), $customerId]);

        $db->commit();

        logActivity('invoice_created', "Created invoice: $invoiceNo");
        redirectWithMessage("/billing/view.php?id=$invoiceId", 'success', 'Invoice created successfully!');
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error creating invoice: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Create Invoice</h2>
    <a href="<?php echo BASE_URL; ?>/billing/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Invoices
    </a>
</div>

<?php if (empty($rates)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Metal rates not set for today.
        <a href="/settings/rates.php" class="alert-link">Please set rates first</a>.
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" action="" id="invoiceForm">
    <div class="row">
        <!-- Invoice Details -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?php echo $customer['id']; ?>" data-balance="<?php echo $customer['current_balance']; ?>">
                                        <?php echo $customer['business_name']; ?> (Bal: ₹<?php echo number_format($customer['current_balance'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?php echo date(DB_DATE_FORMAT); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="<?php echo date(DB_DATE_FORMAT, strtotime('+30 days')); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">GST Rate (%)</label>
                            <input type="number" name="gst_rate" id="gst_rate" class="form-control" value="<?php echo GST_RATE; ?>" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Items -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Invoice Items</h5>
            <button type="button" class="btn btn-sm btn-success" onclick="addInvoiceItem()">
                <i class="bi bi-plus"></i> Add Item
            </button>
        </div>
        <div class="card-body">
            <div id="invoice-items-container">
                <!-- First Item Row -->
                <div class="invoice-item-row border rounded p-3 mb-3">
                    <div class="row">
                        <div class="col-md-1 text-center">
                            <span class="item-number">1</span>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="items[0][item_name]" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select name="items[0][category_id]" class="form-select">
                                <option value="">Select</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Metal</label>
                            <select name="items[0][metal_type]" class="form-select metal-type" required>
                                <option value="">Select</option>
                                <option value="gold">Gold</option>
                                <option value="silver">Silver</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Purity</label>
                            <select name="items[0][purity]" class="form-select purity-select" required>
                                <option value="">Select</option>
                                <option value="24K">24K</option>
                                <option value="22K">22K</option>
                                <option value="18K">18K</option>
                                <option value="14K">14K</option>
                                <option value="925">Silver 925</option>
                                <option value="999">Silver 999</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Qty</label>
                            <input type="number" name="items[0][quantity]" class="form-control" value="1" min="1">
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-2">
                            <label class="form-label">Gross Wt (g)</label>
                            <input type="number" name="items[0][gross_weight]" class="form-control gross-weight" step="0.001" min="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Net Wt (g)</label>
                            <input type="number" name="items[0][net_weight]" class="form-control net-weight" step="0.001" min="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Wastage %</label>
                            <input type="number" name="items[0][wastage_percent]" class="form-control wastage-percent" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Rate/g (₹)</label>
                            <input type="number" name="items[0][rate_per_gram]" class="form-control rate-per-gram" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Making Type</label>
                            <select name="items[0][making_charge_type]" class="form-select making-charge-type">
                                <option value="per_gram">Per Gram</option>
                                <option value="fixed">Fixed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Making Rate (₹)</label>
                            <input type="number" name="items[0][making_charge_rate]" class="form-control making-charge-rate" step="0.01" min="0" value="0">
                        </div>
                    </div>

                    <div class="row mt-3 bg-light p-2 rounded">
                        <div class="col-md-3">
                            <small class="text-muted">Metal Amount:</small>
                            <input type="text" class="form-control metal-amount" readonly value="0.00">
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Making Charges:</small>
                            <input type="text" class="form-control making-charge-amount" readonly value="0.00">
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Item Total:</small>
                            <input type="text" name="items[0][item_total]" class="form-control item-total" readonly value="0.00">
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="button" class="btn btn-danger btn-sm mt-4" onclick="removeInvoiceItem(this)">
                                <i class="bi bi-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Totals -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Notes</h5>
                </div>
                <div class="card-body">
                    <textarea name="notes" class="form-control" rows="4" placeholder="Enter any additional notes..."></textarea>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt-cutoff"></i> Invoice Summary</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-end"><strong>Subtotal (Metal + Making):</strong></td>
                            <td width="150">
                                <input type="text" id="subtotal" name="subtotal" class="form-control" readonly value="0.00">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end"><strong>Discount:</strong></td>
                            <td>
                                <input type="number" id="discount_amount" name="discount_amount" class="form-control" step="0.01" min="0" value="0" onchange="calculateInvoiceTotals()">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end"><strong>Taxable Amount:</strong></td>
                            <td>
                                <input type="text" id="taxable_amount" name="taxable_amount" class="form-control" readonly value="0.00">
                            </td>
                        </tr>
                        <tr class="bg-light">
                            <td colspan="2"><small class="text-muted"><strong>GST Breakdown (Indian Market Rates):</strong></small></td>
                        </tr>
                        <tr>
                            <td class="text-end"><small>GST on Metal (3% - Gold/Silver):</small></td>
                            <td>
                                <input type="text" id="metal_gst" class="form-control form-control-sm" readonly value="0.00">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end"><small>GST on Making (5% - Job Work):</small></td>
                            <td>
                                <input type="text" id="making_gst" class="form-control form-control-sm" readonly value="0.00">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end"><strong>CGST (50% of GST):</strong></td>
                            <td>
                                <input type="text" id="cgst_amount" name="cgst_amount" class="form-control" readonly value="0.00">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end"><strong>SGST (50% of GST):</strong></td>
                            <td>
                                <input type="text" id="sgst_amount" name="sgst_amount" class="form-control" readonly value="0.00">
                            </td>
                        </tr>
                        <tr class="bg-warning">
                            <td class="text-end"><strong>Total Invoice Amount:</strong></td>
                            <td>
                                <input type="text" id="total_amount" name="total_amount" class="form-control fw-bold" readonly value="0.00">
                            </td>
                        </tr>
                    </table>

                    <hr>

                    <!-- Payment Section -->
                    <h6 class="text-success"><i class="bi bi-cash-coin"></i> Payment Received</h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Paid Amount (₹)</label>
                            <input type="number" id="paid_amount" name="paid_amount" class="form-control" step="0.01" min="0" value="0" onchange="calculateInvoiceTotals()" placeholder="0.00">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-2">
                            <label class="form-label small">Reference No (Optional)</label>
                            <input type="text" name="payment_reference" class="form-control" placeholder="UTR/Cheque No">
                        </div>
                    </div>

                    <hr>

                    <table class="table table-sm">
                        <tr class="bg-success text-white">
                            <td class="text-end"><strong>Balance Due:</strong></td>
                            <td width="150">
                                <input type="text" id="balance_amount" name="balance_amount" class="form-control fw-bold" readonly value="0.00">
                            </td>
                        </tr>
                        <tr>
                            <td class="text-end"><small>Payment Status:</small></td>
                            <td>
                                <span id="payment_status" class="badge bg-secondary">Pending</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
        <a href="/billing/list.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-save"></i> Create Invoice
        </button>
    </div>
</form>

<script>
    // Store metal rates for JavaScript
    var metalRates = <?php echo json_encode($rates); ?>;
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>