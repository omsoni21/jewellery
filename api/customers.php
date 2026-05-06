<?php

/**
 * API Customers Endpoint
 * Returns customer list and individual customer details
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

try {
  $db = getDBConnection();

  if (isset($_GET['id'])) {
    // Get single customer
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch();

    if ($customer) {
      echo json_encode($customer);
    } else {
      echo json_encode(['error' => 'Customer not found']);
    }
  } else {
    // Get all customers
    $stmt = $db->query("SELECT * FROM customers WHERE is_active = 1 ORDER BY business_name");
    $customers = $stmt->fetchAll();

    echo json_encode($customers);
  }
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Server error: ' . $e->getMessage()
  ]);
}
