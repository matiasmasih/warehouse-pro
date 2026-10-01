<?php
// put_away.php — Move stock from receiving dock to storage bin
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';
require_once __DIR__ . '/ref.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('transfers');

$uid = (int)$_SESSION['user_id'];

$formMsg = $_SESSION['pa_msg'] ?? '';
unset($_SESSION['pa_msg']);
$formErr = '';

// ============================================================
// ACTION: put away (move from receiving bin to a storage bin)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_putaway'])) {
    $invId   = (int)($_POST['inv_id'] ?? 0);
    $toLocId = (int)($_POST['to_location_id'] ?? 0);
    $qty     = (int)($_POST['qty'] ?? 0);

    if ($invId <= 0 || $toLocId <= 0 || $qty <= 0) {
        $formErr = 'Choose source line, destination bin, and quantity.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the source inventory row
            $st = $pdo->prepare(
                "SELECT i.id, i.product_id, i.location_id, i.quantity,
                        l.type AS loc_type, l.warehouse_id
                 FROM inventory i
                 JOIN locations l ON l.id = i.location_id
                 WHERE i.id = ? FOR UPDATE"
            );
            $st->execute([$invId]);
            $src = $st->fetch();
            if (!$src) throw new RuntimeException('Source inventory row not found.');

            if ($src['loc_type'] !== 'receiving') {
                throw new RuntimeException("Source bin is '{$src['loc_type']}', not a receiving dock.");
            }
            if ((int)$src['quantity'] < $qty) {
                throw new RuntimeException("Only {$src['quantity']} units at the dock.");
            }

            // Verify destination bin is in the same warehouse and is storage
            $st = $pdo->prepare(
                "SELECT id, type, warehouse_id FROM locations WHERE id = ? LIMIT 1"
            );
            $st->execute([$toLocId]);
            $dst = $st->fetch();
            if (!$dst) throw new RuntimeException('Destination bin not found.');
            if ((int)$dst['warehouse_id'] !== (int)$src['warehouse_id']) {
                throw new RuntimeException('Destination must be in the same warehouse.');
            }
            if ($dst['type'] !== 'storage') {
                throw new RuntimeException('Destination must be a storage bin.');
            }

            // Deduct from source
            $pdo->prepare("UPDATE inventory SET quantity = quantity - ?, last_movement_at = NOW() WHERE id = ?")
                ->execute([$qty, $invId]);

            // Add to destination (upsert)
            $st = $pdo->prepare("SELECT id FROM inventory WHERE product_id = ? AND location_id = ? LIMIT 1");
            $st->execute([(int)$src['product_id'], $toLocId]);
            $dstInv = $st->fetch();
            if ($dstInv) {
                $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, last_movement_at = NOW() WHERE id = ?")
                    ->execute([$qty, $dstInv['id']]);
            } else {
                $pdo->prepare("INSERT INTO inventory (product_id, location_id, quantity, last_movement_at) VALUES (?, ?, ?, NOW())")
                    ->execute([(int)$src['product_id'], $toLocId, $qty]);
            }

            // Movement log
            $pdo->prepare(
                "INSERT INTO stock_movements
                    (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                 VALUES (?, NULL, ?, ?, ?, 'transfer', 'Put-away: dock to storage', ?, ?)"
            )->execute([
                (int)$src['product_id'],
                (int)$src['location_id'],
                $toLocId,
                $qty,
                next_ref($pdo, 'PA'),
                $uid,
            ]);

            // Audit
            $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'put_away', ?)")
                ->execute([$uid, "Put away {$qty} units (product #{$src['product_id']}) from dock #{$src['location_id']} to bin #{$toLocId}"]);

            $pdo->commit();
            $_SESSION['pa_msg'] = "Put away {$qty} unit(s) to the storage bin.";
            header('Location: put_away.php'); exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formErr = $e->getMessage();
        }
    }
}

// ============================================================
// DATA: stock currently on receiving docks
// ============================================================
$dockStock = $pdo->query(
    "SELECT i.id AS inv_id, i.quantity, i.location_id,
            p.id AS product_id, p.name AS product_name, p.sku,
            l.code AS bin_code, l.name AS bin_name, l.warehouse_id,
            w.name AS warehouse_name, w.city
     FROM inventory i
     JOIN products  p ON p.id = i.product_id
     JOIN locations l ON l.id = i.location_id
     JOIN warehouses w ON w.id = l.warehouse_id
     WHERE l.type = 'receiving' AND i.quantity > 0
     ORDER BY w.name, l.code, p.name"
)->fetchAll();

// Storage bins per warehouse (for the destination dropdown)
$storageBins = [];
foreach ($dockStock as $row) {
    $whId = (int)$row['warehouse_id'];
    if (!isset($storageBins[$whId])) {
        $st = $pdo->prepare(
            "SELECT id, code, name FROM locations
             WHERE warehouse_id = ? AND type = 'storage'
             ORDER BY code"
        );
        $st->execute([$whId]);
        $storageBins[$whId] = $st->fetchAll();
    }
}

// ============================================================
// DATA: recent put-away movements
// ============================================================
$recent = $pdo->query(
    "SELECT m.id, m.timestamp, m.quantity, m.reference,
            p.name AS product_name, p.sku,
            fl.code AS from_bin, tl.code AS to_bin,
            w.name AS warehouse_name,
            u.username
     FROM stock_movements m
     LEFT JOIN products p ON p.id = m.product_id
     LEFT JOIN locations fl ON fl.id = m.from_location
     LEFT JOIN locations tl ON tl.id = m.to_location
     LEFT JOIN warehouses w ON w.id = COALESCE(fl.warehouse_id, tl.warehouse_id)
     LEFT JOIN users u ON u.id = m.user_id
     WHERE m.reference LIKE 'PA-%'
     ORDER BY m.id DESC LIMIT 20"
)->fetchAll();

$page_title = 'Put-Away';
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
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-box-seam"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($dockStock) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Dock lines</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-stack"></i></div>
      <div>
        <?php $dockUnits = 0; foreach ($dockStock as $d) $dockUnits += (int)$d['quantity']; ?>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($dockUnits, 0, ',', ' ') ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Units on docks</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-clock-history"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($recent) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Recent put-aways</div>
      </div>
    </div>
  </div>
</div>

<!-- Dock stock -->
<div class="table-card" style="margin-bottom:20px;">
  <h5><i class="bi bi-box-seam"></i> Stock on Receiving Docks (<?= count($dockStock) ?>)</h5>

  <?php if (!$dockStock): ?>
    <p style="color:var(--text-muted);">Nothing on the receiving docks. Everything has been put away. 👍</p>
  <?php else: ?>
    <?php foreach ($dockStock as $row): ?>
      <form method="post" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:flex-end;padding:12px 0;border-bottom:1px solid var(--border);">
        <input type="hidden" name="do_putaway" value="1">
        <input type="hidden" name="inv_id" value="<?= (int)$row['inv_id'] ?>">
        <input type="hidden" name="to_location_id" id="toLoc<?= (int)$row['inv_id'] ?>" value="">

        <div>
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Product</div>
          <div style="font-weight:600;"><?= htmlspecialchars($row['product_name']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted);"><code><?= htmlspecialchars($row['sku']) ?></code></div>
        </div>

        <div>
          <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">From dock</div>
          <div><code><?= htmlspecialchars($row['bin_code']) ?></code> · <?= htmlspecialchars($row['warehouse_name']) ?></div>
          <div style="font-size:.75rem;color:var(--text-muted);">Qty: <strong><?= (int)$row['quantity'] ?></strong></div>
        </div>

        <div>
          <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:4px;">To storage bin</label>
          <select name="to_location_select" required onchange="document.getElementById('toLoc<?= (int)$row['inv_id'] ?>').value=this.value"
                  style="width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);">
            <option value="">— choose bin —</option>
            <?php foreach (($storageBins[(int)$row['warehouse_id']] ?? []) as $b): ?>
              <option value="<?= (int)$b['id'] ?>">[<?= htmlspecialchars($b['code']) ?>] <?= htmlspecialchars($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:flex;gap:8px;align-items:flex-end;">
          <div>
            <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:4px;">Qty</label>
            <input type="number" name="qty" min="1" max="<?= (int)$row['quantity'] ?>" value="<?= (int)$row['quantity'] ?>"
                   style="width:90px;padding:8px 10px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);">
          </div>
          <button type="submit" class="btn-add" style="padding:8px 14px;font-size:.85rem;">
            <i class="bi bi-box-arrow-in-down"></i> Put away
          </button>
        </div>
      </form>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Recent put-aways -->
<div class="table-card">
  <h5><i class="bi bi-clock-history"></i> Recent Put-Aways (<?= count($recent) ?>)</h5>
  <?php if (!$recent): ?>
    <p style="color:var(--text-muted);">No put-aways yet.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table style="min-width:1000px;white-space:nowrap;">
      <thead>
        <tr><th>When</th><th>Product</th><th>From</th><th>To</th><th>Warehouse</th><th>Qty</th><th>Reference</th><th>User</th></tr>
      </thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['timestamp']) ?></td>
          <td><?= htmlspecialchars($r['product_name'] ?? '—') ?><?= $r['sku'] ? ' · ' . htmlspecialchars($r['sku']) : '' ?></td>
          <td><?= $r['from_bin'] ? '<code>' . htmlspecialchars($r['from_bin']) . '</code>' : '—' ?></td>
          <td><?= $r['to_bin']   ? '<code>' . htmlspecialchars($r['to_bin'])   . '</code>' : '—' ?></td>
          <td><?= htmlspecialchars($r['warehouse_name'] ?? '—') ?></td>
          <td><?= number_format((int)$r['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($r['reference'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
