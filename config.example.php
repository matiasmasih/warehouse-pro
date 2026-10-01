<?php
// config.example.php — Safe template for WarehousePro configuration
// Copy this to config.php and fill in your real credentials.

$host = 'localhost';
$db   = 'WarehouseProDB';
$user = 'warehouse_app';
$pass = 'CHANGE_ME';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}
?>
