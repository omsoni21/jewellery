<?php
/**
 * AJAX Endpoint for Barcode Search
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/barcode.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$barcode = isset($_GET['barcode']) ? sanitize($_GET['barcode']) : '';

if (empty($barcode)) {
    echo json_encode(['success' => false, 'error' => 'Barcode is required']);
    exit();
}

$db = getDBConnection();
$product = searchProductByBarcode($db, $barcode);

if ($product) {
    echo json_encode([
        'success' => true,
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'metal_type' => $product['metal_type'],
            'purity' => $product['purity'],
            'stock_quantity' => $product['stock_quantity'] ?? 0,
            'stock_weight' => $product['stock_weight'] ?? 0
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
}
