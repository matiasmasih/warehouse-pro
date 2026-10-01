<?php
// admin_edit_product.php — Edit an existing product (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('products');

if (my_access('products') !== 'full') {
    header('Location: products.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: products.php?error=invalid_id');
    exit;
}
$product_id = (int)$_GET['id'];

// Load product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    header('Location: products.php?error=not_found');
    exit;
}

// Current on-hand from inventory (authoritative)
$stockStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(quantity),0) AS units, COUNT(DISTINCT location_id) AS bins
     FROM inventory WHERE product_id = ?"
);
$stockStmt->execute([$product_id]);
$stock = $stockStmt->fetch(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $sku          = trim($_POST['sku'] ?? '');
    $barcode      = trim($_POST['barcode'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $category_id  = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $supplier_id  = $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
    $unit_price   = $_POST['unit_price'] !== '' ? (float)$_POST['unit_price'] : null;
    $weight       = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
    $dimensions   = trim($_POST['dimensions'] ?? '');
    $reorder_point= $_POST['reorder_point'] !== '' ? (int)$_POST['reorder_point'] : 10;
    $status       = $_POST['status'] ?? 'active';

    $valid_status = ['active','pending','discontinued'];

    if ($name === '') {
        $error = 'Product name is required.';
    } elseif ($sku === '') {
        $error = 'SKU is required.';
    } elseif (!preg_match('/^[A-Za-z0-9\-_.]{2,100}$/', $sku)) {
        $error = 'SKU must be 2–100 characters: letters, numbers, dash, dot, underscore.';
    } elseif (!in_array($status, $valid_status, true)) {
        $error = 'Invalid status.';
    } elseif ($unit_price !== null && $unit_price < 0) {
        $error = 'Unit price cannot be negative.';
    } elseif ($weight !== null && $weight < 0) {
        $error = 'Weight cannot be negative.';
    } elseif ($reorder_point < 0) {
        $error = 'Reorder point cannot be negative.';
    } else {
        // Duplicate SKU excluding self
        $dup = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id <> ?");
        $dup->execute([$sku, $product_id]);
        if ($dup->fetchColumn()) {
            $error = 'That SKU is already in use by another product.';
        }

        if (!$error && $category_id !== null) {
            $chk = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $chk->execute([$category_id]);
            if (!$chk->fetchColumn()) {
                $error = 'Selected category does not exist.';
            }
        }
        if (!$error && $supplier_id !== null) {
            $chk = $pdo->prepare("SELECT id FROM suppliers WHERE id = ?");
            $chk->execute([$supplier_id]);
            if (!$chk->fetchColumn()) {
                $error = 'Selected supplier does not exist.';
            }
        }
    }

    if (!$error) {
        try {
            $changed = [];
            if ($name          !== $p['name'])                            $changed[] = 'name';
            if ($sku           !== $p['sku'])                             $changed[] = 'sku';
            if (($barcode ?: null) !== $p['barcode'])                     $changed[] = 'barcode';
            if (($description ?: null) !== $p['description'])             $changed[] = 'description';
            if ($category_id   !== ($p['category_id'] !== null ? (int)$p['category_id'] : null)) $changed[] = 'category';
            if ($supplier_id   !== ($p['supplier_id'] !== null ? (int)$p['supplier_id'] : null)) $changed[] = 'supplier';
            if ((string)$unit_price !== (string)$p['unit_price'])         $changed[] = 'price';
            if ((string)$weight !== (string)$p['weight'])                 $changed[] = 'weight';
            if (($dimensions ?: null) !== $p['dimensions'])               $changed[] = 'dimensions';
            if ($reorder_point !== (int)$p['reorder_point'])              $changed[] = 'reorder';
            if ($status        !== $p['status'])                          $changed[] = 'status';

            $stmt = $pdo->prepare(
                "UPDATE products
                 SET name = ?, sku = ?, barcode = ?, description = ?,
                     category_id = ?, supplier_id = ?, unit_price = ?, weight = ?,
                     dimensions = ?, reorder_point = ?, status = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $name,
                $sku,
                $barcode     ?: null,
                $description ?: null,
                $category_id,
                $supplier_id,
                $unit_price,
                $weight,
                $dimensions  ?: null,
                $reorder_point,
                $status,
                $product_id,
            ]);

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'product_updated',
                "Updated product #{$product_id} ({$sku} — {$name})" .
                    ($changed ? ' — changed: ' . implode(', ', $changed) : ' — no changes')
            ]);

            header('Location: products.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    // Repopulate on error
    $p['name']          = $name;
    $p['sku']           = $sku;
    $p['barcode']       = $barcode;
    $p['description']   = $description;
    $p['category_id']   = $category_id;
    $p['supplier_id']   = $supplier_id;
    $p['unit_price']    = $unit_price;
    $p['weight']        = $weight;
    $p['dimensions']    = $dimensions;
    $p['reorder_point'] = $reorder_point;
    $p['status']        = $status;
}

$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$suppliers  = $pdo->query("SELECT id, name FROM suppliers  ORDER BY name")->fetchAll();

$page_title = 'Edit Product';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:900px;margin:0 auto;">
  <h5>
    <i class="bi bi-pencil-square"></i> Edit Product —
    <strong><?= htmlspecialchars($p['name']) ?></strong>
    <code><?= htmlspecialchars($p['sku']) ?></code>
  </h5>

  <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
    <i class="bi bi-info-circle"></i>
    On hand: <strong><?= (int)$stock['units'] ?></strong> units across <strong><?= (int)$stock['bins'] ?></strong> bin(s).
    Stock is derived from inventory — managed via receive / pick / transfer / adjustment, not edited here.
  </div>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Product Name *</label>
        <input type="text" name="name" required maxlength="255"
               value="<?= htmlspecialchars($p['name']) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">SKU *</label>
        <input type="text" name="sku" required maxlength="100"
               value="<?= htmlspecialchars($p['sku']) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Barcode</label>
        <input type="text" name="barcode" maxlength="100"
               value="<?= htmlspecialchars($p['barcode'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Category</label>
        <select name="category_id"
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— None —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ((int)$p['category_id'] === (int)$c['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Supplier</label>
        <select name="supplier_id"
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— None —</option>
          <?php foreach ($suppliers as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ((int)$p['supplier_id'] === (int)$s['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Unit Price (€)</label>
        <input type="number" step="0.01" min="0" name="unit_price"
               value="<?= htmlspecialchars((string)($p['unit_price'] ?? '')) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Weight (kg)</label>
        <input type="number" step="0.01" min="0" name="weight"
               value="<?= htmlspecialchars((string)($p['weight'] ?? '')) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Dimensions</label>
        <input type="text" name="dimensions" maxlength="100"
               value="<?= htmlspecialchars($p['dimensions'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Reorder Point</label>
        <input type="number" min="0" name="reorder_point"
               value="<?= htmlspecialchars((string)($p['reorder_point'] ?? '10')) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Status</label>
        <select name="status"
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <?php foreach (['active','pending','discontinued'] as $st): ?>
            <option value="<?= $st ?>" <?= ($p['status'] === $st) ? 'selected' : '' ?>>
              <?= ucfirst($st) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Description</label>
        <textarea name="description" rows="3"
                  style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="products.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Update Product
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
