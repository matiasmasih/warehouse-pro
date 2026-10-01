<?php
// inventory.php — Bin-level stock list (modern)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('inventory');

$page_title = 'Inventory';
include 'warehouse_header.php';

$q        = trim($_GET['q'] ?? '');
$wh       = (int)($_GET['wh'] ?? 0);
$lowOnly  = !empty($_GET['low']);
$sort     = $_GET['sort'] ?? 'product';
$dirRaw   = strtolower($_GET['dir'] ?? 'asc');
$dir      = $dirRaw === 'desc' ? 'DESC' : 'ASC';

$allowedSort = ['product','sku','warehouse','bin','qty','updated'];
if (!in_array($sort, $allowedSort, true)) $sort = 'product';
$map = [
    'product'   => 'p.name',
    'sku'       => 'p.sku',
    'warehouse' => 'w.name',
    'bin'       => 'l.code',
    'qty'       => 'i.quantity',
    'updated'   => 'i.last_updated',
];
$sortCol = $map[$sort];

$where = ["i.quantity > 0"];
$args  = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR l.code LIKE ?)";
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($wh > 0) {
    $where[] = "l.warehouse_id = ?";
    $args[]  = $wh;
}
if ($lowOnly) {
    $where[] = "p.reorder_point IS NOT NULL AND i.quantity <= p.reorder_point";
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT i.id, i.quantity, i.last_updated,
               p.name AS product_name, p.sku, p.reorder_point, p.unit_price,
               l.code AS bin_code, l.name AS bin_name, l.type AS loc_type,
               w.name AS warehouse_name, w.city
        FROM inventory i
        JOIN products  p ON p.id = i.product_id
        JOIN locations l ON l.id = i.location_id
        JOIN warehouses w ON w.id = l.warehouse_id
        $whereSql
        ORDER BY $sortCol $dir
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

$warehouses = $pdo->query("SELECT id, name, city FROM warehouses ORDER BY name")->fetchAll();

$totalUnits = 0;
$totalValue = 0.0;
foreach ($rows as $r) {
    $totalUnits += (int)$r['quantity'];
    $totalValue += (int)$r['quantity'] * (float)$r['unit_price'];
}

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
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-list-ol"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($rows) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Bin rows shown</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-stack"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($totalUnits, 0, ',', ' ') ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Units shown</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-currency-euro"></i>
      </div>
      <div>
        <div style="font-size:1.3rem;font-weight:800;"><?= number_format($totalValue, 0, ',', ' ') ?> €</div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Value shown</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi bi-exclamation-triangle"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;">
          <?php
            $lowCount = 0;
            foreach ($rows as $r) {
              if ($r['reorder_point'] !== null && (int)$r['quantity'] <= (int)$r['reorder_point']) $lowCount++;
            }
            echo $lowCount;
          ?>
        </div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Below reorder</div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <h5><i class="bi bi-stack"></i> Inventory — bin level</h5>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search product, SKU, bin…"
           style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($w['name']) ?><?= $w['city'] ? ' (' . htmlspecialchars($w['city']) . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <label style="display:flex;align-items:center;gap:6px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);cursor:pointer;">
      <input type="checkbox" name="low" value="1" <?= $lowOnly ? 'checked' : '' ?>>
      Low stock only
    </label>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="inventory.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No inventory rows match.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th><?= sort_link('sku',       'SKU',       $sort, $dirRaw) ?></th>
          <th><?= sort_link('product',   'Product',   $sort, $dirRaw) ?></th>
          <th><?= sort_link('warehouse', 'Warehouse', $sort, $dirRaw) ?></th>
          <th><?= sort_link('bin',       'Bin',       $sort, $dirRaw) ?></th>
          <th><?= sort_link('qty',       'Qty',       $sort, $dirRaw) ?></th>
          <th>Value</th>
          <th><?= sort_link('updated',   'Updated',   $sort, $dirRaw) ?></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $isLow = $r['reorder_point'] !== null && (int)$r['quantity'] <= (int)$r['reorder_point'];
          $qtyBadge = $isLow ? 'status-pending' : 'status-active';
          $value = (int)$r['quantity'] * (float)$r['unit_price'];
        ?>
        <tr>
          <td><code><?= htmlspecialchars($r['sku']) ?></code></td>
          <td><?= htmlspecialchars($r['product_name']) ?></td>
          <td><?= htmlspecialchars($r['warehouse_name']) ?><?= $r['city'] ? ' (' . htmlspecialchars($r['city']) . ')' : '' ?></td>
          <td>
            <code><?= htmlspecialchars($r['bin_code'] ?? '—') ?></code>
            <div style="font-size:.75rem;color:var(--text-muted);"><?= htmlspecialchars($r['bin_name']) ?></div>
          </td>
          <td><span class="status-badge <?= $qtyBadge ?>"><?= number_format((int)$r['quantity'], 0, ',', ' ') ?></span></td>
          <td><?= number_format($value, 2, ',', ' ') ?> €</td>
          <td><?= htmlspecialchars($r['last_updated'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
