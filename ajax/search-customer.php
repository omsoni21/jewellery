<?php

/**
 * AJAX: Search Customers
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

header('Content-Type: application/json');

$query = $_GET['q'] ?? $_POST['query'] ?? '';
$query = sanitize($query);

if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query too short']);
    exit;
}

$db = getDBConnection();
$searchTerm = "%$query%";
$stmt = $db->prepare("SELECT id, business_name, phone, gst_number as gstin, current_balance, 
                      CONCAT_WS(', ', address_line1, city, state) as address
                      FROM customers 
                      WHERE is_active = 1 AND (business_name LIKE ? OR phone LIKE ? OR gst_number LIKE ?) 
                      ORDER BY business_name ASC
                      LIMIT 10");
$stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
$customers = $stmt->fetchAll();

echo json_encode(['success' => true, 'customers' => $customers]);
