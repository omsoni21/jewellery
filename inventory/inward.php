<?php
/**
 * Stock Inward Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Stock Inward';
$db = getDBConnection();

// Get categories for dropdown
$stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Get existing products for selection
$stmt = $db->query("SELECT id, name, metal_type, purity FROM products WHERE is_active = 1 ORDER BY name");
$products = $stmt->fetchAll();
// Handle stock inward form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $transactionType = $_POST['transaction_type'] ?? 'inward';
        $referenceNo = sanitize($_POST['reference_no'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        
        // Process each item
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                $productId = intval($item['product_id'] ?? 0);
                $isNewProduct = isset($item['is_new_product']) && $item['is_new_product'] == '1';
                
                // Create new product if needed
                if ($isNewProduct || !$productId) {
                    $stmt = $db->prepare("INSERT INTO products 
                        (product_code, category_id, name, metal_type, purity, description) 
                        VALUES (?, ?, ?, ?, ?, ?)");
                    
                    // Generate product code
                    $stmt2 = $db->query("SELECT MAX(CAST(SUBSTRING(product_code, 4) AS UNSIGNED)) as max_num FROM products WHERE product_code LIKE 'PRD%'");
                    $result = $stmt2->fetch();
                    $nextNum = ($result['max_num'] ?? 0) + 1;
                    $productCode = 'PRD' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                    
                    $stmt->execute([
                        $productCode,
                        intval($item['category_id'] ?? 0),
                        sanitize($item['new_product_name'] ?? ''),
                        $item['metal_type'] ?? 'gold',
                        $item['purity'] ?? '22K',
                        ''
                    ]);
                    
                    $productId = $db->lastInsertId();
                    
                    // Create initial stock entry
                    $stmt = $db->prepare("INSERT INTO stock (product_id, quantity, gross_weight, net_weight) VALUES (?, 0, 0, 0)");
                    $stmt->execute([$productId]);
                }
                
                if (!$productId) {
                  continue;
                   }
                
                $quantity = intval($item['quantity'] ?? 0);
                $grossWeight = floatval($item['gross_weight'] ?? 0);
                $netWeight = floatval($item['net_weight'] ?? 0);
                $wastagePercent = floatval($item['wastage_percent'] ?? 0);
                
                if ($quantity <= 0 || $netWeight <= 0) continue;
                
                // Calculate wastage weight
                $wastageWeight = $netWeight * ($wastagePercent / 100);
                
                // Update stock
                $stmt = $db->prepare("UPDATE stock SET 
                    quantity = quantity + ?,
                    gross_weight = gross_weight + ?,
                    net_weight = net_weight + ?,
                    wastage_weight = wastage_weight + ?,
                    last_updated = NOW()
                    WHERE product_id = ?");
                $stmt->execute([$quantity, $grossWeight, $netWeight, $wastageWeight, $productId]);
                
                // Record transaction
                $stmt = $db->prepare("INSERT INTO stock_transactions 
                    (product_id, transaction_type, quantity, gross_weight, net_weight, wastage_percent, reference_type, reference_id, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, 'purchase', ?, ?, ?)");
                $stmt->execute([
                    $productId, $transactionType, $quantity, $grossWeight, 
                    $netWeight, $wastagePercent, $referenceNo, $notes, $_SESSION['user_id']
                ]);
            }
        }
        
        $db->commit();
        
        logActivity('stock_inward', "Stock inward recorded: $referenceNo");
        redirectWithMessage('/inventory/stock.php', 'success', 'Stock inward recorded successfully!');
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error recording stock inward: ' . $e->getMessage();
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-arrow-in-down"></i> Stock Inward</h2>
    <a href="<?php echo BASE_URL; ?>/inventory/stock.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Stock
    </a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Stock Entry Details</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="stockInwardForm">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Transaction Type</label>
                    <select name="transaction_type" class="form-select">
                        <option value="inward">Stock Inward (Purchase)</option>
                        <option value="opening">Opening Stock</option>
                        <option value="adjustment">Stock Adjustment (+)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reference Number</label>
                    <input type="text" name="reference_no" class="form-control"placeholder="Enter invoice, challan or GRN number">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" disabled>
                </div>
            </div>
            
            <!-- Stock Items -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Stock Items</h6>
                    <button type="button" class="btn btn-sm btn-success" onclick="addStockItem()">
                        <i class="bi bi-plus"></i> Add Item
                    </button>
                </div>
                <div class="card-body">
                    <div id="stock-items-container">
                        <!-- First Item Row -->
                        <div class="stock-item-row border rounded p-3 mb-3">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" name="items[0][is_new_product]" class="form-check-input is-new-product" value="1" id="new_product_0" onchange="toggleProductSelect(0)">
                                        <label class="form-check-label" for="new_product_0">Create New Product</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row existing-product-row-0">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Select Product</label>
                                    <select name="items[0][product_id]" class="form-select product-select">
                                        <option value="">-- Select Product --</option>
                                        <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>">
                                            <?php echo $product['name'] . ' (' . ucfirst($product['metal_type']) . ' ' . $product['purity'] . ')'; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row new-product-row-0" style="display:none;">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" name="items[0][new_product_name]" class="form-control">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="items[0][category_id]" class="form-select">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Metal</label>
                                    <select name="items[0][metal_type]" class="form-select">
                                        <option value="gold">Gold</option>
                                        <option value="silver">Silver</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Purity</label>
                                    <select name="items[0][purity]" class="form-select">
                                        <option value="24K">24K</option>
                                        <option value="22K" selected>22K</option>
                                        <option value="18K">18K</option>
                                        <option value="14K">14K</option>
                                        <option value="999">999</option>
                                        <option value="925">925</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" name="items[0][quantity]" class="form-control" min="1" value="1" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Gross Wt (g)</label>
                                   <input type="number" name="items[0][gross_weight]" class="form-control" step="0.001" min="0" oninput="validateWeights(this)">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Net Wt (g) <span class="text-danger">*</span></label>
                                   <input type="number" name="items[0][net_weight]" class="form-control" step="0.001" min="0.001" required oninput="validateWeights(this)">
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Wastage %</label>
                                    <input type="number" name="items[0][wastage_percent]" class="form-control" step="0.01" min="0" value="0">
                                </div>
                                <div class="col-md-4 mb-3 text-end">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeStockItem(this)">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" pplaceholder="Enter stock inward remarks or supplier notes"></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="/inventory/stock.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> Record Stock Inward
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let itemCount = 1;

function toggleProductSelect(index) {
    const isNew = document.getElementById('new_product_' + index).checked;
    document.querySelector('.existing-product-row-' + index).style.display = isNew ? 'none' : 'flex';
    document.querySelector('.new-product-row-' + index).style.display = isNew ? 'flex' : 'none';
    
    // Toggle required fields
    const productSelect = document.querySelector('.existing-product-row-' + index + ' .product-select');
    const productName = document.querySelector('.new-product-row-' + index + ' input[name*="[new_product_name]"]');
    
    if (productSelect) productSelect.required = !isNew;
    if (productName) productName.required = isNew;
}

function addStockItem() {
    const container = document.getElementById('stock-items-container');
    const template = container.children[0].cloneNode(true);
    
    // Update indices
    template.innerHTML = template.innerHTML.replace(/\[0\]/g, '[' + itemCount + ']');
    template.innerHTML = template.innerHTML.replace(/_0/g, '_' + itemCount);
    template.innerHTML = template.innerHTML.replace(/existing-product-row-0/g, 'existing-product-row-' + itemCount);
    template.innerHTML = template.innerHTML.replace(/new-product-row-0/g, 'new-product-row-' + itemCount);
    
    // Reset values
    template.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
        if (input.type === 'number' && input.name.includes('quantity')) {
            input.value = '1';
        } else {
            input.value = '';
        }
    });
    template.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    // Reset visibility
    template.querySelector('.existing-product-row-' + itemCount).style.display = 'flex';
    template.querySelector('.new-product-row-' + itemCount).style.display = 'none';
    
    container.appendChild(template);
    itemCount++;
}

function removeStockItem(btn) {
    const rows = document.querySelectorAll('.stock-item-row');
    if (rows.length > 1) {
        btn.closest('.stock-item-row').remove();
    } else {
        alert('At least one item is required.');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
