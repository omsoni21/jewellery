<?php

/**
 * API Payments Endpoint
 * Handles payment creation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $db = getDBConnection();

    $customer_id = $_POST['customer_id'] ?? 0;
    $invoice_id = $_POST['invoice_id'] ?? 0;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $reference_no = $_POST['reference_no'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if ($customer_id == 0 || $amount == 0) {
      echo json_encode([
        'success' => false,
        'message' => 'Customer ID and amount are required'
      ]);
      exit();
    }

    // Insert payment
    $stmt = $db->prepare("INSERT INTO payments (customer_id, invoice_id, payment_date, amount, payment_method, reference_no, notes, created_at) 
                              VALUES (?, ?, NOW(), ?, ?, ?, ?, NOW())");
    $stmt->execute([$customer_id, $invoice_id, $amount, $payment_method, $reference_no, $notes]);

    $payment_id = $db->lastInsertId();

    // Update invoice balance if invoice_id is provided
    if ($invoice_id > 0) {
      $stmt = $db->prepare("UPDATE invoices SET paid_amount = paid_amount + ?, balance_amount = total_amount - (paid_amount + ?) WHERE id = ?");
      $stmt->execute([$amount, $amount, $invoice_id]);

      // Update payment status
      $stmt = $db->prepare("UPDATE invoices SET payment_status = CASE 
                WHEN balance_amount <= 0 THEN 'paid' 
                WHEN paid_amount > 0 THEN 'partial' 
                ELSE 'pending' 
            END WHERE id = ?");
      $stmt->execute([$invoice_id]);
    }

    echo json_encode([
      'success' => true,
      'message' => 'Payment recorded successfully',
      'payment_id' => $payment_id
    ]);
  } catch (Exception $e) {
    echo json_encode([
      'success' => false,
      'message' => 'Server error: ' . $e->getMessage()
    ]);
  }
} else {
  echo json_encode([
    'success' => false,
    'message' => 'Invalid request method'
  ]);
}
