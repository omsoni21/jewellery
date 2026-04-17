<?php

/**
 * GST Filing Page
 * Generate and file GST returns
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/gst_api.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'GST Filing';
$db = getDBConnection();

// Get filing period
$period = isset($_GET['period']) ? sanitize($_GET['period']) : date('mY');
$returnType = isset($_GET['return_type']) ? sanitize($_GET['return_type']) : 'GSTR1';

// Generate GSTR-1 data
$gstr1Data = generateGSTR1Data($db, $period);

// Get company settings
$stmt = $db->query("SELECT * FROM company_settings LIMIT 1");
$company = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-file-earmark-text"></i> GST Filing</h2>
    <div>
        <a href="<?php echo BASE_URL; ?>/settings/company.php#gst-api" class="btn btn-outline-primary">
            <i class="bi bi-gear"></i> Configure API
        </a>
        <a href="<?php echo BASE_URL; ?>/reports/gst.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to GST Report
        </a>
    </div>
</div>

<!-- Filing Period Selection -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Select Filing Period</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Return Type</label>
                <select name="return_type" class="form-select">
                    <option value="GSTR1" <?php echo $returnType === 'GSTR1' ? 'selected' : ''; ?>>GSTR-1 (Outward Supplies)</option>
                    <option value="GSTR3B" <?php echo $returnType === 'GSTR3B' ? 'selected' : ''; ?>>GSTR-3B (Monthly Return)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Period (MMYYYY)</label>
                <input type="text" name="period" class="form-control" value="<?php echo $period; ?>" placeholder="MMYYYY" maxlength="6">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary d-block">
                    <i class="bi bi-search"></i> Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($gstr1Data['success']): ?>
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card primary">
                <div class="card-body">
                    <div class="stats-icon"><i class="bi bi-receipt"></i></div>
                    <div class="stats-number"><?php echo $gstr1Data['total_invoices']; ?></div>
                    <div class="stats-label">Total Invoices</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <div class="stats-icon"><i class="bi bi-currency-rupee"></i></div>
                    <div class="stats-number"><?php echo formatCurrency($gstr1Data['total_taxable_value']); ?></div>
                    <div class="stats-label">Taxable Value</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card info">
                <div class="card-body">
                    <div class="stats-icon"><i class="bi bi-percent"></i></div>
                    <div class="stats-number"><?php echo formatCurrency($gstr1Data['total_igst'] + $gstr1Data['total_cgst'] + $gstr1Data['total_sgst']); ?></div>
                    <div class="stats-label">Total GST</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <div class="stats-icon"><i class="bi bi-building"></i></div>
                    <div class="stats-number"><?php echo count($gstr1Data['b2b']); ?></div>
                    <div class="stats-label">B2B Customers</div>
                </div>
            </div>
        </div>
    </div>

    <!-- GST Breakdown -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">GST Breakdown</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td>IGST:</td>
                            <td class="text-end"><?php echo formatCurrency($gstr1Data['total_igst']); ?></td>
                        </tr>
                        <tr>
                            <td>CGST:</td>
                            <td class="text-end"><?php echo formatCurrency($gstr1Data['total_cgst']); ?></td>
                        </tr>
                        <tr>
                            <td>SGST:</td>
                            <td class="text-end"><?php echo formatCurrency($gstr1Data['total_sgst']); ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Total GST:</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($gstr1Data['total_igst'] + $gstr1Data['total_cgst'] + $gstr1Data['total_sgst']); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Filing Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="downloadJSON()">
                            <i class="bi bi-download"></i> Download JSON (for GST Portal)
                        </button>
                        <button class="btn btn-outline-success" onclick="downloadExcel()">
                            <i class="bi bi-file-excel"></i> Download Excel Report
                        </button>
                        <button class="btn btn-primary" onclick="fileReturn()" <?php echo empty($company['gst_api_key']) ? 'disabled' : ''; ?>>
                            <i class="bi bi-cloud-upload"></i> File Return via API
                            <?php if (empty($company['gst_api_key'])): ?>
                                <small class="d-block">(API credentials not configured)</small>
                            <?php endif; ?>
                        </button>
                    </div>

                    <?php if (empty($company['gst_api_key'])): ?>
                        <hr>
                        <div class="alert alert-warning mb-0">
                            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> API Credentials Required</h6>
                            <p class="mb-2 small">To file GST returns directly via API, you need to configure your credentials:</p>
                            <ol class="mb-2 small ps-3">
                                <li>Go to <a href="<?php echo BASE_URL; ?>/settings/company.php" class="alert-link">Settings → Company Settings</a></li>
                                <li>Scroll to "GST API Credentials" section</li>
                                <li>Enter your API Key, Secret, Username & Password</li>
                                <li>Save settings and return here</li>
                            </ol>
                            <a href="<?php echo BASE_URL; ?>/settings/company.php" class="btn btn-sm btn-warning">
                                <i class="bi bi-gear"></i> Configure Now
                            </a>
                        </div>
                    <?php else: ?>
                        <hr>
                        <div class="alert alert-success mb-0">
                            <h6 class="alert-heading"><i class="bi bi-check-circle"></i> API Configured</h6>
                            <p class="mb-0 small">Your GST API credentials are configured and ready to use.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- B2B Invoices -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">B2B Invoices (Registered Taxpayers)</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($gstr1Data['b2b'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Customer GSTIN</th>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Taxable Value</th>
                                <th>CGST</th>
                                <th>SGST</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gstr1Data['b2b'] as $b2b): ?>
                                <?php foreach ($b2b['inv'] as $inv): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($b2b['ctin']); ?></td>
                                        <td><?php echo htmlspecialchars($inv['inum']); ?></td>
                                        <td><?php echo formatDate($inv['idt']); ?></td>
                                        <td><?php echo formatCurrency($inv['val']); ?></td>
                                        <td><?php echo formatCurrency($inv['items'][0]['itm_det']['camt']); ?></td>
                                        <td><?php echo formatCurrency($inv['items'][0]['itm_det']['samt']); ?></td>
                                        <td><?php echo formatCurrency($inv['val']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted">No B2B invoices for this period</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- JSON Preview (Hidden) -->
    <!--
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">GSTR-1 JSON Preview</h5>
        </div>
        <div class="card-body">
            <pre class="bg-light p-3 rounded"><code id="jsonPreview"><?php echo json_encode($gstr1Data, JSON_PRETTY_PRINT); ?></code></pre>
        </div>
    </div>
    -->

    <script>
        // Store GSTR-1 data for download
        var gstr1Data = <?php echo json_encode($gstr1Data); ?>;

        function downloadJSON() {
            var dataStr = JSON.stringify(gstr1Data, null, 2);
            var dataUri = 'data:application/json;charset=utf-8,' + encodeURIComponent(dataStr);

            var exportFileDefaultName = 'GSTR1_<?php echo $period; ?>.json';

            var linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }

        function downloadExcel() {
            // Create CSV content
            var csv = 'Invoice No,Date,Customer GSTIN,Customer Name,Taxable Value,CGST,SGST,IGST,Total\n';

            gstr1Data.b2b.forEach(function(b2b) {
                b2b.inv.forEach(function(inv) {
                    var item = inv.items[0].itm_det;
                    csv += inv.inum + ',' + inv.idt + ',' + b2b.ctin + ',,' + item.txval + ',' + item.camt + ',' + item.samt + ',' + item.iamt + ',' + inv.val + '\n';
                });
            });

            var dataUri = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
            var linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', 'GSTR1_<?php echo $period; ?>.csv');
            linkElement.click();
        }

        function fileReturn() {
            if (!confirm('Are you sure you want to file this return? This action cannot be undone.')) {
                return;
            }

            alert('GST API filing would be initiated here.\n\nIn production, this would:\n1. Authenticate with GST portal\n2. Submit the GSTR-1 data\n3. Return filing acknowledgment\n\nCurrently running in simulation mode.');
        }
    </script>

<?php else: ?>
    <div class="alert alert-danger">
        Error generating GSTR-1 data: <?php echo $gstr1Data['error'] ?? 'Unknown error'; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>