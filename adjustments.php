<?php
// adjustments.php — Stock adjustments (form + list, single file)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

require_once __DIR__ . '/ref.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('adjustments');

$uid = (int)$_SESSION['user_id'];

// ============================================================
// HANDLE POST (create adjustment) — with PRG redirect
// ============================================================
$formErr = '';
$formMsg = $_SESSION['adjust_msg'] ?? '';
unset($_SESSION['adjust_msg']);

$validReasons = ['damage','theft','found','recount','expiry','quality','supplier_return'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_adjust'])) {
    $locId     = (int)($_POST['location_id'] ?? 0);
    $productId = (int)($_POST['product_id']  ?? 0);
    $batchId   = (int)($_POST['batch_id']    ?? 0);
    $qty       = (int)($_POST['qty']         ?? 0);
    $direction = ($_POST['direction'] ?? 'down') === 'up' ? 'up' : 'down';
    $reason    = in_array($_POST['reason'] ?? '', $validReasons, true) ? $_POST['reason'] : 'recount';

    // Force direction from reason
    $downOnly = ['damage','theft','expiry','quality','supplier_return'];
    $upOnly   = ['found'];
    if (in_array($reason, $downOnly, true)) $direction = 'down';
    if (in_array($reason, $upOnly,   true)) $direction = 'up';

    if ($locId <= 0 || $productId <= 0 || $qty <= 0) {
        $formErr = 'Choose bin, product, and quantity.';
    } else {
        try {
            $pdo->beginTransaction();

            $invSt = $pdo->prepare("SELECT id, quantity FROM inventory WHERE product_id = ? AND location_id = ? FOR UPDATE");
            $invSt->execute([$productId, $locId]);
            $inv = $invSt->fetch();

            if ($direction === 'down') {
                if (!$inv || (int)$inv['quantity'] < $qty) {
                    $have = $inv ? (int)$inv['quantity'] : 0;
                    throw new RuntimeException("Cannot deduct — only {$have} in stock at that bin.");
                }
                if ($batchId > 0) {
                    $bSt = $pdo->prepare("SELECT quantity FROM product_batches WHERE id = ? AND product_id = ? FOR UPDATE");
                    $bSt->execute([$batchId, $productId]);
                    $batch = $bSt->fetch();
                    if (!$batch) throw new RuntimeException("Batch not found.");
                    if ((int)$batch['quantity'] < $qty) {
                        throw new RuntimeException("Batch only has {$batch['quantity']} units, cannot deduct {$qty}.");
                    }
                    $newQty = (int)$batch['quantity'] - $qty;
                    $pdo->prepare("UPDATE product_batches SET quantity = ?, status = IF(? = 0, 'depleted', status) WHERE id = ?")
                        ->execute([$newQty, $newQty, $batchId]);
                }
                $pdo->prepare("UPDATE inventory SET quantity = quantity - ?, last_movement_at = NOW() WHERE id = ?")
                    ->execute([$qty, $inv['id']]);
            } else {
                if ($inv) {
                    $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, last_movement_at = NOW() WHERE id = ?")
                        ->execute([$qty, $inv['id']]);
                } else {
                    $pdo->prepare("INSERT INTO inventory (product_id, location_id, quantity, last_movement_at) VALUES (?, ?, ?, NOW())")
                        ->execute([$productId, $locId, $qty]);
                }
            }

            $pdo->prepare(
                "INSERT INTO stock_movements
                    (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                 VALUES (?, ?, ?, ?, ?, 'adjustment', ?, ?, ?)"
            )->execute([
                $productId,
                $batchId > 0 ? $batchId : null,
                $direction === 'down' ? $locId : null,
                $direction === 'up'   ? $locId : null,
                $qty, $reason,
                next_ref($pdo, 'ADJ'),
                $uid,
            ]);

            $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'adjust_stock', ?)")
                ->execute([$uid, ucfirst($direction) . " adjustment: {$qty} units (product #{$productId}, loc #{$locId}) — reason: {$reason}"]);

            $pdo->commit();
            $_SESSION['adjust_msg'] = "Adjustment applied: {$direction} {$qty} unit(s) — reason: {$reason}.";
            header('Location: adjustments.php');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formErr = $e->getMessage();
        }
    }
}

// ============================================================
// DROPDOWN DATA
// ============================================================
$products_list = $pdo->query("SELECT id, sku, name FROM products WHERE status='active' ORDER BY name")->fetchAll();
$locations_list = $pdo->query(
    "SELECT l.id, l.code, l.name, w.name AS warehouse_name
     FROM locations l JOIN warehouses w ON w.id = l.warehouse_id
     WHERE l.type IN ('storage','receiving','dispatch','quarantine','returns')
     ORDER BY w.name, l.code"
)->fetchAll();

$reasonLabels = [
    'damage'          => 'Damage — broken, write off',
    'theft'           => 'Theft — missing, write off',
    'found'           => 'Found — extra stock, write on',
    'recount'         => 'Recount — physical count correction',
    'expiry'          => 'Expiry — expired stock disposed',
    'quality'         => 'Quality — QC rejected',
    'supplier_return' => 'Supplier return — sending back',
];

// ============================================================
// LIST
// ============================================================
$q       = trim($_GET['q'] ?? '');
$wh      = (int)($_GET['wh'] ?? 0);
$reasonF = trim($_GET['reason'] ?? '');
$sort    = $_GET['sort'] ?? 'when';
$dirRaw  = strtolower($_GET['dir'] ?? 'desc');
$dir     = $dirRaw === 'asc' ? 'ASC' : 'DESC';

$allowedSort = ['when','product','qty'];
if (!in_array($sort, $allowedSort, true)) $sort = 'when';
$map = ['when'=>'m.timestamp','product'=>'p.name','qty'=>'m.quantity'];
$sortCol = $map[$sort];

$where = ["m.movement_type = 'adjustment'"];
$args  = [];
if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR m.reference LIKE ? OR m.reason LIKE ?)";
    array_push($args, "%$q%", "%$q%", "%$q%", "%$q%");
}
if ($wh > 0) {
    $where[] = "(fl.warehouse_id = ? OR tl.warehouse_id = ?)";
    $args[]  = $wh;
    $args[]  = $wh;
}
if ($reasonF !== '') {
    $where[] = "m.reason = ?";
    $args[]  = $reasonF;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT m.id, m.quantity, m.reason, m.reference, m.timestamp,
               p.name AS product_name, p.sku,
               fl.code AS from_bin, fw.name AS from_wh,
               tl.code AS to_bin,   tw.name AS to_wh,
               b.batch_number,
               u.username
        FROM stock_movements m
        LEFT JOIN products   p  ON p.id  = m.product_id
        LEFT JOIN locations  fl ON fl.id = m.from_location
        LEFT JOIN locations  tl ON tl.id = m.to_location
        LEFT JOIN warehouses fw ON fw.id = fl.warehouse_id
        LEFT JOIN warehouses tw ON tw.id = tl.warehouse_id
        LEFT JOIN product_batches b ON b.id = m.batch_id
        LEFT JOIN users      u  ON u.id  = m.user_id
        $whereSql
        ORDER BY $sortCol $dir
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

$warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();

$totalUp = 0; $totalDown = 0;
foreach ($rows as $r) {
    if (!empty($r['to_bin']) && empty($r['from_bin'])) $totalUp   += (int)$r['quantity'];
    else                                                 $totalDown += (int)$r['quantity'];
}

function sort_link($key, $label, $current, $dirRaw) {
    $next  = ($current === $key && $dirRaw === 'asc') ? 'desc' : 'asc';
    $arrow = ($current === $key) ? ($dirRaw === 'asc' ? ' ▲' : ' ▼') : '';
    $qs = http_build_query(array_merge($_GET, ['sort' => $key, 'dir' => $next]));
    return '<a href="?' . $qs . '" style="color:inherit;text-decoration:none;">' . htmlspecialchars($label) . $arrow . '</a>';
}

$page_title = 'Adjustments';
include 'warehouse_header.php';
?>

<?php if ($formMsg): ?>
  <div style="padding:12px 16px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($formMsg) ?>
  </div>
<?php endif; ?>

<!-- KPI cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-sliders"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($rows) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Adjustments</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-arrow-up-circle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;">+<?= number_format($totalUp, 0, ',', ' ') ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Units added</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-arrow-down-circle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;">-<?= number_format($totalDown, 0, ',', ' ') ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Units removed</div>
      </div>
    </div>
  </div>
</div>

<!-- New Adjustment form -->
<div class="table-card" style="margin-bottom:20px;">
  <h5><i class="bi bi-plus-circle"></i> New Adjustment</h5>

  <?php if ($formErr): ?>
    <div style="padding:10px 14px;border-radius:10px;background:rgba(239,68,68,.15);color:#dc2626;font-weight:600;margin-bottom:14px;">
      <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($formErr) ?>
    </div>
  <?php endif; ?>

  <form method="post" style="display:grid;gap:14px;max-width:820px;">
    <input type="hidden" name="do_adjust" value="1">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Reason</label>
        <select name="reason" id="reasonSel" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
          <?php foreach ($reasonLabels as $k => $lbl): ?>
            <option value="<?= $k ?>"><?= htmlspecialchars($lbl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Direction (auto)</label>
        <select name="direction" id="dirSel" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
          <option value="down">⬇ Decrease</option>
          <option value="up">⬆ Increase</option>
        </select>
      </div>
    </div>

    <div>
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Product</label>
      <select name="product_id" id="productSel" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
        <option value="">— choose product —</option>
        <?php foreach ($products_list as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['sku']) ?> · <?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Batch (optional)</label>
      <select name="batch_id" id="batchSel" style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
        <option value="0">— no batch / product-level —</option>
      </select>
    </div>

    <div>
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Bin</label>
      <select name="location_id" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
        <option value="">— choose bin —</option>
        <?php foreach ($locations_list as $L): ?>
          <option value="<?= (int)$L['id'] ?>"><?= htmlspecialchars(($L['code'] ? '[' . $L['code'] . '] ' : '') . $L['name']) ?> · <?= htmlspecialchars($L['warehouse_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Quantity</label>
      <input type="number" min="1" name="qty" required
             style="width:140px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    </div>

    <div>
      <button type="submit" class="btn-add" style="padding:10px 18px;font-size:.95rem;">
        <i class="bi bi-check2-circle"></i> Apply Adjustment
      </button>
    </div>
  </form>
</div>

<!-- Adjustment list -->
<div class="table-card">
  <h5><i class="bi bi-sliders"></i> Adjustments (<?= count($rows) ?>)</h5>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search product, SKU, reason…"
           style="flex:1;min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="reason" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All reasons</option>
      <?php foreach ($reasonLabels as $k => $lbl): ?>
        <option value="<?= $k ?>" <?= $reasonF === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="adjustments.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No adjustments yet. Use the form above to record one.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:600px;">
    <table style="min-width:1200px;white-space:nowrap;">
      <thead style="position:sticky;top:0;z-index:2;">
        <tr>
          <th><?= sort_link('when',    'When',    $sort, $dirRaw) ?></th>
          <th><?= sort_link('product', 'Product', $sort, $dirRaw) ?></th>
          <th>Direction</th>
          <th>Batch</th>
          <th>Warehouse</th>
          <th>Bin</th>
          <th><?= sort_link('qty',     'Qty',     $sort, $dirRaw) ?></th>
          <th>Reason</th>
          <th>Reference</th>
          <th>User</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $a): ?>
        <?php
          $isUp   = !empty($a['to_bin']) && empty($a['from_bin']);
          $bin    = $isUp ? $a['to_bin'] : $a['from_bin'];
          $whName = $isUp ? $a['to_wh']  : $a['from_wh'];
        ?>
        <tr>
          <td><?= htmlspecialchars($a['timestamp']) ?></td>
          <td><?= htmlspecialchars($a['product_name'] ?? '—') ?><?= $a['sku'] ? ' · ' . htmlspecialchars($a['sku']) : '' ?></td>
          <td>
            <?php if ($isUp): ?>
              <span class="status-badge status-active"><i class="bi bi-arrow-up"></i> up</span>
            <?php else: ?>
              <span class="status-badge status-cancelled"><i class="bi bi-arrow-down"></i> down</span>
            <?php endif; ?>
          </td>
          <td><?= $a['batch_number'] ? '<code>' . htmlspecialchars($a['batch_number']) . '</code>' : '—' ?></td>
          <td><?= htmlspecialchars($whName ?? '—') ?></td>
          <td><?= $bin ? '<code>' . htmlspecialchars($bin) . '</code>' : '—' ?></td>
          <td><?= number_format((int)$a['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($a['reason'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['reference'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['username'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const reasonSel  = document.getElementById('reasonSel');
  const dirSel     = document.getElementById('dirSel');
  const productSel = document.getElementById('productSel');
  const batchSel   = document.getElementById('batchSel');

  const downOnly = ['damage','theft','expiry','quality','supplier_return'];
  const upOnly   = ['found'];

  function syncDirection() {
    const r = reasonSel.value;
    if (downOnly.includes(r))    { dirSel.value = 'down'; dirSel.disabled = true; }
    else if (upOnly.includes(r)) { dirSel.value = 'up';   dirSel.disabled = true; }
    else                         { dirSel.disabled = false; }
  }
  reasonSel.addEventListener('change', syncDirection);
  syncDirection();

  productSel.addEventListener('change', function() {
    const pid = this.value;
    batchSel.innerHTML = '<option value="0">— no batch / product-level —</option>';
    if (!pid) return;
    fetch('batch_lookup.php?product_id=' + pid)
      .then(r => r.json())
      .then(list => {
        list.forEach(b => {
          const opt = document.createElement('option');
          opt.value = b.id;
          const exp = b.expiry_date ? ' · exp ' + b.expiry_date : '';
          opt.textContent = b.batch_number + exp + ' · qty ' + b.quantity + ' · ' + (b.bin_code || '—');
          batchSel.appendChild(opt);
        });
      })
      .catch(() => {});
  });
});
</script>

<?php include 'warehouse_footer.php'; ?>
