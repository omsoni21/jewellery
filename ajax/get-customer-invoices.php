<?php
/**
 * AJAX: Get Customer Pending Invoices
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

header('Content-Type: application/json');

$customerId = intval($_POST['customer_id'] ?? 0);

if (!$customerId) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID']);
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("SELECT id, invoice_no, balance_amount FROM invoices WHERE customer_id = ? AND balance_amount > 0 ORDER BY invoice_date DESC");
$stmt->execute([$customerId]);
$invoices = $stmt->fetchAll();

echo json_encode(['success' => true, 'invoices' => $invoices]);
