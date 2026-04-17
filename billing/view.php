<?php

/**
 * View Invoice Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$pageTitle = 'View Invoice';
$db = getDBConnection();

$invoiceId = intval($_GET['id'] ?? 0);

if (!$invoiceId) {
    redirectWithMessage('/billing/list.php', 'danger', 'Invalid invoice ID.');
}

// Get invoice details
$stmt = $db->prepare("SELECT i.*, c.*, i.id as invoice_id 
                      FROM invoices i 
                      JOIN customers c ON i.customer_id = c.id 
                      WHERE i.id = ?");
$stmt->execute([$invoiceId]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirectWithMessage('/billing/list.php', 'danger', 'Invoice not found.');
}

// Get invoice items
$stmt = $db->prepare("SELECT *, (gross_weight * 100 / NULLIF(rate_per_gram, 0) * 0.01) as purity_percent_calc FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoiceId]);
$items = $stmt->fetchAll();

// If purity_percent is not directly available, we'll calculate it from net_weight and gross_weight
foreach ($items as &$item) {
    if (!isset($item['purity_percent']) || $item['purity_percent'] == 0) {
        // Calculate from net_weight (fine weight) and gross_weight
        if ($item['gross_weight'] > 0) {
            $item['purity_percent'] = ($item['net_weight'] / $item['gross_weight']) * 100;
        } else {
            $item['purity_percent'] = 99.9;
        }
    }
}

// Get company settings
$stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
$company = $stmt->fetch();

// Get payments for this invoice
$stmt = $db->prepare("SELECT p.*, u.full_name as created_by_name 
                      FROM payments p 
                      LEFT JOIN users u ON p.created_by = u.id 
                      WHERE p.invoice_id = ? 
                      ORDER BY p.payment_date DESC");
$stmt->execute([$invoiceId]);
$payments = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2><i class="bi bi-receipt"></i> Invoice Details</h2>
    <div class="btn-group">
        <a href="<?php echo BASE_URL; ?>/billing/list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="<?php echo BASE_URL; ?>/billing/print.php?id=<?php echo $invoiceId; ?>" class="btn btn-primary btn-lg" target="_blank">
            <i class="bi bi-printer"></i> Print Invoice
        </a>
        <a href="<?php echo BASE_URL; ?>/billing/pdf.php?id=<?php echo $invoiceId; ?>" class="btn btn-info">
            <i class="bi bi-file-pdf"></i> PDF
        </a>
        <?php if ($invoice['balance_amount'] > 0 && hasRole([ROLE_ADMIN, ROLE_ACCOUNTANT])): ?>
            <a href="<?php echo BASE_URL; ?>/payments/entry.php?invoice_id=<?php echo $invoiceId; ?>" class="btn btn-success">
                <i class="bi bi-cash"></i> Add Payment
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Invoice Display -->
<div class="invoice-preview">
    <!-- Invoice Header -->
    <div class="invoice-header">
        <div class="row">
            <div class="col-md-6 invoice-company">
                <h3><?php echo $company['company_name'] ?? 'Your Company'; ?></h3>
                <p class="mb-1"><?php echo $company['address_line1'] ?? ''; ?></p>
                <p class="mb-1"><?php echo ($company['city'] ?? '') . ', ' . ($company['state'] ?? '') . ' - ' . ($company['pincode'] ?? ''); ?></p>
                <p class="mb-1"><strong>GST:</strong> <?php echo $company['gst_number'] ?? 'N/A'; ?></p>
                <p class="mb-0"><strong>Phone:</strong> <?php echo $company['phone'] ?? 'N/A'; ?></p>
            </div>
            <div class="col-md-6 invoice-title text-end">
                <h2>TAX INVOICE</h2>
                <p class="mb-1"><strong>Invoice #:</strong> <?php echo $invoice['invoice_no']; ?></p>
                <p class="mb-1"><strong>Date:</strong> <?php echo formatDate($invoice['invoice_date']); ?></p>
                <p class="mb-0"><strong>Due Date:</strong> <?php echo formatDate($invoice['due_date']); ?></p>
            </div>
        </div>
    </div>

    <!-- Bill To -->
    <div class="invoice-details mb-4">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-primary">Bill To:</h6>
                <h5><?php echo $invoice['business_name']; ?></h5>
                <p class="mb-1"><?php echo $invoice['contact_person'] ?? ''; ?></p>
                <p class="mb-1"><?php echo $invoice['address_line1'] ?? ''; ?></p>
                <p class="mb-1"><?php echo ($invoice['city'] ?? '') . ', ' . ($invoice['state'] ?? '') . ' - ' . ($invoice['pincode'] ?? ''); ?></p>
                <p class="mb-1"><strong>GST:</strong> <?php echo $invoice['gst_number'] ?: 'N/A'; ?></p>
                <p class="mb-0"><strong>Phone:</strong> <?php echo $invoice['phone'] ?: 'N/A'; ?></p>
            </div>
            <div class="col-md-6 text-end">
                <h6 class="text-primary">Payment Status</h6>
                <?php if ($invoice['payment_status'] === 'paid'): ?>
                    <span class="badge bg-success fs-6">PAID</span>
                <?php elseif ($invoice['payment_status'] === 'partial'): ?>
                    <span class="badge bg-warning fs-6">PARTIALLY PAID</span>
                <?php else: ?>
                    <span class="badge bg-danger fs-6">PENDING</span>
                <?php endif; ?>
                <p class="mt-2 mb-0"><strong>Balance:</strong> <?php echo formatCurrency($invoice['balance_amount']); ?></p>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <table class="table table-bordered invoice-table">
        <thead class="table-dark">
            <tr>
                <th width="40" class="text-center">#</th>
                <th width="200">Item Name</th>
                <th width="80">Metal</th>
                <th width="60" class="text-center">Qty</th>
                <th width="100">Weight(g)</th>
                <th width="120">Purity</th>
                <th width="100">Fine Wt</th>
                <th width="110">Rate/g</th>
                <th width="120">Metal Amt</th>
                <th width="100">MC Type</th>
                <th width="100">MC Rate</th>
                <th width="110">MC Amt</th>
                <th width="120">Total</th>
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
                // Calculate fine weight if not stored
                $fineWeight = $item['net_weight'];

                // Accumulate totals
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
                        <?php
                        echo $item['purity'];
                        // Show purity percentage if available
                        if (isset($item['purity_percent']) && $item['purity_percent'] > 0) {
                            echo "<br><small class='text-muted'>(" . number_format($item['purity_percent'], 1) . "%)</small>";
                        }
                        ?>
                    </td>
                    <td class="text-end"><?php echo number_format($fineWeight, 3); ?></td>
                    <td class="text-end"><?php echo formatCurrency($item['rate_per_gram']); ?></td>
                    <td class="text-end"><?php echo formatCurrency($item['metal_amount']); ?></td>
                    <td class="text-center">
                        <?php
                        $mcTypeDisplay = str_replace('_', ' ', $item['making_charge_type']);
                        echo ucfirst($mcTypeDisplay);
                        ?>
                    </td>
                    <td class="text-end"><?php echo formatCurrency($item['making_charge_rate']); ?></td>
                    <td class="text-end"><?php echo formatCurrency($item['making_charge_amount']); ?></td>
                    <td class="text-end"><strong><?php echo formatCurrency($item['item_total']); ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Metal Summary -->
    <div class="row mt-3 mb-3">
        <div class="col-md-6">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-gem"></i> Metal Summary</h6>
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-6 border-end">
                            <small class="text-muted">Gold</small><br>
                            <strong><?php echo number_format($totalGoldWeight, 3); ?>g</strong><br>
                            <small>Value: <?php echo formatCurrency($totalGoldValue); ?></small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Silver</small><br>
                            <strong><?php echo number_format($totalSilverWeight, 3); ?>g</strong><br>
                            <small>Value: <?php echo formatCurrency($totalSilverValue); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0"><i class="bi bi-calculator"></i> Calculation Summary</h6>
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-6 border-end">
                            <small class="text-muted">Total Metal Amt</small><br>
                            <strong><?php echo formatCurrency($totalMetalAmount); ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Total Making</small><br>
                            <strong><?php echo formatCurrency($totalMakingAmount); ?></strong>
                        </div>
                    </div>
                </div>
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
        <div class="row mt-3 mb-3">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white py-2">
                        <h6 class="mb-0"><i class="bi bi-check-circle"></i> Paid Metal (<?php echo number_format($paymentPercentage, 1); ?>%)</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row">
                            <div class="col-6 border-end">
                                <small class="text-muted">Gold Delivered</small><br>
                                <strong class="text-success"><?php echo number_format($paidGoldWeight, 3); ?>g</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Silver Delivered</small><br>
                                <strong class="text-success"><?php echo number_format($paidSilverWeight, 3); ?>g</strong>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <small class="text-muted">Amount Paid:</small> <strong class="text-success"><?php echo formatCurrency($invoice['paid_amount']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white py-2">
                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Balance Metal (Due)</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row">
                            <div class="col-6 border-end">
                                <small class="text-muted">Gold Pending</small><br>
                                <strong class="text-danger"><?php echo number_format($balanceGoldWeight, 3); ?>g</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Silver Pending</small><br>
                                <strong class="text-danger"><?php echo number_format($balanceSilverWeight, 3); ?>g</strong>
                            </div>
                        </div>
                        <div class="mt-2 pt-2 border-top">
                            <small class="text-muted">Balance Due:</small> <strong class="text-danger"><?php echo formatCurrency($invoice['balance_amount']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning">
            <i class="bi bi-info-circle"></i> <strong>Note:</strong>
            Metal will be delivered proportionally as payments are received.
            Currently <strong><?php echo number_format($paymentPercentage, 1); ?>%</strong> of total metal (<?php echo number_format($paidGoldWeight + $paidSilverWeight, 3); ?>g) has been delivered against payment.
            Remaining <strong><?php echo number_format(100 - $paymentPercentage, 1); ?>%</strong> (<?php echo number_format($balanceGoldWeight + $balanceSilverWeight, 3); ?>g) will be delivered upon full payment.
        </div>
    <?php endif; ?>

    <!-- Totals -->
    <div class="invoice-totals">
        <div class="row">
            <div class="col-md-6">
                <!-- Adjustments -->
                <div class="card border-info mb-3">
                    <div class="card-header bg-info text-white py-2">
                        <h6 class="mb-0"><i class="bi bi-sliders"></i> Adjustments</h6>
                    </div>
                    <div class="card-body py-2">
                        <?php
                        // Parse notes to get adjustment values
                        $notes = $invoice['notes'] ?? '';
                        preg_match('/Old Gold: ₹([0-9.]+)/', $notes, $oldGoldMatch);
                        preg_match('/Silver Return: ₹([0-9.]+)/', $notes, $silverReturnMatch);
                        preg_match('/Other: ₹([0-9.]+)/', $notes, $otherMatch);

                        $oldGoldValue = isset($oldGoldMatch[1]) ? floatval($oldGoldMatch[1]) : 0;
                        $silverReturn = isset($silverReturnMatch[1]) ? floatval($silverReturnMatch[1]) : 0;
                        $otherCharges = isset($otherMatch[1]) ? floatval($otherMatch[1]) : 0;
                        $discount = $invoice['discount_amount'] ?? 0;
                        ?>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td><small>Old Gold Value:</small></td>
                                <td class="text-end"><small>- <?php echo formatCurrency($oldGoldValue); ?></small></td>
                            </tr>
                            <tr>
                                <td><small>Silver Return:</small></td>
                                <td class="text-end"><small>- <?php echo formatCurrency($silverReturn); ?></small></td>
                            </tr>
                            <tr>
                                <td><small>Discount:</small></td>
                                <td class="text-end"><small>- <?php echo formatCurrency($discount); ?></small></td>
                            </tr>
                            <tr>
                                <td><small>Other Charges:</small></td>
                                <td class="text-end"><small>+ <?php echo formatCurrency($otherCharges); ?></small></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless">
                    <tr>
                        <td class="text-end"><strong>Subtotal (Metal + Making):</strong></td>
                        <td class="text-end" width="180"><?php echo formatCurrency($invoice['subtotal']); ?></td>
                    </tr>
                    <?php if ($invoice['discount_amount'] > 0): ?>
                        <tr class="text-danger">
                            <td class="text-end">Discount:</td>
                            <td class="text-end">- <?php echo formatCurrency($invoice['discount_amount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-end">Taxable Amount:</td>
                        <td class="text-end"><?php echo formatCurrency($invoice['taxable_amount']); ?></td>
                    </tr>
                    <?php if ($invoice['cgst_amount'] > 0): ?>
                        <tr>
                            <td class="text-end">CGST (<?php echo GST_RATE / 2; ?>%):</td>
                            <td class="text-end"><?php echo formatCurrency($invoice['cgst_amount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoice['sgst_amount'] > 0): ?>
                        <tr>
                            <td class="text-end">SGST (<?php echo GST_RATE / 2; ?>%):</td>
                            <td class="text-end"><?php echo formatCurrency($invoice['sgst_amount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoice['igst_amount'] > 0): ?>
                        <tr>
                            <td class="text-end">IGST (<?php echo GST_RATE; ?>%):</td>
                            <td class="text-end"><?php echo formatCurrency($invoice['igst_amount']); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="bg-primary text-white">
                        <td class="text-end">
                            <h5 class="mb-0"><strong>Total Amount:</strong></h5>
                        </td>
                        <td class="text-end">
                            <h5 class="mb-0"><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></h5>
                        </td>
                    </tr>
                    <?php if ($invoice['paid_amount'] > 0): ?>
                        <tr class="bg-success text-white">
                            <td class="text-end"><strong>Paid Amount:</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($invoice['paid_amount']); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($invoice['balance_amount'] > 0): ?>
                        <tr class="bg-danger text-white">
                            <td class="text-end"><strong>Balance Due:</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($invoice['balance_amount']); ?></strong></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <?php if ($invoice['notes']): ?>
        <div class="mt-4">
            <h6>Notes:</h6>
            <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Bank Details -->
    <div class="mt-4 pt-4 border-top">
        <h6>Bank Details:</h6>
        <p class="mb-1"><strong>Bank:</strong> <?php echo $company['bank_name'] ?? 'N/A'; ?></p>
        <p class="mb-1"><strong>Account #:</strong> <?php echo $company['bank_account_no'] ?? 'N/A'; ?></p>
        <p class="mb-1"><strong>IFSC:</strong> <?php echo $company['bank_ifsc'] ?? 'N/A'; ?></p>
        <p class="mb-0"><strong>Branch:</strong> <?php echo $company['bank_branch'] ?? 'N/A'; ?></p>
    </div>

    <!-- Signature -->
    <div class="row mt-5">
        <div class="col-md-6">
            <p class="mb-5">Customer Signature</p>
        </div>
        <div class="col-md-6 text-end">
            <p class="mb-5">Authorized Signature</p>
        </div>
    </div>
</div>

<!-- Payment History -->
<?php if (!empty($payments)): ?>
    <div class="card mt-4 no-print">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-cash-stack"></i> Payment History</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Notes</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo formatDate($payment['payment_date']); ?></td>
                            <td><?php echo formatCurrency($payment['amount']); ?></td>
                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                            <td><?php echo $payment['reference_no'] ?: '-'; ?></td>
                            <td><?php echo $payment['notes'] ?: '-'; ?></td>
                            <td><?php echo $payment['created_by_name']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php if (isset($_GET['print']) && $_GET['print'] == '1'): ?>
    <script>
        // Auto-show print dialog when redirected from create page
        setTimeout(function() {
            if (confirm('Invoice created successfully! Do you want to print it now?')) {
                window.open('<?php echo BASE_URL; ?>/billing/print.php?id=<?php echo $invoiceId; ?>', '_blank');
            }
        }, 500);
    </script>
<?php endif; ?>