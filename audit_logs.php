<?php
// audit_logs.php — System audit log (admin full, manager view, staff no)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('audit_logs');

$page_title = 'Audit Logs';

// ---------- Filters ----------
$q      = trim($_GET['q'] ?? '');
$action = trim($_GET['action'] ?? '');
$user   = (int)($_GET['user'] ?? 0);
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to']   ?? '');
$sort   = $_GET['sort'] ?? 'when';
$dirRaw = strtolower($_GET['dir'] ?? 'desc');
$dir    = $dirRaw === 'asc' ? 'ASC' : 'DESC';

$allowedSort = ['when','user','action'];
if (!in_array($sort, $allowedSort, true)) $sort = 'when';
$map = [
    'when'   => 'a.timestamp',
    'user'   => 'u.username',
    'action' => 'a.action',
];
$sortCol = $map[$sort];

$where = []; $args = [];
if ($q !== '') {
    $where[] = "(a.action LIKE ? OR a.description LIKE ? OR u.username LIKE ?)";
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($action !== '') { $where[] = "a.action = ?"; $args[] = $action; }
if ($user > 0)      { $where[] = "a.user_id = ?"; $args[] = $user; }
if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = "a.timestamp >= ?"; $args[] = $from . ' 00:00:00';
}
if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = "a.timestamp <= ?"; $args[] = $to . ' 23:59:59';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT a.id, a.action, a.description, a.timestamp,
               u.username, u.role
        FROM audit_logs a
        LEFT JOIN users u ON u.id = a.user_id
        $whereSql
        ORDER BY $sortCol $dir
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

// ---------- CSV export ----------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'When', 'User', 'Role', 'Action', 'Description']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['timestamp'],
            $r['username'] ?? 'system',
            $r['role'] ?? '',
            $r['action'],
            $r['description'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ---------- Dropdown data ----------
$actions = $pdo->query("SELECT action, COUNT(*) AS cnt FROM audit_logs GROUP BY action ORDER BY cnt DESC")->fetchAll();
$users   = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

function sort_link($key, $label, $current, $dirRaw) {
    $next  = ($current === $key && $dirRaw === 'asc') ? 'desc' : 'asc';
    $arrow = ($current === $key) ? ($dirRaw === 'asc' ? ' ▲' : ' ▼') : '';
    $qs = http_build_query(array_merge($_GET, ['sort' => $key, 'dir' => $next]));
    return '<a href="?' . $qs . '" style="color:inherit;text-decoration:none;">' . htmlspecialchars($label) . $arrow . '</a>';
}

function action_icon($action) {
    $map = [
        'login'                 => ['bi-box-arrow-in-right',  '#16a34a'],
        'user_created'          => ['bi-person-plus-fill',    '#16a34a'],
        'user_updated'          => ['bi-person-gear',         '#f59e0b'],
        'user_disabled'         => ['bi-person-slash',        '#dc2626'],
        'warehouse_created'     => ['bi-building-add',        '#16a34a'],
        'warehouse_updated'     => ['bi-building-gear',       '#f59e0b'],
        'warehouse_deactivated' => ['bi-building-slash',      '#dc2626'],
        'location_created'      => ['bi-geo-alt-fill',        '#16a34a'],
        'location_updated'      => ['bi-geo',                 '#f59e0b'],
        'location_deleted'      => ['bi-geo-alt',             '#dc2626'],
        'product_created'       => ['bi-box-seam-fill',       '#16a34a'],
        'product_updated'       => ['bi-box-seam',            '#f59e0b'],
        'product_discontinued'  => ['bi-box',                 '#dc2626'],
        'category_created'      => ['bi-tag-fill',            '#16a34a'],
        'category_updated'      => ['bi-tag',                 '#f59e0b'],
        'category_deactivated'  => ['bi-tag',                 '#dc2626'],
        'supplier_created'      => ['bi-truck',               '#16a34a'],
        'supplier_updated'      => ['bi-truck',               '#f59e0b'],
        'supplier_deactivated'  => ['bi-truck',               '#dc2626'],
        'po_created'            => ['bi-cart-plus',           '#f59e0b'],
        'shipment_created'      => ['bi-send',                '#2563eb'],
        'shipment_delivered'    => ['bi-check-circle-fill',   '#16a34a'],
        'pick_shipment'         => ['bi-box-arrow-up',        '#2563eb'],
        'receive_po'            => ['bi-box-arrow-in-down',   '#f59e0b'],
        'transfer'              => ['bi-arrow-left-right',    '#7c3aed'],
        'adjustment'            => ['bi-sliders',             '#dc2626'],
        'return_stock'          => ['bi-arrow-return-left',   '#dc2626'],
    ];
       return $map[$action] ?? ['bi-dot', '#94a3b8'];
    }

      function action_label($action) {
          return ucwords(str_replace('_', ' ', $action));
}

include 'warehouse_header.php';
?>

<!-- ============ KPI ROW ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-clock-history"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($rows) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Entries shown</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-tag"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($actions) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Distinct actions</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-people"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($users) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Users tracked</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-activity"></i></div>
      <div>
        <div style="font-size:1.1rem;font-weight:800;"><?= $rows ? htmlspecialchars($rows[0]['timestamp']) : '—' ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Most recent</div>
      </div>
    </div>
  </div>
</div>

<!-- ============ LOG TABLE ============ -->
<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-clock-history"></i> Audit Log (<?= count($rows) ?>)</h5>
    <?php if (!empty($rows)): ?>
      <a href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['export' => 'csv']))) ?>"
         class="btn-add" style="text-decoration:none;">
        <i class="bi bi-download"></i> Export CSV
      </a>
    <?php endif; ?>
  </div>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search action, description, user…"
           style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">

    <select name="action" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All actions</option>
      <?php foreach ($actions as $a): ?>
        <option value="<?= htmlspecialchars($a['action']) ?>" <?= $action === $a['action'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($a['action']) ?> (<?= (int)$a['cnt'] ?>)
        </option>
      <?php endforeach; ?>
    </select>

    <select name="user" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All users</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= $user === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
      <?php endforeach; ?>
    </select>

    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" title="From date"
           style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">

    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" title="To date"
           style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">

    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="audit_logs.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No audit entries match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:600px;">
    <table style="min-width:1100px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr>
          <th>#</th>
          <th><?= sort_link('when',   'When',   $sort, $dirRaw) ?></th>
          <th><?= sort_link('user',   'User',   $sort, $dirRaw) ?></th>
          <th>Role</th>
          <th><?= sort_link('action', 'Action', $sort, $dirRaw) ?></th>
          <th>Description</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $a): ?>
        <?php [$icon, $color] = action_icon($a['action']); ?>
        <tr>
          <td><?= (int)$a['id'] ?></td>
          <td><?= htmlspecialchars($a['timestamp']) ?></td>
          <td><?= htmlspecialchars($a['username'] ?? 'system') ?></td>
          <td><?= htmlspecialchars($a['role'] ?? '—') ?></td>
          <td>
            <i class="bi <?= $icon ?>" style="color:<?= $color ?>;"></i>
            <strong><?= htmlspecialchars(action_label($a['action'])) ?></strong>
          </td>
          <td><?= htmlspecialchars($a['description'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
