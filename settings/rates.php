<?php

/**
 * Metal Rates Management Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole(ROLE_ADMIN);

$pageTitle = 'Metal Rates';
$db = getDBConnection();

// Handle AJAX request for live rates
if (isset($_GET['ajax']) && $_GET['ajax'] == 1 && isset($_GET['action']) && $_GET['action'] == 'fetch_live_rates') {
    header('Content-Type: application/json');
    $liveRates = fetchLiveMetalRates();

    if (isset($liveRates['gold_24k']) && $liveRates['gold_24k'] > 0) {
        echo json_encode([
            'success' => true,
            'rates' => $liveRates
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $liveRates['error'] ?? 'Unable to fetch live rates'
        ]);
    }
    exit;
}

// Function to fetch live gold/silver rates from API
function fetchLiveMetalRates()
{
    $liveRates = [
        'gold_24k' => null,
        'gold_22k' => null,
        'gold_18k' => null,
        'gold_14k' => null,
        'silver_999' => null,
        'silver_925' => null,
        'last_updated' => null,
        'source' => null
    ];

    try {
        // Try multiple free APIs for Indian gold/silver rates

        // API 1: Gold-Silver Rates API (example)
        $api_urls = [
            'https://api.gold-api.com/price/XAU', // Gold price in USD
            'https://api.gold-api.com/price/XAG'  // Silver price in USD
        ];

        $gold_usd = null;
        $silver_usd = null;

        // Fetch Gold price in USD
        $ch = curl_init($api_urls[0]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['price'])) {
                $gold_usd = floatval($data['price']);
            }
        }

        // Fetch Silver price in USD
        $ch = curl_init($api_urls[1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['price'])) {
                $silver_usd = floatval($data['price']);
            }
        }

        // Fetch USD to INR exchange rate
        $usd_to_inr = 83.50; // Default fallback rate
        $ch = curl_init('https://api.exchangerate-api.com/v4/latest/USD');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code == 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['rates']['INR'])) {
                $usd_to_inr = floatval($data['rates']['INR']);
            }
        }

        // Calculate Indian rates (per gram)
        // 1 troy ounce = 31.1035 grams
        if ($gold_usd) {
            $gold_inr_per_oz = $gold_usd * $usd_to_inr;
            $gold_inr_per_gram = $gold_inr_per_oz / 31.1035;

            // Add approx 2-3% for Indian market premium
            $gold_inr_per_gram *= 1.025;

            $liveRates['gold_24k'] = round($gold_inr_per_gram, 2);
            $liveRates['gold_22k'] = round($gold_inr_per_gram * 0.9167, 2); // 22K = 91.67% pure
            $liveRates['gold_18k'] = round($gold_inr_per_gram * 0.75, 2);    // 18K = 75% pure
            $liveRates['gold_14k'] = round($gold_inr_per_gram * 0.5833, 2);  // 14K = 58.33% pure
        }

        if ($silver_usd) {
            $silver_inr_per_oz = $silver_usd * $usd_to_inr;
            $silver_inr_per_gram = $silver_inr_per_oz / 31.1035;

            // Add approx 2-3% for Indian market premium
            $silver_inr_per_gram *= 1.025;

            $liveRates['silver_999'] = round($silver_inr_per_gram, 2);
            $liveRates['silver_925'] = round($silver_inr_per_gram * 0.925, 2); // 925 Sterling
        }

        $liveRates['last_updated'] = date('d-m-Y H:i:s');
        $liveRates['source'] = 'Live International Market (Converted to INR)';
        $liveRates['usd_to_inr'] = $usd_to_inr;
    } catch (Exception $e) {
        // Return null values if API fails
        $liveRates['error'] = 'Unable to fetch live rates. Please enter manually.';
    }

    return $liveRates;
}

// Fetch live rates
$liveRates = fetchLiveMetalRates();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rateDate = $_POST['rate_date'] ?? date(DB_DATE_FORMAT);

    try {
        $db->beginTransaction();

        // Gold rates
        $goldRates = [
            ['metal_type' => 'gold', 'purity' => '24K', 'rate' => floatval($_POST['gold_24k'] ?? 0)],
            ['metal_type' => 'gold', 'purity' => '22K', 'rate' => floatval($_POST['gold_22k'] ?? 0)],
            ['metal_type' => 'gold', 'purity' => '18K', 'rate' => floatval($_POST['gold_18k'] ?? 0)],
            ['metal_type' => 'gold', 'purity' => '14K', 'rate' => floatval($_POST['gold_14k'] ?? 0)],
        ];

        // Silver rates
        $silverRates = [
            ['metal_type' => 'silver', 'purity' => '999', 'rate' => floatval($_POST['silver_999'] ?? 0)],
            ['metal_type' => 'silver', 'purity' => '925', 'rate' => floatval($_POST['silver_925'] ?? 0)],
        ];

        $allRates = array_merge($goldRates, $silverRates);

        $stmt = $db->prepare("INSERT INTO metal_rates (metal_type, purity, rate_per_gram, rate_date, created_by) 
                              VALUES (?, ?, ?, ?, ?) 
                              ON DUPLICATE KEY UPDATE rate_per_gram = VALUES(rate_per_gram), created_by = VALUES(created_by)");

        foreach ($allRates as $rate) {
            if ($rate['rate'] > 0) {
                $stmt->execute([$rate['metal_type'], $rate['purity'], $rate['rate'], $rateDate, $_SESSION['user_id']]);
            }
        }

        $db->commit();

        logActivity('rates_updated', "Updated metal rates for $rateDate");
        redirectWithMessage('/settings/rates.php', 'success', 'Metal rates updated successfully!');
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error updating rates: ' . $e->getMessage();
    }
}

// Get today's rates
$stmt = $db->prepare("SELECT * FROM metal_rates WHERE rate_date = CURDATE()");
$stmt->execute();
$todayRates = $stmt->fetchAll();

$rates = [];
foreach ($todayRates as $rate) {
    $rates[$rate['metal_type']][$rate['purity']] = $rate['rate_per_gram'];
}

// Get historical rates
$stmt = $db->query("SELECT mr.*, u.full_name as updated_by 
                    FROM metal_rates mr 
                    LEFT JOIN users u ON mr.created_by = u.id 
                    ORDER BY mr.rate_date DESC, mr.metal_type, mr.purity 
                    LIMIT 50");
$historicalRates = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-currency-exchange"></i> Metal Rates</h2>
    <button type="button" class="btn btn-info" onclick="fetchLiveRates()">
        <i class="bi bi-arrow-clockwise"></i> Refresh Live Rates
    </button>
</div>

<!-- Live Rates Display -->
<div class="alert alert-info mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1"><i class="bi bi-broadcast"></i> Live Market Rates (India - INR)</h5>
            <p class="mb-0 small">
                <?php if (isset($liveRates['gold_24k']) && $liveRates['gold_24k'] > 0): ?>
                    <span class="text-success"><i class="bi bi-check-circle"></i> Last Updated: <?php echo $liveRates['last_updated']; ?></span>
                    <span class="ms-3">Source: <?php echo $liveRates['source']; ?></span>
                    <?php if (isset($liveRates['usd_to_inr'])): ?>
                        <span class="ms-3">USD/INR: ₹<?php echo number_format($liveRates['usd_to_inr'], 2); ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> <?php echo $liveRates['error'] ?? 'Click "Refresh Live Rates" to fetch latest prices'; ?></span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- Live Rates Cards -->
<div class="row mb-4">
    <?php if (isset($liveRates['gold_24k']) && $liveRates['gold_24k'] > 0): ?>
        <div class="col-md-6 mb-3">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-coin"></i> Live Gold Rates (per gram)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">24K Gold</h6>
                                <h3 class="text-warning mb-0">₹<?php echo number_format($liveRates['gold_24k'], 2); ?></h3>
                                <small class="text-muted">99.9% Pure</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">22K Gold</h6>
                                <h3 class="text-warning mb-0">₹<?php echo number_format($liveRates['gold_22k'], 2); ?></h3>
                                <small class="text-muted">91.67% Pure</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">18K Gold</h6>
                                <h3 class="text-warning mb-0">₹<?php echo number_format($liveRates['gold_18k'], 2); ?></h3>
                                <small class="text-muted">75% Pure</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">14K Gold</h6>
                                <h3 class="text-warning mb-0">₹<?php echo number_format($liveRates['gold_14k'], 2); ?></h3>
                                <small class="text-muted">58.33% Pure</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card border-secondary">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-coin"></i> Live Silver Rates (per gram)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">999 Silver</h6>
                                <h3 class="text-secondary mb-0">₹<?php echo number_format($liveRates['silver_999'], 2); ?></h3>
                                <small class="text-muted">99.9% Pure</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h6 class="text-muted mb-1">925 Silver</h6>
                                <h3 class="text-secondary mb-0">₹<?php echo number_format($liveRates['silver_925'], 2); ?></h3>
                                <small class="text-muted">Sterling (92.5%)</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mb-0 mt-3">
                        <small><i class="bi bi-info-circle"></i> Rates include ~2.5% Indian market premium</small>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center">
                <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                <h5 class="mt-2">Live Rates Not Available</h5>
                <p>Please click "Refresh Live Rates" button or enter rates manually below.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Today's Rates Form -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Update Today's Rates</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Rate Date</label>
                        <input type="date" name="rate_date" class="form-control" value="<?php echo date(DB_DATE_FORMAT); ?>">
                    </div>

                    <h6 class="text-warning mb-3"><i class="bi bi-coin"></i> Gold Rates (per gram)</h6>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">24K Gold (₹)</label>
                            <input type="number" name="gold_24k" class="form-control" step="0.01" min="0"
                                value="<?php echo $rates['gold']['24K'] ?? ''; ?>" placeholder="0.00">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">22K Gold (₹)</label>
                            <input type="number" name="gold_22k" class="form-control" step="0.01" min="0"
                                value="<?php echo $rates['gold']['22K'] ?? ''; ?>" placeholder="0.00">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">18K Gold (₹)</label>
                            <input type="number" name="gold_18k" class="form-control" step="0.01" min="0"
                                value="<?php echo $rates['gold']['18K'] ?? ''; ?>" placeholder="0.00">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">14K Gold (₹)</label>
                            <input type="number" name="gold_14k" class="form-control" step="0.01" min="0"
                                value="<?php echo $rates['gold']['14K'] ?? ''; ?>" placeholder="0.00">
                        </div>
                    </div>

                    <h6 class="text-secondary mb-3"><i class="bi bi-coin"></i> Silver Rates (per gram)</h6>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">999 Silver (₹)</label>
                            <input type="number" name="silver_999" class="form-control" step="0.01" min="0"
                                value="<?php echo $rates['silver']['999'] ?? ''; ?>" placeholder="0.00">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">925 Silver (₹)</label>
                            <input type="number" name="silver_925" class="form-control" step="0.01" min="0"
                                value="<?php echo $rates['silver']['925'] ?? ''; ?>" placeholder="0.00">
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Rates
                        </button>
                    </div>

                    <?php if (isset($liveRates['gold_24k']) && $liveRates['gold_24k'] > 0): ?>
                        <div class="d-grid mt-2">
                            <button type="button" class="btn btn-success" onclick="autoFillLiveRates()">
                                <i class="bi bi-download"></i> Auto-Fill Live Rates
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Historical Rates -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Rate History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Metal</th>
                                <th>Purity</th>
                                <th>Rate/g</th>
                                <th>Updated By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historicalRates)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No rate history available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historicalRates as $rate): ?>
                                    <tr>
                                        <td><?php echo formatDate($rate['rate_date']); ?></td>
                                        <td><?php echo ucfirst($rate['metal_type']); ?></td>
                                        <td><?php echo $rate['purity']; ?></td>
                                        <td><?php echo formatCurrency($rate['rate_per_gram']); ?></td>
                                        <td><?php echo $rate['updated_by'] ?? 'System'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Current Rates Display -->
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Today's Active Rates</h5>
            </div>
            <div class="card-body">
                <?php if (empty($rates)): ?>
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle"></i> No rates set for today. Please update rates above.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php if (isset($rates['gold'])): ?>
                            <div class="col-md-6">
                                <h6 class="text-warning"><i class="bi bi-coin"></i> Gold</h6>
                                <ul class="list-group">
                                    <?php foreach ($rates['gold'] as $purity => $rate): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><?php echo $purity; ?> Gold</span>
                                            <strong><?php echo formatCurrency($rate); ?>/g</strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($rates['silver'])): ?>
                            <div class="col-md-6">
                                <h6 class="text-secondary"><i class="bi bi-coin"></i> Silver</h6>
                                <ul class="list-group">
                                    <?php foreach ($rates['silver'] as $purity => $rate): ?>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span><?php echo $purity; ?> Silver</span>
                                            <strong><?php echo formatCurrency($rate); ?>/g</strong>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-fill form with live rates
    function autoFillLiveRates() {
        <?php if (isset($liveRates['gold_24k'])): ?>
            document.querySelector('input[name="gold_24k"]').value = '<?php echo $liveRates['gold_24k']; ?>';
            document.querySelector('input[name="gold_22k"]').value = '<?php echo $liveRates['gold_22k']; ?>';
            document.querySelector('input[name="gold_18k"]').value = '<?php echo $liveRates['gold_18k']; ?>';
            document.querySelector('input[name="gold_14k"]').value = '<?php echo $liveRates['gold_14k']; ?>';
            document.querySelector('input[name="silver_999"]').value = '<?php echo $liveRates['silver_999']; ?>';
            document.querySelector('input[name="silver_925"]').value = '<?php echo $liveRates['silver_925']; ?>';

            // Show success message
            showAlert('Live rates filled successfully! Review and click "Update Rates" to save.', 'success');
        <?php else: ?>
            showAlert('Live rates not available. Please enter manually.', 'warning');
        <?php endif; ?>
    }

    // Fetch live rates via AJAX
    function fetchLiveRates() {
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Fetching...';
        btn.disabled = true;

        fetch('?ajax=1&action=fetch_live_rates')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert(data.message || 'Failed to fetch live rates', 'danger');
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                showAlert('Error fetching rates: ' + error.message, 'danger');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
    }

    // Show alert message
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

        const container = document.querySelector('.container-fluid') || document.body;
        container.insertBefore(alertDiv, container.firstChild);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Add CSS for spinning icon
    const style = document.createElement('style');
    style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .spin {
        animation: spin 1s linear infinite;
        display: inline-block;
    }
`;
    document.head.appendChild(style);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>