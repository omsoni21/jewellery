<?php

/**
 * Debug: Count parameters in create.php INSERT statements
 */

require_once 'config/database.php';

echo "=== DEBUGGING INSERT STATEMENTS ===\n\n";

// Read create.php
$file = file_get_contents('billing/create.php');

// Find the invoice_items INSERT
if (preg_match('/INSERT INTO invoice_items.*?VALUES.*?\)/s', $file, $matches)) {
  $insert = $matches[0];

  // Count columns
  if (preg_match('/\((.*?)\)\s*VALUES/s', $insert, $cols)) {
    $columns = array_map('trim', explode(',', $cols[1]));
    echo "invoice_items INSERT:\n";
    echo "Number of columns: " . count($columns) . "\n";
    echo "Columns: " . implode(', ', $columns) . "\n\n";
  }

  // Count placeholders
  if (preg_match_all('/\?/', $insert, $places)) {
    echo "Number of placeholders: " . count($places[0]) . "\n\n";
  }
}

// Find the execute array for invoice_items
if (preg_match('/INSERT INTO invoice_items.*?foreach.*?execute\(\[(.*?)\]\)/s', $file, $matches)) {
  $exec = $matches[1];
  // Count array elements (rough count by commas)
  $elements = substr_count($exec, ',') + 1;
  echo "invoice_items EXECUTE array:\n";
  echo "Approximate elements: $elements\n\n";
}

// Find the invoices INSERT
if (preg_match('/INSERT INTO invoices.*?VALUES.*?\)/s', $file, $matches)) {
  $insert = $matches[0];

  // Count columns
  if (preg_match('/\((.*?)\)\s*VALUES/s', $insert, $cols)) {
    $columns = array_map('trim', explode(',', $cols[1]));
    echo "invoices INSERT:\n";
    echo "Number of columns: " . count($columns) . "\n";
    echo "Columns: " . implode(', ', $columns) . "\n\n";
  }

  // Count placeholders
  if (preg_match_all('/\?/', $insert, $places)) {
    echo "Number of placeholders: " . count($places[0]) . "\n\n";
  }
}

echo "=== CHECKING ACTUAL DATABASE STRUCTURE ===\n\n";

$db = getDBConnection();

echo "invoice_items columns:\n";
$stmt = $db->query('DESCRIBE invoice_items');
while ($row = $stmt->fetch()) {
  echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n\ninvoices columns:\n";
$stmt = $db->query('DESCRIBE invoices');
while ($row = $stmt->fetch()) {
  echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n=== DONE ===\n";
