<?php

/**
 * Dashboard Page
 */

require_once __DIR__ . '/includes/functions.php';
// ✅ SAFE fallback for formatCurrency
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount)
    {
        return '₹' . number_format((float)$amount, 2);
    }
}
requireAuth();


$pageTitle = 'Dashboard';
$db = getDBConnection();

// Get dashboard statistics
$today = date(DB_DATE_FORMAT);
$currentMonth = date('Y-m');

// Today's sales (only today's new invoices, not old dues)
$stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE DATE(invoice_date) = ?");
$stmt->execute([$today]);
$todaySales = $stmt->fetch()['total'];

// Monthly sales
$stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
$stmt->execute([$currentMonth]);
$monthlySales = $stmt->fetch()['total'];

// Total customers
$stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE is_active = 1");
$totalCustomers = $stmt->fetch()['total'];

// Outstanding amount
$stmt = $db->query("SELECT COALESCE(SUM(balance_amount), 0) as total FROM invoices WHERE payment_status != 'paid'");
$outstandingAmount = $stmt->fetch()['total'];

// Recent invoices
$stmt = $db->query("SELECT i.*, c.business_name FROM invoices i 
                    JOIN customers c ON i.customer_id = c.id 
                    ORDER BY i.created_at DESC LIMIT 5");
$recentInvoices = $stmt->fetchAll();


// Today's metal rates
$stmt = $db->prepare("SELECT metal_type, purity, rate_per_gram FROM metal_rates WHERE rate_date = ? ORDER BY metal_type, purity");
$stmt->execute([$today]);
$todayRates = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 mb-4">
        <div class="card stats-card primary">
            <div class="card-body">
                <div class="stats-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stats-number"><?php echo formatCurrency($todaySales); ?></div>
                <div class="stats-label">Today's Sales</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card stats-card success">
            <div class="card-body">
                <div class="stats-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="stats-number"><?php echo formatCurrency($monthlySales); ?></div>
                <div class="stats-label">Monthly Sales</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card stats-card info">
            <div class="card-body">
                <div class="stats-icon"><i class="bi bi-people"></i></div>
                <div class="stats-number"><?php echo $totalCustomers; ?></div>
                <div class="stats-label">Total Customers</div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card stats-card warning">
            <div class="card-body">
                <div class="stats-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="stats-number"><?php echo formatCurrency($outstandingAmount); ?></div>
                <div class="stats-label">Outstanding</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Metal Rates -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-currency-exchange"></i> Today's Metal Rates</h5>
            </div>
            <div class="card-body">
                <?php if (empty($todayRates)): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-circle"></i> No rates set for today.
                        <a href="<?php echo BASE_URL; ?>/settings/rates.php" class="alert-link">Set rates now</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($todayRates as $rate): ?>
                            <div class="col-6 mb-3">
                                <div class="rate-display">
                                    <div class="rate-value"><?php echo formatCurrency($rate['rate_per_gram']); ?></div>
                                    <div class="rate-label"><?php echo ucfirst($rate['metal_type']) . ' ' . $rate['purity']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>/settings/rates.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-gear"></i> Manage Rates
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Recent Invoices -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Recent Invoices</h5>
                <a href="<?php echo BASE_URL; ?>/billing/list.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentInvoices)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No invoices yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentInvoices as $invoice): ?>
                                    <tr>
                                        <td><strong><?php echo $invoice['invoice_no']; ?></strong></td>
                                        <td><?php echo $invoice['business_name']; ?></td>
                                        <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                                        <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                                        <td>
                                            <?php if ($invoice['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($invoice['payment_status'] === 'partial'): ?>
                                                <span class="badge bg-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (hasRole([ROLE_ADMIN, ROLE_BILLING])): ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>/billing/create.php" class="btn btn-primary w-100 py-3">
                                <i class="bi bi-receipt fs-3 d-block mb-2"></i>
                                Create Invoice
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>/customers/add.php" class="btn btn-success w-100 py-3">
                                <i class="bi bi-person-plus fs-3 d-block mb-2"></i>
                                Add Customer
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (hasRole([ROLE_ADMIN, ROLE_ACCOUNTANT])): ?>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>/payments/entry.php" class="btn btn-info w-100 py-3">
                                <i class="bi bi-cash-coin fs-3 d-block mb-2"></i>
                                Payment Entry
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="<?php echo BASE_URL; ?>/inventory/inward.php" class="btn btn-warning w-100 py-3">
                                <i class="bi bi-box-arrow-in-down fs-3 d-block mb-2"></i>
                                Stock Inward
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>