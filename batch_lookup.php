<?php
// batch_lookup.php — JSON list of available batches for a product
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { http_response_code(401); exit; }

$pid = (int)($_GET['product_id'] ?? 0);
if ($pid <= 0) { echo '[]'; exit; }

$st = $pdo->prepare(
    "SELECT b.id, b.batch_number, b.expiry_date, b.quantity, l.code AS bin_code
     FROM product_batches b
     LEFT JOIN locations l ON l.id = b.location_id
     WHERE b.product_id = ? AND b.status = 'available' AND b.quantity > 0
     ORDER BY (b.expiry_date IS NULL), b.expiry_date ASC, b.id ASC"
);
$st->execute([$pid]);
header('Content-Type: application/json');
echo json_encode($st->fetchAll());

