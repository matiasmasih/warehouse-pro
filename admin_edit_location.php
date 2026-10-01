<?php
// admin_edit_location.php — Edit an existing location (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('locations');

if (my_access('locations') !== 'full') {
    header('Location: locations.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: locations.php?error=invalid_id');
    exit;
}
$location_id = (int)$_GET['id'];

// Load location
$stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
$stmt->execute([$location_id]);
$l = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$l) {
    header('Location: locations.php?error=not_found');
    exit;
}

// Stock status — used to warn if renaming/moving a bin that holds stock
$stockStmt = $pdo->prepare(
    "SELECT COUNT(*) AS skus, COALESCE(SUM(quantity),0) AS units
     FROM inventory WHERE location_id = ?"
);
$stockStmt->execute([$location_id]);
$stock = $stockStmt->fetch(PDO::FETCH_ASSOC);
$hasStock = ((int)$stock['units'] > 0);

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
        $chk = $pdo->prepare("SELECT id FROM warehouses WHERE id = ?");
        $chk->execute([$warehouse_id]);
        if (!$chk->fetchColumn()) {
            $error = 'Selected warehouse does not exist.';
        } elseif ($code !== '') {
            $dup = $pdo->prepare("SELECT id FROM locations WHERE code = ? AND id <> ?");
            $dup->execute([$code, $location_id]);
            if ($dup->fetchColumn()) {
                $error = 'That location code is already in use.';
            }
        }
    }

    if (!$error) {
        try {
            $changed = [];
            if ($warehouse_id !== (int)$l['warehouse_id'])  $changed[] = 'warehouse';
            if ($name !== $l['name'])                       $changed[] = 'name';
            if ($code !== ($l['code'] ?? ''))               $changed[] = 'code';
            if ($type !== $l['type'])                       $changed[] = 'type';
            if (($address ?: null) !== $l['address'])       $changed[] = 'address';
            if (($description ?: null) !== $l['description']) $changed[] = 'description';

            $stmt = $pdo->prepare(
                "UPDATE locations
                 SET warehouse_id = ?, name = ?, code = ?, type = ?, address = ?, description = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $warehouse_id,
                $name,
                $code ?: null,
                $type,
                $address ?: null,
                $description ?: null,
                $location_id,
            ]);

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'location_updated',
                "Updated location #{$location_id} ({$name}" . ($code ? ", code {$code}" : '') . ")" .
                    ($changed ? ' — changed: ' . implode(', ', $changed) : ' — no changes')
            ]);

            header('Location: locations.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    // Repopulate on error
    $l['warehouse_id'] = $warehouse_id;
    $l['name']         = $name;
    $l['code']         = $code;
    $l['type']         = $type;
    $l['address']      = $address;
    $l['description']  = $description;
}

$warehouses = $pdo->query("SELECT id, name, city, code, status FROM warehouses ORDER BY name")->fetchAll();
$types = ['receiving','storage','dispatch','returns','quarantine'];

$page_title = 'Edit Location';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:820px;margin:0 auto;">
  <h5>
    <i class="bi bi-pencil-square"></i> Edit Location —
    <strong><?= htmlspecialchars($l['name']) ?></strong>
    <?php if ($l['code']): ?><code><?= htmlspecialchars($l['code']) ?></code><?php endif; ?>
  </h5>

  <?php if ($hasStock): ?>
    <div class="status-badge status-pending" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <i class="bi bi-info-circle"></i>
      This location currently holds <strong><?= (int)$stock['units'] ?></strong> units across <strong><?= (int)$stock['skus'] ?></strong> SKUs. Changes here won't move stock.
    </div>
  <?php endif; ?>

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
          <?php foreach ($warehouses as $w): ?>
            <option value="<?= (int)$w['id'] ?>" <?= ((int)$l['warehouse_id'] === (int)$w['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($w['code']) ?> — <?= htmlspecialchars($w['name']) ?><?= $w['city'] ? ' (' . htmlspecialchars($w['city']) . ')' : '' ?><?= $w['status'] !== 'active' ? ' [inactive]' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Location Name *</label>
        <input type="text" name="name" required maxlength="100"
               value="<?= htmlspecialchars($l['name']) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Code</label>
        <input type="text" name="code" maxlength="20" pattern="[A-Za-z0-9\-]{2,20}"
               value="<?= htmlspecialchars($l['code'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;text-transform:uppercase;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Type *</label>
        <select name="type" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <?php foreach ($types as $t): ?>
            <option value="<?= $t ?>" <?= ($l['type'] === $t) ? 'selected' : '' ?>>
              <?= ucfirst($t) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Address</label>
        <input type="text" name="address" maxlength="255"
               value="<?= htmlspecialchars($l['address'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Description</label>
        <textarea name="description" rows="3"
                  style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($l['description'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="locations.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Update Location
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
