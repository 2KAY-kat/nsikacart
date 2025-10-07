<?php
// Absolute error reporting for testing
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Make sure paths are correct
require_once __DIR__ . '/config/db.php';

// Simple query to fetch first 10 products
try {
    $stmt = $pdo->query("SELECT * FROM products LIMIT 10");
    $products = $stmt->fetchAll();

    echo "<h2>Live Products from Railway</h2>";
    if (!$products) {
        echo "<p>No products found in the database.</p>";
        exit;
    }

    echo "<pre>";
    print_r($products);
    echo "</pre>";

} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
}
