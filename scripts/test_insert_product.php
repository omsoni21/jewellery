<?php
require_once __DIR__ . '/../config/database.php';
$db = getDBConnection();

try {
    $name = 'TEST PROD ' . time();
    $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(product_code,4) AS UNSIGNED)) as max_num FROM products WHERE product_code LIKE 'PRD%'");
    $res = $stmt->fetch();
    $next = (($res['max_num'] ?? 0) + 1);
    $code = 'PRD' . str_pad($next, 4, '0', STR_PAD_LEFT);

    $stmt = $db->prepare('INSERT INTO products (product_code, category_id, name, metal_type, purity, description) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$code, 1, $name, 'gold', '22K', 'test']);
    $productId = $db->lastInsertId();

    $stmt2 = $db->prepare('INSERT INTO stock (product_id, quantity, gross_weight, net_weight) VALUES (?, 0, 0, 0)');
    $stmt2->execute([$productId]);

    echo "Inserted productId={$productId} code={$code}\n";

    $q = $db->prepare('SELECT p.id, p.product_code, p.name, s.quantity, s.net_weight FROM products p LEFT JOIN stock s ON p.id = s.product_id WHERE p.id = ?');
    $q->execute([$productId]);
    print_r($q->fetch());
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
