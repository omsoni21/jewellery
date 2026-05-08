<?php

/**
 * Sales Report Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Sales Report';
$db = getDBConnection();

// Handle filters
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-t');
$customerId = intval($_GET['customer_id'] ?? 0);

// Build query
$whereConditions = ["i.invoice_date BETWEEN ? AND ?"];
$params = [$fromDate, $toDate];

if ($customerId > 0) {
    $whereConditions[] = "i.customer_id = ?";
    $params[] = $customerId;
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get sales data
$query = "SELECT i.*, c.business_name, c.gst_number as customer_gst
          FROM invoices i 
          JOIN customers c ON i.customer_id = c.id 
          $whereClause 
          ORDER BY i.invoice_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Calculate totals
$totalSales = 0;
$totalTaxable = 0;
$totalCGST = 0;
$totalSGST = 0;
$totalPaid = 0;
$totalBalance = 0;

foreach ($sales as $sale) {
    $totalSales += $sale['total_amount'];
    $totalTaxable += $sale['taxable_amount'];
    $totalCGST += $sale['cgst_amount'];
    $totalSGST += $sale['sgst_amount'];
    $totalPaid += $sale['paid_amount'];
    $totalBalance += $sale['balance_amount'];
}

// Get customers for filter
$stmt = $db->query("SELECT id, business_name FROM customers WHERE is_active = 1 ORDER BY business_name");
$customers = $stmt->fetchAll();

// Get comprehensive customer report data
$customerReportQuery = "SELECT 
    c.id,
    c.business_name,
    c.gst_number,
    c.opening_balance,
    c.current_balance,
    COALESCE(SUM(DISTINCT i.total_amount), 0) as total_sales,
    COALESCE(SUM(DISTINCT i.paid_amount), 0) as total_paid,
    COALESCE(SUM(DISTINCT i.balance_amount), 0) as outstanding_dues,
    COALESCE((SELECT SUM(ii.item_total) FROM invoice_items ii WHERE ii.invoice_id IN (SELECT id FROM invoices WHERE customer_id = c.id AND ii.metal_type = 'gold')), 0) as gold_purchase,
    COALESCE((SELECT SUM(ii.item_total) FROM invoice_items ii WHERE ii.invoice_id IN (SELECT id FROM invoices WHERE customer_id = c.id AND ii.metal_type = 'silver')), 0) as silver_purchase
FROM customers c
LEFT JOIN invoices i ON c.id = i.customer_id
WHERE c.is_active = 1
GROUP BY c.id, c.business_name, c.gst_number, c.opening_balance, c.current_balance
ORDER BY outstanding_dues DESC";

$customerReport = $db->query($customerReportQuery)->fetchAll();

// Calculate report totals
$reportTotals = [
    'total_sales' => 0,
    'total_paid' => 0,
    'total_outstanding' => 0,
    'total_gold' => 0,
    'total_silver' => 0
];

foreach ($customerReport as $row) {
    $reportTotals['total_sales'] += $row['total_sales'];
    $reportTotals['total_paid'] += $row['total_paid'];
    $reportTotals['total_outstanding'] += $row['outstanding_dues'];
    $reportTotals['total_gold'] += $row['gold_purchase'];
    $reportTotals['total_silver'] += $row['silver_purchase'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-graph-up"></i> Sales Report</h2>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button onclick="generatePDF()" class="btn btn-success">
            <i class="bi bi-file-earmark-pdf"></i> Download PDF
        </button>
    </div>
</div>

<!-- Section 1: Sales Details -->
<h4 class="mb-3"><i class="bi bi-receipt"></i> Sales Details</h4>

<!-- Filters -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo $fromDate; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo $toDate; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-select">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>" <?php echo $customerId == $customer['id'] ? 'selected' : ''; ?>>
                            <?php echo $customer['business_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalSales); ?></h4>
                <small>Total Sales</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalTaxable); ?></h4>
                <small>Taxable Amount</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalCGST); ?></h4>
                <small>CGST</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-secondary text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalSGST); ?></h4>
                <small>SGST</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalPaid); ?></h4>
                <small>Total Paid</small>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h4><?php echo formatCurrency($totalBalance); ?></h4>
                <small>Total Balance</small>
            </div>
        </div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Sales Details (<?php echo formatDate($fromDate); ?> to <?php echo formatDate($toDate); ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="salesTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>GST No</th>
                        <th class="text-end">Taxable</th>
                        <th class="text-end">CGST</th>
                        <th class="text-end">SGST</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No sales found for the selected period.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo formatDate($sale['invoice_date']); ?></td>
                                <td><a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $sale['id']; ?>"><?php echo $sale['invoice_no']; ?></a></td>
                                <td><?php echo $sale['business_name']; ?></td>
                                <td><?php echo $sale['customer_gst'] ?: '-'; ?></td>
                                <td class="text-end"><?php echo formatCurrency($sale['taxable_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($sale['cgst_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($sale['sgst_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($sale['total_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($sale['paid_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($sale['balance_amount']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="bg-light fw-bold">
                    <tr>
                        <td colspan="4" class="text-end">TOTAL:</td>
                        <td class="text-end"><?php echo formatCurrency($totalTaxable); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalCGST); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalSGST); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalSales); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalPaid); ?></td>
                        <td class="text-end"><?php echo formatCurrency($totalBalance); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Separator -->
<div class="my-5"></div>

<!-- Section 2: Customer Summary -->
<h4 class="mb-3"><i class="bi bi-people"></i> Customer Summary</h4>

<!-- Customer Summary Report -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0" id="customerReportTable">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        <th>GST No</th>
                        <th class="text-end">Total Sales</th>
                        <th class="text-end">Total Paid</th>
                        <th class="text-end">Outstanding Dues</th>
                        <th class="text-end">Gold Purchase</th>
                        <th class="text-end">Silver Purchase</th>
                        <th class="text-end">Current Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach ($customerReport as $customer): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo $customer['business_name']; ?></strong></td>
                            <td><?php echo $customer['gst_number'] ?: '-'; ?></td>
                            <td class="text-end"><?php echo formatCurrency($customer['total_sales']); ?></td>
                            <td class="text-end text-success"><?php echo formatCurrency($customer['total_paid']); ?></td>
                            <td class="text-end text-danger"><?php echo formatCurrency($customer['outstanding_dues']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($customer['gold_purchase']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($customer['silver_purchase']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($customer['current_balance']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="bg-dark text-white fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">TOTAL:</td>
                        <td class="text-end"><?php echo formatCurrency($reportTotals['total_sales']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($reportTotals['total_paid']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($reportTotals['total_outstanding']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($reportTotals['total_gold']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($reportTotals['total_silver']); ?></td>
                        <td class="text-end">-</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    function generatePDF() {
        alert('Use Print button and select "Save as PDF" option to download PDF');
        window.print();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
