<?php
// quarantine.php — Quarantine management (flag / release / dispose)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

require_once __DIR__ . '/ref.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('adjustments');

$uid = (int)$_SESSION['user_id'];

$formMsg = $_SESSION['q_msg'] ?? '';
unset($_SESSION['q_msg']);
$formErr = '';

$validReasons = ['damage','expiry','quality','customer_return','recall','investigation'];

// ============================================================
// ACTION: flag a batch into quarantine
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_flag'])) {
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $qty     = (int)($_POST['qty'] ?? 0);
    $reason  = in_array($_POST['reason'] ?? '', $validReasons, true) ? $_POST['reason'] : 'investigation';

    if ($batchId <= 0 || $qty <= 0) {
        $formErr = 'Choose a batch and enter quantity.';
    } else {
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare(
                "SELECT b.id, b.product_id, b.batch_number, b.quantity, b.location_id, b.status
                 FROM product_batches b WHERE b.id = ? FOR UPDATE"
            );
            $st->execute([$batchId]);
            $b = $st->fetch();
            if (!$b) throw new RuntimeException('Batch not found.');
            if ($b['status'] !== 'available') {
                throw new RuntimeException("Batch is '{$b['status']}', cannot quarantine.");
            }
            if ((int)$b['quantity'] < $qty) {
                throw new RuntimeException("Batch has {$b['quantity']} units, cannot quarantine {$qty}.");
            }

            // Find the quarantine bin for this batch's warehouse
            $qSt = $pdo->prepare(
                "SELECT l.id FROM locations l
                 JOIN locations orig ON orig.warehouse_id = l.warehouse_id
                 WHERE orig.id = ? AND l.type = 'quarantine'
                 ORDER BY l.code LIMIT 1"
            );
            $qSt->execute([(int)$b['location_id']]);
            $qBin = $qSt->fetchColumn();
            if (!$qBin) throw new RuntimeException('No quarantine bin in that warehouse.');

            $pdo->prepare("UPDATE product_batches SET status = 'quarantine' WHERE id = ?")->execute([$batchId]);

            $pdo->prepare(
                "INSERT INTO stock_movements
                    (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                 VALUES (?, ?, ?, ?, ?, 'transfer', ?, ?, ?)"
            )->execute([
                (int)$b['product_id'], $batchId,
                (int)$b['location_id'], (int)$qBin, $qty,
                'Quarantine: ' . $reason,
                next_ref($pdo, 'QT'), $uid,
            ]);

            $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'quarantine_flag', ?)")
                ->execute([$uid, "Flagged batch {$b['batch_number']} ({$qty} units) — reason: {$reason}"]);

            $pdo->commit();
            $_SESSION['q_msg'] = "Batch {$b['batch_number']} flagged to quarantine ({$qty} units, reason: {$reason}).";
            header('Location: quarantine.php'); exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formErr = $e->getMessage();
        }
    }
}

// ============================================================
// ACTION: release or dispose a quarantined batch
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_release'])) {
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    if (!in_array($action, ['release','dispose'], true)) {
        $formErr = 'Invalid action.';
    } else {
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare(
                "SELECT b.id, b.product_id, b.batch_number, b.quantity, b.location_id, b.status
                 FROM product_batches b WHERE b.id = ? FOR UPDATE"
            );
            $st->execute([$batchId]);
            $b = $st->fetch();
            if (!$b) throw new RuntimeException('Batch not found.');
            if ($b['status'] !== 'quarantine') throw new RuntimeException('Batch is not in quarantine.');

            if ($action === 'release') {
                $pdo->prepare("UPDATE product_batches SET status='available' WHERE id = ?")->execute([$batchId]);

                $pdo->prepare(
                    "INSERT INTO stock_movements
                        (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                     VALUES (?, ?, ?, NULL, ?, 'adjustment', 'quarantine_release', ?, ?)"
                )->execute([
                    (int)$b['product_id'], $batchId, (int)$b['location_id'], (int)$b['quantity'],
                    next_ref($pdo, 'QR'), $uid,
                ]);

                $msg = "Batch {$b['batch_number']} released back to available stock.";
                $actionName = 'quarantine_release';
            } else {
                // dispose: mark depleted + zero batch qty + zero inventory
                $pdo->prepare("UPDATE product_batches SET quantity = 0, status = 'depleted' WHERE id = ?")->execute([$batchId]);

                $pdo->prepare("UPDATE inventory SET quantity = GREATEST(0, quantity - ?), last_movement_at = NOW() WHERE product_id = ? AND location_id = ?")
                    ->execute([(int)$b['quantity'], (int)$b['product_id'], (int)$b['location_id']]);

                $pdo->prepare(
                    "INSERT INTO stock_movements
                        (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                     VALUES (?, ?, ?, NULL, ?, 'adjustment', 'quarantine_dispose', ?, ?)"
                )->execute([
                    (int)$b['product_id'], $batchId, (int)$b['location_id'], (int)$b['quantity'],
                    next_ref($pdo, 'QD'), $uid,
                ]);

                $msg = "Batch {$b['batch_number']} disposed ({$b['quantity']} units written off).";
                $actionName = 'quarantine_dispose';
            }

            $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)")
                ->execute([$uid, $actionName, $msg]);

            $pdo->commit();
            $_SESSION['q_msg'] = $msg;
            header('Location: quarantine.php'); exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formErr = $e->getMessage();
        }
    }
}

// ============================================================
// DATA: available batches for the flag form
// ============================================================
$products = $pdo->query("SELECT id, sku, name FROM products WHERE status='active' ORDER BY name")->fetchAll();

// ============================================================
// DATA: quarantine queue
// ============================================================
$quar = $pdo->query(
    "SELECT b.id, b.batch_number, b.expiry_date, b.quantity, b.status, b.location_id,
            p.name AS product_name, p.sku,
            l.code AS bin_code, w.name AS warehouse_name
     FROM product_batches b
     LEFT JOIN products  p ON p.id = b.product_id
     LEFT JOIN locations l ON l.id = b.location_id
     LEFT JOIN warehouses w ON w.id = l.warehouse_id
     WHERE b.status = 'quarantine'
     ORDER BY b.expiry_date IS NULL, b.expiry_date ASC, b.id DESC"
)->fetchAll();

// ============================================================
// DATA: recent quarantine movements
// ============================================================
$recent = $pdo->query(
    "SELECT m.id, m.timestamp, m.quantity, m.reason, m.reference,
            p.name AS product_name, p.sku,
            b.batch_number, u.username
     FROM stock_movements m
     LEFT JOIN products p ON p.id = m.product_id
     LEFT JOIN product_batches b ON b.id = m.batch_id
     LEFT JOIN users u ON u.id = m.user_id
     WHERE m.reason LIKE 'quarantine%' OR m.reason LIKE 'Quarantine%'
     ORDER BY m.id DESC LIMIT 15"
)->fetchAll();

$page_title = 'Quarantine';
include 'warehouse_header.php';
?>

<?php if ($formMsg): ?>
  <div style="padding:12px 16px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($formMsg) ?>
  </div>
<?php endif; ?>
<?php if ($formErr): ?>
  <div style="padding:12px 16px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($formErr) ?>
  </div>
<?php endif; ?>

<!-- KPI strip -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-shield-exclamation"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($quar) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">In quarantine</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-box"></i></div>
      <div>
        <?php $totalQ = 0; foreach ($quar as $q) $totalQ += (int)$q['quantity']; ?>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($totalQ, 0, ',', ' ') ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Units held</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-clock-history"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($recent) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Recent actions</div>
      </div>
    </div>
  </div>
</div>

<!-- Flag batch form -->
<div class="table-card" style="margin-bottom:20px;">
  <h5><i class="bi bi-shield-exclamation"></i> Flag a Batch to Quarantine</h5>
  <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:14px;">
    Move a batch from available stock into the quarantine bay for inspection, damage, or recall.
  </p>

  <form method="post" style="display:grid;gap:14px;max-width:820px;">
    <input type="hidden" name="do_flag" value="1">

    <div>
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Product</label>
      <select name="product_id" id="productSel" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
        <option value="">— choose product —</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['sku']) ?> · <?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Batch</label>
      <select name="batch_id" id="batchSel" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
        <option value="">— choose product first —</option>
      </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Reason</label>
        <select name="reason" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
          <option value="damage">Damage — broken/crushed</option>
          <option value="expiry">Expiry — past or near expiry</option>
          <option value="quality">Quality — QC failed</option>
          <option value="customer_return">Customer return</option>
          <option value="recall">Recall — supplier recall</option>
          <option value="investigation">Investigation — under review</option>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Quantity</label>
        <input type="number" min="1" name="qty" required
               style="width:140px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      </div>
    </div>

    <div>
      <button type="submit" class="btn-add" style="padding:10px 18px;font-size:.95rem;">
        <i class="bi bi-shield-exclamation"></i> Flag to Quarantine
      </button>
    </div>
  </form>
</div>

<!-- Quarantine queue -->
<div class="table-card" style="margin-bottom:20px;">
  <h5><i class="bi bi-list-ul"></i> Quarantine Queue (<?= count($quar) ?>)</h5>
  <?php if (!$quar): ?>
    <p style="color:var(--text-muted);">Nothing in quarantine.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table style="min-width:1200px;white-space:nowrap;">
      <thead>
        <tr>
          <th>Batch</th>
          <th>Product</th>
          <th>Bin</th>
          <th>Warehouse</th>
          <th>Qty</th>
          <th>Expiry</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($quar as $q): ?>
        <tr>
          <td><code><?= htmlspecialchars($q['batch_number']) ?></code></td>
          <td><?= htmlspecialchars($q['product_name'] ?? '—') ?><?= $q['sku'] ? ' · ' . htmlspecialchars($q['sku']) : '' ?></td>
          <td><?= $q['bin_code'] ? '<code>' . htmlspecialchars($q['bin_code']) . '</code>' : '—' ?></td>
          <td><?= htmlspecialchars($q['warehouse_name'] ?? '—') ?></td>
          <td><?= number_format((int)$q['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($q['expiry_date'] ?? '—') ?></td>
          <td style="white-space:nowrap;">
            <form method="post" style="display:inline;">
              <input type="hidden" name="batch_id" value="<?= (int)$q['id'] ?>">
              <input type="hidden" name="action" value="release">
              <button type="submit" name="do_release" value="1" class="btn-add" style="padding:4px 10px;font-size:.78rem;" onclick="return confirm('Release this batch back to available stock?');">
                <i class="bi bi-check2"></i> Release
              </button>
            </form>
            <form method="post" style="display:inline;margin-left:4px;">
              <input type="hidden" name="batch_id" value="<?= (int)$q['id'] ?>">
              <input type="hidden" name="action" value="dispose">
              <button type="submit" name="do_release" value="1" class="btn-delete" style="padding:4px 10px;font-size:.78rem;" onclick="return confirm('DISPOSE this batch? This writes off all units.');">
                <i class="bi bi-trash"></i> Dispose
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Recent quarantine activity -->
<div class="table-card">
  <h5><i class="bi bi-clock-history"></i> Recent Quarantine Activity</h5>
  <?php if (!$recent): ?>
    <p style="color:var(--text-muted);">No quarantine activity yet.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table style="min-width:1000px;white-space:nowrap;">
      <thead>
        <tr><th>When</th><th>Product</th><th>Batch</th><th>Qty</th><th>Reason</th><th>Reference</th><th>User</th></tr>
      </thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['timestamp']) ?></td>
          <td><?= htmlspecialchars($r['product_name'] ?? '—') ?></td>
          <td><?= $r['batch_number'] ? '<code>' . htmlspecialchars($r['batch_number']) . '</code>' : '—' ?></td>
          <td><?= number_format((int)$r['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($r['reason'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['reference'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
document.getElementById('productSel').addEventListener('change', function() {
  const pid = this.value;
  const sel = document.getElementById('batchSel');
  sel.innerHTML = '<option value="">— loading… —</option>';
  if (!pid) { sel.innerHTML = '<option value="">— choose product first —</option>'; return; }
  fetch('batch_lookup.php?product_id=' + pid)
    .then(r => r.json())
    .then(list => {
      sel.innerHTML = '<option value="">— choose batch —</option>';
      list.forEach(b => {
        const opt = document.createElement('option');
        opt.value = b.id;
        const exp = b.expiry_date ? ' · exp ' + b.expiry_date : '';
        opt.textContent = b.batch_number + exp + ' · qty ' + b.quantity + ' · ' + (b.bin_code || '—');
        sel.appendChild(opt);
      });
    })
    .catch(() => { sel.innerHTML = '<option value="">— load failed —</option>'; });
});
</script>

<?php include 'warehouse_footer.php'; ?>
