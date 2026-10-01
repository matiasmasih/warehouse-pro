<?php
// admin_dashboard.php — Admin control panel
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('admin_dashboard');

$page_title = 'Admin Dashboard';
include 'warehouse_header.php';

function one(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function all(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }

/* ---------- System KPIs ---------- */
$warehouses = (int) one($pdo, 'SELECT COUNT(*) FROM warehouses');
$products   = (int) one($pdo, 'SELECT COUNT(*) FROM products');
$units      = (int) one($pdo, 'SELECT COALESCE(SUM(quantity),0) FROM inventory');
$users      = (int) one($pdo, 'SELECT COUNT(*) FROM users');
$openPOs    = (int) one($pdo, "SELECT COUNT(*) FROM purchase_orders WHERE status NOT IN ('received','cancelled')");
$openShips  = (int) one($pdo, "SELECT COUNT(*) FROM shipments WHERE status NOT IN ('delivered','cancelled')");
$expired    = (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())");
$quarantine = (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='quarantine'");

/* ---------- New admin KPIs ---------- */
$dbSizeMb   = (float) one($pdo, "SELECT ROUND(COALESCE(SUM(data_length+index_length),0)/1024/1024, 1)
                                 FROM information_schema.tables WHERE table_schema = DATABASE()");
$mvToday    = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE DATE(`timestamp`) = CURDATE()");
$mv7d       = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE `timestamp` >= (NOW() - INTERVAL 7 DAY)");
$pending    = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE approval_status='pending'");
$active7d   = (int) one($pdo, "SELECT COUNT(*) FROM users WHERE last_login IS NOT NULL AND last_login >= (NOW() - INTERVAL 7 DAY)");

/* ---------- Security & Attendance KPIs (NEW) ---------- */
$failedToday = (int) one($pdo, "SELECT COUNT(*) FROM failed_logins WHERE DATE(created_at) = CURDATE()");
$failed7d    = (int) one($pdo, "SELECT COUNT(*) FROM failed_logins WHERE created_at >= NOW() - INTERVAL 7 DAY");
$onShiftNow  = (int) one($pdo, "SELECT COUNT(*) FROM timeclock WHERE clock_out_at IS NULL");
$shiftsToday = (int) one($pdo, "SELECT COUNT(*) FROM shifts WHERE shift_date = CURDATE()");
$shiftsWeek  = (int) one($pdo, "SELECT COUNT(*) FROM shifts WHERE shift_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");

/* ---------- Disk usage (pure PHP, no shell) ---------- */
$diskFree  = @disk_free_space(__DIR__);
$diskTotal = @disk_total_space(__DIR__);
$diskPct   = ($diskFree !== false && $diskTotal) ? round((1 - $diskFree / $diskTotal) * 100, 1) : null;
$diskFreeG = $diskFree !== false ? round($diskFree / 1073741824, 1) : null;
$diskTotG  = $diskTotal ? round($diskTotal / 1073741824, 1) : null;

/* ---------- Users summary ---------- */
$userList = all($pdo, "SELECT username, role, email, last_login, is_active
                        FROM users ORDER BY role, username");

/* ---------- Stock by warehouse ---------- */
$byWh = all($pdo,
    "SELECT w.id, w.name, w.city,
            COALESCE(SUM(i.quantity),0) AS units,
            COUNT(DISTINCT i.product_id) AS skus
     FROM warehouses w
     LEFT JOIN locations l ON l.warehouse_id = w.id
     LEFT JOIN inventory i ON i.location_id  = l.id
     GROUP BY w.id, w.name, w.city
     ORDER BY units DESC"
);

/* ---------- Recent audit ---------- */
$audit = all($pdo,
    "SELECT a.action, a.description, a.timestamp, u.username
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.id DESC LIMIT 10"
);

/* ---------- helpers ---------- */
function ago($ts) {
    if (!$ts) return 'never';
    $d = time() - strtotime($ts);
    if ($d < 60)     return 'just now';
    if ($d < 3600)   return floor($d/60) . 'm ago';
    if ($d < 86400)  return floor($d/3600) . 'h ago';
    if ($d < 604800) return floor($d/86400) . 'd ago';
    return date('Y-m-d', strtotime($ts));
}
function role_badge($r) {
    $map = ['admin'=>'status-danger','manager'=>'status-warning','staff'=>'status-active'];
    $cls = $map[$r] ?? 'status-active';
    return '<span class="status-badge '.$cls.'">'.htmlspecialchars($r).'</span>';
}
?>

<!-- ============ SYSTEM KPI TILES ============ -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['Warehouses',     $warehouses, 'bi-building'],
    ['Products',       $products,   'bi-box'],
    ['Units in stock', $units,      'bi-stack'],
    ['Users',          $users,      'bi-people-fill'],
    ['Open POs',       $openPOs,    'bi-cart-plus'],
    ['Open shipments', $openShips,  'bi-send'],
    ['Expired batches',$expired,    'bi-calendar-x'],
    ['Quarantine',     $quarantine, 'bi-shield-exclamation'],
  ];
  foreach ($kpis as [$label, $val, $icon]): ?>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:18px;display:flex;align-items:center;gap:14px;">
      <div style="width:46px;height:46px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.3rem;">
        <i class="bi <?= $icon ?>"></i>
      </div>
      <div>
        <div style="font-size:1.7rem;font-weight:800;" class="counter" data-target="<?= $val ?>">0</div>
        <div style="font-size:.75rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;"><?= htmlspecialchars($label) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ============ SYSTEM HEALTH + QUICK LINKS ============ -->
<div class="row g-3 mb-4">
  <div class="col-md-7 d-flex">
    <div class="table-card" style="width:100%;">
      <h5><i class="bi bi-activity"></i> System Health</h5>
      <table>
        <tbody>
          <tr><td>DB size</td>                <td><strong><?= $dbSizeMb ?> MB</strong></td></tr>
          <tr><td>Movements today</td>        <td><strong><?= number_format($mvToday) ?></strong></td></tr>
          <tr><td>Movements (last 7 days)</td><td><strong><?= number_format($mv7d) ?></strong></td></tr>
          <tr><td>Pending approvals</td>      <td><strong><?= number_format($pending) ?></strong></td></tr>
          <tr><td>Active users (7 days)</td>  <td><strong><?= number_format($active7d) ?></strong></td></tr>
          <tr>
            <td>Disk usage</td>
            <td>
              <?php if ($diskPct !== null): ?>
                <strong><?= $diskPct ?>%</strong>
                <span style="color:var(--text-muted);font-size:.85rem;">
                  (<?= $diskFreeG ?> GB free of <?= $diskTotG ?> GB)
                </span>
              <?php else: ?>
                <span style="color:var(--text-muted);">unavailable</span>
              <?php endif; ?>
            </td>
          </tr>
          <tr><td>Last backup</td>            <td><span style="color:var(--text-muted);">not tracked</span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-md-5 d-flex">
    <div class="table-card" style="width:100%;display:flex;flex-direction:column;">
      <h5><i class="bi bi-lightning-charge"></i> Quick Links</h5>
      <div style="display:grid;grid-template-columns:1fr 1fr;grid-auto-rows:1fr;gap:10px;flex:1;">
        <a href="users.php"         class="btn-add" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;height:100%;"><i class="bi bi-people"></i> Users</a>
        <a href="warehouses.php"    class="btn-add" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;height:100%;"><i class="bi bi-building"></i> Warehouses</a>
        <a href="locations.php"     class="btn-add" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;height:100%;"><i class="bi bi-geo-alt"></i> Locations</a>
        <a href="products.php"      class="btn-add" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;height:100%;"><i class="bi bi-box"></i> Products</a>
        <a href="categories.php"    class="btn-add" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;height:100%;"><i class="bi bi-tags"></i> Categories</a>
        <a href="suppliers.php"     class="btn-add" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;height:100%;"><i class="bi bi-truck"></i> Suppliers</a>
        <a href="audit_logs.php"    class="btn-add" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;height:100%;grid-column:span 2;"><i class="bi bi-journal-text"></i> Audit Logs</a>
      </div>
    </div>
  </div>
</div>

<!-- ============ SECURITY & ATTENDANCE (NEW) ============ -->
<div class="table-card">
  <h5><i class="bi bi-shield-check"></i> Security &amp; Attendance</h5>
  <div class="row g-3">

    <div class="col-6 col-md-3">
      <div style="padding:14px;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;flex-shrink:0;">
          <i class="bi bi-shield-exclamation"></i>
        </div>
        <div>
          <div style="font-size:1.4rem;font-weight:800;"><?= $failedToday ?></div>
          <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Failed logins today</div>
          <?php if ($failed7d > 0): ?>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;"><?= $failed7d ?> in last 7 days</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div style="padding:14px;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;flex-shrink:0;">
          <i class="bi bi-person-check-fill"></i>
        </div>
        <div>
          <div style="font-size:1.4rem;font-weight:800;"><?= $onShiftNow ?></div>
          <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">On shift now</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div style="padding:14px;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;flex-shrink:0;">
          <i class="bi bi-calendar-check"></i>
        </div>
        <div>
          <div style="font-size:1.4rem;font-weight:800;"><?= $shiftsToday ?></div>
          <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Shifts today</div>
          <?php if ($shiftsWeek > 0): ?>
            <div style="font-size:.7rem;color:var(--text-muted);margin-top:2px;"><?= $shiftsWeek ?> next 7 days</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div style="padding:14px;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;gap:12px;">
        <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;flex-shrink:0;">
          <i class="bi bi-journal-text"></i>
        </div>
        <div>
          <div style="font-size:1.4rem;font-weight:800;"><?= $pending ?></div>
          <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Pending approvals</div>
        </div>
      </div>
    </div>

  </div>

  <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
    <a href="failed_logins.php" class="btn-add" style="background:#dc2626;color:#fff;text-decoration:none;">
      <i class="bi bi-shield-exclamation"></i> View failed logins
    </a>
    <a href="attendance.php" class="btn-add" style="background:#16a34a;color:#fff;text-decoration:none;">
      <i class="bi bi-people"></i> View attendance
    </a>
    <a href="shifts.php" class="btn-add" style="background:#2563eb;color:#fff;text-decoration:none;">
      <i class="bi bi-calendar-week"></i> View shifts
    </a>
  </div>
</div>

<!-- ============ USER MANAGEMENT SUMMARY ============ -->
<div class="table-card">
  <h5>
    <i class="bi bi-person-badge"></i> User Management
    <a href="users.php" class="btn-add" style="float:right;text-decoration:none;font-size:.85rem;">Manage →</a>
  </h5>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Username</th><th>Role</th><th>Email</th><th>Last login</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($userList as $u): ?>
        <tr>
          <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
          <td><?= role_badge($u['role']) ?></td>
          <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
          <td><?= htmlspecialchars(ago($u['last_login'])) ?></td>
          <td>
            <?php if ((int)$u['is_active'] === 1): ?>
              <span class="status-badge status-active">Active</span>
            <?php else: ?>
              <span class="status-badge status-cancelled">Disabled</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============ STOCK BY WAREHOUSE ============ -->
<div class="table-card">
  <h5><i class="bi bi-building"></i> Stock by Warehouse</h5>
  <?php if (!$byWh): ?>
    <p style="color:var(--text-muted);">No warehouse data.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Warehouse</th><th>City</th><th>SKUs</th><th>Units</th></tr></thead>
      <tbody>
      <?php foreach ($byWh as $w): ?>
        <tr>
          <td><?= htmlspecialchars($w['name']) ?></td>
          <td><?= htmlspecialchars($w['city'] ?? '—') ?></td>
          <td><?= (int)$w['skus'] ?></td>
          <td><strong><?= number_format((int)$w['units']) ?></strong></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ============ RECENT ACTIVITY ============ -->
<div class="table-card">
  <h5>
    <i class="bi bi-clock-history"></i> Recent Activity
    <a href="audit_logs.php" class="btn-add" style="float:right;text-decoration:none;font-size:.85rem;">View all →</a>
  </h5>
  <?php if (!$audit): ?>
    <p style="color:var(--text-muted);">No activity logged yet.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>User</th><th>Action</th><th>Details</th><th>When</th></tr></thead>
      <tbody>
      <?php foreach ($audit as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['username'] ?? 'system') ?></td>
          <td><span class="status-badge status-active"><?= htmlspecialchars($a['action']) ?></span></td>
          <td><?= htmlspecialchars($a['description'] ?? '') ?></td>
          <td><?= htmlspecialchars(ago($a['timestamp'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
