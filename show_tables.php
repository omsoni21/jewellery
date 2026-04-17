<?php
require_once 'config/database.php';

try {
  $pdo = getDBConnection();

  // Query to get all tables
  $stmt = $pdo->query("SHOW TABLES");
  $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

  echo "<h2>Database Tables in '" . DB_NAME . "'</h2>";
  echo "<table border='1' cellpadding='10' cellspacing='0'>";
  echo "<tr><th>#</th><th>Table Name</th><th>Row Count</th></tr>";

  $count = 1;
  foreach ($tables as $table) {
    // Get row count for each table
    $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
    $rowCount = $countStmt->fetchColumn();

    echo "<tr>";
    echo "<td>" . $count++ . "</td>";
    echo "<td>" . htmlspecialchars($table) . "</td>";
    echo "<td>" . number_format($rowCount) . "</td>";
    echo "</tr>";
  }

  echo "</table>";
  echo "<p><strong>Total Tables:</strong> " . count($tables) . "</p>";
} catch (PDOException $e) {
  echo "Error: " . $e->getMessage();
}
?>

<style>
  body {
    font-family: Arial, sans-serif;
    margin: 40px;
    background-color: #f5f5f5;
  }

  h2 {
    color: #333;
    margin-bottom: 20px;
  }

  table {
    width: 100%;
    max-width: 800px;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-collapse: collapse;
  }

  th {
    background-color: #4CAF50;
    color: white;
    text-align: left;
    padding: 12px;
  }

  td {
    padding: 10px 12px;
  }

  tr:nth-child(even) {
    background-color: #f9f9f9;
  }

  tr:hover {
    background-color: #f1f1f1;
  }

  p {
    margin-top: 20px;
    font-size: 16px;
  }
</style>