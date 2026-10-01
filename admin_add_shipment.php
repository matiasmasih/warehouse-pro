<?php
// admin_add_shipment.php — Create a new Shipment (admin, manager, or staff)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('shipments');

if (my_access('shipments') !== 'full') {
    header('Location: shipments.php?error=not_allowed');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $address       = trim($_POST['address'] ?? '');
    $warehouse_id  = (int)($_POST['warehouse_id'] ?? 0);
    $shipment_date = trim($_POST['shipment_date'] ?? date('Y-m-d'));
    $notes         = trim($_POST['notes'] ?? '');

    $product_ids = $_POST['line_product'] ?? [];
    $quantities  = $_POST['line_quantity'] ?? [];

    $lines = [];

    if ($customer_name === '') {
        $error = 'Customer name is required.';
    } elseif ($warehouse_id <= 0) {
        $error = 'Please choose a warehouse.';
    } elseif ($shipment_date === '') {
        $error = 'Ship date is required.';
    } else {
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid = (int)($product_ids[$i] ?? 0);
            $qty = (int)($quantities[$i] ?? 0);

            if ($pid <= 0 && $qty === 0) continue;
            if ($pid <= 0 || $qty <= 0) {
                $error = 'Each line must have a product and a quantity greater than zero.';
                break;
            }

            $lines[] = ['product_id' => $pid, 'quantity' => $qty];
        }

        if (!$error && empty($lines)) {
            $error = 'Add at least one line item.';
        }

        if (!$error) {
            $chk = $pdo->prepare("SELECT id FROM warehouses WHERE id = ?");
            $chk->execute([$warehouse_id]);
            if (!$chk->fetchColumn()) { $error = 'Selected warehouse does not exist.'; }
        }
        if (!$error) {
            $in = implode(',', array_fill(0, count($lines), '?'));
            $chk = $pdo->prepare("SELECT COUNT(*) FROM products WHERE id IN ($in)");
            $chk->execute(array_column($lines, 'product_id'));
            if ((int)$chk->fetchColumn() !== count($lines)) {
                $error = 'One or more selected products do not exist.';
            }
        }

        if (!$error) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO shipments
                        (customer_name, address, shipped_by, warehouse_id, shipment_date, status, notes)
                     VALUES (?, ?, ?, ?, ?, 'pending', ?)"
                );
                $stmt->execute([
                    $customer_name,
                    $address ?: null,
                    $_SESSION['user_id'],
                    $warehouse_id,
                    $shipment_date,
                    $notes ?: null,
                ]);
                $shipment_id = (int)$pdo->lastInsertId();

                $lineStmt = $pdo->prepare(
                    "INSERT INTO shipment_items (shipment_id, product_id, quantity)
                     VALUES (?, ?, ?)"
                );
                foreach ($lines as $l) {
                    $lineStmt->execute([$shipment_id, $l['product_id'], $l['quantity']]);
                }

                $log = $pdo->prepare(
                    "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
                );
                $log->execute([
                    $_SESSION['user_id'],
                    'shipment_created',
                    "Created SH-{$shipment_id} for customer '{$customer_name}', warehouse #{$warehouse_id}, " .
                        count($lines) . " line(s)"
                ]);

                $pdo->commit();
                header('Location: shipments.php?saved=success');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$warehouses = $pdo->query("SELECT id, name, city FROM warehouses WHERE status = 'active' ORDER BY name")->fetchAll();
$products   = $pdo->query("SELECT id, sku, name FROM products WHERE status != 'discontinued' ORDER BY name")->fetchAll();

$page_title = 'Create Shipment';
include 'warehouse_header.php';
?>

<?php if ($error): ?>
  <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form method="POST" autocomplete="off" id="shipForm">

  <!-- ============ CARD 1: SHIPMENT DETAILS ============ -->
  <div class="table-card" style="max-width:1100px;margin:0 auto 18px;">
    <h5><i class="bi bi-file-earmark-text"></i> Shipment Details</h5>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Customer Name *</label>
        <input type="text" name="customer_name" required maxlength="255"
               value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>"
               placeholder="e.g. Acme Retail Oy"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Delivery Address</label>
        <textarea name="address" rows="2" maxlength="500"
                  placeholder="Street, city, postal code, country"
                  style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Warehouse *</label>
        <select name="warehouse_id" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— Select warehouse —</option>
          <?php foreach ($warehouses as $w): ?>
            <option value="<?= (int)$w['id'] ?>" <?= ((int)($_POST['warehouse_id'] ?? 0) === (int)$w['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($w['name']) ?><?= $w['city'] ? ' (' . htmlspecialchars($w['city']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Ship Date *</label>
        <input type="date" name="shipment_date" required
               value="<?= htmlspecialchars($_POST['shipment_date'] ?? date('Y-m-d')) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Notes</label>
        <input type="text" name="notes" maxlength="500"
               value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>"
               placeholder="Optional notes for this shipment"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>
    </div>
  </div>

  <!-- ============ CARD 2: LINE ITEMS ============ -->
  <div class="table-card" style="max-width:1100px;margin:0 auto;">
    <h5><i class="bi bi-list-ul"></i> Line Items</h5>

    <div style="overflow-x:auto;border:1px solid var(--border);border-radius:10px;">
      <table id="lineTable" style="width:100%;border-collapse:collapse;min-width:700px;">
        <thead>
          <tr style="background:var(--card-bg);color:var(--text-muted);border-bottom:2px solid var(--border);">
            <th style="padding:10px;text-align:left;font-size:.75rem;text-transform:uppercase;">Product *</th>
            <th style="padding:10px;text-align:right;font-size:.75rem;text-transform:uppercase;width:140px;">Quantity *</th>
            <th style="padding:10px;width:40px;"></th>
          </tr>
        </thead>
        <tbody id="lineBody">
          <?php
          $postedLines = [];
          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              $lp = $_POST['line_product']  ?? [];
              $lq = $_POST['line_quantity'] ?? [];
              for ($i = 0; $i < count($lp); $i++) {
                  $postedLines[] = ['product' => $lp[$i] ?? '', 'quantity' => $lq[$i] ?? ''];
              }
          }
          if (empty($postedLines)) {
              $postedLines = [['product'=>'','quantity'=>'']];
          }
          foreach ($postedLines as $pl): ?>
          <tr class="line-row" style="border-bottom:1px solid var(--border);">
            <td style="padding:8px;">
              <select name="line_product[]" class="prod-select" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;">
                <option value="">— Select product —</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int)$p['id'] ?>" <?= ((int)$pl['product'] === (int)$p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['sku']) ?> — <?= htmlspecialchars($p['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td style="padding:8px;">
              <input type="number" name="line_quantity[]" min="1" class="qty-input"
                     value="<?= htmlspecialchars((string)$pl['quantity']) ?>"
                     style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;text-align:right;">
            </td>
            <td style="padding:8px;text-align:center;">
              <button type="button" class="btn-del-row" style="background:#ef4444;color:#fff;border:0;padding:6px 10px;border-radius:6px;cursor:pointer;">
                <i class="bi bi-x-lg"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <button type="button" id="addRow"
            style="margin-top:10px;background:var(--primary);color:#fff;border:0;padding:8px 14px;border-radius:8px;cursor:pointer;font-weight:600;">
      <i class="bi bi-plus-lg"></i> Add Line
    </button>

    <div style="margin-top:10px;font-size:.85rem;color:var(--text-muted);">
      <i class="bi bi-info-circle"></i>
      Tracking number is generated automatically when the shipment is picked.
    </div>
  </div>

  <!-- ============ ACTIONS ============ -->
  <div style="max-width:1100px;margin:22px auto 0;display:flex;justify-content:space-between;align-items:center;">
    <a href="shipments.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
      <i class="bi bi-arrow-left"></i> Cancel
    </a>
    <button type="submit" class="btn-add">
      <i class="bi bi-save"></i> Create Shipment
    </button>
  </div>
</form>

<script>
(function() {
  const lineBody = document.getElementById('lineBody');

  function bindRow(tr) {
    tr.querySelector('.btn-del-row').addEventListener('click', function() {
      if (document.querySelectorAll('#lineBody tr').length > 1) {
        tr.remove();
      }
    });
  }

  document.querySelectorAll('#lineBody tr').forEach(bindRow);

  document.getElementById('addRow').addEventListener('click', function() {
    const first = document.querySelector('#lineBody tr');
    const clone = first.cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelectorAll('select').forEach(s => s.value = '');
    lineBody.appendChild(clone);
    bindRow(clone);
  });
})();
</script>

<?php include 'warehouse_footer.php'; ?>
