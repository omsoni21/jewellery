<?php
/**
 * GST Summary Report Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'GST Summary Report';
$db = getDBConnection();

// Get date range
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');

// Get GST summary data
$stmt = $db->prepare("SELECT 
    DATE_FORMAT(invoice_date, '%Y-%m') as month,
    COUNT(*) as invoice_count,
    SUM(taxable_amount) as total_taxable,
    SUM(cgst_amount) as total_cgst,
    SUM(sgst_amount) as total_sgst,
    SUM(total_amount) as total_amount
    FROM invoices 
    WHERE invoice_date BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY month DESC");
$stmt->execute([$fromDate, $toDate]);
$monthlySummary = $stmt->fetchAll();

// Get detailed invoice data
$stmt = $db->prepare("SELECT i.*, c.business_name, c.gst_number as customer_gst
                      FROM invoices i
                      JOIN customers c ON i.customer_id = c.id
                      WHERE i.invoice_date BETWEEN ? AND ?
                      ORDER BY i.invoice_date DESC");
$stmt->execute([$fromDate, $toDate]);
$invoices = $stmt->fetchAll();

// Calculate totals
$totalTaxable = 0;
$totalCGST = 0;
$totalSGST = 0;
$totalGST = 0;
$totalAmount = 0;

foreach ($invoices as $inv) {
    $totalTaxable += $inv['taxable_amount'];
    $totalCGST += $inv['cgst_amount'];
    $totalSGST += $inv['sgst_amount'];
    $totalGST += ($inv['cgst_amount'] + $inv['sgst_amount']);
    $totalAmount += $inv['total_amount'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-text"></i> GST Summary Report</h2>
    <button onclick="window.print()" class="btn btn-primary no-print">
        <i class="bi bi-printer"></i> Print Report
    </button>
</div>

<!-- Filters -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $fromDate; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $toDate; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- GST Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4><?php echo count($invoices); ?></h4>
                <small>Total Invoices</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalTaxable); ?></h4>
                <small>Taxable Value</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalCGST); ?></h4>
                <small>CGST (<?php echo GST_RATE/2; ?>%)</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalSGST); ?></h4>
                <small>SGST (<?php echo GST_RATE/2; ?>%)</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalGST); ?></h4>
                <small>Total GST</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalAmount); ?></h4>
                <small>Total Value</small>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Summary -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Monthly GST Summary</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Invoices</th>
                        <th class="text-end">Taxable Value</th>
                        <th class="text-end">CGST</th>
                        <th class="text-end">SGST</th>
                        <th class="text-end">Total GST</th>
                        <th class="text-end">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($monthlySummary)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No data found for selected period.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($monthlySummary as $row): ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                        <td><?php echo $row['invoice_count']; ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['total_taxable']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['total_cgst']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['total_sgst']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['total_cgst'] + $row['total_sgst']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">TOTAL:</td>
                        <td class="text-end"><?php echo formatCurrency($totalTaxable); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalCGST); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalSGST); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalGST); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalAmount); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Detailed Invoice List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Invoice-wise GST Details (<?php echo formatDate($fromDate); ?> to <?php echo formatDate($toDate); ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Customer GST</th>
                        <th class="text-end">Taxable</th>
                        <th class="text-end">CGST</th>
                        <th class="text-end">SGST</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No invoices found for selected period.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?php echo formatDate($inv['invoice_date']); ?></td>
                        <td><a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $inv['id']; ?>"><?php echo $inv['invoice_no']; ?></a></td>
                        <td><?php echo $inv['business_name']; ?></td>
                        <td><?php echo $inv['customer_gst'] ?: '-'; ?></td>
                        <td class="text-end"><?php echo formatCurrency($inv['taxable_amount']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($inv['cgst_amount']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($inv['sgst_amount']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($inv['total_amount']); ?></td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- GST Calculation Note -->
<div class="alert alert-info mt-4">
    <h6><i class="bi bi-info-circle"></i> GST Information</h6>
    <p class="mb-1"><strong>GST Rate:</strong> <?php echo GST_RATE; ?>% (Default for Jewellery)</p>
    <p class="mb-1"><strong>CGST:</strong> <?php echo GST_RATE/2; ?>%</p>
    <p class="mb-0"><strong>SGST:</strong> <?php echo GST_RATE/2; ?>%</p>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
