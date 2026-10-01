<?php
// admin_add_category.php — Create a new category (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('categories');

if (my_access('categories') !== 'full') {
    header('Location: categories.php?error=not_allowed');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = 'Category name is required.';
    } elseif (mb_strlen($name) > 100) {
        $error = 'Category name must be 100 characters or less.';
    } else {
        $dup = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $dup->execute([$name]);
        if ($dup->fetchColumn()) {
            $error = 'A category with that name already exists.';
        }
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO categories (name, description, status) VALUES (?, ?, 'active')"
            );
            $stmt->execute([$name, $description ?: null]);
            $newId = (int)$pdo->lastInsertId();

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'category_created',
                "Created category #{$newId} ({$name})"
            ]);

            header('Location: categories.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Add Category';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:640px;margin:0 auto;">
  <h5><i class="bi bi-plus-circle-fill"></i> Add Category</h5>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Name *</label>
      <input type="text" name="name" required maxlength="100"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
             placeholder="e.g. Electronics"
             style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
    </div>

    <div style="margin-bottom:20px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Description</label>
      <textarea name="description" rows="3" maxlength="1000"
                placeholder="Optional short description"
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;">
      <a href="categories.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Create Category
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
