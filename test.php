<?php
require 'config/db.php';

$stmt = $pdo->query("SELECT * FROM products LIMIT 5");
$products = $stmt->fetchAll();

echo "<pre>";
print_r($products);
echo "</pre>";
