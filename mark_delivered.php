<?php
// mark_delivered.php — Mark a shipped shipment as delivered (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('shipments');

if (!in_array(current_role(), ['admin','manager'], true)) {
    header('Location: shipments.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: shipments.php?error=invalid_id');
    exit;
}
$shipment_id = (int)$_GET['id'];

// Load shipment
$stmt = $pdo->prepare("SELECT id, customer_name, status, tracking_number FROM shipments WHERE id = ?");
$stmt->execute([$shipment_id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    header('Location: shipments.php?error=not_found');
    exit;
}

// Only shipped → delivered is allowed
if ($s['status'] !== 'shipped') {
    header('Location: shipments.php?error=not_shipped');
    exit;
}

try {
    $upd = $pdo->prepare("UPDATE shipments SET status = 'delivered' WHERE id = ?");
    $upd->execute([$shipment_id]);

    $log = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
    );
    $log->execute([
        $_SESSION['user_id'],
        'shipment_delivered',
        "Marked SH-{$shipment_id} as delivered" .
            ($s['tracking_number'] ? " (tracking {$s['tracking_number']})" : '') .
            " — customer: " . ($s['customer_name'] ?? 'n/a')
    ]);

    header('Location: shipments.php?delivered=success');
    exit;
} catch (PDOException $e) {
    header('Location: shipments.php?error=failed');
    exit;
}
