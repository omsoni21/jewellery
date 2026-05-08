<?php
/**
 * Products Management Page
 */

require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$pageTitle = 'Products';
$db = getDBConnection();

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = sanitize($_POST['name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $metalType = $_POST['metal_type'] ?? 'gold';
    $purity = $_POST['purity'] ?? '22K';
    $description = sanitize($_POST['description'] ?? '');
    $minimumStock = intval($_POST['minimum_stock'] ?? 0);
    $minimumWeight = floatval($_POST['minimum_weight'] ?? 0);
    
    try {
        // Generate product code
        $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(product_code, 4) AS UNSIGNED)) as max_num FROM products WHERE product_code LIKE 'PRD%'");
        $result = $stmt->fetch();
        $nextNum = ($result['max_num'] ?? 0) + 1;
        $productCode = 'PRD' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        
        // Insert product; include minimum_stock/minimum_weight if columns exist
        $hasMinCols = false;
        try {
            $colCheck = $db->query("SHOW COLUMNS FROM products LIKE 'minimum_stock'")->fetch();
            if ($colCheck) $hasMinCols = true;
        } catch (Exception $e) {
            // ignore
        }

        if ($hasMinCols) {
            $stmt = $db->prepare("INSERT INTO products (product_code, category_id, name, metal_type, purity, description, minimum_stock, minimum_weight) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$productCode, $categoryId, $name, $metalType, $purity, $description, $minimumStock, $minimumWeight]);
        } else {
            $stmt = $db->prepare("INSERT INTO products (product_code, category_id, name, metal_type, purity, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$productCode, $categoryId, $name, $metalType, $purity, $description]);
        }
        
        $productId = $db->lastInsertId();
        
        // Create stock entry
        $stmt = $db->prepare("INSERT INTO stock (product_id, quantity, gross_weight, net_weight) VALUES (?, 0, 0, 0)");
        $stmt->execute([$productId]);
        
        logActivity('product_created', "Created product: $name");
        redirectWithMessage('/inventory/products.php', 'success', 'Product created successfully!');
    } catch (Exception $e) {
        $error = 'Error creating product: ' . $e->getMessage();
    }
}

// Handle product status toggle
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    $stmt = $db->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$productId]);
    redirectWithMessage('/inventory/products.php', 'success', 'Product status updated!');
}

// Get filters
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

// Get products with stock
$query = "SELECT p.*, c.name as category_name, s.quantity, s.net_weight 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          LEFT JOIN stock s ON p.id = s.product_id 
          $whereClause 
          ORDER BY p.name";
$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories
$stmt = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-box"></i> Products</h2>
    <a href="<?php echo BASE_URL; ?>/inventory/stock.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Stock
    </a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Products List -->
    <div class="col-md-8">
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
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Metal</th>
                                <th>Stock</th>
                                <th>Weight</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo $product['product_code']; ?></span></td>
                                <td><strong><?php echo $product['name']; ?></strong></td>
                                <td><?php echo $product['category_name'] ?? '-'; ?></td>
                                <td><?php echo ucfirst($product['metal_type']) . ' ' . $product['purity']; ?></td>
                                <td><?php echo $product['quantity'] ?? 0; ?></td>
                                <td><?php echo formatWeight($product['net_weight'] ?? 0); ?></td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?toggle=1&id=<?php echo $product['id']; ?>" class="btn btn-sm <?php echo $product['is_active'] ? 'btn-warning' : 'btn-success'; ?>" 
                                       onclick="return confirm('Are you sure?')">
                                        <?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Product Form -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Add New Product</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
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
                                <option value="999">999</option>
                                <option value="925">925</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Stock (pcs)</label>
                            <input type="number" name="minimum_stock" class="form-control" min="0" value="5">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Minimum Weight (g)</label>
                            <input type="number" name="minimum_weight" class="form-control" step="0.001" min="0" value="10">
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus"></i> Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
