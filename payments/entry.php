<?php
/**
 * Payment Entry Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Payment Entry';
$db = getDBConnection();

$invoiceId = intval($_GET['invoice_id'] ?? 0);
$customerId = intval($_GET['customer_id'] ?? 0);

// Get customers for dropdown
$stmt = $db->query("SELECT id, business_name, current_balance FROM customers WHERE is_active = 1 ORDER BY business_name");
$customers = $stmt->fetchAll();

// Get invoice details if invoice_id provided
$invoice = null;
if ($invoiceId) {
    $stmt = $db->prepare("SELECT i.*, c.business_name FROM invoices i JOIN customers c ON i.customer_id = c.id WHERE i.id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    if ($invoice) {
        $customerId = $invoice['customer_id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $customerId = intval($_POST['customer_id'] ?? 0);
        $invoiceId = intval($_POST['invoice_id'] ?? 0);
        $paymentDate = $_POST['payment_date'] ?? date(DB_DATE_FORMAT);
        $amount = floatval($_POST['amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $referenceNo = sanitize($_POST['reference_no'] ?? '');
        $bankName = sanitize($_POST['bank_name'] ?? '');
        $chequeNo = sanitize($_POST['cheque_no'] ?? '');
        $chequeDate = $_POST['cheque_date'] ?? null;
        $notes = sanitize($_POST['notes'] ?? '');
        
        if ($amount <= 0) {
            throw new Exception('Payment amount must be greater than zero.');
        }
        
        // Insert payment
        $stmt = $db->prepare("INSERT INTO payments 
            (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, bank_name, cheque_no, cheque_date, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $customerId, $invoiceId ?: null, $paymentDate, $amount, $paymentMethod,
            $referenceNo, $bankName, $chequeNo, $chequeDate, $notes, $_SESSION['user_id']
        ]);
        
        // Update customer balance
        $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance - ? WHERE id = ?");
        $stmt->execute([$amount, $customerId]);
        
        // Update invoice if applicable
        if ($invoiceId) {
            $stmt = $db->prepare("UPDATE invoices SET 
                paid_amount = paid_amount + ?, 
                balance_amount = balance_amount - ?,
                payment_status = CASE 
                    WHEN balance_amount - ? <= 0 THEN 'paid'
                    WHEN paid_amount + ? > 0 THEN 'partial'
                    ELSE 'pending'
                END
                WHERE id = ?");
            $stmt->execute([$amount, $amount, $amount, $amount, $invoiceId]);
        }
        
        // Add to customer ledger
        $referenceNo = $referenceNo ?: 'PAY-' . time();
        $stmt = $db->prepare("INSERT INTO customer_ledger 
            (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
            SELECT id, ?, 'payment', ?, ?, 0, ?, current_balance, ? FROM customers WHERE id = ?");
        $stmt->execute([$paymentDate, $invoiceId ?: null, $referenceNo, $amount, $notes, $customerId]);
        
        $db->commit();
        
        logActivity('payment_received', "Received payment of " . formatCurrency($amount) . " from customer ID: $customerId");
        redirectWithMessage('/payments/ledger.php?customer_id=' . $customerId, 'success', 'Payment recorded successfully!');
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-cash-coin"></i> Payment Entry</h2>
    <a href="<?php echo BASE_URL; ?>/payments/ledger.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Ledger
    </a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="paymentForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id" class="form-select" required onchange="loadCustomerInvoices()">
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" 
                                        data-balance="<?php echo $customer['current_balance']; ?>"
                                        <?php echo $customerId == $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo $customer['business_name']; ?> (Bal: ₹<?php echo number_format($customer['current_balance'], 2); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Against Invoice (Optional)</label>
                            <select name="invoice_id" id="invoice_id" class="form-select">
                                <option value="">-- General Payment --</option>
                                <?php if ($invoice): ?>
                                <option value="<?php echo $invoice['id']; ?>" selected>
                                    <?php echo $invoice['invoice_no']; ?> - Balance: <?php echo formatCurrency($invoice['balance_amount']); ?>
                                </option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="<?php echo date(DB_DATE_FORMAT); ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select name="payment_method" id="payment_method" class="form-select" required onchange="togglePaymentFields()">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="upi">UPI</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="reference_no" class="form-control" placeholder="Transaction ID, UPI Ref, etc.">
                        </div>
                        
                        <div class="col-md-6 mb-3" id="bank_name_field" style="display:none;">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row" id="cheque_fields" style="display:none;">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cheque Number</label>
                            <input type="text" name="cheque_no" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cheque Date</label>
                            <input type="date" name="cheque_date" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Any additional information..."></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>/payments/ledger.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Customer Balance</h5>
            </div>
            <div class="card-body">
                <div id="customer_balance_info">
                    <p class="text-muted">Select a customer to view balance information.</p>
                </div>
            </div>
        </div>
        
        <?php if ($invoice): ?>
        <div class="card mt-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Invoice Details</h5>
            </div>
            <div class="card-body">
                <p><strong>Invoice #:</strong> <?php echo $invoice['invoice_no']; ?></p>
                <p><strong>Total:</strong> <?php echo formatCurrency($invoice['total_amount']); ?></p>
                <p><strong>Paid:</strong> <?php echo formatCurrency($invoice['paid_amount']); ?></p>
                <p class="mb-0"><strong>Balance:</strong> <span class="text-danger fw-bold"><?php echo formatCurrency($invoice['balance_amount']); ?></span></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function togglePaymentFields() {
    var method = document.getElementById('payment_method').value;
    var bankField = document.getElementById('bank_name_field');
    var chequeFields = document.getElementById('cheque_fields');
    
    if (method === 'bank' || method === 'cheque') {
        bankField.style.display = 'block';
    } else {
        bankField.style.display = 'none';
    }
    
    if (method === 'cheque') {
        chequeFields.style.display = 'flex';
    } else {
        chequeFields.style.display = 'none';
    }
}

function loadCustomerInvoices() {
    var customerId = document.getElementById('customer_id').value;
    var invoiceSelect = document.getElementById('invoice_id');
    var balanceInfo = document.getElementById('customer_balance_info');
    
    if (!customerId) {
        invoiceSelect.innerHTML = '<option value="">-- General Payment --</option>';
        balanceInfo.innerHTML = '<p class="text-muted">Select a customer to view balance information.</p>';
        return;
    }
    
    // Get customer balance
    var selectedOption = document.getElementById('customer_id').options[document.getElementById('customer_id').selectedIndex];
    var balance = selectedOption.getAttribute('data-balance');
    
    balanceInfo.innerHTML = '<h3 class="' + (balance > 0 ? 'text-danger' : 'text-success') + '">' + 
                           '₹' + parseFloat(balance).toFixed(2) + '</h3>' +
                           '<p class="text-muted">' + (balance > 0 ? 'Customer owes you' : 'You owe customer') + '</p>';
    
    // Load pending invoices
    $.ajax({
        url: '/ajax/get-customer-invoices.php',
        type: 'POST',
        data: { customer_id: customerId },
        dataType: 'json',
        success: function(response) {
            var html = '<option value="">-- General Payment --</option>';
            if (response.success && response.invoices.length > 0) {
                response.invoices.forEach(function(inv) {
                    html += '<option value="' + inv.id + '">' + 
                           inv.invoice_no + ' - Bal: ₹' + parseFloat(inv.balance_amount).toFixed(2) + 
                           '</option>';
                });
            }
            invoiceSelect.innerHTML = html;
        }
    });
}

// Load on page load if customer selected
<?php if ($customerId): ?>
document.addEventListener('DOMContentLoaded', function() {
    loadCustomerInvoices();
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
