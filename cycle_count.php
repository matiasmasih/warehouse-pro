<?php
// cycle_count.php — Cycle count a bin (form + recent counts)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

require_once __DIR__ . '/ref.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('adjustments');

$uid = (int)$_SESSION['user_id'];

$formMsg = $_SESSION['cc_msg'] ?? '';
unset($_SESSION['cc_msg']);
$formErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_count'])) {
    $locId   = (int)($_POST['location_id'] ?? 0);
    $counted = $_POST['counted'] ?? [];

    if ($locId <= 0) {
        $formErr = 'Choose a bin to count.';
    } elseif (!$counted) {
        $formErr = 'No products in that bin to count.';
    } else {
        try {
            $pdo->beginTransaction();
            $adjustments = 0;
            $varianceTotal = 0;

            foreach ($counted as $invId => $cnt) {
                $invId = (int)$invId;
                $cnt   = max(0, (int)$cnt);

                $st = $pdo->prepare(
                    "SELECT i.id, i.product_id, i.location_id, i.quantity
                     FROM inventory i WHERE i.id = ? AND i.location_id = ? FOR UPDATE"
                );
                $st->execute([$invId, $locId]);
                $row = $st->fetch();
                if (!$row) continue;

                $sysQty   = (int)$row['quantity'];
                $variance = $cnt - $sysQty;
                if ($variance === 0) continue;

                $pdo->prepare("UPDATE inventory SET quantity = ?, last_counted_at = NOW(), last_movement_at = NOW() WHERE id = ?")
                    ->execute([$cnt, $invId]);

                $pdo->prepare(
                    "INSERT INTO stock_movements
                        (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                     VALUES (?, NULL, ?, ?, ?, 'adjustment', 'recount', ?, ?)"
                )->execute([
                    (int)$row['product_id'],
                    $variance < 0 ? $locId : null,
                    $variance > 0 ? $locId : null,
                    abs($variance),
                    next_ref($pdo, 'CC'),
                    $uid,
                ]);

                $adjustments++;
                $varianceTotal += $variance;
            }

            $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'cycle_count', ?)")
                ->execute([$uid, "Cycle count at loc #{$locId}: {$adjustments} correction(s), net variance {$varianceTotal}"]);

            $pdo->commit();
            $_SESSION['cc_msg'] = "Cycle count saved. {$adjustments} correction(s) applied. Net variance: " . ($varianceTotal >= 0 ? '+' : '') . $varianceTotal . '.';
            header('Location: cycle_count.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formErr = $e->getMessage();
        }
    }
}

$selectedLocId = (int)($_GET['loc'] ?? 0);

$locations_list = $pdo->query(
    "SELECT l.id, l.code, l.name, l.type, w.name AS warehouse_name
     FROM locations l JOIN warehouses w ON w.id = l.warehouse_id
     WHERE l.type IN ('storage','receiving','dispatch')
     ORDER BY w.name, l.code"
)->fetchAll();

$binInfo = null;
$binContents = [];
if ($selectedLocId > 0) {
    $st = $pdo->prepare(
        "SELECT l.id, l.code, l.name, l.type, w.name AS warehouse_name
         FROM locations l JOIN warehouses w ON w.id = l.warehouse_id
         WHERE l.id = ? LIMIT 1"
    );
    $st->execute([$selectedLocId]);
    $binInfo = $st->fetch();

    if ($binInfo) {
        $st = $pdo->prepare(
            "SELECT i.id AS inv_id, i.quantity AS system_qty, i.last_counted_at,
                    p.id AS product_id, p.name AS product_name, p.sku,
                    (SELECT b.batch_number FROM product_batches b
                     WHERE b.product_id = p.id AND b.location_id = i.location_id
                     ORDER BY (b.expiry_date IS NULL), b.expiry_date ASC LIMIT 1) AS batch_number
             FROM inventory i
             JOIN products p ON p.id = i.product_id
             WHERE i.location_id = ?
             ORDER BY p.name"
        );
        $st->execute([$selectedLocId]);
        $binContents = $st->fetchAll();
    }
}

$recent = $pdo->query(
    "SELECT m.id, m.timestamp, m.quantity, m.reference,
            p.name AS product_name, p.sku,
            l.code AS bin_code, w.name AS warehouse_name,
            u.username
     FROM stock_movements m
     LEFT JOIN products p ON p.id = m.product_id
     LEFT JOIN locations l ON l.id = COALESCE(m.to_location, m.from_location)
     LEFT JOIN warehouses w ON w.id = l.warehouse_id
     LEFT JOIN users u ON u.id = m.user_id
     WHERE m.movement_type='adjustment' AND m.reason='recount'
     ORDER BY m.id DESC LIMIT 25"
)->fetchAll();

$page_title = 'Cycle Count';
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

<div class="table-card" style="margin-bottom:20px;">
  <h5><i class="bi bi-clipboard-check"></i> Cycle Count</h5>
  <p style="color:var(--text-muted);font-size:.9rem;margin-bottom:14px;">
    Choose a bin, enter the physical count for each product. Differences are saved as <code>recount</code> adjustments.
  </p>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div style="flex:1;min-width:280px;">
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Bin</label>
      <select name="loc" required onchange="this.form.submit()" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
        <option value="">— choose bin to count —</option>
        <?php foreach ($locations_list as $L): ?>
          <option value="<?= (int)$L['id'] ?>" <?= $selectedLocId === (int)$L['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars(($L['code'] ? '[' . $L['code'] . '] ' : '') . $L['name']) ?> · <?= htmlspecialchars($L['warehouse_name']) ?> (<?= htmlspecialchars($L['type']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>

<?php if ($selectedLocId > 0 && $binInfo): ?>
  <div class="table-card" style="margin-bottom:20px;">
    <h5><i class="bi bi-list-check"></i> <?= htmlspecialchars($binInfo['code'] ?? '') ?> — <?= htmlspecialchars($binInfo['name']) ?> · <?= htmlspecialchars($binInfo['warehouse_name']) ?></h5>

    <?php if (!$binContents): ?>
      <p style="color:var(--text-muted);">This bin is empty. Nothing to count.</p>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="do_count" value="1">
        <input type="hidden" name="location_id" value="<?= (int)$selectedLocId ?>">
        <div style="overflow-x:auto;">
          <table style="min-width:900px;">
            <thead>
              <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Batch</th>
                <th>System qty</th>
                <th>Counted qty</th>
                <th>Variance</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($binContents as $row): ?>
              <tr>
                <td><code><?= htmlspecialchars($row['sku']) ?></code></td>
                <td><?= htmlspecialchars($row['product_name']) ?></td>
                <td><?= $row['batch_number'] ? '<code>' . htmlspecialchars($row['batch_number']) . '</code>' : '—' ?></td>
                <td><span class="status-badge status-active"><?= (int)$row['system_qty'] ?></span></td>
                <td>
                  <input type="number" min="0"
                         name="counted[<?= (int)$row['inv_id'] ?>]"
                         value="<?= (int)$row['system_qty'] ?>"
                         data-sys="<?= (int)$row['system_qty'] ?>"
                         data-for="<?= (int)$row['inv_id'] ?>"
                         oninput="updateVariance(this)"
                         style="width:110px;padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);">
                </td>
                <td class="variance-cell" data-for="<?= (int)$row['inv_id'] ?>" style="font-weight:600;color:var(--text-muted);">0</td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div style="margin-top:18px;display:flex;gap:10px;">
          <button type="submit" class="btn-add" style="padding:10px 18px;font-size:.95rem;">
            <i class="bi bi-check2-circle"></i> Save Count
          </button>
          <a href="cycle_count.php" class="btn-secondary-custom" style="padding:10px 18px;font-size:.95rem;text-decoration:none;">Cancel</a>
        </div>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="table-card">
  <h5><i class="bi bi-clock-history"></i> Recent Cycle Counts (<?= count($recent) ?>)</h5>
  <?php if (!$recent): ?>
    <p style="color:var(--text-muted);">No cycle counts yet.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:500px;">
    <table style="min-width:1000px;white-space:nowrap;">
      <thead style="position:sticky;top:0;z-index:2;">
        <tr><th>When</th><th>Product</th><th>Bin</th><th>Warehouse</th><th>Variance</th><th>Reference</th><th>User</th></tr>
      </thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['timestamp']) ?></td>
          <td><?= htmlspecialchars($r['product_name'] ?? '—') ?><?= $r['sku'] ? ' · ' . htmlspecialchars($r['sku']) : '' ?></td>
          <td><?= $r['bin_code'] ? '<code>' . htmlspecialchars($r['bin_code']) . '</code>' : '—' ?></td>
          <td><?= htmlspecialchars($r['warehouse_name'] ?? '—') ?></td>
          <td><span class="status-badge status-pending"><?= (int)$r['quantity'] ?></span></td>
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
function updateVariance(input) {
  const sys = parseInt(input.dataset.sys || '0', 10);
  const cnt = parseInt(input.value || '0', 10);
  const diff = cnt - sys;
  const id = input.dataset.for;
  const cell = document.querySelector('.variance-cell[data-for="' + id + '"]');
  if (!cell) return;
  if (diff === 0) { cell.textContent = '0'; cell.style.color = 'var(--text-muted)'; }
  else if (diff > 0) { cell.textContent = '+' + diff; cell.style.color = '#16a34a'; }
  else { cell.textContent = diff; cell.style.color = '#dc2626'; }
}
</script>

<?php include 'warehouse_footer.php'; ?>
