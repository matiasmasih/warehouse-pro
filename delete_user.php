<?php
// delete_user.php — Disable a user (soft-delete, admin only)
// Kept the filename for link compatibility, but this performs a DISABLE, not a DELETE.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('users');

if (current_role() !== 'admin') {
    header('Location: users.php?error=not_allowed');
    exit;
}

// Validate target ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: users.php?error=invalid_id');
    exit;
}
$targetId = (int)$_GET['id'];
$selfId   = (int)$_SESSION['user_id'];

// Block self
if ($targetId === $selfId) {
    header('Location: users.php?error=cannot_disable_self');
    exit;
}

// Load target user
$stmt = $pdo->prepare("SELECT id, username, role, is_active FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    header('Location: users.php?error=user_not_found');
    exit;
}

// Already disabled? Nothing to do.
if ((int)$target['is_active'] === 0) {
    header('Location: users.php?error=already_disabled');
    exit;
}

// Last-admin guard: if target is an admin, make sure another active admin remains
if ($target['role'] === 'admin') {
    $activeAdmins = (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1"
    )->fetchColumn();

    if ($activeAdmins <= 1) {
        header('Location: users.php?error=last_admin');
        exit;
    }
}

// Soft-disable
try {
    $upd = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $upd->execute([$targetId]);

    // Audit log
    $log = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
    );
    $log->execute([
        $selfId,
        'user_disabled',
        "Disabled user #{$targetId} ({$target['username']}, role {$target['role']})"
    ]);

    header('Location: users.php?disabled=success');
    exit;
} catch (PDOException $e) {
    header('Location: users.php?error=failed');
    exit;
}
