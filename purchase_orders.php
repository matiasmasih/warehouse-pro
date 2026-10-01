<?php
// purchase_orders.php — Purchase orders list (modern, CRUD entry)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('purchase_orders');

$page_title = 'Purchase Orders';
include 'warehouse_header.php';

$q      = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$wh     = (int)($_GET['wh'] ?? 0);
$sort   = $_GET['sort'] ?? 'id';
$dirRaw = strtolower($_GET['dir'] ?? 'desc');
$dir    = $dirRaw === 'asc' ? 'ASC' : 'DESC';

$allowedSort = ['id','supplier','warehouse','expected','status','total'];
if (!in_array($sort, $allowedSort, true)) $sort = 'id';
$map = [
    'id'        => 'po.id',
    'supplier'  => 's.name',
    'warehouse' => 'w.name',
    'expected'  => 'po.expected_date',
    'status'    => 'po.status',
    'total'     => 'po.total_amount',
];
$sortCol = $map[$sort];

$where = []; $args = [];
if ($q !== '') { $where[] = "(s.name LIKE ? OR CAST(po.id AS CHAR) LIKE ?)"; array_push($args, "%$q%", "%$q%"); }
if ($status !== '') { $where[] = "po.status = ?"; $args[] = $status; }
if ($wh > 0) { $where[] = "po.warehouse_id = ?"; $args[] = $wh; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT po.id, po.order_date, po.expected_date, po.status, po.total_amount, po.received_at,
               s.name AS supplier_name,
               w.name AS warehouse_name, w.city,
               (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.purchase_order_id = po.id) AS `lines`
        FROM purchase_orders po
        LEFT JOIN suppliers  s ON s.id = po.supplier_id
        LEFT JOIN warehouses w ON w.id = po.warehouse_id
        $whereSql
        ORDER BY $sortCol $dir
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

$warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();

$counts = ['pending'=>0,'received'=>0,'cancelled'=>0];
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
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-cart-plus"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['pending'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Pending</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-check-circle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['received'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Received</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-x-circle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['cancelled'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Cancelled</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-currency-euro"></i></div>
      <div>
        <div style="font-size:1.3rem;font-weight:800;"><?= number_format(array_sum(array_column($rows, 'total_amount')), 0, ',', ' ') ?> €</div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Value shown</div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-cart-plus"></i> Purchase Orders (<?= count($rows) ?>)</h5>
    <?php if (my_access('purchase_orders') === 'full'): ?>
      <a href="admin_add_po.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Create PO
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Purchase order created successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':   echo 'Invalid PO ID.'; break;
          case 'not_found':    echo 'Purchase order not found.'; break;
          case 'no_lines':     echo 'A purchase order must have at least one line item.'; break;
          case 'invalid_line': echo 'One or more lines have invalid product or quantity.'; break;
          case 'failed':       echo 'Operation failed. Please try again.'; break;
          default:             echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search supplier or PO #…"
           style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All statuses</option>
      <?php foreach (['pending','received','cancelled'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="purchase_orders.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No purchase orders match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table style="min-width:1100px;white-space:nowrap;">
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th><?= sort_link('id',        'PO',        $sort, $dirRaw) ?></th>
          <th><?= sort_link('supplier',  'Supplier',  $sort, $dirRaw) ?></th>
          <th><?= sort_link('warehouse', 'Warehouse', $sort, $dirRaw) ?></th>
          <th>Order date</th>
          <th><?= sort_link('expected',  'Expected',  $sort, $dirRaw) ?></th>
          <th>Lines</th>
          <th><?= sort_link('total',     'Total',     $sort, $dirRaw) ?></th>
          <th><?= sort_link('status',    'Status',    $sort, $dirRaw) ?></th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $po): ?>
        <tr>
          <td>PO-<?= (int)$po['id'] ?></td>
          <td><?= htmlspecialchars($po['supplier_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($po['warehouse_name'] ?? '—') ?><?= $po['city'] ? ' (' . htmlspecialchars($po['city']) . ')' : '' ?></td>
          <td><?= htmlspecialchars($po['order_date'] ?? '—') ?></td>
          <td><?= htmlspecialchars($po['expected_date'] ?? '—') ?></td>
          <td><?= (int)$po['lines'] ?></td>
          <td><?= number_format((float)$po['total_amount'], 2, ',', ' ') ?> €</td>
          <td><span class="status-badge status-<?= htmlspecialchars($po['status']) ?>"><?= htmlspecialchars($po['status']) ?></span></td>
          <td>
            <?php if ($po['status'] === 'pending' && my_access('purchase_orders') !== 'view'): ?>
              <a class="btn-add" href="receive_po.php?id=<?= (int)$po['id'] ?>"><i class="bi bi-box-arrow-in-down"></i> Receive</a>
            <?php elseif ($po['status'] === 'pending'): ?>
              <a class="btn-secondary-custom" href="receive_po.php?id=<?= (int)$po['id'] ?>"><i class="bi bi-eye"></i> View</a>
            <?php else: ?>
              —
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
