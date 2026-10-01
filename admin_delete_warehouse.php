<?php
// admin_delete_warehouse.php — Deactivate a warehouse (soft delete, admin only)
// Filename kept for link compatibility, but this performs a status update, not DELETE.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('warehouses');

if (current_role() !== 'admin') {
    header('Location: warehouses.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: warehouses.php?error=invalid_id');
    exit;
}
$warehouse_id = (int)$_GET['id'];

// Load warehouse
$stmt = $pdo->prepare("SELECT id, name, code, status FROM warehouses WHERE id = ?");
$stmt->execute([$warehouse_id]);
$w = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$w) {
    header('Location: warehouses.php?error=not_found');
    exit;
}

// Already inactive?
if ($w['status'] !== 'active') {
    header('Location: warehouses.php?error=already_inactive');
    exit;
}

// Stock guard — cannot deactivate a warehouse that still holds stock
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(i.quantity),0)
     FROM inventory i
     JOIN locations l ON l.id = i.location_id
     WHERE l.warehouse_id = ?"
);
$stmt->execute([$warehouse_id]);
$units = (int)$stmt->fetchColumn();

if ($units > 0) {
    header('Location: warehouses.php?error=has_stock');
    exit;
}

// Soft deactivate
try {
    $upd = $pdo->prepare("UPDATE warehouses SET status = 'inactive' WHERE id = ?");
    $upd->execute([$warehouse_id]);

    $log = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
    );
    $log->execute([
        $_SESSION['user_id'],
        'warehouse_deactivated',
        "Deactivated warehouse #{$warehouse_id} ({$w['code']} — {$w['name']})"
    ]);

    header('Location: warehouses.php?deactivated=success');
    exit;
} catch (PDOException $e) {
    header('Location: warehouses.php?error=failed');
    exit;
}
