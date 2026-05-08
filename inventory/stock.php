<?php

/**
 * Stock View Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Stock View';
$db = getDBConnection();

// Handle filters
$metalType = $_GET['metal_type'] ?? '';
$purity = $_GET['purity'] ?? '';
$categoryId = intval($_GET['category_id'] ?? 0);

// Build query
$whereConditions = [];
$params = [];

if (!empty($metalType)) {
    $whereConditions[] = "p.metal_type = ?";
    $params[] = $metalType;
}

if (!empty($purity)) {
    $whereConditions[] = "p.purity = ?";
    $params[] = $purity;
}

if ($categoryId > 0) {
    $whereConditions[] = "p.category_id = ?";
    $params[] = $categoryId;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get stock data
$query = "SELECT p.*, c.name as category_name, s.quantity, s.gross_weight, s.net_weight, s.wastage_weight, s.purity_weight, s.last_updated, p.minimum_stock, p.minimum_weight
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN stock s ON p.id = s.product_id 
          $whereClause 
          ORDER BY p.metal_type, p.purity, p.name";

$stmt = $db->prepare($query);
$stmt->execute($params);
$stockItems = $stmt->fetchAll();

// Get stock with minimum levels
// NOTE: Removed duplicate query that was overwriting $stockItems from above.
// $stockItems is already populated by the query above.

// Get categories for filter
$stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Get stock summary
$stmt = $db->query("SELECT 
    p.metal_type, 
    p.purity, 
    COUNT(*) as product_count,
    SUM(s.quantity) as total_qty,
    SUM(s.net_weight) as total_weight
FROM products p 
LEFT JOIN stock s ON p.id = s.product_id 
WHERE p.is_active = 1
GROUP BY p.metal_type, p.purity
ORDER BY p.metal_type, p.purity");
$stockSummary = $stmt->fetchAll();


include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam"></i> Stock View</h2>
    <div>
        <a href="<?php echo BASE_URL; ?>/inventory/inward.php" class="btn btn-success">
            <i class="bi bi-box-arrow-in-down"></i> Stock Inward
        </a>
        <a href="<?php echo BASE_URL; ?>/inventory/products.php" class="btn btn-primary">
            <i class="bi bi-box"></i> Products
        </a>
        <!-- Quick Add Buttons -->
        <button type="button" class="btn btn-outline-primary ms-2" onclick="openAddProductModal()">
            <i class="bi bi-plus-circle"></i> Quick Add Product
        </button>
        <button type="button" class="btn btn-outline-success ms-2" onclick="openAddStockModal()">
            <i class="bi bi-plus-square"></i> Quick Add Stock
        </button>
    </div>
</div>


<!-- Stock Summary -->
<div class="row mb-4">
    <?php foreach ($stockSummary as $summary): ?>
        <div class="col-md-3 mb-3">
            <div class="card stats-card <?php echo $summary['metal_type'] === 'gold' ? 'warning' : 'secondary'; ?>">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="bi bi-<?php echo $summary['metal_type'] === 'gold' ? 'coin' : 'circle'; ?>"></i>
                    </div>
                    <div class="stats-number"><?php echo formatWeight($summary['total_weight'] ?? 0); ?></div>
                    <div class="stats-label">
                        <?php echo ucfirst($summary['metal_type']) . ' ' . $summary['purity']; ?>
                        <small class="d-block"><?php echo $summary['product_count']; ?> products, <?php echo $summary['total_qty'] ?? 0; ?> pcs</small>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <select name="metal_type" class="form-select">
                    <option value="">All Metals</option>
                    <option value="gold" <?php echo $metalType === 'gold' ? 'selected' : ''; ?>>Gold</option>
                    <option value="silver" <?php echo $metalType === 'silver' ? 'selected' : ''; ?>>Silver</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="purity" class="form-select">
                    <option value="">All Purities</option>
                    <option value="24K" <?php echo $purity === '24K' ? 'selected' : ''; ?>>24K</option>
                    <option value="22K" <?php echo $purity === '22K' ? 'selected' : ''; ?>>22K</option>
                    <option value="18K" <?php echo $purity === '18K' ? 'selected' : ''; ?>>18K</option>
                    <option value="14K" <?php echo $purity === '14K' ? 'selected' : ''; ?>>14K</option>
                    <option value="999" <?php echo $purity === '999' ? 'selected' : ''; ?>>Silver 999</option>
                    <option value="925" <?php echo $purity === '925' ? 'selected' : ''; ?>>Silver 925</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Stock Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Metal</th>
                                <th>Purity</th>
                                <th>Quantity</th>
                                <th>Min Qty</th>
                                <th>Gross Wt</th>
                                <th>Net Wt</th>
                                <th>Wastage Wt</th>
                                <th>Purity Wt</th>
                                <th>Min Wt</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                <tbody>
                    <?php if (empty($stockItems)): ?>
                        <tr>
                            <td colspan="14" class="text-center py-4 text-muted">No stock items found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stockItems as $item):
                            $isLowQty = ($item['quantity'] ?? 0) < ($item['minimum_stock'] ?? 5);
                            $isLowWt = ($item['net_weight'] ?? 0) < ($item['minimum_weight'] ?? 10);
                            $isLowStock = ($isLowQty || $isLowWt);
                            $rowClass = $isLowStock ? 'table-danger' : '';
                        ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td>
                                    <strong><?php echo $item['name']; ?></strong>
                                    <?php if ($isLowStock): ?>
                                        <i class="bi bi-exclamation-triangle-fill text-danger ms-1" title="Low Stock"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['category_name'] ?? '-'; ?></td>
                                <td><?php echo ucfirst($item['metal_type']); ?></td>
                                <td><?php echo $item['purity']; ?></td>
                                <td>
                                    <strong class="<?php echo $isLowQty ? 'text-danger' : ''; ?>">
                                        <?php echo $item['quantity'] ?? 0; ?>
                                    </strong>
                                    <?php if ($isLowQty): ?>
                                        <br><small class="text-danger">(-<?php echo ($item['minimum_stock'] ?? 5) - ($item['quantity'] ?? 0); ?> pcs)</small>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?php echo $item['minimum_stock'] ?? 5; ?></small></td>
                                <td><?php echo formatWeight($item['gross_weight'] ?? 0); ?></td>
                                <td>
                                    <strong class="<?php echo $isLowWt ? 'text-danger' : ''; ?>">
                                        <?php echo formatWeight($item['net_weight'] ?? 0); ?>
                                    </strong>
                                    <?php if ($isLowWt): ?>
                                        <br><small class="text-danger">(-<?php echo formatWeight(($item['minimum_weight'] ?? 10) - ($item['net_weight'] ?? 0)); ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatWeight($item['wastage_weight'] ?? 0); ?></td>
                                <td><?php echo formatWeight($item['purity_weight'] ?? 0); ?></td>
                                <td><small class="text-muted"><?php echo formatWeight($item['minimum_weight'] ?? 10); ?></small></td>
                                <td><small class="text-muted"><?php echo !empty($item['last_updated']) ? date('d M Y H:i', strtotime($item['last_updated'])) : '-'; ?></small></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" onclick="openAddStockModal(<?php echo (int)$item['id']; ?>, '<?php echo addslashes($item['name']); ?>')">Add Stock</button>
                                    <a href="<?php echo BASE_URL; ?>/inventory/products.php?toggle=1&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-secondary ms-1">Toggle</a>
                                </td>
                                <td>
                                    <?php if ($isLowStock): ?>
                                        <?php if ($isLowQty && $isLowWt): ?>
                                            <span class="badge bg-danger" title="Both quantity and weight are below minimum">
                                                <i class="bi bi-x-circle"></i> LOW QTY & WT
                                            </span>
                                        <?php elseif ($isLowQty): ?>
                                            <span class="badge bg-warning text-dark" title="Quantity below minimum">
                                                <i class="bi bi-exclamation-triangle"></i> LOW QTY
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark" title="Weight below minimum">
                                                <i class="bi bi-exclamation-triangle"></i> LOW WT
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle"></i> OK
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- Quick Add Product Modal -->
<div class="modal" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addProductForm" method="POST" action="<?php echo BASE_URL; ?>/inventory/products.php">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Add Product</h5>
                    <button type="button" class="btn-close" onclick="closeAddProductModal()"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Metal Type</label>
                            <select name="metal_type" class="form-select">
                                <option value="gold">Gold</option>
                                <option value="silver">Silver</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Purity</label>
                            <select name="purity" class="form-select">
                                <option value="24K">24K</option>
                                <option value="22K" selected>22K</option>
                                <option value="18K">18K</option>
                                <option value="14K">14K</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddProductModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Add Stock Modal -->
<div class="modal" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addStockForm" method="POST" action="<?php echo BASE_URL; ?>/inventory/inward.php">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Add Stock</h5>
                    <button type="button" class="btn-close" onclick="closeAddStockModal()"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="items[0][is_new_product]" value="0">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select name="items[0][product_id]" id="quickProductSelect" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php foreach ($stockItems as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" name="items[0][quantity]" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Net Weight (g)</label>
                        <input type="number" name="items[0][net_weight]" class="form-control" step="0.001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wastage %</label>
                        <input type="number" name="items[0][wastage_percent]" class="form-control" step="0.01" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddStockModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddProductModal() {
    document.getElementById('addProductModal').style.display = 'block';
}
function closeAddProductModal() {
    document.getElementById('addProductModal').style.display = 'none';
}
function openAddStockModal(id, name) {
    const modal = document.getElementById('addStockModal');
    const sel = document.getElementById('quickProductSelect');
    if (id) {
        sel.value = id;
    }
    modal.style.display = 'block';
}
function closeAddStockModal() {
    document.getElementById('addStockModal').style.display = 'none';
}
</script>