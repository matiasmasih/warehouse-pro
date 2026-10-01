<?php
// admin_delete_product.php — Discontinue a product (soft delete, admin or manager)
// Allowed even with stock on hand — the point is to stop reordering / sunset the SKU.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('products');

if (my_access('products') !== 'full') {
    header('Location: products.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: products.php?error=invalid_id');
    exit;
}
$product_id = (int)$_GET['id'];

// Load product
$stmt = $pdo->prepare("SELECT id, name, sku, status FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    header('Location: products.php?error=not_found');
    exit;
}

// Already discontinued?
if ($p['status'] === 'discontinued') {
    header('Location: products.php?error=already_disc');
    exit;
}

// Current on-hand (for the audit log message — not a block)
$stockStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM inventory WHERE product_id = ?");
$stockStmt->execute([$product_id]);
$units = (int)$stockStmt->fetchColumn();

// Soft discontinue
try {
    $upd = $pdo->prepare("UPDATE products SET status = 'discontinued' WHERE id = ?");
    $upd->execute([$product_id]);

    $log = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
    );
    $log->execute([
        $_SESSION['user_id'],
        'product_discontinued',
        "Discontinued product #{$product_id} ({$p['sku']} — {$p['name']})" .
            ($units > 0 ? " — note: {$units} unit(s) still in stock" : '')
    ]);

    header('Location: products.php?discontinued=success');
    exit;
} catch (PDOException $e) {
    header('Location: products.php?error=failed');
    exit;
}
