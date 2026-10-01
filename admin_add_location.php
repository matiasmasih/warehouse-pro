<?php
// admin_add_location.php — Create a new location (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('locations');

// Must have 'full' access (admin + manager)
if (my_access('locations') !== 'full') {
    header('Location: locations.php?error=not_allowed');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $warehouse_id = (int)($_POST['warehouse_id'] ?? 0);
    $name         = trim($_POST['name'] ?? '');
    $code         = strtoupper(trim($_POST['code'] ?? ''));
    $type         = $_POST['type'] ?? 'storage';
    $address      = trim($_POST['address'] ?? '');
    $description  = trim($_POST['description'] ?? '');

    $valid_types = ['receiving','storage','dispatch','returns','quarantine'];

    if ($warehouse_id <= 0) {
        $error = 'Please choose a warehouse.';
    } elseif ($name === '') {
        $error = 'Location name is required.';
    } elseif (!in_array($type, $valid_types, true)) {
        $error = 'Invalid location type.';
    } elseif ($code !== '' && !preg_match('/^[A-Z0-9\-]{2,20}$/', $code)) {
        $error = 'Code must be 2–20 characters, uppercase letters/numbers/dash only.';
    } else {
        // Verify warehouse exists
        $chk = $pdo->prepare("SELECT id FROM warehouses WHERE id = ?");
        $chk->execute([$warehouse_id]);
        if (!$chk->fetchColumn()) {
            $error = 'Selected warehouse does not exist.';
        } elseif ($code !== '') {
            // Duplicate code check
            $dup = $pdo->prepare("SELECT id FROM locations WHERE code = ?");
            $dup->execute([$code]);
            if ($dup->fetchColumn()) {
                $error = 'That location code is already in use.';
            }
        }
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO locations (warehouse_id, name, code, type, address, description)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $warehouse_id,
                $name,
                $code ?: null,
                $type,
                $address ?: null,
                $description ?: null,
            ]);
            $newId = (int)$pdo->lastInsertId();

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'location_created',
                "Created location #{$newId} ({$name}" . ($code ? ", code {$code}" : '') . ", type {$type})"
            ]);

            header('Location: locations.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$warehouses = $pdo->query("SELECT id, name, city, code FROM warehouses WHERE status = 'active' ORDER BY name")->fetchAll();
$types = ['receiving','storage','dispatch','returns','quarantine'];

$page_title = 'Add Location';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:820px;margin:0 auto;">
  <h5><i class="bi bi-geo-alt-fill"></i> Add Location</h5>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Warehouse *</label>
        <select name="warehouse_id" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— Select warehouse —</option>
          <?php foreach ($warehouses as $w): ?>
            <option value="<?= (int)$w['id'] ?>" <?= ((int)($_POST['warehouse_id'] ?? 0) === (int)$w['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($w['code']) ?> — <?= htmlspecialchars($w['name']) ?><?= $w['city'] ? ' (' . htmlspecialchars($w['city']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Location Name *</label>
        <input type="text" name="name" required maxlength="100"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               placeholder="e.g. A1-B01 Storage Bin"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Code <span style="font-weight:400;color:var(--text-muted);font-size:.85rem;">(optional, e.g. HEL-A1-B01)</span></label>
        <input type="text" name="code" maxlength="20" pattern="[A-Za-z0-9\-]{2,20}"
               value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;text-transform:uppercase;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Type *</label>
        <select name="type" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <?php foreach ($types as $t): ?>
            <option value="<?= $t ?>" <?= (($_POST['type'] ?? 'storage') === $t) ? 'selected' : '' ?>>
              <?= ucfirst($t) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Address</label>
        <input type="text" name="address" maxlength="255"
               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Description</label>
        <textarea name="description" rows="3"
                  style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="locations.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Create Location
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
