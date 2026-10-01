<?php
// admin_delete_shift.php — Remove a shift (admin or manager, soft-cancels via delete)
// Shifts are scheduling records, not audit history — safe to hard-delete.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('shifts');

if (my_access('shifts') !== 'full') {
    header('Location: shifts.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: shifts.php?error=invalid_id');
    exit;
}
$shift_id = (int)$_GET['id'];

// Load shift + staff info
$stmt = $pdo->prepare(
    "SELECT s.id, s.shift_date, s.shift_type, s.starts_at, s.ends_at, u.username
     FROM shifts s
     LEFT JOIN users u ON u.id = s.user_id
     WHERE s.id = ?"
);
$stmt->execute([$shift_id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    header('Location: shifts.php?error=not_found');
    exit;
}

try {
    $del = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
    $del->execute([$shift_id]);

    $log = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
    );
    $log->execute([
        $_SESSION['user_id'],
        'shift_deleted',
        "Removed shift #{$shift_id} — {$s['username']} {$s['shift_type']} on {$s['shift_date']} " .
            "(" . substr($s['starts_at'], 0, 5) . "–" . substr($s['ends_at'], 0, 5) . ")"
    ]);

    header('Location: shifts.php?deleted=success');
    exit;
} catch (PDOException $e) {
    header('Location: shifts.php?error=failed');
    exit;
}
