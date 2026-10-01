<?php
// admin_edit_category.php — Edit an existing category (admin or manager)
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
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$category_id]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$c) {
    header('Location: categories.php?error=not_found');
    exit;
}

// Product usage summary for info banner
$usageStmt = $pdo->prepare(
    "SELECT COUNT(*) AS products,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_products
     FROM products WHERE category_id = ?"
);
$usageStmt->execute([$category_id]);
$usage = $usageStmt->fetch(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Category name is required.';
    } elseif (mb_strlen($name) > 100) {
        $error = 'Category name must be 100 characters or less.';
    } else {
        $dup = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id <> ?");
        $dup->execute([$name, $category_id]);
        if ($dup->fetchColumn()) {
            $error = 'Another category already uses that name.';
        }
    }

    if (!$error) {
        try {
            $changed = [];
            if ($name !== $c['name'])                            $changed[] = 'name';
            if (($description ?: null) !== $c['description'])    $changed[] = 'description';

            $stmt = $pdo->prepare(
                "UPDATE categories SET name = ?, description = ? WHERE id = ?"
            );
            $stmt->execute([$name, $description ?: null, $category_id]);

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'category_updated',
                "Updated category #{$category_id} ({$name})" .
                    ($changed ? ' — changed: ' . implode(', ', $changed) : ' — no changes')
            ]);

            header('Location: categories.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    // Repopulate on error
    $c['name']        = $name;
    $c['description'] = $description;
}

$page_title = 'Edit Category';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:640px;margin:0 auto;">
  <h5><i class="bi bi-pencil-square"></i> Edit Category — <strong><?= htmlspecialchars($c['name']) ?></strong></h5>

  <?php if ((int)$usage['products'] > 0): ?>
    <div class="status-badge status-pending" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <i class="bi bi-info-circle"></i>
      Used by <strong><?= (int)$usage['products'] ?></strong> product(s) —
      <strong><?= (int)$usage['active_products'] ?></strong> active.
    </div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Name *</label>
      <input type="text" name="name" required maxlength="100"
             value="<?= htmlspecialchars($c['name']) ?>"
             style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
    </div>

    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Description</label>
      <textarea name="description" rows="3" maxlength="1000"
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($c['description'] ?? '') ?></textarea>
    </div>

    <div style="margin-bottom:20px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Status</label>
      <div style="padding:10px 14px;background:#f1f5f9;border-radius:10px;">
        <span class="status-badge status-<?= $c['status'] === 'active' ? 'active' : 'cancelled' ?>">
          <?= htmlspecialchars($c['status']) ?>
        </span>
        <span style="color:var(--text-muted);font-size:.85rem;margin-left:10px;">
          Change status from the
          <a href="categories.php" style="color:var(--primary);">Categories list</a>.
        </span>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;">
      <a href="categories.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Update Category
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
