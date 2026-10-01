<?php
// admin_add_warehouse.php — Create a new warehouse (admin only)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('warehouses');

if (current_role() !== 'admin') {
    header('Location: warehouses.php?error=not_allowed');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $code       = strtoupper(trim($_POST['code'] ?? ''));
    $address    = trim($_POST['address'] ?? '');
    $city       = trim($_POST['city'] ?? '');
    $country    = trim($_POST['country'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $manager_id = $_POST['manager_id'] ?? '';
    $status     = $_POST['status'] ?? 'active';

    if ($name === '' || $code === '') {
        $error = 'Name and code are required.';
    } elseif (!preg_match('/^[A-Z0-9]{2,10}$/', $code)) {
        $error = 'Code must be 2–10 uppercase letters/numbers (e.g. HEL, TRE2).';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!in_array($status, ['active','inactive'], true)) {
        $error = 'Invalid status.';
    } else {
        $dup = $pdo->prepare("SELECT id FROM warehouses WHERE code = ?");
        $dup->execute([$code]);
        if ($dup->fetchColumn()) {
            $error = 'That warehouse code is already in use.';
        }
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO warehouses (name, code, address, city, country, phone, email, manager_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $name,
                $code,
                $address    ?: null,
                $city       ?: null,
                $country    ?: null,
                $phone      ?: null,
                $email      ?: null,
                $manager_id !== '' ? (int)$manager_id : null,
                $status,
            ]);
            $newId = (int)$pdo->lastInsertId();

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'warehouse_created',
                "Created warehouse #{$newId} ({$code} — {$name})"
            ]);

            header('Location: warehouses.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$managers = $pdo->query(
    "SELECT id, username FROM users WHERE role IN ('manager','admin') AND is_active = 1 ORDER BY username"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Add Warehouse';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:820px;margin:0 auto;">
  <h5><i class="bi bi-plus-circle-fill"></i> Add Warehouse</h5>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Warehouse Name *</label>
        <input type="text" name="name" required maxlength="150"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               placeholder="e.g. Helsinki Central Warehouse"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Code * <span style="font-weight:400;color:var(--text-muted);font-size:.85rem;">(2–10 uppercase)</span></label>
        <input type="text" name="code" required maxlength="10" pattern="[A-Z0-9]{2,10}"
               value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
               placeholder="HEL"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;text-transform:uppercase;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">City</label>
        <input type="text" name="city" maxlength="100"
               value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Country</label>
        <input type="text" name="country" maxlength="100"
               value="<?= htmlspecialchars($_POST['country'] ?? 'Finland') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Phone</label>
        <input type="tel" name="phone" maxlength="50"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Address</label>
        <input type="text" name="address" maxlength="255"
               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Email</label>
        <input type="email" name="email" maxlength="150"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Manager</label>
        <select name="manager_id" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— None —</option>
          <?php foreach ($managers as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= (($_POST['manager_id'] ?? '') == $m['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($m['username']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Status</label>
        <select name="status" style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="active"   <?= (($_POST['status'] ?? 'active') === 'active')   ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="warehouses.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Create Warehouse
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
