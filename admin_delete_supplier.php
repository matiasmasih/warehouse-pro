<?php
// admin_delete_supplier.php — Deactivate a supplier (soft delete, admin or manager)
// Real-warehouse rule: deactivation is allowed even if products use this supplier.
// Existing product→supplier links stay intact; the supplier is hidden from new-product pickers.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('suppliers');

if (my_access('suppliers') !== 'full') {
    header('Location: suppliers.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: suppliers.php?error=invalid_id');
    exit;
}
$supplier_id = (int)$_GET['id'];

// Load supplier
$stmt = $pdo->prepare("SELECT id, name, status FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    header('Location: suppliers.php?error=not_found');
    exit;
}

// Already inactive?
if ($s['status'] !== 'active') {
    header('Location: suppliers.php?error=already_inactive');
    exit;
}

// Usage count for audit log (not a block)
$useStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE supplier_id = ?");
$useStmt->execute([$supplier_id]);
$productCount = (int)$useStmt->fetchColumn();

// Soft deactivate
try {
    $upd = $pdo->prepare("UPDATE suppliers SET status = 'inactive' WHERE id = ?");
    $upd->execute([$supplier_id]);

    $log = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
    );
    $log->execute([
        $_SESSION['user_id'],
        'supplier_deactivated',
        "Deactivated supplier #{$supplier_id} ({$s['name']})" .
            ($productCount > 0 ? " — note: {$productCount} product(s) still reference it" : '')
    ]);

    header('Location: suppliers.php?deactivated=success');
    exit;
} catch (PDOException $e) {
    header('Location: suppliers.php?error=failed');
    exit;
}
