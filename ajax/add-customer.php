<?php

/**
 * AJAX: Add New Customer
 */

require_once __DIR__ . '/../includes/functions.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || $_POST['action'] !== 'add_customer') {
  echo json_encode(['success' => false, 'message' => 'Invalid request']);
  exit;
}

try {
  $db = getDBConnection();

  $businessName = sanitize($_POST['business_name'] ?? '');
  $phone = sanitize($_POST['phone'] ?? '');
  $contactPerson = sanitize($_POST['contact_person'] ?? '');
  $gstNumber = sanitize(strtoupper($_POST['gst_number'] ?? ''));
  $addressLine1 = sanitize($_POST['address_line1'] ?? '');
  $city = sanitize($_POST['city'] ?? '');
  $state = sanitize($_POST['state'] ?? '');
  $currentBalance = floatval($_POST['current_balance'] ?? 0);

  // Validate required fields
  if (empty($businessName)) {
    throw new Exception('Business/Customer name is required');
  }

  if (empty($phone) || strlen($phone) < 10) {
    throw new Exception('Valid phone number is required');
  }

  // Check if phone already exists
  $stmt = $db->prepare("SELECT id FROM customers WHERE phone = ? AND is_active = 1");
  $stmt->execute([$phone]);
  if ($stmt->fetch()) {
    throw new Exception('Customer with this phone number already exists');
  }

  // Generate customer code
  $stmt = $db->query("SELECT COUNT(*) as count FROM customers");
  $count = $stmt->fetch()['count'] + 1;
  $customerCode = 'CUST' . str_pad($count, 5, '0', STR_PAD_LEFT);

  // Insert customer
  $stmt = $db->prepare("INSERT INTO customers 
        (customer_code, business_name, contact_person, phone, gst_number, address_line1, city, state, current_balance, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");

  $stmt->execute([
    $customerCode,
    $businessName,
    $contactPerson,
    $phone,
    $gstNumber,
    $addressLine1,
    $city,
    $state,
    $currentBalance
  ]);

  $customerId = $db->lastInsertId();

  // Get the newly created customer
  $stmt = $db->prepare("SELECT id, business_name, phone, gst_number as gstin, current_balance FROM customers WHERE id = ?");
  $stmt->execute([$customerId]);
  $customer = $stmt->fetch();

  logActivity('customer_added', "Added new customer: $businessName ($phone) from billing page");

  echo json_encode([
    'success' => true,
    'message' => 'Customer added successfully',
    'customer' => $customer
  ]);
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
