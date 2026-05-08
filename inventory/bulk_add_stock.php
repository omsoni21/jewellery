<?php
/**
 * Bulk add stock to listed products (respects filters passed)
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole([ROLE_ADMIN, ROLE_ACCOUNTANT]);

$db = getDBConnection();

$metalType = $_POST['metal_type'] ?? '';
$purity = $_POST['purity'] ?? '';
$categoryId = intval($_POST['category_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 0);
$netWeight = floatval($_POST['net_weight'] ?? 0);
$wastagePercent = floatval($_POST['wastage_percent'] ?? 0);
$notes = sanitize($_POST['notes'] ?? 'Bulk add');

if ($quantity <= 0 && $netWeight <= 0) {
    redirectWithMessage('/inventory/stock.php', 'error', 'Quantity or net weight must be greater than zero.');
}

// Build WHERE
$where = [];
$params = [];
if (!empty($metalType)) { $where[] = 'p.metal_type = ?'; $params[] = $metalType; }
if (!empty($purity)) { $where[] = 'p.purity = ?'; $params[] = $purity; }
if ($categoryId > 0) { $where[] = 'p.category_id = ?'; $params[] = $categoryId; }
$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT p.id FROM products p LEFT JOIN stock s ON p.id = s.product_id $whereSql ORDER BY p.name");
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    $updateStock = $db->prepare("UPDATE stock SET quantity = quantity + ?, gross_weight = gross_weight + ?, net_weight = net_weight + ?, wastage_weight = wastage_weight + ?, last_updated = NOW() WHERE product_id = ?");
    $insertTrans = $db->prepare("INSERT INTO stock_transactions (product_id, transaction_type, quantity, gross_weight, net_weight, wastage_percent, reference_type, reference_id, notes, created_by) VALUES (?, 'inward', ?, 0, ?, ?, 'bulk', NULL, ?, ?)");

    foreach ($products as $p) {
        $pid = (int)$p['id'];
        $updateStock->execute([$quantity, 0, $netWeight, $netWeight * ($wastagePercent / 100), $pid]);
        $insertTrans->execute([$pid, $quantity, $netWeight, $wastagePercent, $notes, $_SESSION['user_id'] ?? null]);
    }

    $db->commit();
    logActivity('bulk_stock', "Bulk added qty={$quantity}, net_weight={$netWeight}g to listed products");
    redirectWithMessage('/inventory/stock.php', 'success', 'Bulk stock updated for listed products.');
} catch (Exception $e) {
    $db->rollBack();
    redirectWithMessage('/inventory/stock.php', 'error', 'Bulk update failed: ' . $e->getMessage());
}
