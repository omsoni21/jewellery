<?php

/**
 * API Stock Endpoint
 * Returns current stock levels
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

try {
  $db = getDBConnection();

  $stmt = $db->query("SELECT s.*, p.name as product_name, cat.name as category 
                        FROM stock s 
                        JOIN products p ON s.product_id = p.id 
                        JOIN categories cat ON p.category_id = cat.id 
                        ORDER BY p.name");
  $stock = $stmt->fetchAll();

  echo json_encode($stock);
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Server error: ' . $e->getMessage()
  ]);
}
