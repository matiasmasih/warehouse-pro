<?php
// admin_delete_category.php — Deactivate a category (soft delete, admin or manager)
// Real-warehouse rule: deactivation is allowed even if products use this category.
// The category becomes inactive (hidden from new-product pickers), but existing product links stay intact.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('categories');

if (my_access('categories') !== 'full') {
    header('Location: categories.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: categories.php?error=invalid_id');
    exit;
}
$category_id = (int)$_GET['id'];

// Load category
$stmt = $pdo->prepare("SELECT id, name, status FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    header('Location: categories.php?error=not_found');
    exit;
}

// Already inactive?
if ($c['status'] !== 'active') {
    header('Location: categories.php?error=already_inactive');
    exit;
}

// Usage count for audit log (not a block — just recorded)
$useStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
$useStmt->execute([$category_id]);
$productCount = (int)$useStmt->fetchColumn();

// Soft deactivate
try {
    $upd = $pdo->prepare("UPDATE categories SET status = 'inactive' WHERE id = ?");
    $upd->execute([$category_id]);

    $log = $pdo->prepare(
        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
    );
    $log->execute([
        $_SESSION['user_id'],
        'category_deactivated',
        "Deactivated category #{$category_id} ({$c['name']})" .
            ($productCount > 0 ? " — note: {$productCount} product(s) still reference it" : '')
    ]);

    header('Location: categories.php?deactivated=success');
    exit;
} catch (PDOException $e) {
    header('Location: categories.php?error=failed');
    exit;
}
