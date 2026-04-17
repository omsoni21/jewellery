<?php
/**
 * Invoice List Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_BILLING, ROLE_ACCOUNTANT]);

$pageTitle = 'Invoices';
$db = getDBConnection();

// Handle filters
$search = $_GET['search'] ?? '';
$customerId = intval($_GET['customer_id'] ?? 0);
$status = $_GET['status'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(i.invoice_no LIKE ? OR c.business_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($customerId > 0) {
    $whereConditions[] = "i.customer_id = ?";
    $params[] = $customerId;
}

if (!empty($status)) {
    $whereConditions[] = "i.payment_status = ?";
    $params[] = $status;
}

if (!empty($fromDate)) {
    $whereConditions[] = "i.invoice_date >= ?";
    $params[] = $fromDate;
}

if (!empty($toDate)) {
    $whereConditions[] = "i.invoice_date <= ?";
    $params[] = $toDate;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM invoices i JOIN customers c ON i.customer_id = c.id $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get invoices
$query = "SELECT i.*, c.business_name, c.phone 
          FROM invoices i 
          JOIN customers c ON i.customer_id = c.id 
          $whereClause 
          ORDER BY i.created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Get customers for filter
$stmt = $db->query("SELECT id, business_name FROM customers WHERE is_active = 1 ORDER BY business_name");
$customers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-receipt"></i> Invoices</h2>
    <?php if (hasRole([ROLE_ADMIN, ROLE_BILLING])): ?>
    <a href="<?php echo BASE_URL; ?>/billing/create.php" class="btn btn-primary">
        <i class="bi bi-plus"></i> Create Invoice
    </a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-2">
                <input type="text" class="form-control" name="search" placeholder="Invoice # or Customer" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="customer_id" class="form-select">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $customer): ?>
                    <option value="<?php echo $customer['id']; ?>" <?php echo $customerId == $customer['id'] ? 'selected' : ''; ?>>
                        <?php echo $customer['business_name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="from_date" placeholder="From Date" value="<?php echo $fromDate; ?>">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="to_date" placeholder="To Date" value="<?php echo $toDate; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No invoices found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><strong><?php echo $invoice['invoice_no']; ?></strong></td>
                        <td>
                            <?php echo $invoice['business_name']; ?>
                            <small class="d-block text-muted"><?php echo $invoice['phone']; ?></small>
                        </td>
                        <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                        <td><?php echo formatDate($invoice['due_date']); ?></td>
                        <td><?php echo formatCurrency($invoice['total_amount']); ?></td>
                        <td><?php echo formatCurrency($invoice['paid_amount']); ?></td>
                        <td><?php echo formatCurrency($invoice['balance_amount']); ?></td>
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
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo BASE_URL; ?>/billing/view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/billing/print.php?id=<?php echo $invoice['id']; ?>" class="btn btn-secondary" title="Print" target="_blank">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <?php if ($invoice['balance_amount'] > 0 && hasRole([ROLE_ADMIN, ROLE_ACCOUNTANT])): ?>
                                <a href="<?php echo BASE_URL; ?>/payments/entry.php?invoice_id=<?php echo $invoice['id']; ?>" class="btn btn-success" title="Add Payment">
                                    <i class="bi bi-cash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&customer_id=<?php echo $customerId; ?>&status=<?php echo $status; ?>&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>">Previous</a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&customer_id=<?php echo $customerId; ?>&status=<?php echo $status; ?>&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&customer_id=<?php echo $customerId; ?>&status=<?php echo $status; ?>&from_date=<?php echo $fromDate; ?>&to_date=<?php echo $toDate; ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
