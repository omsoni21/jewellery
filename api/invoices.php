<?php

/**
 * API Invoices Endpoint
 * Returns invoice list and individual invoice details
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

try {
  $db = getDBConnection();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle invoice creation
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['customer_id']) || !isset($data['items']) || empty($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
        exit;
    }

    $db->beginTransaction();

    try {
        // Generate Invoice Number
        $stmt = $db->query("SELECT invoice_no FROM invoices ORDER BY id DESC LIMIT 1");
        $last_invoice = $stmt->fetch();
        $next_number = 1;
        if ($last_invoice) {
            $parts = explode('-', $last_invoice['invoice_no']);
            if (count($parts) == 2) {
                $next_number = intval($parts[1]) + 1;
            }
        }
        $invoice_no = 'INV-' . str_pad($next_number, 4, '0', STR_PAD_LEFT);

        $customer_id = $data['customer_id'];
        $invoice_date = $data['invoice_date'] ?? date('Y-m-d');
        $due_date = $data['due_date'] ?? date('Y-m-d');
        $discount_amount = isset($data['discount_amount']) ? floatval($data['discount_amount']) : 0.00;
        $notes = $data['notes'] ?? '';

        $subtotal = 0;
        foreach ($data['items'] as $item) {
            // Simplified calculation assuming item_total is precalculated or calculate here
            $item_total = isset($item['item_total']) ? floatval($item['item_total']) : 0;
            $subtotal += $item_total;
        }

        $taxable_amount = $subtotal - $discount_amount;
        if ($taxable_amount < 0) $taxable_amount = 0;

        // Assuming 3% IGST for simplicity or split CGST/SGST 1.5% each
        $igst_amount = $taxable_amount * 0.03;
        $total_amount = $taxable_amount + $igst_amount;

        $stmt = $db->prepare("INSERT INTO invoices 
            (invoice_no, customer_id, invoice_date, due_date, subtotal, discount_amount, taxable_amount, igst_amount, total_amount, payment_status, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, 1)");
        
        $stmt->execute([
            $invoice_no, $customer_id, $invoice_date, $due_date, 
            $subtotal, $discount_amount, $taxable_amount, $igst_amount, $total_amount, $notes
        ]);
        
        $invoice_id = $db->lastInsertId();

        $stmtItem = $db->prepare("INSERT INTO invoice_items 
            (invoice_id, product_id, item_name, metal_type, purity, quantity, gross_weight, net_weight, rate_per_gram, metal_amount, making_charge_amount, item_total) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($data['items'] as $item) {
            $product_id = isset($item['product_id']) ? $item['product_id'] : null;
            $stmtItem->execute([
                $invoice_id,
                $product_id,
                $item['item_name'] ?? 'Custom Item',
                $item['metal_type'] ?? 'gold',
                $item['purity'] ?? '22K',
                isset($item['quantity']) ? intval($item['quantity']) : 1,
                isset($item['gross_weight']) ? floatval($item['gross_weight']) : 0,
                isset($item['net_weight']) ? floatval($item['net_weight']) : 0,
                isset($item['rate_per_gram']) ? floatval($item['rate_per_gram']) : 0,
                isset($item['metal_amount']) ? floatval($item['metal_amount']) : 0,
                isset($item['making_charge_amount']) ? floatval($item['making_charge_amount']) : 0,
                isset($item['item_total']) ? floatval($item['item_total']) : 0
            ]);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Invoice created successfully',
            'invoice' => ['id' => $invoice_id, 'invoice_no' => $invoice_no]
        ]);
    } catch (Exception $ex) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $ex->getMessage()]);
    }
  } else if (isset($_GET['id'])) {
    // Get single invoice with items
    $stmt = $db->prepare("SELECT i.*, c.business_name FROM invoices i 
                              JOIN customers c ON i.customer_id = c.id 
                              WHERE i.id = ?");
    $stmt->execute([$_GET['id']]);
    $invoice = $stmt->fetch();

    if ($invoice) {
      // Get invoice items
      $stmt = $db->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
      $stmt->execute([$_GET['id']]);
      $items = $stmt->fetchAll();

      $invoice['items'] = $items;
      echo json_encode($invoice);
    } else {
      echo json_encode(['error' => 'Invoice not found']);
    }
  } else {
    // Get all invoices
    $stmt = $db->query("SELECT i.*, c.business_name FROM invoices i 
                            JOIN customers c ON i.customer_id = c.id 
                            ORDER BY i.created_at DESC LIMIT 50");
    $invoices = $stmt->fetchAll();

    echo json_encode($invoices);
  }
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Server error: ' . $e->getMessage()
  ]);
}
