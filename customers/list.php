<?php

/**
 * Customer List Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_BILLING]);

$pageTitle = 'Customers';
$db = getDBConnection();

// Handle search
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$whereClause = "WHERE is_active = 1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (business_name LIKE ? OR contact_person LIKE ? OR phone LIKE ? OR gst_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
}

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM customers $whereClause");
$countStmt->execute($params);
$totalRecords = $countStmt->fetch()['total'];
$totalPages = ceil($totalRecords / $perPage);

// Get customers with invoice statistics
$query = "SELECT c.*, 
          COUNT(DISTINCT i.id) as total_invoices,
          COALESCE(SUM(i.total_amount), 0) as total_sales,
          COALESCE(SUM(i.paid_amount), 0) as total_paid,
          COALESCE(SUM(i.balance_amount), 0) as total_due,
          COUNT(DISTINCT CASE WHEN i.payment_status = 'pending' THEN i.id END) as pending_invoices,
          COUNT(DISTINCT CASE WHEN i.payment_status = 'partial' THEN i.id END) as partial_invoices,
          COUNT(DISTINCT CASE WHEN i.payment_status = 'paid' THEN i.id END) as paid_invoices,
          MAX(i.invoice_date) as last_invoice_date,
          MAX(i.invoice_no) as last_invoice_no
          FROM customers c
          LEFT JOIN invoices i ON c.id = i.customer_id
          $whereClause
          GROUP BY c.id
          ORDER BY c.business_name ASC LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-people"></i> Customers</h2>
    <a href="<?php echo BASE_URL; ?>/customers/add.php" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Add Customer
    </a>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Search by name, phone, GST number..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search): ?>
                        <a href="<?php echo BASE_URL; ?>/customers/list.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Customers Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Business Name</th>
                        <th>Phone</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-end">Total Sales</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                        <th class="text-center">Last Invoice</th>
                        <th class="text-end">Balance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <?php echo $search ? 'No customers found matching your search.' : 'No customers added yet.'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo $customer['customer_code'] ?: 'N/A'; ?></span></td>
                                <td>
                                    <strong><?php echo $customer['business_name']; ?></strong>
                                    <?php if ($customer['contact_person']): ?>
                                        <br><small class="text-muted"><?php echo $customer['contact_person']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $customer['phone'] ?: '-'; ?></td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center">
                                        <strong><?php echo $customer['total_invoices'] ?? 0; ?></strong>
                                        <small>
                                            <?php if (($customer['pending_invoices'] ?? 0) > 0): ?>
                                                <span class="badge bg-danger"><?php echo $customer['pending_invoices']; ?>P</span>
                                            <?php endif; ?>
                                            <?php if (($customer['partial_invoices'] ?? 0) > 0): ?>
                                                <span class="badge bg-warning"><?php echo $customer['partial_invoices']; ?>Part</span>
                                            <?php endif; ?>
                                            <?php if (($customer['paid_invoices'] ?? 0) > 0): ?>
                                                <span class="badge bg-success"><?php echo $customer['paid_invoices']; ?>✓</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <strong><?php echo formatCurrency($customer['total_sales'] ?? 0); ?></strong>
                                </td>
                                <td class="text-end text-success">
                                    <?php echo formatCurrency($customer['total_paid'] ?? 0); ?>
                                </td>
                                <td class="text-end">
                                    <?php if (($customer['total_due'] ?? 0) > 0): ?>
                                        <strong class="text-danger"><?php echo formatCurrency($customer['total_due']); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">₹0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($customer['last_invoice_no']): ?>
                                        <small>
                                            <strong><?php echo $customer['last_invoice_no']; ?></strong><br>
                                            <span class="text-muted"><?php echo formatDate($customer['last_invoice_date']); ?></span>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $customer['current_balance'] > 0 ? 'balance-negative' : 'balance-positive'; ?>">
                                    <strong><?php echo formatCurrency($customer['current_balance']); ?></strong>
                                    <?php if ($customer['current_balance'] < 0): ?>
                                        <br><small class="text-success">Advance</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>/customers/view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/billing/list.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-primary" title="View Invoices">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/payments/ledger.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-success" title="Ledger">
                                            <i class="bi bi-book"></i>
                                        </a>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>