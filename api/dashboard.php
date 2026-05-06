<?php

/**
 * API Dashboard Endpoint
 * Returns dashboard statistics and data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

try {
  $db = getDBConnection();
  $today = date('Y-m-d');
  $currentMonth = date('Y-m');

  // Today's sales
  $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE DATE(invoice_date) = ?");
  $stmt->execute([$today]);
  $todaySales = $stmt->fetch()['total'];

  // Monthly sales
  $stmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
  $stmt->execute([$currentMonth]);
  $monthlySales = $stmt->fetch()['total'];

  // Total customers
  $stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE is_active = 1");
  $totalCustomers = $stmt->fetch()['total'];

  // Outstanding amount
  $stmt = $db->query("SELECT COALESCE(SUM(balance_amount), 0) as total FROM invoices WHERE payment_status != 'paid'");
  $outstandingAmount = $stmt->fetch()['total'];

  // Recent invoices
  $stmt = $db->query("SELECT i.*, c.business_name FROM invoices i 
                        JOIN customers c ON i.customer_id = c.id 
                        ORDER BY i.created_at DESC LIMIT 5");
  $recentInvoices = $stmt->fetchAll();

  // Today's metal rates
  $stmt = $db->prepare("SELECT metal_type, purity, rate_per_gram FROM metal_rates WHERE rate_date = ? ORDER BY metal_type, purity");
  $stmt->execute([$today]);
  $metalRates = $stmt->fetchAll();

  echo json_encode([
    'success' => true,
    'todaySales' => (float)$todaySales,
    'monthlySales' => (float)$monthlySales,
    'totalCustomers' => (int)$totalCustomers,
    'outstandingAmount' => (float)$outstandingAmount,
    'recentInvoices' => $recentInvoices,
    'metalRates' => $metalRates
  ]);
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Server error: ' . $e->getMessage()
  ]);
}
