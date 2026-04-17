<?php

/**
 * View Customer Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_BILLING, ROLE_ACCOUNTANT]);

$pageTitle = 'View Customer';
$db = getDBConnection();

$customerId = intval($_GET['id'] ?? 0);

if (!$customerId) {
    redirectWithMessage('/customers/list.php', 'danger', 'Invalid customer ID.');
}

// Get customer details
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    redirectWithMessage('/customers/list.php', 'danger', 'Customer not found.');
}

// Get customer's ALL invoices with payment details
$stmt = $db->prepare("SELECT 
    i.*,
    COUNT(p.id) as payment_count,
    COALESCE(SUM(p.amount), 0) as total_payments
    FROM invoices i
    LEFT JOIN payments p ON i.id = p.invoice_id
    WHERE i.customer_id = ?
    GROUP BY i.id
    ORDER BY i.invoice_date DESC, i.invoice_no DESC");
$stmt->execute([$customerId]);
$invoices = $stmt->fetchAll();

// Get customer's ledger
$stmt = $db->prepare("SELECT * FROM customer_ledger WHERE customer_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 10");
$stmt->execute([$customerId]);
$ledgerEntries = $stmt->fetchAll();

// Calculate detailed statistics
$stmt = $db->prepare("SELECT 
    COUNT(*) as total_invoices,
    SUM(total_amount) as total_sales,
    SUM(paid_amount) as total_paid,
    SUM(balance_amount) as total_due,
    COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_count,
    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count
    FROM invoices WHERE customer_id = ?");
$stmt->execute([$customerId]);
$stats = $stmt->fetch();

// Get current customer balance
$stmt = $db->prepare("SELECT current_balance FROM customers WHERE id = ?");
$stmt->execute([$customerId]);
$customerBalance = $stmt->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person"></i> Customer Details</h2>
    <div>
        <a href="<?php echo BASE_URL; ?>/customers/list.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <a href="<?php echo BASE_URL; ?>/customers/edit.php?id=<?php echo $customerId; ?>" class="btn btn-warning">
            <i class="bi bi-pencil"></i> Edit
        </a>
    </div>
</div>

<!-- Customer Info Card -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?php echo $customer['business_name']; ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <h6 class="text-muted">Contact Information</h6>
                <p class="mb-1"><strong>Contact Person:</strong> <?php echo $customer['contact_person'] ?: '-'; ?></p>
                <p class="mb-1"><strong>Phone:</strong> <?php echo $customer['phone'] ?: '-'; ?></p>
                <p class="mb-0"><strong>Email:</strong> <?php echo $customer['email'] ?: '-'; ?></p>
            </div>
            <div class="col-md-3">
                <h6 class="text-muted">Tax Information</h6>
                <p class="mb-1"><strong>GST Number:</strong> <?php echo $customer['gst_number'] ?: '-'; ?></p>
                <p class="mb-1"><strong>PAN Number:</strong> <?php echo $customer['pan_number'] ?: '-'; ?></p>
                <p class="mb-0"><strong>Customer Code:</strong> <?php echo $customer['customer_code'] ?: '-'; ?></p>
            </div>
            <div class="col-md-3">
                <h6 class="text-muted">Address</h6>
                <p class="mb-1"><?php echo $customer['address_line1'] ?: '-'; ?></p>
                <p class="mb-1"><?php echo $customer['address_line2'] ?: ''; ?></p>
                <p class="mb-0"><?php echo ($customer['city'] ?: '-') . ', ' . ($customer['state'] ?: '') . ' - ' . ($customer['pincode'] ?: ''); ?></p>
            </div>
            <div class="col-md-3">
                <h6 class="text-muted">Account Settings</h6>
                <p class="mb-1"><strong>Credit Limit:</strong> <?php echo $customer['credit_limit'] > 0 ? formatCurrency($customer['credit_limit']) : '-'; ?></p>
                <p class="mb-1"><strong>Payment Terms:</strong> <?php echo $customer['payment_terms']; ?> days</p>
                <p class="mb-0"><strong>Status:</strong>
                    <?php if ($customer['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo $stats['total_invoices'] ?? 0; ?></h3>
                <p class="mb-0">Total Invoices</p>
                <small>
                    <?php if (($stats['pending_count'] ?? 0) > 0): ?>
                        <span class="badge bg-danger"><?php echo $stats['pending_count']; ?> Pending</span>
                    <?php endif; ?>
                    <?php if (($stats['partial_count'] ?? 0) > 0): ?>
                        <span class="badge bg-warning"><?php echo $stats['partial_count']; ?> Partial</span>
                    <?php endif; ?>
                    <?php if (($stats['paid_count'] ?? 0) > 0): ?>
                        <span class="badge bg-success"><?php echo $stats['paid_count']; ?> Paid</span>
                    <?php endif; ?>
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3><?php echo formatCurrency($stats['total_sales'] ?? 0); ?></h3>
                <p class="mb-0">Total Sales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?php echo formatCurrency($stats['total_paid'] ?? 0); ?></h3>
                <p class="mb-0">Total Paid</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card <?php echo ($customerBalance > 0) ? 'bg-danger' : 'bg-success'; ?> text-white">
            <div class="card-body text-center">
                <h3><?php echo formatCurrency(abs($customerBalance ?? 0)); ?></h3>
                <p class="mb-0"><?php echo $customerBalance > 0 ? 'Amount Due' : 'Advance'; ?></p>
                <?php if ($customerBalance < 0): ?>
                    <small class="badge bg-light text-success">Customer Credit</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Invoices -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Invoices</h5>
                <a href="<?php echo BASE_URL; ?>/billing/list.php?customer_id=<?php echo $customerId; ?>" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No invoices yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td><a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $inv['id']; ?>"><?php echo $inv['invoice_no']; ?></a></td>
                                        <td><?php echo formatDate($inv['invoice_date']); ?></td>
                                        <td><?php echo formatCurrency($inv['total_amount']); ?></td>
                                        <td>
                                            <?php if ($inv['payment_status'] === 'paid'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php elseif ($inv['payment_status'] === 'partial'): ?>
                                                <span class="badge bg-warning">Partial</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Pending</span>
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
    </div>

    <!-- Recent Ledger Entries -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="<?php echo BASE_URL; ?>/payments/ledger.php?customer_id=<?php echo $customerId; ?>" class="btn btn-sm btn-primary">View Ledger</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Debit</th>
                                <th>Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ledgerEntries)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">No transactions yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ledgerEntries as $entry): ?>
                                    <tr>
                                        <td><?php echo formatDate($entry['transaction_date']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $entry['transaction_type'] === 'invoice' ? 'primary' : ($entry['transaction_type'] === 'payment' ? 'success' : 'secondary'); ?>">
                                                <?php echo ucfirst($entry['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td class="text-danger"><?php echo $entry['debit'] > 0 ? formatCurrency($entry['debit']) : '-'; ?></td>
                                        <td class="text-success"><?php echo $entry['credit'] > 0 ? formatCurrency($entry['credit']) : '-'; ?></td>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>