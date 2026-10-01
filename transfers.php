<?php
// transfers.php — Transfer form + transfer list (one page)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

require_once __DIR__ . '/ref.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('transfers');

$uid = (int)$_SESSION['user_id'];

// ============================================================
// HANDLE NEW TRANSFER SUBMIT
// ============================================================
$formMsg = '';
$formErr = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_transfer'])) {
    $fromLocId = (int)($_POST['from_location'] ?? 0);
    $toLocId   = (int)($_POST['to_location']   ?? 0);
    $productId = (int)($_POST['product_id']    ?? 0);
    $qty       = (int)($_POST['qty']           ?? 0);
    $reason    = trim($_POST['reason'] ?? 'Bin-to-bin transfer');

    if ($fromLocId <= 0 || $toLocId <= 0 || $productId <= 0 || $qty <= 0) {
        $formErr = 'Fill every field (product, from bin, to bin, quantity).';
    } elseif ($fromLocId === $toLocId) {
        $formErr = 'From and To bins must be different.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock source row
            $src = $pdo->prepare("SELECT id, quantity FROM inventory WHERE product_id = ? AND location_id = ? FOR UPDATE");
            $src->execute([$productId, $fromLocId]);
            $srcRow = $src->fetch();
            if (!$srcRow || (int)$srcRow['quantity'] < $qty) {
                $have = $srcRow ? (int)$srcRow['quantity'] : 0;
                throw new RuntimeException("Not enough stock at source (have {$have}, need {$qty}).");
            }

            // Deduct source
            $pdo->prepare("UPDATE inventory SET quantity = quantity - ?, last_movement_at = NOW() WHERE id = ?")
                ->execute([$qty, $srcRow['id']]);

            // Add to destination (upsert)
            $dst = $pdo->prepare("SELECT id FROM inventory WHERE product_id = ? AND location_id = ? LIMIT 1");
            $dst->execute([$productId, $toLocId]);
            $dstRow = $dst->fetch();
            if ($dstRow) {
                $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, last_movement_at = NOW() WHERE id = ?")
                    ->execute([$qty, $dstRow['id']]);
            } else {
                $pdo->prepare("INSERT INTO inventory (product_id, location_id, quantity, last_movement_at) VALUES (?, ?, ?, NOW())")
                    ->execute([$productId, $toLocId, $qty]);
            }

            // Movement log
            $pdo->prepare(
                "INSERT INTO stock_movements
                    (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                 VALUES (?, NULL, ?, ?, ?, 'transfer', ?, ?, ?)"
            )->execute([
                $productId, $fromLocId, $toLocId, $qty,
                $reason, next_ref($pdo, 'TR'), $uid,
            ]);

            // Audit
            $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'transfer_stock', ?)")
                ->execute([$uid, "Transferred {$qty} units (product #{$productId}) from loc #{$fromLocId} to loc #{$toLocId}"]);

            $pdo->commit();
            $formMsg = "Transferred {$qty} unit(s) successfully.";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formErr = $e->getMessage();
        }
    }
}

// ============================================================
// LOAD DROPDOWN DATA
// ============================================================
$warehouses_list = $pdo->query("SELECT id, name, code FROM warehouses ORDER BY name")->fetchAll();
$products_list   = $pdo->query("SELECT id, sku, name FROM products WHERE status='active' ORDER BY name")->fetchAll();
$locations_list  = $pdo->query(
    "SELECT l.id, l.code, l.name, w.name AS warehouse_name
     FROM locations l JOIN warehouses w ON w.id = l.warehouse_id
     WHERE l.type IN ('storage','receiving','dispatch')
     ORDER BY w.name, l.code"
)->fetchAll();

// ============================================================
// LOAD TRANSFER LIST
// ============================================================
$q       = trim($_GET['q'] ?? '');
$wh      = (int)($_GET['wh'] ?? 0);
$sort    = $_GET['sort'] ?? 'when';
$dirRaw  = strtolower($_GET['dir'] ?? 'desc');
$dir     = $dirRaw === 'asc' ? 'ASC' : 'DESC';

$allowedSort = ['when','product','qty'];
if (!in_array($sort, $allowedSort, true)) $sort = 'when';
$map = [
    'when'    => 'm.timestamp',
    'product' => 'p.name',
    'qty'     => 'm.quantity',
];
$sortCol = $map[$sort];

$where = ["m.movement_type = 'transfer'"];
$args  = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR m.reference LIKE ?)";
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($wh > 0) {
    $where[] = "(fw.id = ? OR tw.id = ?)";
    $args[]  = $wh;
    $args[]  = $wh;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT m.id, m.quantity, m.reason, m.reference, m.notes, m.timestamp,
               p.name AS product_name, p.sku,
               fl.code AS from_bin, fl.name AS from_name, fw.name AS from_wh, fw.city AS from_city,
               tl.code AS to_bin,   tl.name AS to_name,   tw.name AS to_wh,   tw.city AS to_city,
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

$totalUnits = 0;
$sameWh = 0;
$crossWh = 0;
foreach ($rows as $r) {
    $totalUnits += (int)$r['quantity'];
    if (($r['from_wh'] ?? '') === ($r['to_wh'] ?? '')) $sameWh++;
    else $crossWh++;
}

function sort_link($key, $label, $current, $dirRaw) {
    $next  = ($current === $key && $dirRaw === 'asc') ? 'desc' : 'asc';
    $arrow = ($current === $key) ? ($dirRaw === 'asc' ? ' ▲' : ' ▼') : '';
    $qs = http_build_query(array_merge($_GET, ['sort' => $key, 'dir' => $next]));
    return '<a href="?' . $qs . '" style="color:inherit;text-decoration:none;">' . htmlspecialchars($label) . $arrow . '</a>';
}

$page_title = 'Transfers';
include 'warehouse_header.php';
?>

<!-- KPI row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-arrow-left-right"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($rows) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Transfers</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-box-seam"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($totalUnits, 0, ',', ' ') ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Units moved</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-diagram-2"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $sameWh ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Same WH</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-truck"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $crossWh ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Cross WH</div>
      </div>
    </div>
  </div>
</div>

<!-- New transfer form -->
<div class="table-card" style="margin-bottom:20px;">
  <h5><i class="bi bi-plus-circle"></i> New Transfer</h5>

  <?php if ($formMsg): ?>
    <div style="padding:10px 14px;border-radius:10px;background:rgba(34,197,94,.15);color:#16a34a;font-weight:600;margin-bottom:14px;">
      <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($formMsg) ?>
    </div>
  <?php endif; ?>
  <?php if ($formErr): ?>
    <div style="padding:10px 14px;border-radius:10px;background:rgba(239,68,68,.15);color:#dc2626;font-weight:600;margin-bottom:14px;">
      <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($formErr) ?>
    </div>
  <?php endif; ?>

  <form method="post" style="display:grid;gap:14px;max-width:760px;">
    <input type="hidden" name="do_transfer" value="1">
    <div>
      <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Product</label>
      <select name="product_id" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
        <option value="">— choose product —</option>
        <?php foreach ($products_list as $p): ?>
          <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['sku']) ?> · <?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">From bin</label>
        <select name="from_location" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
          <option value="">— choose source —</option>
          <?php foreach ($locations_list as $L): ?>
            <option value="<?= (int)$L['id'] ?>"><?= htmlspecialchars(($L['code'] ? '[' . $L['code'] . '] ' : '') . $L['name']) ?> · <?= htmlspecialchars($L['warehouse_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">To bin</label>
        <select name="to_location" required style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
          <option value="">— choose destination —</option>
          <?php foreach ($locations_list as $L): ?>
            <option value="<?= (int)$L['id'] ?>"><?= htmlspecialchars(($L['code'] ? '[' . $L['code'] . '] ' : '') . $L['name']) ?> · <?= htmlspecialchars($L['warehouse_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:14px;">
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Quantity</label>
        <input type="number" min="1" name="qty" required
               style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      </div>
      <div>
        <label style="display:block;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">Reason</label>
        <input type="text" name="reason" value="Bin-to-bin transfer"
               style="width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      </div>
    </div>

    <div>
      <button type="submit" class="btn-add" style="padding:10px 18px;font-size:.95rem;">
        <i class="bi bi-arrow-left-right"></i> Execute Transfer
      </button>
    </div>
  </form>
</div>

<!-- Transfer list -->
<div class="table-card">
  <h5><i class="bi bi-list-ul"></i> Transfers (<?= count($rows) ?>)</h5>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search product, SKU, reference…"
           style="flex:1;min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="transfers.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No transfers yet. Use the form above to move stock between bins or warehouses.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th><?= sort_link('when',    'When',    $sort, $dirRaw) ?></th>
          <th><?= sort_link('product', 'Product', $sort, $dirRaw) ?></th>
          <th>Batch</th>
          <th>From WH</th>
          <th>From bin</th>
          <th>To WH</th>
          <th>To bin</th>
          <th><?= sort_link('qty',     'Qty',     $sort, $dirRaw) ?></th>
          <th>Reference</th>
          <th>Reason</th>
          <th>User</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr style="white-space:nowrap;">

          <td><?= htmlspecialchars($r['timestamp']) ?></td>
          <td style="white-space:nowrap;"><?= htmlspecialchars($r['product_name'] ?? '—') ?><?= $r['sku'] ? ' · ' . htmlspecialchars($r['sku']) : '' ?></td>
          <td><?= $r['batch_number'] ? '<code>' . htmlspecialchars($r['batch_number']) . '</code>' : '—' ?></td>
          <td><?= htmlspecialchars($r['from_wh'] ?? '—') ?></td>
          <td><?= $r['from_bin'] ? '<code>' . htmlspecialchars($r['from_bin']) . '</code>' : '—' ?></td>
          <td><?= htmlspecialchars($r['to_wh'] ?? '—') ?></td>
          <td><?= $r['to_bin'] ? '<code>' . htmlspecialchars($r['to_bin']) . '</code>' : '—' ?></td>
          <td><?= number_format((int)$r['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($r['reference'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['reason'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
