<?php

/**
 * Company Settings Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole(ROLE_ADMIN);

$pageTitle = 'Company Settings';
$db = getDBConnection();

// Get current settings
$stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
$company = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'company_name' => sanitize($_POST['company_name'] ?? ''),
        'gst_number' => sanitize($_POST['gst_number'] ?? ''),
        'pan_number' => sanitize($_POST['pan_number'] ?? ''),
        'address_line1' => sanitize($_POST['address_line1'] ?? ''),
        'address_line2' => sanitize($_POST['address_line2'] ?? ''),
        'city' => sanitize($_POST['city'] ?? ''),
        'state' => sanitize($_POST['state'] ?? ''),
        'pincode' => sanitize($_POST['pincode'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'bank_name' => sanitize($_POST['bank_name'] ?? ''),
        'bank_account_no' => sanitize($_POST['bank_account_no'] ?? ''),
        'bank_ifsc' => sanitize($_POST['bank_ifsc'] ?? ''),
        'bank_branch' => sanitize($_POST['bank_branch'] ?? ''),
        'gst_rate' => floatval($_POST['gst_rate'] ?? 3),
        'gst_api_key' => sanitize($_POST['gst_api_key'] ?? ''),
        'gst_api_secret' => sanitize($_POST['gst_api_secret'] ?? ''),
        'gst_username' => sanitize($_POST['gst_username'] ?? ''),
        'gst_password' => sanitize($_POST['gst_password'] ?? '')
    ];

    try {
        if ($company) {
            // Update existing
            $stmt = $db->prepare("UPDATE company_settings SET 
                company_name = ?, gst_number = ?, pan_number = ?, address_line1 = ?, address_line2 = ?,
                city = ?, state = ?, pincode = ?, phone = ?, email = ?,
                bank_name = ?, bank_account_no = ?, bank_ifsc = ?, bank_branch = ?, gst_rate = ?,
                gst_api_key = ?, gst_api_secret = ?, gst_username = ?, gst_password = ?
                WHERE id = ?");
            $stmt->execute([
                $data['company_name'],
                $data['gst_number'],
                $data['pan_number'],
                $data['address_line1'],
                $data['address_line2'],
                $data['city'],
                $data['state'],
                $data['pincode'],
                $data['phone'],
                $data['email'],
                $data['bank_name'],
                $data['bank_account_no'],
                $data['bank_ifsc'],
                $data['bank_branch'],
                $data['gst_rate'],
                $data['gst_api_key'],
                $data['gst_api_secret'],
                $data['gst_username'],
                $data['gst_password'],
                $company['id']
            ]);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO company_settings 
                (company_name, gst_number, pan_number, address_line1, address_line2, city, state, pincode, phone, email,
                 bank_name, bank_account_no, bank_ifsc, bank_branch, gst_rate, gst_api_key, gst_api_secret, gst_username, gst_password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['company_name'],
                $data['gst_number'],
                $data['pan_number'],
                $data['address_line1'],
                $data['address_line2'],
                $data['city'],
                $data['state'],
                $data['pincode'],
                $data['phone'],
                $data['email'],
                $data['bank_name'],
                $data['bank_account_no'],
                $data['bank_ifsc'],
                $data['bank_branch'],
                $data['gst_rate'],
                $data['gst_api_key'],
                $data['gst_api_secret'],
                $data['gst_username'],
                $data['gst_password']
            ]);
        }

        logActivity('company_settings_updated', 'Updated company settings');
        redirectWithMessage('/settings/company.php', 'success', 'Company settings updated successfully!');
    } catch (Exception $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-building"></i> Company Settings</h2>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Company Information</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Basic Information</h6>

                    <div class="mb-3">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo $company['company_name'] ?? ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">GST Number</label>
                        <input type="text" name="gst_number" class="form-control" value="<?php echo $company['gst_number'] ?? ''; ?>" placeholder="00AAAAA0000A1Z5">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">PAN Number</label>
                        <input type="text" name="pan_number" class="form-control" value="<?php echo $company['pan_number'] ?? ''; ?>" placeholder="AAAAA0000A">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $company['phone'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $company['email'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Default GST Rate (%)</label>
                        <input type="number" name="gst_rate" class="form-control" value="<?php echo $company['gst_rate'] ?? GST_RATE; ?>" step="0.01" min="0">
                    </div>
                </div>

                <div class="col-md-6">
                    <h6 class="text-primary mb-3">Address</h6>

                    <div class="mb-3">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" name="address_line1" class="form-control" value="<?php echo $company['address_line1'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" name="address_line2" class="form-control" value="<?php echo $company['address_line2'] ?? ''; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="<?php echo $company['city'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="<?php echo $company['state'] ?? ''; ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="<?php echo $company['pincode'] ?? ''; ?>">
                    </div>

                    <h6 class="text-primary mb-3 mt-4">Bank Details</h6>

                    <div class="mb-3">
                        <label class="form-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" value="<?php echo $company['bank_name'] ?? ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="bank_account_no" class="form-control" value="<?php echo $company['bank_account_no'] ?? ''; ?>">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control" value="<?php echo $company['bank_ifsc'] ?? ''; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Branch</label>
                            <input type="text" name="bank_branch" class="form-control" value="<?php echo $company['bank_branch'] ?? ''; ?>">
                        </div>
                    </div>

                    <h6 class="text-primary mb-3 mt-4">GST API Credentials</h6>

                    <div class="mb-3">
                        <label class="form-label">GST API Key</label>
                        <input type="text" name="gst_api_key" class="form-control" value="<?php echo $company['gst_api_key'] ?? ''; ?>" placeholder="Enter your GST API Key">
                        <small class="text-muted">Get this from GST Portal or your API provider</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">GST API Secret</label>
                        <input type="password" name="gst_api_secret" class="form-control" value="<?php echo $company['gst_api_secret'] ?? ''; ?>" placeholder="Enter your GST API Secret">
                        <small class="text-muted">Keep this secure and never share</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">GST Portal Username</label>
                        <input type="text" name="gst_username" class="form-control" value="<?php echo $company['gst_username'] ?? ''; ?>" placeholder="Enter GST portal username">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">GST Portal Password</label>
                        <input type="password" name="gst_password" class="form-control" value="<?php echo $company['gst_password'] ?? ''; ?>" placeholder="Enter GST portal password">
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>