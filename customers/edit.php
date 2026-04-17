<?php
/**
 * Edit Customer Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_BILLING]);

$pageTitle = 'Edit Customer';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
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
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    try {
        $stmt = $db->prepare("UPDATE customers SET 
            business_name = ?, contact_person = ?, phone = ?, email = ?, 
            gst_number = ?, pan_number = ?, address_line1 = ?, address_line2 = ?,
            city = ?, state = ?, pincode = ?, credit_limit = ?, payment_terms = ?, 
            is_active = ? WHERE id = ?");
        
        $stmt->execute([
            $data['business_name'], $data['contact_person'], $data['phone'], $data['email'],
            $data['gst_number'], $data['pan_number'], $data['address_line1'], $data['address_line2'],
            $data['city'], $data['state'], $data['pincode'], $data['credit_limit'],
            $data['payment_terms'], $data['is_active'], $customerId
        ]);
        
        logActivity('customer_updated', "Updated customer: {$data['business_name']}");
        redirectWithMessage('/customers/list.php', 'success', 'Customer updated successfully!');
        
    } catch (PDOException $e) {
        $error = 'Error updating customer: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-pencil"></i> Edit Customer</h2>
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
        <form method="POST" action="">
            <div class="row">
                <!-- Business Information -->
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Business Information</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Customer Code</label>
                        <input type="text" class="form-control" value="<?php echo $customer['customer_code']; ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Business Name <span class="text-danger">*</span></label>
                        <input type="text" name="business_name" class="form-control" value="<?php echo htmlspecialchars($customer['business_name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" value="<?php echo htmlspecialchars($customer['contact_person'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Tax Information -->
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Tax Information</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">GST Number</label>
                        <input type="text" name="gst_number" class="form-control" value="<?php echo htmlspecialchars($customer['gst_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="pan_number" class="form-control" value="<?php echo htmlspecialchars($customer['pan_number'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Credit Limit (₹)</label>
                        <input type="number" name="credit_limit" class="form-control" step="0.01" min="0" value="<?php echo $customer['credit_limit']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Terms (Days)</label>
                        <input type="number" name="payment_terms" class="form-control" min="0" value="<?php echo $customer['payment_terms']; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Balance</label>
                        <input type="text" class="form-control" value="<?php echo formatCurrency($customer['current_balance']); ?>" disabled>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?php echo $customer['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active Customer</label>
                    </div>
                </div>
            </div>
            
            <hr class="my-4">
            
            <!-- Address Information -->
            <h6 class="text-primary mb-3">Address</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" name="address_line1" class="form-control" value="<?php echo htmlspecialchars($customer['address_line1'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" name="address_line2" class="form-control" value="<?php echo htmlspecialchars($customer['address_line2'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="<?php echo htmlspecialchars($customer['pincode'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <a href="/customers/list.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Customer
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
