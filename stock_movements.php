<?php
// stock_movements.php — Movement ledger (modern)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('stock_movements');

$page_title = 'Stock Movements';
include 'warehouse_header.php';

$q       = trim($_GET['q'] ?? '');
$type    = trim($_GET['type'] ?? '');
$wh      = (int)($_GET['wh'] ?? 0);
$sort    = $_GET['sort'] ?? 'when';
$dirRaw  = strtolower($_GET['dir'] ?? 'desc');
$dir     = $dirRaw === 'asc' ? 'ASC' : 'DESC';

$allowedSort = ['when','product','type','qty'];
if (!in_array($sort, $allowedSort, true)) $sort = 'when';
$map = [
    'when'    => 'm.timestamp',
    'product' => 'p.name',
    'type'    => 'm.movement_type',
    'qty'     => 'm.quantity',
];
$sortCol = $map[$sort];

$where = []; $args = [];
if ($q !== '') { $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR m.reference LIKE ?)"; array_push($args, "%$q%", "%$q%", "%$q%"); }
if ($type !== '') { $where[] = "m.movement_type = ?"; $args[] = $type; }
if ($wh > 0) { $where[] = "(fl.warehouse_id = ? OR tl.warehouse_id = ?)"; $args[] = $wh; $args[] = $wh; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT m.id, m.movement_type, m.quantity, m.reason, m.reference, m.notes, m.timestamp,
               p.name AS product_name, p.sku,
               fl.code AS from_bin, tl.code AS to_bin,
               b.batch_number,
               u.username
        FROM stock_movements m
        LEFT JOIN products  p  ON p.id  = m.product_id
        LEFT JOIN locations fl ON fl.id = m.from_location
        LEFT JOIN locations tl ON tl.id = m.to_location
        LEFT JOIN product_batches b ON b.id = m.batch_id
        LEFT JOIN users u ON u.id = m.user_id
        $whereSql
        ORDER BY $sortCol $dir
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

$warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();
$types = ['inbound','outbound','transfer','adjustment'];

$counts = ['inbound'=>0,'outbound'=>0,'transfer'=>0,'adjustment'=>0];
foreach ($rows as $r) { if (isset($counts[$r['movement_type']])) $counts[$r['movement_type']]++; }

function sort_link($key, $label, $current, $dirRaw) {
    $next  = ($current === $key && $dirRaw === 'asc') ? 'desc' : 'asc';
    $arrow = ($current === $key) ? ($dirRaw === 'asc' ? ' ▲' : ' ▼') : '';
    $qs = http_build_query(array_merge($_GET, ['sort' => $key, 'dir' => $next]));
    return '<a href="?' . $qs . '" style="color:inherit;text-decoration:none;">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-box-arrow-in-down"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['inbound'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Inbound</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-box-arrow-up"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['outbound'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Outbound</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-arrow-left-right"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['transfer'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Transfers</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-sliders"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['adjustment'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Adjustments</div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <h5><i class="bi bi-arrow-repeat"></i> Movement Ledger (<?= count($rows) ?> shown)</h5>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search product, SKU, reference…"
           style="flex:1;min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="type" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All types</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="stock_movements.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No movements match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:600px;">
    <table style="min-width:1300px;white-space:nowrap;">
      <thead style="position:sticky;top:0;z-index:2;">
        <tr>
          <th>#</th>
          <th><?= sort_link('when',    'When',    $sort, $dirRaw) ?></th>
          <th><?= sort_link('type',    'Type',    $sort, $dirRaw) ?></th>
          <th><?= sort_link('product', 'Product', $sort, $dirRaw) ?></th>
          <th>Batch</th>
          <th>From</th>
          <th>To</th>
          <th><?= sort_link('qty',     'Qty',     $sort, $dirRaw) ?></th>
          <th>Reference</th>
          <th>User</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $m): ?>
        <tr>
          <td><?= (int)$m['id'] ?></td>
          <td><?= htmlspecialchars($m['timestamp']) ?></td>
          <td><span class="status-badge status-<?= htmlspecialchars($m['movement_type']) ?>"><?= htmlspecialchars($m['movement_type']) ?></span></td>
          <td><?= htmlspecialchars($m['product_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($m['batch_number'] ?? '—') ?></td>
          <td><?= $m['from_bin'] ? '<code>' . htmlspecialchars($m['from_bin']) . '</code>' : '—' ?></td>
          <td><?= $m['to_bin']   ? '<code>' . htmlspecialchars($m['to_bin'])   . '</code>' : '—' ?></td>
          <td><?= number_format((int)$m['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($m['reference'] ?? '—') ?></td>
          <td><?= htmlspecialchars($m['username'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
