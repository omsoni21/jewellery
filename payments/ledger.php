<?php
/**
 * Customer Ledger Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Customer Ledger';
$db = getDBConnection();

$customerId = intval($_GET['customer_id'] ?? 0);

// Get all customers for dropdown
$stmt = $db->query("SELECT id, business_name, current_balance FROM customers WHERE is_active = 1 ORDER BY business_name");
$customers = $stmt->fetchAll();

$customer = null;
$ledger = [];

if ($customerId) {
    // Get customer details
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    // Get ledger entries
    $stmt = $db->prepare("SELECT cl.*, i.invoice_no 
                          FROM customer_ledger cl 
                          LEFT JOIN invoices i ON cl.reference_id = i.id AND cl.transaction_type = 'invoice'
                          WHERE cl.customer_id = ? 
                          ORDER BY cl.transaction_date DESC, cl.id DESC");
    $stmt->execute([$customerId]);
    $ledger = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-book"></i> Customer Ledger</h2>
    <a href="<?php echo BASE_URL; ?>/payments/entry.php" class="btn btn-success">
        <i class="bi bi-cash-coin"></i> Record Payment
    </a>
</div>

<!-- Customer Selection -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Select Customer</label>
                <select name="customer_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $cust): ?>
                    <option value="<?php echo $cust['id']; ?>" <?php echo $customerId == $cust['id'] ? 'selected' : ''; ?>>
                        <?php echo $cust['business_name']; ?> (Balance: ₹<?php echo number_format($cust['current_balance'], 2); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($customer): ?>
<!-- Customer Info -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><?php echo $customer['business_name']; ?></h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <p class="mb-1"><strong>Contact:</strong> <?php echo $customer['contact_person'] ?: '-'; ?></p>
                <p class="mb-0"><strong>Phone:</strong> <?php echo $customer['phone'] ?: '-'; ?></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>GST:</strong> <?php echo $customer['gst_number'] ?: '-'; ?></p>
                <p class="mb-0"><strong>Credit Limit:</strong> <?php echo $customer['credit_limit'] > 0 ? formatCurrency($customer['credit_limit']) : '-'; ?></p>
            </div>
            <div class="col-md-3">
                <p class="mb-1"><strong>Opening Balance:</strong> <?php echo formatCurrency($customer['opening_balance']); ?></p>
                <p class="mb-0"><strong>Current Balance:</strong> <span class="<?php echo $customer['current_balance'] > 0 ? 'text-danger fw-bold' : 'text-success'; ?>"><?php echo formatCurrency($customer['current_balance']); ?></span></p>
            </div>
            <div class="col-md-3 text-end">
                <a href="<?php echo BASE_URL; ?>/customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">
                    <i class="bi bi-eye"></i> View Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Ledger Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Transaction History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Notes</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ledger)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No transactions found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($ledger as $entry): ?>
                    <tr>
                        <td><?php echo formatDate($entry['transaction_date']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $entry['transaction_type'] === 'invoice' ? 'primary' : ($entry['transaction_type'] === 'payment' ? 'success' : 'secondary'); ?>">
                                <?php echo ucfirst($entry['transaction_type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($entry['transaction_type'] === 'invoice' && $entry['reference_id']): ?>
                                <a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $entry['reference_id']; ?>"><?php echo $entry['reference_no']; ?></a>
                            <?php else: ?>
                                <?php echo $entry['reference_no'] ?: '-'; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $entry['notes'] ?: '-'; ?></td>
                        <td class="text-end text-danger">
                            <?php echo $entry['debit'] > 0 ? formatCurrency($entry['debit']) : '-'; ?>
                        </td>
                        <td class="text-end text-success">
                            <?php echo $entry['credit'] > 0 ? formatCurrency($entry['credit']) : '-'; ?>
                        </td>
                        <td class="text-end fw-bold">
                            <?php echo formatCurrency($entry['balance']); ?>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Please select a customer to view their ledger.
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
