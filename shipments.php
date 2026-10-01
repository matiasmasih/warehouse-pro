<?php
// shipments.php — Outbound shipments list (modern, CRUD entry)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('shipments');

$page_title = 'Shipments';
include 'warehouse_header.php';

$q      = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$wh     = (int)($_GET['wh'] ?? 0);
$sort   = $_GET['sort'] ?? 'id';
$dirRaw = strtolower($_GET['dir'] ?? 'desc');
$dir    = $dirRaw === 'asc' ? 'ASC' : 'DESC';

$allowedSort = ['id','customer','warehouse','shipdate','status','tracking'];
if (!in_array($sort, $allowedSort, true)) $sort = 'id';
$map = [
    'id'        => 'sh.id',
    'customer'  => 'sh.customer_name',
    'warehouse' => 'w.name',
    'shipdate'  => 'sh.shipment_date',
    'status'    => 'sh.status',
    'tracking'  => 'sh.tracking_number',
];
$sortCol = $map[$sort];

$where = []; $args = [];
if ($q !== '') { $where[] = "(sh.customer_name LIKE ? OR sh.tracking_number LIKE ? OR CAST(sh.id AS CHAR) LIKE ?)"; array_push($args, "%$q%", "%$q%", "%$q%"); }
if ($status !== '') { $where[] = "sh.status = ?"; $args[] = $status; }
if ($wh > 0) { $where[] = "sh.warehouse_id = ?"; $args[] = $wh; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT sh.id, sh.customer_name, sh.address, sh.shipment_date, sh.status,
               sh.tracking_number, sh.shipped_at, sh.notes,
               w.name AS warehouse_name, w.city,
               u.username AS shipped_by_name,
               (SELECT COUNT(*) FROM shipment_items si WHERE si.shipment_id = sh.id) AS `lines`
        FROM shipments sh
        LEFT JOIN warehouses w ON w.id = sh.warehouse_id
        LEFT JOIN users     u ON u.id = sh.shipped_by
        $whereSql
        ORDER BY $sortCol $dir
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

$warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();

$counts = ['pending'=>0,'shipped'=>0,'delivered'=>0];
foreach ($rows as $r) { if (isset($counts[$r['status']])) $counts[$r['status']]++; }

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
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['pending'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Pending</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-send"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['shipped'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Shipped</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-check-circle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['delivered'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Delivered</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-list-ol"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($rows) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Total shown</div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-send"></i> Shipments (<?= count($rows) ?>)</h5>
    <?php if (my_access('shipments') === 'full'): ?>
      <a href="admin_add_shipment.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Create Shipment
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Shipment created successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':   echo 'Invalid shipment ID.'; break;
          case 'not_found':    echo 'Shipment not found.'; break;
          case 'no_lines':     echo 'A shipment must have at least one line item.'; break;
          case 'invalid_line': echo 'One or more lines have invalid product or quantity.'; break;
          case 'failed':       echo 'Operation failed. Please try again.'; break;
          default:             echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search customer, tracking #…"
           style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All statuses</option>
      <?php foreach (['pending','shipped','delivered'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="shipments.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No shipments match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table style="min-width:1200px;white-space:nowrap;">
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th><?= sort_link('id',        'Shipment',  $sort, $dirRaw) ?></th>
          <th><?= sort_link('customer',  'Customer',  $sort, $dirRaw) ?></th>
          <th><?= sort_link('warehouse', 'Warehouse', $sort, $dirRaw) ?></th>
          <th><?= sort_link('shipdate',  'Ship date', $sort, $dirRaw) ?></th>
          <th>Lines</th>
          <th><?= sort_link('tracking',  'Tracking',  $sort, $dirRaw) ?></th>
          <th>Shipped by</th>
          <th><?= sort_link('status',    'Status',    $sort, $dirRaw) ?></th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $s): ?>
        <tr>
          <td>SH-<?= (int)$s['id'] ?></td>
          <td><?= htmlspecialchars($s['customer_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['warehouse_name'] ?? '—') ?><?= $s['city'] ? ' (' . htmlspecialchars($s['city']) . ')' : '' ?></td>
          <td><?= htmlspecialchars($s['shipment_date'] ?? '—') ?></td>
          <td><?= (int)$s['lines'] ?></td>
          <td><?= htmlspecialchars($s['tracking_number'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['shipped_by_name'] ?? '—') ?></td>
          <td><span class="status-badge status-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></td>
          <td style="white-space:nowrap;">
            <?php if ($s['status'] === 'pending'): ?>
              <a class="btn-add" href="pick_shipment.php?id=<?= (int)$s['id'] ?>" style="padding:4px 10px;font-size:.8rem;">
                <i class="bi bi-box-arrow-up"></i> Pick
              </a>
            <?php elseif ($s['status'] === 'shipped'): ?>
              <a href="mark_delivered.php?id=<?= (int)$s['id'] ?>"
                 class="btn-add"
                 style="padding:4px 10px;font-size:.8rem;background:#2563eb;color:#fff;text-decoration:none;"
                 onclick="return confirm('Mark SH-<?= (int)$s['id'] ?> as delivered?');">
                <i class="bi bi-truck"></i> In transit
              </a>
            <?php elseif ($s['status'] === 'delivered'): ?>
              <a href="pick_shipment.php?id=<?= (int)$s['id'] ?>"
                 class="btn-add"
                 style="padding:4px 10px;font-size:.8rem;background:#16a34a;color:#fff;text-decoration:none;">
                <i class="bi bi-check-circle-fill"></i> Delivered
              </a>
            <?php else: ?>
              <span style="color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
