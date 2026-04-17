<?php
/**
 * Stock Report Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Stock Report';
$db = getDBConnection();

// Get filters
$metalType = $_GET['metal_type'] ?? '';
$purity = $_GET['purity'] ?? '';
$categoryId = intval($_GET['category_id'] ?? 0);
$stockStatus = $_GET['stock_status'] ?? '';

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
if ($stockStatus === 'low') {
    $whereConditions[] = "(s.quantity < 5 OR s.net_weight < 10)";
} elseif ($stockStatus === 'out') {
    $whereConditions[] = "(s.quantity = 0 OR s.quantity IS NULL)";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get stock data
$query = "SELECT p.*, c.name as category_name, s.quantity, s.gross_weight, s.net_weight, s.wastage_weight
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN stock s ON p.id = s.product_id 
          $whereClause 
          ORDER BY p.metal_type, p.purity, p.name";
$stmt = $db->prepare($query);
$stmt->execute($params);
$stockItems = $stmt->fetchAll();

// Get summary by metal and purity
$summaryQuery = "SELECT p.metal_type, p.purity, 
                 COUNT(*) as product_count,
                 SUM(s.quantity) as total_qty,
                 SUM(s.gross_weight) as total_gross_weight,
                 SUM(s.net_weight) as total_net_weight,
                 SUM(s.wastage_weight) as total_wastage_weight
                 FROM products p 
                 LEFT JOIN stock s ON p.id = s.product_id 
                 WHERE p.is_active = 1
                 GROUP BY p.metal_type, p.purity
                 ORDER BY p.metal_type, p.purity";
$summary = $db->query($summaryQuery)->fetchAll();

// Get categories
$stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Calculate totals
$totalProducts = count($stockItems);
$totalQuantity = 0;
$totalNetWeight = 0;
$lowStockCount = 0;

foreach ($stockItems as $item) {
    $totalQuantity += $item['quantity'] ?? 0;
    $totalNetWeight += $item['net_weight'] ?? 0;
    if (($item['quantity'] ?? 0) < 5 || ($item['net_weight'] ?? 0) < 10) {
        $lowStockCount++;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box-seam"></i> Stock Report</h2>
    <button onclick="window.print()" class="btn btn-primary no-print">
        <i class="bi bi-printer"></i> Print Report
    </button>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h3><?php echo $totalProducts; ?></h3>
                <p class="mb-0">Total Products</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h3><?php echo $totalQuantity; ?></h3>
                <p class="mb-0">Total Quantity</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h3><?php echo formatWeight($totalNetWeight); ?></h3>
                <p class="mb-0">Total Net Weight</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body text-center">
                <h3><?php echo $lowStockCount; ?></h3>
                <p class="mb-0">Low Stock Items</p>
            </div>
        </div>
    </div>
</div>

<!-- Metal/Purity Summary -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Stock Summary by Metal & Purity</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Metal</th>
                        <th>Purity</th>
                        <th>Products</th>
                        <th>Quantity</th>
                        <th>Gross Weight</th>
                        <th>Net Weight</th>
                        <th>Wastage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row): ?>
                    <tr>
                        <td><?php echo ucfirst($row['metal_type']); ?></td>
                        <td><?php echo $row['purity']; ?></td>
                        <td><?php echo $row['product_count']; ?></td>
                        <td><?php echo $row['total_qty'] ?? 0; ?></td>
                        <td><?php echo formatWeight($row['total_gross_weight'] ?? 0); ?></td>
                        <td><?php echo formatWeight($row['total_net_weight'] ?? 0); ?></td>
                        <td><?php echo formatWeight($row['total_wastage_weight'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-2">
                <select name="metal_type" class="form-select">
                    <option value="">All Metals</option>
                    <option value="gold" <?php echo $metalType === 'gold' ? 'selected' : ''; ?>>Gold</option>
                    <option value="silver" <?php echo $metalType === 'silver' ? 'selected' : ''; ?>>Silver</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="purity" class="form-select">
                    <option value="">All Purities</option>
                    <option value="24K" <?php echo $purity === '24K' ? 'selected' : ''; ?>>24K</option>
                    <option value="22K" <?php echo $purity === '22K' ? 'selected' : ''; ?>>22K</option>
                    <option value="18K" <?php echo $purity === '18K' ? 'selected' : ''; ?>>18K</option>
                    <option value="14K" <?php echo $purity === '14K' ? 'selected' : ''; ?>>14K</option>
                    <option value="999" <?php echo $purity === '999' ? 'selected' : ''; ?>>999</option>
                    <option value="925" <?php echo $purity === '925' ? 'selected' : ''; ?>>925</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>><?php echo $cat['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="stock_status" class="form-select">
                    <option value="">All Stock Status</option>
                    <option value="low" <?php echo $stockStatus === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="out" <?php echo $stockStatus === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Detailed Stock Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Detailed Stock Report</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="stockTable">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Metal</th>
                        <th>Purity</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Gross Wt</th>
                        <th class="text-end">Net Wt</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockItems)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No products found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($stockItems as $item): ?>
                    <tr class="<?php echo ($item['quantity'] ?? 0) == 0 ? 'table-danger' : (($item['quantity'] ?? 0) < 5 || ($item['net_weight'] ?? 0) < 10 ? 'table-warning' : ''); ?>">
                        <td><span class="badge bg-secondary"><?php echo $item['product_code']; ?></span></td>
                        <td><strong><?php echo $item['name']; ?></strong></td>
                        <td><?php echo $item['category_name'] ?? '-'; ?></td>
                        <td><?php echo ucfirst($item['metal_type']); ?></td>
                        <td><?php echo $item['purity']; ?></td>
                        <td class="text-end"><?php echo $item['quantity'] ?? 0; ?></td>
                        <td class="text-end"><?php echo formatWeight($item['gross_weight'] ?? 0); ?></td>
                        <td class="text-end"><?php echo formatWeight($item['net_weight'] ?? 0); ?></td>
                        <td>
                            <?php if (($item['quantity'] ?? 0) == 0): ?>
                                <span class="badge bg-danger">Out of Stock</span>
                            <?php elseif (($item['quantity'] ?? 0) < 5 || ($item['net_weight'] ?? 0) < 10): ?>
                                <span class="badge bg-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge bg-success">OK</span>
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
