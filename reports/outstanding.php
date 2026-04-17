<?php
/**
 * Outstanding Payment Report Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Outstanding Report';
$db = getDBConnection();

// Get outstanding invoices
$stmt = $db->query("SELECT i.*, c.business_name, c.phone, c.credit_limit, 
                    DATEDIFF(CURDATE(), i.due_date) as days_overdue
                    FROM invoices i 
                    JOIN customers c ON i.customer_id = c.id 
                    WHERE i.balance_amount > 0 
                    ORDER BY i.due_date ASC");
$outstanding = $stmt->fetchAll();

// Calculate totals
$totalOutstanding = 0;
$totalOverdue = 0;
$overdueCount = 0;

foreach ($outstanding as $inv) {
    $totalOutstanding += $inv['balance_amount'];
    if ($inv['days_overdue'] > 0) {
        $totalOverdue += $inv['balance_amount'];
        $overdueCount++;
    }
}

// Get customer-wise summary
$stmt = $db->query("SELECT c.id, c.business_name, c.phone, c.credit_limit,
                    COUNT(i.id) as invoice_count,
                    SUM(i.balance_amount) as total_due,
                    SUM(CASE WHEN i.due_date < CURDATE() THEN i.balance_amount ELSE 0 END) as overdue_amount,
                    MAX(DATEDIFF(CURDATE(), i.due_date)) as max_days_overdue
                    FROM customers c
                    JOIN invoices i ON c.id = i.customer_id
                    WHERE i.balance_amount > 0
                    GROUP BY c.id, c.business_name, c.phone, c.credit_limit
                    ORDER BY total_due DESC");
$customerSummary = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-exclamation-triangle"></i> Outstanding Payments Report</h2>
    <button onclick="window.print()" class="btn btn-primary no-print">
        <i class="bi bi-printer"></i> Print Report
    </button>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-warning text-dark">
            <div class="card-body text-center">
                <h3><?php echo formatCurrency($totalOutstanding); ?></h3>
                <p class="mb-0">Total Outstanding</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3><?php echo formatCurrency($totalOverdue); ?></h3>
                <p class="mb-0">Total Overdue</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo count($outstanding); ?></h3>
                <p class="mb-0">Pending Invoices</p>
            </div>
        </div>
    </div>
</div>

<!-- Customer-wise Summary -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Customer-wise Outstanding Summary</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Invoices</th>
                        <th class="text-end">Total Due</th>
                        <th class="text-end">Overdue</th>
                        <th>Max Days Overdue</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customerSummary)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No outstanding payments.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($customerSummary as $cust): ?>
                    <tr>
                        <td><strong><?php echo $cust['business_name']; ?></strong></td>
                        <td><?php echo $cust['phone'] ?: '-'; ?></td>
                        <td><?php echo $cust['invoice_count']; ?></td>
                        <td class="text-end <?php echo $cust['total_due'] > $cust['credit_limit'] ? 'text-danger fw-bold' : ''; ?>">
                            <?php echo formatCurrency($cust['total_due']); ?>
                        </td>
                        <td class="text-end text-danger">
                            <?php echo $cust['overdue_amount'] > 0 ? formatCurrency($cust['overdue_amount']) : '-'; ?>
                        </td>
                        <td>
                            <?php if ($cust['max_days_overdue'] > 0): ?>
                                <span class="badge bg-danger"><?php echo $cust['max_days_overdue']; ?> days</span>
                            <?php else: ?>
                                <span class="badge bg-success">On Time</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($cust['total_due'] > $cust['credit_limit']): ?>
                                <span class="badge bg-danger">Over Limit</span>
                            <?php elseif ($cust['overdue_amount'] > 0): ?>
                                <span class="badge bg-warning">Overdue</span>
                            <?php else: ?>
                                <span class="badge bg-info">Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Detailed Outstanding Invoices -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detailed Outstanding Invoices</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Days Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($outstanding)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No outstanding invoices.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($outstanding as $inv): ?>
                    <tr class="<?php echo $inv['days_overdue'] > 30 ? 'table-danger' : ($inv['days_overdue'] > 0 ? 'table-warning' : ''); ?>">
                        <td><a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $inv['id']; ?>"><?php echo $inv['invoice_no']; ?></a></td>
                        <td><?php echo $inv['business_name']; ?></td>
                        <td><?php echo formatDate($inv['invoice_date']); ?></td>
                        <td><?php echo formatDate($inv['due_date']); ?></td>
                        <td><?php echo formatCurrency($inv['total_amount']); ?></td>
                        <td><?php echo formatCurrency($inv['paid_amount']); ?></td>
                        <td class="fw-bold"><?php echo formatCurrency($inv['balance_amount']); ?></td>
                        <td>
                            <?php if ($inv['days_overdue'] > 0): ?>
                                <span class="badge bg-danger"><?php echo $inv['days_overdue']; ?> days</span>
                            <?php else: ?>
                                <span class="badge bg-success"><?php echo abs($inv['days_overdue']); ?> days left</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
