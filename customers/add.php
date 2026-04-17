<?php
/**
 * Add Customer Page
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gst_api.php';
requireRole([ROLE_ADMIN, ROLE_BILLING]);

$pageTitle = 'Add Customer';
$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate customer code
    $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(customer_code, 4) AS UNSIGNED)) as max_num FROM customers WHERE customer_code LIKE 'CUST%'");
    $result = $stmt->fetch();
    $nextNum = ($result['max_num'] ?? 0) + 1;
    $customerCode = 'CUST' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    
    $data = [
        'customer_code' => $customerCode,
        'business_name' => sanitize($_POST['business_name'] ?? ''),
        'contact_person' => sanitize($_POST['contact_person'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'gst_number' => sanitize($_POST['gst_number'] ?? ''),
        'pan_number' => sanitize($_POST['pan_number'] ?? ''),
        'address_line1' => sanitize($_POST['address_line1'] ?? ''),
        'address_line2' => sanitize($_POST['address_line2'] ?? ''),
        'city' => sanitize($_POST['city'] ?? ''),
        'state' => sanitize($_POST['state'] ?? ''),
        'pincode' => sanitize($_POST['pincode'] ?? ''),
        'credit_limit' => floatval($_POST['credit_limit'] ?? 0),
        'payment_terms' => intval($_POST['payment_terms'] ?? 30),
        'opening_balance' => floatval($_POST['opening_balance'] ?? 0),
        'current_balance' => floatval($_POST['opening_balance'] ?? 0)
    ];
    
    try {
        $stmt = $db->prepare("INSERT INTO customers 
            (customer_code, business_name, contact_person, phone, email, gst_number, pan_number,
             address_line1, address_line2, city, state, pincode, credit_limit, payment_terms, opening_balance, current_balance) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['customer_code'], $data['business_name'], $data['contact_person'], 
            $data['phone'], $data['email'], $data['gst_number'], $data['pan_number'],
            $data['address_line1'], $data['address_line2'], $data['city'], 
            $data['state'], $data['pincode'], $data['credit_limit'], 
            $data['payment_terms'], $data['opening_balance'], $data['current_balance']
        ]);
        
        $customerId = $db->lastInsertId();
        
        // Add opening balance to ledger if any
        if ($data['opening_balance'] != 0) {
            $stmt = $db->prepare("INSERT INTO customer_ledger 
                (customer_id, transaction_date, transaction_type, reference_no, debit, credit, balance, notes) 
                VALUES (?, CURDATE(), 'opening', ?, ?, ?, ?, 'Opening Balance')");
            
            if ($data['opening_balance'] > 0) {
                $stmt->execute([$customerId, $data['customer_code'], $data['opening_balance'], 0, $data['opening_balance']]);
            } else {
                $stmt->execute([$customerId, $data['customer_code'], 0, abs($data['opening_balance']), $data['opening_balance']]);
            }
        }
        
        logActivity('customer_created', "Created customer: {$data['business_name']}");
        redirectWithMessage('/customers/list.php', 'success', 'Customer added successfully!');
        
    } catch (PDOException $e) {
        $error = 'Error adding customer: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-person-plus"></i> Add Customer</h2>
    <a href="<?php echo BASE_URL; ?>/customers/list.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to List
    </a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Customer Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="row">
                <!-- Business Information -->
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Business Information</h6>
                    
                    <div class="mb-3">
                        <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="business_name" name="business_name" required>
                        <div class="invalid-feedback">Please enter business name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_person" class="form-label">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                </div>
                
                <!-- Tax Information -->
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Tax Information</h6>
                    
                    <div class="mb-3">
                        <label for="gst_number" class="form-label">GST Number</label>
                        <?php echo getGSTINVerificationWidget('gst_number', ['business_name' => 'business_name', 'address_line1' => 'address_line1', 'city' => 'city', 'state' => 'state']); ?>
                        <small class="text-muted">Enter 15-digit GSTIN and click Verify to auto-fill details</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pan_number" class="form-label">PAN Number</label>
                        <input type="text" class="form-control" id="pan_number" name="pan_number" placeholder="AAAAA0000A">
                    </div>
                    
                    <div class="mb-3">
                        <label for="credit_limit" class="form-label">Credit Limit (₹)</label>
                        <input type="number" class="form-control" id="credit_limit" name="credit_limit" step="0.01" min="0" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_terms" class="form-label">Payment Terms (Days)</label>
                        <input type="number" class="form-control" id="payment_terms" name="payment_terms" min="0" value="30">
                    </div>
                    
                    <div class="mb-3">
                        <label for="opening_balance" class="form-label">Opening Balance (₹)</label>
                        <input type="number" class="form-control" id="opening_balance" name="opening_balance" step="0.01" value="0">
                        <small class="text-muted">Positive = Customer owes you, Negative = You owe customer</small>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Address Information -->
            <h6 class="text-primary mb-3">Address</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="address_line1" class="form-label">Address Line 1</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address_line2" class="form-label">Address Line 2</label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pincode" class="form-label">Pincode</label>
                        <input type="text" class="form-control" id="pincode" name="pincode">
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="/customers/list.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Save Customer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
