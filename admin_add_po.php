<?php
// admin_add_po.php — Create a new Purchase Order (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('purchase_orders');

if (my_access('purchase_orders') !== 'full') {
    header('Location: purchase_orders.php?error=not_allowed');
    exit;
}

$error = '';

// POST — create the PO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id   = (int)($_POST['supplier_id'] ?? 0);
    $warehouse_id  = (int)($_POST['warehouse_id'] ?? 0);
    $order_date    = trim($_POST['order_date'] ?? date('Y-m-d'));
    $expected_date = trim($_POST['expected_date'] ?? '');
    $notes         = trim($_POST['notes'] ?? '');

    $product_ids = $_POST['line_product'] ?? [];
    $quantities  = $_POST['line_quantity'] ?? [];
    $prices      = $_POST['line_price'] ?? [];
    $batch_nums  = $_POST['line_batch'] ?? [];
    $expiries    = $_POST['line_expiry'] ?? [];

    $lines = [];

    if ($supplier_id <= 0) {
        $error = 'Please choose a supplier.';
    } elseif ($warehouse_id <= 0) {
        $error = 'Please choose a warehouse.';
    } elseif ($order_date === '') {
        $error = 'Order date is required.';
    } else {
        for ($i = 0; $i < count($product_ids); $i++) {
            $pid  = (int)($product_ids[$i] ?? 0);
            $qty  = (int)($quantities[$i] ?? 0);
            $price = (float)($prices[$i] ?? 0);
            $bn   = trim($batch_nums[$i] ?? '');
            $exp  = trim($expiries[$i] ?? '');

            if ($pid <= 0 && $qty === 0) continue;
            if ($pid <= 0 || $qty <= 0) {
                $error = 'Each line must have a product and a quantity greater than zero.';
                break;
            }
            if ($price < 0) {
                $error = 'Unit price cannot be negative.';
                break;
            }
            if ($exp !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $exp)) {
                $error = 'Invalid expiry date on one of the lines.';
                break;
            }

            $lines[] = [
                'product_id'   => $pid,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'batch_number' => $bn !== '' ? $bn : null,
                'expiry_date'  => $exp !== '' ? $exp : null,
            ];
        }

        if (!$error && empty($lines)) {
            $error = 'Add at least one line item.';
        }

        if (!$error) {
            $chk = $pdo->prepare("SELECT id FROM suppliers WHERE id = ? AND status = 'active'");
            $chk->execute([$supplier_id]);
            if (!$chk->fetchColumn()) { $error = 'Selected supplier does not exist or is inactive.'; }
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

                $total = 0.0;
                foreach ($lines as $l) { $total += $l['quantity'] * $l['unit_price']; }

                $stmt = $pdo->prepare(
                    "INSERT INTO purchase_orders
                        (supplier_id, warehouse_id, order_date, expected_date, status, total_amount, notes, created_by)
                     VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)"
                );
                $stmt->execute([
                    $supplier_id,
                    $warehouse_id,
                    $order_date,
                    $expected_date ?: null,
                    $total,
                    $notes ?: null,
                    $_SESSION['user_id'],
                ]);
                $po_id = (int)$pdo->lastInsertId();

                $lineStmt = $pdo->prepare(
                    "INSERT INTO purchase_order_items
                        (purchase_order_id, product_id, batch_number, expiry_date, quantity, unit_price)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                foreach ($lines as $l) {
                    $lineStmt->execute([
                        $po_id,
                        $l['product_id'],
                        $l['batch_number'],
                        $l['expiry_date'],
                        $l['quantity'],
                        $l['unit_price'],
                    ]);
                }

                $log = $pdo->prepare(
                    "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
                );
                $log->execute([
                    $_SESSION['user_id'],
                    'po_created',
                    "Created PO-{$po_id} for supplier #{$supplier_id}, warehouse #{$warehouse_id}, " .
                        count($lines) . " line(s), total " . number_format($total, 2, ',', ' ') . " €"
                ]);

                $pdo->commit();
                header('Location: purchase_orders.php?saved=success');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$suppliers  = $pdo->query("SELECT id, name FROM suppliers WHERE status = 'active' ORDER BY name")->fetchAll();
$warehouses = $pdo->query("SELECT id, name, city FROM warehouses WHERE status = 'active' ORDER BY name")->fetchAll();
$products   = $pdo->query("SELECT id, sku, name, unit_price FROM products WHERE status != 'discontinued' ORDER BY name")->fetchAll();

$page_title = 'Create Purchase Order';
include 'warehouse_header.php';
?>

<?php if ($error): ?>
  <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
    <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<form method="POST" autocomplete="off" id="poForm">

  <!-- ============ CARD 1: PO DETAILS ============ -->
  <div class="table-card" style="max-width:1100px;margin:0 auto 18px;">
    <h5><i class="bi bi-file-earmark-text"></i> Purchase Order Details</h5>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Supplier *</label>
        <select name="supplier_id" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— Select supplier —</option>
          <?php foreach ($suppliers as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ((int)($_POST['supplier_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
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
        <label style="display:block;font-weight:600;margin-bottom:6px;">Order Date *</label>
        <input type="date" name="order_date" required
               value="<?= htmlspecialchars($_POST['order_date'] ?? date('Y-m-d')) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Expected Date</label>
        <input type="date" name="expected_date"
               value="<?= htmlspecialchars($_POST['expected_date'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Notes</label>
        <input type="text" name="notes" maxlength="500"
               value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>"
               placeholder="Optional notes for this PO"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>
    </div>
  </div>

  <!-- ============ CARD 2: LINE ITEMS ============ -->
  <div class="table-card" style="max-width:1100px;margin:0 auto;">
    <h5><i class="bi bi-list-ul"></i> Line Items</h5>

    <div style="overflow-x:auto;border:1px solid var(--border);border-radius:10px;">
      <table id="lineTable" style="width:100%;border-collapse:collapse;min-width:900px;">
        <thead>
          <tr style="background:var(--card-bg);color:var(--text-muted);border-bottom:2px solid var(--border);">
            <th style="padding:10px;text-align:left;font-size:.75rem;text-transform:uppercase;">Product *</th>
            <th style="padding:10px;text-align:left;font-size:.75rem;text-transform:uppercase;">Batch #</th>
            <th style="padding:10px;text-align:left;font-size:.75rem;text-transform:uppercase;">Expiry</th>
            <th style="padding:10px;text-align:right;font-size:.75rem;text-transform:uppercase;">Qty *</th>
            <th style="padding:10px;text-align:right;font-size:.75rem;text-transform:uppercase;">Unit €</th>
            <th style="padding:10px;text-align:right;font-size:.75rem;text-transform:uppercase;">Subtotal</th>
            <th style="padding:10px;width:40px;"></th>
          </tr>
        </thead>
        <tbody id="lineBody">
          <?php
          $postedLines = [];
          if ($_SERVER['REQUEST_METHOD'] === 'POST') {
              $lp = $_POST['line_product']  ?? [];
              $lq = $_POST['line_quantity'] ?? [];
              $lu = $_POST['line_price']    ?? [];
              $lb = $_POST['line_batch']    ?? [];
              $le = $_POST['line_expiry']   ?? [];
              for ($i = 0; $i < count($lp); $i++) {
                  $postedLines[] = [
                      'product'  => $lp[$i] ?? '',
                      'quantity' => $lq[$i] ?? '',
                      'price'    => $lu[$i] ?? '',
                      'batch'    => $lb[$i] ?? '',
                      'expiry'   => $le[$i] ?? '',
                  ];
              }
          }
          if (empty($postedLines)) {
              $postedLines = [['product'=>'','quantity'=>'','price'=>'','batch'=>'','expiry'=>'']];
          }
          foreach ($postedLines as $pl): ?>
          <tr class="line-row" style="border-bottom:1px solid var(--border);">
            <td style="padding:8px;">
              <select name="line_product[]" class="prod-select" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;">
                <option value="">— Select product —</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= (int)$p['id'] ?>" data-price="<?= htmlspecialchars((string)($p['unit_price'] ?? '')) ?>" <?= ((int)$pl['product'] === (int)$p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['sku']) ?> — <?= htmlspecialchars($p['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td style="padding:8px;"><input type="text" name="line_batch[]" maxlength="100" value="<?= htmlspecialchars($pl['batch']) ?>" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;"></td>
            <td style="padding:8px;"><input type="date" name="line_expiry[]" value="<?= htmlspecialchars($pl['expiry']) ?>" style="width:100%;padding:8px;border:1px solid #cbd5e1;border-radius:8px;"></td>
            <td style="padding:8px;"><input type="number" name="line_quantity[]" min="1" class="qty-input" value="<?= htmlspecialchars((string)$pl['quantity']) ?>" style="width:90px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;text-align:right;"></td>
            <td style="padding:8px;"><input type="number" name="line_price[]" min="0" step="0.01" class="price-input" value="<?= htmlspecialchars((string)$pl['price']) ?>" style="width:100px;padding:8px;border:1px solid #cbd5e1;border-radius:8px;text-align:right;"></td>
            <td style="padding:8px;text-align:right;font-weight:600;" class="line-subtotal">0.00 €</td>
            <td style="padding:8px;text-align:center;">
              <button type="button" class="btn-del-row" style="background:#ef4444;color:#fff;border:0;padding:6px 10px;border-radius:6px;cursor:pointer;">
                <i class="bi bi-x-lg"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="7" style="padding:12px;text-align:right;font-weight:700;font-size:1rem;white-space:nowrap;">
              Total: <span style="font-weight:800;font-size:1.15rem;color:var(--primary);margin-left:8px;" id="totalCell">0.00 €</span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <button type="button" id="addRow"
            style="margin-top:10px;background:var(--primary);color:#fff;border:0;padding:8px 14px;border-radius:8px;cursor:pointer;font-weight:600;">
      <i class="bi bi-plus-lg"></i> Add Line
    </button>
  </div>

  <!-- ============ ACTIONS ============ -->
  <div style="max-width:1100px;margin:22px auto 0;display:flex;justify-content:space-between;align-items:center;">
    <a href="purchase_orders.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
      <i class="bi bi-arrow-left"></i> Cancel
    </a>
    <button type="submit" class="btn-add">
      <i class="bi bi-save"></i> Create Purchase Order
    </button>
  </div>
</form>

<script>
(function() {
  const lineBody = document.getElementById('lineBody');

  function recalc() {
    let total = 0;
    document.querySelectorAll('#lineBody tr').forEach(tr => {
      const q = parseFloat(tr.querySelector('.qty-input').value) || 0;
      const p = parseFloat(tr.querySelector('.price-input').value) || 0;
      const sub = q * p;
      tr.querySelector('.line-subtotal').textContent = sub.toFixed(2) + ' €';
      total += sub;
    });
    document.getElementById('totalCell').textContent = total.toFixed(2) + ' €';
  }

  function bindRow(tr) {
    tr.querySelector('.qty-input').addEventListener('input', recalc);
    tr.querySelector('.price-input').addEventListener('input', recalc);
    tr.querySelector('.prod-select').addEventListener('change', function() {
      const opt = this.selectedOptions[0];
      const priceInput = tr.querySelector('.price-input');
      if (opt && opt.dataset.price && priceInput.value === '') {
        priceInput.value = opt.dataset.price;
      }
      recalc();
    });
    tr.querySelector('.btn-del-row').addEventListener('click', function() {
      if (document.querySelectorAll('#lineBody tr').length > 1) {
        tr.remove();
        recalc();
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

  recalc();
})();
</script>

<?php include 'warehouse_footer.php'; ?>
