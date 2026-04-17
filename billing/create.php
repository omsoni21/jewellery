<?php

/**
 * Wholesale Billing - Modern Fast Interface
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_BILLING]);

$pageTitle = 'Wholesale Billing';
$db = getDBConnection();

// Get today's rates
$stmt = $db->prepare("SELECT metal_type, purity, rate_per_gram FROM metal_rates WHERE rate_date = CURDATE()");
$stmt->execute();
$todayRates = $stmt->fetchAll();

$rates = [];
foreach ($todayRates as $rate) {
    $rates[$rate['metal_type']][$rate['purity']] = $rate['rate_per_gram'];
}

// Convert to JSON for JavaScript
$ratesJSON = json_encode($rates);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Get form data
        $customerId = intval($_POST['customer_id'] ?? 0);
        $paidAmount = floatval($_POST['paid_amount'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $paymentRef = sanitize($_POST['payment_ref'] ?? '');

        // Get items from JSON
        $itemsData = json_decode($_POST['items_data'] ?? '[]', true);

        if (!$customerId) {
            throw new Exception('Please select a customer');
        }

        if (empty($itemsData)) {
            throw new Exception('Please add at least one item');
        }

        // Calculate totals
        $subtotal = 0;
        $totalMetalAmount = 0;
        $totalMakingAmount = 0;
        $items = [];

        foreach ($itemsData as $idx => $item) {
            $quantity = intval($item['quantity'] ?? 1);
            $weight = floatval($item['weight'] ?? 0);
            $rate = floatval($item['rate'] ?? 0);
            $mcRate = floatval($item['mc_rate'] ?? 0);
            $mcType = $item['mc_type'] ?? 'per_gram';
            $purityPercent = floatval($item['purity_percent'] ?? 99.9);

            // Calculate fine weight using manual percentage
            $fineWeight = $weight * ($purityPercent / 100);
            $metalAmount = $fineWeight * $rate;

            // Making charges:
            // MC in Gram = weight × rate
            // MC in Piece = quantity × rate
            $mcAmount = ($mcType === 'per_gram') ? ($weight * $mcRate) : ($quantity * $mcRate);

            $itemTotal = $metalAmount + $mcAmount;

            $totalMetalAmount += $metalAmount;
            $totalMakingAmount += $mcAmount;
            $subtotal += $itemTotal;

            $items[] = [
                'product_id' => $item['product_id'] ?? null,
                'item_name' => $item['name'],
                'metal_type' => $item['metal'],
                'purity' => $item['purity'],
                'quantity' => $quantity,
                'gross_weight' => $weight,
                'net_weight' => $fineWeight,
                'wastage_percent' => 0,
                'wastage_weight' => 0,
                'total_weight' => $weight,
                'rate_per_gram' => $rate,
                'metal_amount' => $metalAmount,
                'making_charge_type' => $mcType,
                'making_charge_rate' => $mcRate,
                'making_charge_amount' => $mcAmount,
                'item_total' => $itemTotal
            ];
        }

        // GST Calculation (Indian Market Rates)
        $metalGST = $totalMetalAmount * 0.03; // Gold/Silver: 3%
        $makingGST = $totalMakingAmount * 0.05; // Making charges: 5%
        $totalGST = $metalGST + $makingGST;
        $cgst = $totalGST / 2;
        $sgst = $totalGST / 2;

        $totalAmount = $subtotal + $totalGST;

        // Balance
        $balanceAmount = $totalAmount - $paidAmount;

        // Payment status
        if ($paidAmount <= 0) {
            $paymentStatus = 'pending';
        } elseif ($paidAmount < $totalAmount) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'paid';
        }

        // Generate invoice number
        $invoiceNo = generateInvoiceNumber();
        $invoiceDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+30 days'));

        // Insert invoice
        $stmt = $db->prepare("INSERT INTO invoices 
            (invoice_no, customer_id, invoice_date, due_date, subtotal, discount_amount, taxable_amount, 
             cgst_amount, sgst_amount, igst_amount, total_amount, paid_amount, balance_amount, payment_status, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $invoiceNo,
            $customerId,
            $invoiceDate,
            $dueDate,
            $subtotal,
            0,
            $subtotal,
            $cgst,
            $sgst,
            0,
            $totalAmount,
            $paidAmount,
            $balanceAmount,
            $paymentStatus,
            '',
            $_SESSION['user_id']
        ]);

        $invoiceId = $db->lastInsertId();

        // Insert invoice items
        $stmt = $db->prepare("INSERT INTO invoice_items (invoice_id, product_id, item_name, metal_type, purity, quantity, gross_weight, net_weight, wastage_percent, wastage_weight, total_weight, rate_per_gram, metal_amount, making_charge_type, making_charge_rate, making_charge_amount, item_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($items as $item) {
            try {
                $stmt->execute([
                    $invoiceId,
                    $item['product_id'] ?? null,
                    $item['item_name'],
                    $item['metal_type'],
                    $item['purity'],
                    $item['quantity'],
                    $item['gross_weight'],
                    $item['net_weight'],
                    $item['wastage_percent'],
                    $item['wastage_weight'],
                    $item['total_weight'],
                    $item['rate_per_gram'],
                    $item['metal_amount'],
                    $item['making_charge_type'],
                    $item['making_charge_rate'],
                    $item['making_charge_amount'],
                    $item['item_total']
                ]);
            } catch (PDOException $e) {
                // Log detailed error
                error_log("Invoice Item Insert Error: " . $e->getMessage());
                error_log("Item data: " . print_r($item, true));

                // Try with default making_charge_type if per_piece fails
                if (strpos($e->getMessage(), 'per_piece') !== false || strpos($e->getMessage(), 'truncated') !== false) {
                    $mcType = ($item['making_charge_type'] === 'per_piece') ? 'per_gram' : $item['making_charge_type'];

                    $stmt->execute([
                        $invoiceId,
                        $item['product_id'] ?? null,
                        $item['item_name'],
                        $item['metal_type'],
                        $item['purity'],
                        $item['quantity'],
                        $item['gross_weight'],
                        $item['net_weight'],
                        $item['wastage_percent'],
                        $item['wastage_weight'],
                        $item['total_weight'],
                        $item['rate_per_gram'],
                        $item['metal_amount'],
                        $mcType,
                        $item['making_charge_rate'],
                        $item['making_charge_amount'],
                        $item['item_total']
                    ]);
                } else {
                    throw $e;
                }
            }
        }

        // Insert payment if any
        if ($paidAmount > 0) {
            $stmt = $db->prepare("INSERT INTO payments 
                (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $customerId,
                $invoiceId,
                $invoiceDate,
                $paidAmount,
                $paymentMethod,
                $paymentRef,
                "Payment against invoice $invoiceNo",
                $_SESSION['user_id']
            ]);
        }

        // Update customer balance
        $stmt = $db->prepare("UPDATE customers SET current_balance = current_balance + ? WHERE id = ?");
        $stmt->execute([$balanceAmount, $customerId]);

        // Add to ledger
        $stmt = $db->prepare("INSERT INTO customer_ledger 
            (customer_id, transaction_date, transaction_type, reference_id, reference_no, debit, credit, balance, notes) 
            VALUES (?, CURDATE(), 'invoice', ?, ?, ?, 0, ?, ?)");

        // Get current balance
        $stmt2 = $db->prepare("SELECT current_balance FROM customers WHERE id = ?");
        $stmt2->execute([$customerId]);
        $currentBalance = $stmt2->fetchColumn();

        $stmt->execute([$customerId, $invoiceId, $invoiceNo, $balanceAmount, $currentBalance, '']);

        $db->commit();

        // Redirect to invoice view with print option
        header('Location: view.php?id=' . $invoiceId . '&print=1');
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        // VERY detailed error logging
        $errorMsg = "Invoice Creation Error\n";
        $errorMsg .= "Message: " . $e->getMessage() . "\n";
        $errorMsg .= "File: " . $e->getFile() . "\n";
        $errorMsg .= "Line: " . $e->getLine() . "\n";
        $errorMsg .= "Trace:\n" . $e->getTraceAsString();

        error_log($errorMsg);

        // Show error on page
        $error = '<strong>SQL Error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br>';
        $error .= '<strong>File:</strong> ' . $e->getFile() . '<br>';
        $error .= '<strong>Line:</strong> ' . $e->getLine();
    }
}

include __DIR__ . '/../includes/header.php';

$error = $error ?? '';
?>

<style>
    .sticky-footer {
        position: sticky;
        bottom: 0;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: white;
        padding: 15px 20px;
        border-radius: 10px 10px 0 0;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
        z-index: 1000;
    }

    .item-row {
        transition: all 0.2s;
        background: white;
    }

    .item-row:hover {
        background: #f8fafc;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .item-row td {
        padding: 8px 5px !important;
    }

    .item-row input,
    .item-row select {
        border: 1px solid #e2e8f0;
        padding: 10px 12px;
        font-size: 0.95rem;
        font-weight: 500;
        min-height: 38px;
        width: 100%;
    }

    .item-row input:focus,
    .item-row select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    /* Larger inputs for key fields */
    .item-row input[data-field="weight"],
    .item-row input[data-field="rate"],
    .item-row input[data-field="mc_rate"],
    .item-row input[data-field="total"] {
        font-size: 1rem;
        font-weight: 700;
        padding: 12px 14px;
        min-height: 42px;
    }

    .item-row input[data-field="total"] {
        background: #f0fdf4;
        color: #166534;
        font-size: 1.05rem;
    }

    /* Better visibility for all inputs */
    .item-row input[name="name"],
    .item-row input[data-field="name"] {
        font-size: 0.95rem;
        padding: 10px 12px;
    }

    .item-row input[readonly] {
        background-color: #f8fafc;
        font-weight: 600;
        color: #334155;
    }

    .customer-search {
        position: relative;
        z-index: 99999;
    }

    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #3b82f6;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(59, 130, 246, 0.35);
        max-height: 450px;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 100000;
        display: none;
        margin-top: 5px;
    }

    /* Scrollbar styling */
    .search-results::-webkit-scrollbar {
        width: 8px;
    }

    .search-results::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }

    .search-results::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        border-radius: 10px;
    }

    .search-results::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .search-result-item {
        padding: 15px 18px;
        cursor: pointer;
        border-bottom: 2px solid #f1f5f9;
        transition: all 0.2s;
        background: white;
        position: relative;
    }

    .search-result-item:hover {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-left: 4px solid #3b82f6;
        padding-left: 16px;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
        z-index: 1;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item strong {
        color: #1e40af;
        font-size: 1.05rem;
    }

    .search-result-item small {
        color: #64748b;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 15px;
    }

    .gold-card {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    }

    .silver-card {
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
    }

    .live-rates-bar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .rate-item {
        display: inline-block;
        margin-right: 15px;
        padding: 8px 12px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 6px;
        backdrop-filter: blur(10px);
    }

    .rate-item small {
        font-size: 0.75rem;
        opacity: 0.9;
    }

    .rate-item strong {
        font-size: 1.15rem;
        font-weight: 700;
    }

    .purity-group {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .purity-group select {
        flex: 1.2;
        min-width: 0;
    }

    .purity-group input {
        width: 70px;
        flex-shrink: 0;
    }

    /* Table styling for better visibility */
    #itemsTable thead th {
        font-size: 0.85rem;
        font-weight: 600;
        padding: 10px 6px;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
    }

    #itemsTable tbody td {
        padding: 6px 4px;
        vertical-align: middle;
    }

    #selectedCustomerInfo {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bill-type-selector {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .bill-type-btn {
        flex: 1;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s;
    }

    .bill-type-btn:hover {
        border-color: #3b82f6;
    }

    .bill-type-btn.active {
        border-color: #3b82f6;
        background: #eff6ff;
    }

    .bill-type-btn i {
        font-size: 1.5rem;
        display: block;
        margin-bottom: 5px;
    }

    .bill-type-btn.active.sale {
        border-color: #10b981;
        background: #ecfdf5;
    }

    .bill-type-btn.active.return {
        border-color: #ef4444;
        background: #fef2f2;
    }
</style>

<div class="container-fluid">
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-receipt-cutoff"></i> Wholesale Billing</h3>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-info" onclick="loadLiveRates()">
                <i class="bi bi-arrow-clockwise"></i> Refresh Rates
            </button>
            <button type="button" class="btn btn-sm btn-success" onclick="addNewItem()">
                <i class="bi bi-plus-circle"></i> Add Item (F2)
            </button>
        </div>
    </div>

    <!-- Live Rates Display -->
    <div class="live-rates-bar" id="liveRatesBar">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="rate-item">
                    <small>Gold 24K (99.9%):</small><br>
                    <strong>₹<span id="rateGold24K"><?php echo $rates['gold']['24K'] ?? $rates['gold']['24k'] ?? 0; ?></span>/g</strong>
                </span>
                <span class="rate-item">
                    <small>Gold 22K (91.6%):</small><br>
                    <strong>₹<span id="rateGold22K"><?php echo $rates['gold']['22K'] ?? $rates['gold']['22k'] ?? 0; ?></span>/g</strong>
                </span>
                <span class="rate-item">
                    <small>Gold 18K (75%):</small><br>
                    <strong>₹<span id="rateGold18K"><?php echo $rates['gold']['18K'] ?? $rates['gold']['18k'] ?? 0; ?></span>/g</strong>
                </span>
                <span class="rate-item">
                    <small>Silver 999 (99.9%):</small><br>
                    <strong>₹<span id="rateSilver999"><?php echo $rates['silver']['999'] ?? 0; ?></span>/g</strong>
                </span>
                <span class="rate-item">
                    <small>Silver 925 (92.5%):</small><br>
                    <strong>₹<span id="rateSilver925"><?php echo $rates['silver']['925'] ?? 0; ?></span>/g</strong>
                </span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-light" onclick="openRateUpdateModal()">
                    <i class="bi bi-pencil-square"></i> Update Rates
                </button>
                <small class="text-white-50">Last updated: <?php echo date('h:i A'); ?></small>
            </div>
        </div>
    </div>

    <!-- Bill Type Selector -->
    <div class="bill-type-selector">
        <button type="button" class="bill-type-btn sale active" onclick="setBillType('sale')">
            <i class="bi bi-bag-check text-success"></i>
            <strong>SALE</strong><br>
            <small class="text-muted">Customer Buying</small>
        </button>
        <button type="button" class="bill-type-btn return" onclick="setBillType('return')">
            <i class="bi bi-arrow-return-left text-danger"></i>
            <strong>RETURN</strong><br>
            <small class="text-muted">Customer Returning</small>
        </button>
    </div>
    <input type="hidden" name="bill_type" id="billType" value="sale">

    <form id="billingForm" method="POST" action="">
        <!-- Customer Selection -->
        <div class="card mb-3 border-primary" style="position: relative; z-index: 99998; overflow: visible;">
            <div class="card-body py-3" style="overflow: visible;">
                <div class="row align-items-center" style="overflow: visible;">
                    <div class="col-md-5" style="position: relative; z-index: 99999;">
                        <label class="form-label fw-bold mb-1">
                            <i class="bi bi-person-badge"></i>
                            <span id="customerLabel">Customer (Sale)</span>
                        </label>

                        <!-- Search Box -->
                        <div class="customer-search">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input type="text" id="customerSearchInput" class="form-control"
                                    placeholder="Type customer name, phone, or GST..." autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('customerSearchInput').focus()">
                                    <i class="bi bi-keyboard"></i>
                                </button>
                            </div>
                            <div id="customerSearchResults" class="search-results"></div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div id="selectedCustomerInfo" class="d-none">
                            <div class="d-flex align-items-center gap-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-building text-primary fs-5"></i>
                                        <h5 class="mb-0 fw-bold text-primary" id="custName"></h5>
                                        <span class="badge bg-success" id="billTypeBadge">SALE</span>
                                    </div>
                                    <div class="d-flex gap-3 small">
                                        <span class="text-muted">
                                            <i class="bi bi-telephone"></i>
                                            <span id="custPhone"></span>
                                        </span>
                                        <span class="text-muted">
                                            <i class="bi bi-file-text"></i>
                                            GST: <span id="custGST"></span>
                                        </span>
                                        <span class="text-muted">
                                            <i class="bi bi-wallet2"></i>
                                            Balance: ₹<span id="custBalance"></span>
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearCustomer()">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="noCustomerSelected" class="text-muted small">
                            <div class="d-flex gap-2 align-items-center">
                                <span><i class="bi bi-hand-index"></i> Type 2+ letters to search existing customer</span>
                                <span class="text-muted">|</span>
                                <button type="button" class="btn btn-sm btn-success" onclick="openNewCustomerModal()">
                                    <i class="bi bi-person-plus"></i> Add New Customer
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="customer_id" id="selectedCustomerId">
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="card mb-3">
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="itemsTable">
                        <thead class="table-dark">
                            <tr>
                                <th width="35" class="text-center">#</th>
                                <th width="140">Item Name</th>
                                <th width="100">Metal</th>
                                <th width="90">Qty</th>
                                <th width="100">Weight(g)</th>
                                <th width="150">Purity</th>
                                <th width="90">Fine Wt</th>
                                <th width="100">Rate/g</th>
                                <th width="110">Metal Amt</th>
                                <th width="100">MC Type</th>
                                <th width="90">MC Rate</th>
                                <th width="100">MC Amt</th>
                                <th width="120">Total</th>
                                <th width="45"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <!-- Items will be added here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Metal Summary & Adjustments -->
        <div class="row g-2 mb-3">
            <!-- Metal Summary -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-dark text-white py-1">
                        <h6 class="mb-0 small"><i class="bi bi-gem"></i> Metal Summary</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="stat-card gold-card mb-2">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small>Gold</small><br>
                                    <strong id="goldWeight">0.000g</strong>
                                </div>
                                <div class="text-end">
                                    <small>Value</small><br>
                                    <strong id="goldValue">₹0</strong>
                                </div>
                            </div>
                        </div>
                        <div class="stat-card silver-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <small>Silver</small><br>
                                    <strong id="silverWeight">0.000g</strong>
                                </div>
                                <div class="text-end">
                                    <small>Value</small><br>
                                    <strong id="silverValue">₹0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Adjustments -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white py-1">
                        <h6 class="mb-0 small"><i class="bi bi-sliders"></i> Adjustments</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row g-1">
                            <div class="col-6">
                                <small>Old Gold Value (₹)</small>
                                <input type="number" id="oldGoldValue" class="form-control form-control-sm" value="0" step="0.01" onchange="calculateFinal()">
                            </div>
                            <div class="col-6">
                                <small>Silver Return (₹)</small>
                                <input type="number" id="silverReturn" class="form-control form-control-sm" value="0" step="0.01" onchange="calculateFinal()">
                            </div>
                            <div class="col-6">
                                <small>Discount (₹)</small>
                                <input type="number" id="discount" class="form-control form-control-sm" value="0" step="0.01" onchange="calculateFinal()">
                            </div>
                            <div class="col-6">
                                <small>Other Charges (₹)</small>
                                <input type="number" id="otherCharges" class="form-control form-control-sm" value="0" step="0.01" onchange="calculateFinal()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white py-1">
                        <h6 class="mb-0 small"><i class="bi bi-cash-coin"></i> Payment</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="mb-2">
                            <small>Paid Amount (₹)</small>
                            <input type="number" id="paidAmount" name="paid_amount" class="form-control form-control-sm" value="0" step="0.01" onchange="calculateFinal()">
                        </div>
                        <div class="row g-1">
                            <div class="col-6">
                                <small>Mode</small>
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="upi">UPI</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <small>Ref No</small>
                                <input type="text" name="payment_ref" class="form-control form-control-sm" placeholder="UTR">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Summary Footer -->
        <div class="sticky-footer">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <div class="d-flex gap-4">
                        <div>
                            <small class="text-muted d-block">Subtotal</small>
                            <span class="fs-5 fw-bold">₹<span id="subtotalDisplay">0</span></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">GST (Metal 3% + MC 5%)</small>
                            <span class="fs-5 fw-bold">₹<span id="gstDisplay">0</span></span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Adjustments</small>
                            <span class="fs-5 fw-bold text-info">₹<span id="adjustDisplay">0</span></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <small class="text-muted d-block">Final Amount</small>
                    <span class="display-5 fw-bold text-warning">₹<span id="finalAmountDisplay">0</span></span>
                </div>
                <div class="col-md-3 text-end">
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <div>
                            <small class="text-muted d-block">Balance Due</small>
                            <span class="fs-4 fw-bold" id="balanceDisplay">₹0</span>
                        </div>
                        <button type="button" class="btn btn-info btn-lg" onclick="previewInvoice()" id="previewBtn" style="display:none;">
                            <i class="bi bi-eye-fill"></i> Preview
                        </button>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle-fill"></i> Save (F8)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Rate Update Modal -->
<div class="modal fade" id="rateUpdateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-currency-exchange"></i> Update Metal Rates</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="rateUpdateForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rate Date</label>
                        <input type="date" id="rateDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <h6 class="text-warning mb-3"><i class="bi bi-coin"></i> Gold Rates (per gram)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">24K Gold (99.9%) - ₹</label>
                            <input type="number" id="modalGold24K" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">22K Gold (91.6%) - ₹</label>
                            <input type="number" id="modalGold22K" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">18K Gold (75%) - ₹</label>
                            <input type="number" id="modalGold18K" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">14K Gold (58.3%) - ₹</label>
                            <input type="number" id="modalGold14K" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>

                    <h6 class="text-secondary mb-3"><i class="bi bi-coin"></i> Silver Rates (per gram)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">999 Silver (99.9%) - ₹</label>
                            <input type="number" id="modalSilver999" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">925 Silver (92.5%) - ₹</label>
                            <input type="number" id="modalSilver925" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveRates()">
                    <i class="bi bi-save"></i> Save Rates
                </button>
            </div>
        </div>
    </div>
</div>

<!-- New Customer Modal -->
<div class="modal fade" id="newCustomerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Add New Customer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newCustomerForm">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Business/Customer Name <span class="text-danger">*</span></label>
                        <input type="text" id="newCustName" class="form-control" placeholder="Enter customer or business name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" id="newCustPerson" class="form-control" placeholder="Contact person name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" id="newCustPhone" class="form-control" placeholder="10-digit mobile number" maxlength="10" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">GST Number</label>
                        <input type="text" id="newCustGST" class="form-control" placeholder="Enter GST number (optional)" maxlength="15" style="text-transform: uppercase;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea id="newCustAddress" class="form-control" rows="2" placeholder="Enter address (optional)"></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" id="newCustCity" class="form-control" placeholder="City">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" id="newCustState" class="form-control" placeholder="State">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Opening Balance (₹)</label>
                        <input type="number" id="newCustBalance" class="form-control" value="0" step="0.01">
                        <small class="text-muted">Positive = Customer owes you, Negative = You owe customer</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="saveNewCustomer()">
                    <i class="bi bi-check-circle"></i> Save & Select Customer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let items = [];
    let itemCounter = 0;
    const metalRates = <?php echo $ratesJSON; ?>;

    // Make functions globally accessible with billing_ prefix to avoid conflicts
    window.billingSelectCustomerById = selectCustomerById;
    window.billingSelectCustomer = selectCustomer;

    // Test function - console se call karo: testCustomerSelection()
    window.testCustomerSelection = function() {
        console.log('🧪 TESTING CUSTOMER SELECTION...');

        // Create a test customer
        const testCustomer = {
            id: 99999,
            business_name: 'TEST CUSTOMER',
            phone: '9999999999',
            gstin: 'TEST1234567890',
            current_balance: 0
        };

        console.log('Test customer:', testCustomer);
        billingSelectCustomer(testCustomer);
    };

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        addNewItem(); // Add first empty row

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F2') {
                e.preventDefault();
                addNewItem();
            }
            if (e.key === 'F8') {
                e.preventDefault();
                document.getElementById('billingForm').submit();
            }
        });
    });

    // Customer Search
    let searchTimeout;
    let selectedIndex = -1;
    let currentCustomers = [];

    document.getElementById('customerSearchInput').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value;
        selectedIndex = -1;

        if (query.length < 2) {
            document.getElementById('customerSearchResults').style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`<?php echo BASE_URL; ?>/ajax/search-customer.php?q=${encodeURIComponent(query)}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network error');
                    }
                    return res.json();
                })
                .then(data => {
                    const resultsDiv = document.getElementById('customerSearchResults');
                    resultsDiv.innerHTML = '';
                    currentCustomers = [];

                    if (data.success && data.customers.length > 0) {
                        currentCustomers = data.customers.slice(0, 10);
                        console.log('✅ Customers found:', currentCustomers.length);

                        // Add header with count
                        const headerDiv = document.createElement('div');
                        headerDiv.className = 'px-3 py-2 bg-light border-bottom';
                        headerDiv.innerHTML = `<small class="text-muted"><i class="bi bi-people-fill"></i> ${currentCustomers.length} customer${currentCustomers.length > 1 ? 's' : ''} found</small>`;
                        resultsDiv.appendChild(headerDiv);

                        currentCustomers.forEach((customer, index) => {
                            const div = document.createElement('div');
                            div.className = 'search-result-item';
                            div.dataset.index = index;
                            div.dataset.customerId = customer.id;
                            div.style.cursor = 'pointer';

                            // DIRECT onclick attribute - sabse reliable!
                            div.onclick = function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                console.log('💥 DIRECT ONCLICK - Index:', index);
                                console.log('💥 Customer:', customer);
                                billingSelectCustomerById(index);
                            };

                            div.innerHTML = `
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <strong class="fs-6">${customer.business_name}</strong>
                                            <span class="badge bg-primary">#${customer.id}</span>
                                        </div>
                                        <div class="d-flex flex-wrap gap-3 small text-muted">
                                            <span><i class="bi bi-telephone-fill text-success"></i> ${customer.phone}</span>
                                            ${customer.gstin ? `<span><i class="bi bi-file-earmark-text text-info"></i> GST: ${customer.gstin}</span>` : ''}
                                        </div>
                                        ${customer.address ? `<div class="small text-muted mt-1"><i class="bi bi-geo-alt-fill text-danger"></i> ${customer.address}</div>` : ''}
                                    </div>
                                    <div class="text-end ms-3">
                                        <div class="mb-1">
                                            <small class="text-muted d-block">Balance</small>
                                            <span class="fw-bold ${parseFloat(customer.current_balance || 0) > 0 ? 'text-danger' : 'text-success'}">
                                                ₹${parseFloat(customer.current_balance || 0).toLocaleString('en-IN')}
                                            </span>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary mt-1" onclick="event.preventDefault(); event.stopPropagation(); console.log('🔘 BUTTON CLICKED'); billingSelectCustomerById(${index});">
                                            <i class="bi bi-check-lg"></i> Select
                                        </button>
                                    </div>
                                </div>
                            `;
                            resultsDiv.appendChild(div);
                        });

                        resultsDiv.style.display = 'block';
                    } else {
                        resultsDiv.innerHTML = '<div class="p-3 text-center text-muted"><i class="bi bi-person-x fs-3"></i><br>No customers found</div>';
                        resultsDiv.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Search error:', err);
                    const resultsDiv = document.getElementById('customerSearchResults');
                    resultsDiv.innerHTML = '<div class="p-3 text-center text-danger"><i class="bi bi-exclamation-triangle"></i><br>Error searching customers</div>';
                    resultsDiv.style.display = 'block';
                });
        }, 300);
    });

    // Keyboard navigation for search results
    document.getElementById('customerSearchInput').addEventListener('keydown', function(e) {
        const resultsDiv = document.getElementById('customerSearchResults');
        const items = resultsDiv.querySelectorAll('.search-result-item');

        if (resultsDiv.style.display === 'none' || items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            updateSelection(items);
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            console.log('Enter pressed, selecting customer at index:', selectedIndex);
            billingSelectCustomerById(selectedIndex);
        } else if (e.key === 'Escape') {
            resultsDiv.style.display = 'none';
        }
    });

    function updateSelection(items) {
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.style.background = '#3b82f6';
                item.style.color = 'white';
                item.scrollIntoView({
                    block: 'nearest'
                });
            } else {
                item.style.background = '';
                item.style.color = '';
            }
        });
    }

    // Select customer by index from currentCustomers array
    function selectCustomerById(index) {
        console.log('========================================');
        console.log('🔍 selectCustomerById CALLED');
        console.log('Index:', index);
        console.log('currentCustomers array:', currentCustomers);
        console.log('Array length:', currentCustomers.length);
        console.log('Array contents:', JSON.stringify(currentCustomers, null, 2));

        if (!currentCustomers || currentCustomers.length === 0) {
            console.error('❌ ERROR: currentCustomers is empty!');
            alert('Error: No customers in memory. Please search again.');
            return;
        }

        if (index < 0 || index >= currentCustomers.length) {
            console.error('❌ ERROR: Invalid index!', index);
            alert('Error: Invalid customer index');
            return;
        }

        const customer = currentCustomers[index];
        console.log('👤 Customer at index', index, ':', customer);

        if (!customer) {
            console.error('❌ ERROR: Customer is undefined at index', index);
            alert('Error: Customer data not found');
            return;
        }

        if (!customer.id) {
            console.error('❌ ERROR: Customer has no ID!', customer);
            alert('Error: Invalid customer data (no ID)');
            return;
        }

        console.log('✅ Valid customer found, calling selectCustomer()...');
        billingSelectCustomer(customer);
    }

    function selectCustomer(customer) {
        console.log('========================================');
        console.log('📋 selectCustomer() CALLED');
        console.log('Customer data:', customer);
        console.log('Customer ID:', customer.id);
        console.log('Customer Name:', customer.business_name);
        console.log('========================================');

        if (!customer || !customer.id) {
            console.error('❌ ERROR: Invalid customer data!', customer);
            alert('Error: Invalid customer data');
            return;
        }

        // Set customer ID in hidden field
        const hiddenField = document.getElementById('selectedCustomerId');
        if (hiddenField) {
            hiddenField.value = customer.id;
            console.log('✅ Hidden field set to:', customer.id);
        } else {
            console.error('❌ ERROR: Hidden field not found!');
        }

        // Display customer details
        const nameEl = document.getElementById('custName');
        const phoneEl = document.getElementById('custPhone');
        const gstEl = document.getElementById('custGST');
        const balanceEl = document.getElementById('custBalance');
        const infoSection = document.getElementById('selectedCustomerInfo');
        const noCustomerSection = document.getElementById('noCustomerSelected');

        console.log('DOM Elements check:');
        console.log('- nameEl:', nameEl);
        console.log('- phoneEl:', phoneEl);
        console.log('- infoSection:', infoSection);

        if (nameEl) {
            nameEl.textContent = customer.business_name || 'N/A';
            console.log('✅ Name set to:', customer.business_name);
        }

        if (phoneEl) {
            phoneEl.textContent = customer.phone || 'N/A';
            console.log('✅ Phone set to:', customer.phone);
        }

        if (gstEl) {
            gstEl.textContent = customer.gstin || 'N/A';
            console.log('✅ GST set to:', customer.gstin || 'N/A');
        }

        if (balanceEl) {
            balanceEl.textContent = parseFloat(customer.current_balance || 0).toLocaleString('en-IN');
            console.log('✅ Balance set to:', customer.current_balance);
        }

        // Show customer info section
        if (infoSection) {
            infoSection.classList.remove('d-none');
            console.log('✅ Customer info section SHOWN (d-none removed)');

            // Add animation
            infoSection.style.animation = 'none';
            setTimeout(() => {
                infoSection.style.animation = 'fadeIn 0.3s ease-in';
            }, 10);
        } else {
            console.error('❌ ERROR: infoSection not found!');
        }

        // Hide "no customer" section
        if (noCustomerSection) {
            noCustomerSection.style.display = 'none';
            console.log('✅ No customer section HIDDEN');
        }

        // Hide search results
        const searchResults = document.getElementById('customerSearchResults');
        if (searchResults) {
            searchResults.style.display = 'none';
            console.log('✅ Search results HIDDEN');
        }

        // Clear search input
        const searchInput = document.getElementById('customerSearchInput');
        if (searchInput) {
            searchInput.value = '';
            console.log('✅ Search input CLEARED');
        }

        // Update label based on bill type
        updateCustomerLabel();

        console.log('========================================');
        console.log('✅✅✅ CUSTOMER SUCCESSFULLY SELECTED! ✅✅✅');
        console.log('ID:', customer.id);
        console.log('Name:', customer.business_name);
        console.log('Phone:', customer.phone);
        console.log('========================================');

        // Show success alert
        alert('✅ Customer Selected: ' + customer.business_name);
    }

    // Clear customer selection
    function clearCustomer() {
        document.getElementById('selectedCustomerId').value = '';
        document.getElementById('selectedCustomerInfo').classList.add('d-none');
        document.getElementById('noCustomerSelected').style.display = 'block';
        document.getElementById('customerSearchInput').value = '';
        document.getElementById('customerSearchInput').focus();
    }

    // Update customer label based on bill type
    function updateCustomerLabel() {
        const billType = document.getElementById('billType').value;
        const label = document.getElementById('customerLabel');
        const badge = document.getElementById('billTypeBadge');

        if (billType === 'return') {
            label.innerHTML = '<i class="bi bi-arrow-return-left"></i> Return From';
            badge.textContent = 'RETURN';
            badge.className = 'badge bg-danger';
        } else {
            label.innerHTML = '<i class="bi bi-person-badge"></i> Customer (Sale)';
            badge.textContent = 'SALE';
            badge.className = 'badge bg-success';
        }
    }

    // Add New Item Row
    function addNewItem() {
        itemCounter++;
        const tbody = document.getElementById('itemsBody');
        const row = document.createElement('tr');
        row.className = 'item-row';
        row.dataset.index = itemCounter;

        row.innerHTML = `
        <td class="text-center text-muted">${itemCounter}</td>
        <td><input type="text" class="form-control form-control-sm" placeholder="Item name" data-field="name" required></td>
        <td><select class="form-select form-select-sm" data-field="metal" onchange="updateRate(this)">
            <option value="gold">Gold</option>
            <option value="silver">Silver</option>
        </select></td>
        <td><input type="number" class="form-control form-control-sm" placeholder="1" value="1" min="1" step="1" data-field="quantity" oninput="calculateRow(this)"></td>
        <td><input type="number" class="form-control form-control-sm" placeholder="0.000" step="0.001" data-field="weight" oninput="calculateRow(this)"></td>
        <td>
            <div class="purity-group">
                <select class="form-select form-select-sm" data-field="purity" onchange="updatePurityPercent(this)">
                    <option value="24k">24K (99.9%)</option>
                    <option value="22k">22K (91.6%)</option>
                    <option value="18k">18K (75%)</option>
                    <option value="14k">14K (58.3%)</option>
                    <option value="925">925 (92.5%)</option>
                    <option value="999">999 (99.9%)</option>
                    <option value="custom">Custom</option>
                </select>
                <input type="number" class="form-control form-control-sm" placeholder="%" step="0.1" data-field="purity_percent" value="99.9" oninput="calculateRow(this)">
            </div>
        </td>
        <td><input type="text" class="form-control form-control-sm" readonly data-field="fine_weight" tabindex="-1"></td>
        <td><input type="number" class="form-control form-control-sm" data-field="rate" oninput="calculateRow(this)"></td>
        <td><input type="text" class="form-control form-control-sm" readonly data-field="metal_amount" tabindex="-1"></td>
        <td><select class="form-select form-select-sm" data-field="mc_type" onchange="calculateRow(this)">
            <option value="per_gram">Per Gram</option>
            <option value="per_piece">Per Piece</option>
        </select></td>
        <td><input type="number" class="form-control form-control-sm" placeholder="0" step="0.01" data-field="mc_rate" oninput="calculateRow(this)"></td>
        <td><input type="text" class="form-control form-control-sm" readonly data-field="mc_amount" tabindex="-1"></td>
        <td><input type="text" class="form-control form-control-sm fw-bold" readonly data-field="total" tabindex="-1"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">×</button></td>
    `;

        tbody.appendChild(row);
        updateRate(row.querySelector('[data-field="metal"]'));
    }

    function removeRow(btn) {
        const row = btn.closest('.item-row');
        row.remove();
        calculateAll();
    }

    // Update rate based on metal and purity
    function updateRate(select) {
        const row = select.closest('.item-row');
        const metal = row.querySelector('[data-field="metal"]').value;
        const purity = row.querySelector('[data-field="purity"]').value;

        // Try to find rate - check both lowercase and uppercase
        let rate = 0;

        // First try exact match
        if (metalRates[metal] && metalRates[metal][purity]) {
            rate = metalRates[metal][purity];
        }
        // Try uppercase (24K, 22K, 999)
        else if (metalRates[metal] && metalRates[metal][purity.toUpperCase()]) {
            rate = metalRates[metal][purity.toUpperCase()];
        }
        // For silver 999, try without K
        else if (metal === 'silver' && purity === '999' && metalRates[metal] && metalRates[metal]['999']) {
            rate = metalRates[metal]['999'];
        }
        // For gold 24k/22k/18k/14k
        else if (metal === 'gold' && metalRates[metal]) {
            const purityUpper = purity.toUpperCase();
            if (metalRates[metal][purityUpper]) {
                rate = metalRates[metal][purityUpper];
            }
        }

        row.querySelector('[data-field="rate"]').value = rate;
        calculateRow(select);
    }

    // Update purity percentage when carat changes
    function updatePurityPercent(select) {
        const row = select.closest('.item-row');
        const purity = select.value;
        const percentInput = row.querySelector('[data-field="purity_percent"]');

        const purityMap = {
            '24k': 99.9,
            '22k': 91.6,
            '18k': 75.0,
            '14k': 58.3,
            '925': 92.5,
            '999': 99.9,
            'custom': 0
        };

        if (purity !== 'custom') {
            percentInput.value = purityMap[purity] || 99.9;
        } else {
            percentInput.value = '';
            percentInput.focus();
        }

        calculateRow(select);
    }

    // Calculate single row
    function calculateRow(element) {
        const row = element.closest('.item-row');
        const quantity = parseInt(row.querySelector('[data-field="quantity"]').value) || 1;
        const weight = parseFloat(row.querySelector('[data-field="weight"]').value) || 0;
        const purityPercent = parseFloat(row.querySelector('[data-field="purity_percent"]').value) || 0;
        const rate = parseFloat(row.querySelector('[data-field="rate"]').value) || 0;
        const mcType = row.querySelector('[data-field="mc_type"]').value;
        const mcRate = parseFloat(row.querySelector('[data-field="mc_rate"]').value) || 0;

        // Calculate using manual percentage
        const fineWeight = weight * (purityPercent / 100);
        const metalAmount = fineWeight * rate;

        // Making charges calculation:
        // MC in Gram = weight × rate
        // MC in Piece = quantity (pieces) × rate
        let mcAmount = 0;
        if (mcType === 'per_gram') {
            mcAmount = weight * mcRate; // Weight × MC Rate
        } else {
            mcAmount = quantity * mcRate; // Pieces × MC Rate
        }

        const total = metalAmount + mcAmount;

        // Update row
        row.querySelector('[data-field="fine_weight"]').value = fineWeight.toFixed(3);
        row.querySelector('[data-field="metal_amount"]').value = metalAmount.toFixed(2);
        row.querySelector('[data-field="mc_amount"]').value = mcAmount.toFixed(2);
        row.querySelector('[data-field="total"]').value = total.toFixed(2);

        calculateAll();
    }

    // Calculate everything
    function calculateAll() {
        let goldWeight = 0,
            goldValue = 0;
        let silverWeight = 0,
            silverValue = 0;
        let totalMC = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            const metal = row.querySelector('[data-field="metal"]').value;
            const weight = parseFloat(row.querySelector('[data-field="weight"]').value) || 0;
            const metalAmount = parseFloat(row.querySelector('[data-field="metal_amount"]').value) || 0;
            const mcAmount = parseFloat(row.querySelector('[data-field="mc_amount"]').value) || 0;

            if (metal === 'gold') {
                goldWeight += weight;
                goldValue += metalAmount;
            } else {
                silverWeight += weight;
                silverValue += metalAmount;
            }

            totalMC += mcAmount;
        });

        // Update metal summary
        document.getElementById('goldWeight').textContent = goldWeight.toFixed(3) + 'g';
        document.getElementById('goldValue').textContent = '₹' + goldValue.toLocaleString('en-IN');
        document.getElementById('silverWeight').textContent = silverWeight.toFixed(3) + 'g';
        document.getElementById('silverValue').textContent = '₹' + silverValue.toLocaleString('en-IN');
        document.getElementById('totalMC').textContent = totalMC.toLocaleString('en-IN');

        calculateFinal();
    }

    // Final calculation with adjustments and GST
    function calculateFinal() {
        let subtotal = 0;
        let totalMetalAmount = 0;
        let totalMakingAmount = 0;

        document.querySelectorAll('.item-row').forEach(row => {
            totalMetalAmount += parseFloat(row.querySelector('[data-field="metal_amount"]').value) || 0;
            totalMakingAmount += parseFloat(row.querySelector('[data-field="mc_amount"]').value) || 0;
        });

        subtotal = totalMetalAmount + totalMakingAmount;

        // GST: Metal 3%, Making 5%
        const metalGST = totalMetalAmount * 0.03;
        const makingGST = totalMakingAmount * 0.05;
        const totalGST = metalGST + makingGST;

        // Adjustments
        const oldGoldValue = parseFloat(document.getElementById('oldGoldValue').value) || 0;
        const silverReturn = parseFloat(document.getElementById('silverReturn').value) || 0;
        const discount = parseFloat(document.getElementById('discount').value) || 0;
        const otherCharges = parseFloat(document.getElementById('otherCharges').value) || 0;

        const adjustments = -oldGoldValue - silverReturn - discount + otherCharges;

        const subtotalWithGST = subtotal + totalGST;
        const finalAmount = subtotalWithGST + adjustments;

        const paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;
        const balance = finalAmount - paidAmount;

        // Update display
        document.getElementById('subtotalDisplay').textContent = subtotal.toLocaleString('en-IN');
        document.getElementById('gstDisplay').textContent = totalGST.toLocaleString('en-IN');
        document.getElementById('adjustDisplay').textContent = adjustments.toLocaleString('en-IN');
        document.getElementById('finalAmountDisplay').textContent = finalAmount.toLocaleString('en-IN');
        document.getElementById('balanceDisplay').textContent = '₹' + balance.toLocaleString('en-IN');

        // Color code balance
        const balanceEl = document.getElementById('balanceDisplay');
        if (balance <= 0) {
            balanceEl.className = 'fs-4 fw-bold text-success';
        } else if (paidAmount > 0) {
            balanceEl.className = 'fs-4 fw-bold text-warning';
        } else {
            balanceEl.className = 'fs-4 fw-bold text-white';
        }
    }

    // Load live rates
    function loadLiveRates() {
        location.reload();
    }

    // Open rate update modal
    function openRateUpdateModal() {
        // Load current rates into modal
        <?php if (isset($rates['gold'])): ?>
            document.getElementById('modalGold24K').value = '<?php echo $rates['gold']['24K'] ?? $rates['gold']['24k'] ?? ''; ?>';
            document.getElementById('modalGold22K').value = '<?php echo $rates['gold']['22K'] ?? $rates['gold']['22k'] ?? ''; ?>';
            document.getElementById('modalGold18K').value = '<?php echo $rates['gold']['18K'] ?? $rates['gold']['18k'] ?? ''; ?>';
            document.getElementById('modalGold14K').value = '<?php echo $rates['gold']['14K'] ?? $rates['gold']['14k'] ?? ''; ?>';
        <?php endif; ?>

        <?php if (isset($rates['silver'])): ?>
            document.getElementById('modalSilver999').value = '<?php echo $rates['silver']['999'] ?? ''; ?>';
            document.getElementById('modalSilver925').value = '<?php echo $rates['silver']['925'] ?? ''; ?>';
        <?php endif; ?>

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('rateUpdateModal'));
        modal.show();
    }

    // Open new customer modal
    function openNewCustomerModal() {
        // Clear form
        document.getElementById('newCustomerForm').reset();
        document.getElementById('newCustBalance').value = '0';

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('newCustomerModal'));
        modal.show();

        // Focus on name field
        setTimeout(() => {
            document.getElementById('newCustName').focus();
        }, 500);
    }

    // Save new customer via AJAX
    function saveNewCustomer() {
        const name = document.getElementById('newCustName').value.trim();
        const phone = document.getElementById('newCustPhone').value.trim();
        const person = document.getElementById('newCustPerson').value.trim();
        const gst = document.getElementById('newCustGST').value.trim();
        const address = document.getElementById('newCustAddress').value.trim();
        const city = document.getElementById('newCustCity').value.trim();
        const state = document.getElementById('newCustState').value.trim();
        const balance = document.getElementById('newCustBalance').value || 0;

        // Validate required fields
        if (!name) {
            alert('Please enter customer/business name!');
            document.getElementById('newCustName').focus();
            return;
        }

        if (!phone || phone.length < 10) {
            alert('Please enter valid 10-digit phone number!');
            document.getElementById('newCustPhone').focus();
            return;
        }

        const formData = new FormData();
        formData.append('business_name', name);
        formData.append('phone', phone);
        formData.append('contact_person', person);
        formData.append('gst_number', gst.toUpperCase());
        formData.append('address_line1', address);
        formData.append('city', city);
        formData.append('state', state);
        formData.append('current_balance', balance);
        formData.append('action', 'add_customer');

        fetch('<?php echo BASE_URL; ?>/ajax/add-customer.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Auto-select the new customer
                    selectCustomer(data.customer);

                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newCustomerModal'));
                    modal.hide();

                    // Show success message
                    alert('Customer added successfully!');
                } else {
                    alert('Error: ' + (data.message || 'Failed to add customer'));
                }
            })
            .catch(err => {
                alert('Error adding customer: ' + err.message);
            });
    }

    // Save rates via AJAX
    function saveRates() {
        const rateDate = document.getElementById('rateDate').value;
        const gold24K = document.getElementById('modalGold24K').value;
        const gold22K = document.getElementById('modalGold22K').value;
        const gold18K = document.getElementById('modalGold18K').value;
        const gold14K = document.getElementById('modalGold14K').value;
        const silver999 = document.getElementById('modalSilver999').value;
        const silver925 = document.getElementById('modalSilver925').value;

        if (!gold24K && !gold22K && !silver999) {
            alert('Please enter at least one rate!');
            return;
        }

        const formData = new FormData();
        formData.append('rate_date', rateDate);
        formData.append('gold_24k', gold24K);
        formData.append('gold_22k', gold22K);
        formData.append('gold_18k', gold18K);
        formData.append('gold_14k', gold14K);
        formData.append('silver_999', silver999);
        formData.append('silver_925', silver925);
        formData.append('action', 'update_rates');

        fetch('<?php echo BASE_URL; ?>/ajax/update-rates.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Rates updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update rates'));
                }
            })
            .catch(err => {
                alert('Error updating rates: ' + err.message);
            });
    }

    // Set bill type (Sale/Return)
    function setBillType(type) {
        document.getElementById('billType').value = type;

        // Update button styles
        document.querySelectorAll('.bill-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        if (type === 'sale') {
            document.querySelector('.bill-type-btn.sale').classList.add('active');
        } else {
            document.querySelector('.bill-type-btn.return').classList.add('active');
        }

        // Update UI colors based on type
        const footer = document.querySelector('.sticky-footer');
        if (type === 'return') {
            footer.style.background = 'linear-gradient(135deg, #dc2626 0%, #991b1b 100%)';
        } else {
            footer.style.background = 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)';
        }

        // Update customer label
        updateCustomerLabel();
    }

    // Preview invoice before saving
    function previewInvoice() {
        const customerId = document.getElementById('selectedCustomerId').value;

        if (!customerId) {
            alert('⚠️ Please select a customer first!');
            return;
        }

        // Collect items
        const items = [];
        document.querySelectorAll('.item-row').forEach(row => {
            const name = row.querySelector('[data-field="name"]').value;
            if (name) {
                items.push({
                    name: name,
                    metal: row.querySelector('[data-field="metal"]').value,
                    weight: row.querySelector('[data-field="weight"]').value,
                    rate: row.querySelector('[data-field="rate"]').value,
                    total: row.querySelector('[data-field="total"]').value
                });
            }
        });

        if (items.length === 0) {
            alert('⚠️ Please add at least one item!');
            return;
        }

        // Show preview summary
        let summary = '📋 INVOICE PREVIEW\n\n';
        summary += 'Customer ID: ' + customerId + '\n';
        summary += 'Items: ' + items.length + '\n\n';

        items.forEach((item, idx) => {
            summary += (idx + 1) + '. ' + item.name + ' - ' + item.weight + 'g - ₹' + item.total + '\n';
        });

        summary += '\n✅ Save to generate invoice and print!';

        alert(summary);
    }

    // Form submission
    document.getElementById('billingForm').addEventListener('submit', function(e) {
        // Validate customer
        if (!document.getElementById('selectedCustomerId').value) {
            alert('Please select a customer!');
            e.preventDefault();
            return;
        }

        // Collect items data
        const itemsData = [];
        document.querySelectorAll('.item-row').forEach(row => {
            const name = row.querySelector('[data-field="name"]').value;
            if (name) {
                itemsData.push({
                    product_id: row.querySelector('[data-field="product_id"]')?.value || null,
                    name: name,
                    quantity: row.querySelector('[data-field="quantity"]').value,
                    metal: row.querySelector('[data-field="metal"]').value,
                    weight: row.querySelector('[data-field="weight"]').value,
                    purity: row.querySelector('[data-field="purity"]').value,
                    purity_percent: row.querySelector('[data-field="purity_percent"]').value,
                    rate: row.querySelector('[data-field="rate"]').value,
                    mc_type: row.querySelector('[data-field="mc_type"]').value,
                    mc_rate: row.querySelector('[data-field="mc_rate"]').value,
                    metal_amount: row.querySelector('[data-field="metal_amount"]').value,
                    mc_amount: row.querySelector('[data-field="mc_amount"]').value,
                    total: row.querySelector('[data-field="total"]').value
                });
            }
        });

        if (itemsData.length === 0) {
            alert('Please add at least one item!');
            e.preventDefault();
            return;
        }

        // Store in hidden field
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'items_data';
        hiddenInput.value = JSON.stringify(itemsData);
        this.appendChild(hiddenInput);
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>