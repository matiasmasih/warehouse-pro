<?php
// manager_dashboard.php — Manager operations board
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('manager_dashboard');

$page_title = 'Manager Dashboard — Operations';
include 'warehouse_header.php';

function one(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function all(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }

$uid = (int)$_SESSION['user_id'];

// ---- KPI counts ----
$pendingPOs   = (int) one($pdo, "SELECT COUNT(*) FROM purchase_orders WHERE status='pending'");
$pendingShips = (int) one($pdo, "SELECT COUNT(*) FROM shipments WHERE status='pending'");
$expiring30   = (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='available' AND expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$expired      = (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())");
$quarantine   = (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='quarantine'");
$stockValue   = (float) one($pdo, "SELECT COALESCE(SUM(i.quantity * COALESCE(p.unit_price,0)),0) FROM inventory i JOIN products p ON p.id = i.product_id");

// ---- today's throughput ----
$inboundToday  = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE movement_type='inbound'    AND DATE(timestamp)=CURDATE()");
$outboundToday = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE movement_type='outbound'   AND DATE(timestamp)=CURDATE()");
$transferToday = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE movement_type='transfer'   AND DATE(timestamp)=CURDATE()");
$adjustToday   = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE movement_type='adjustment' AND DATE(timestamp)=CURDATE()");

// ---- Today's team (NEW) ----
$todayShifts = all($pdo,
    "SELECT s.id, s.shift_type, s.starts_at, s.ends_at, s.notes,
            u.id AS user_id, u.username, u.role AS user_role,
            (SELECT COUNT(*) FROM timeclock t
             WHERE t.user_id = s.user_id
               AND DATE(t.clock_in_at) = CURDATE()
               AND t.clock_out_at IS NULL) AS clocked_in_now,
            (SELECT t.clock_in_at FROM timeclock t
             WHERE t.user_id = s.user_id
               AND DATE(t.clock_in_at) = CURDATE()
             ORDER BY t.id DESC LIMIT 1) AS last_clock_in,
            (SELECT t.clock_out_at FROM timeclock t
             WHERE t.user_id = s.user_id
               AND DATE(t.clock_in_at) = CURDATE()
             ORDER BY t.id DESC LIMIT 1) AS last_clock_out
     FROM shifts s
     LEFT JOIN users u ON u.id = s.user_id
     WHERE s.shift_date = CURDATE()
     ORDER BY FIELD(s.shift_type, 'morning', 'evening', 'night'), u.username"
);

$teamByType = ['morning' => [], 'evening' => [], 'night' => []];
foreach ($todayShifts as $row) {
    if (isset($teamByType[$row['shift_type']])) {
        $teamByType[$row['shift_type']][] = $row;
    }
}
$shiftsCovered = count(array_filter($teamByType, fn($a) => count($a) > 0));
$clockedInNow = (int) one($pdo, "SELECT COUNT(*) FROM timeclock WHERE clock_out_at IS NULL AND DATE(clock_in_at) = CURDATE()");

// ---- pending POs ----
$pos = all($pdo,
    "SELECT po.id, po.order_date, po.expected_date, po.total_amount,
            s.name AS supplier_name, w.name AS warehouse_name, w.city
     FROM purchase_orders po
     LEFT JOIN suppliers  s ON s.id = po.supplier_id
     LEFT JOIN warehouses w ON w.id = po.warehouse_id
     WHERE po.status = 'pending'
     ORDER BY po.expected_date ASC, po.id DESC
     LIMIT 12"
);

// ---- pending shipments ----
$ships = all($pdo,
    "SELECT sh.id, sh.customer_name, sh.shipment_date, sh.tracking_number,
            w.name AS warehouse_name
     FROM shipments sh
     LEFT JOIN warehouses w ON w.id = sh.warehouse_id
     WHERE sh.status = 'pending'
     ORDER BY sh.shipment_date ASC, sh.id DESC
     LIMIT 12"
);

// ---- low stock ----
$lowStock = all($pdo,
    "SELECT p.id, p.name, p.sku, p.reorder_point,
            COALESCE(SUM(i.quantity),0) AS on_hand
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.id
     WHERE p.reorder_point IS NOT NULL
     GROUP BY p.id, p.name, p.sku, p.reorder_point
     HAVING on_hand <= p.reorder_point
     ORDER BY on_hand ASC
     LIMIT 12"
);

// ---- expiring soon ----
$expiring = all($pdo,
    "SELECT b.batch_number, b.expiry_date, b.quantity,
            p.name AS product_name, l.name AS location_name, w.name AS warehouse_name
     FROM product_batches b
     LEFT JOIN products  p ON p.id = b.product_id
     LEFT JOIN locations l ON l.id = b.location_id
     LEFT JOIN warehouses w ON w.id = l.warehouse_id
     WHERE b.status='available'
       AND b.expiry_date IS NOT NULL
       AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY b.expiry_date ASC
     LIMIT 12"
);

// ---- quarantine ----
$quar = all($pdo,
    "SELECT b.id, b.batch_number, b.quantity, b.expiry_date,
            p.name AS product_name, l.name AS location_name
     FROM product_batches b
     LEFT JOIN products  p ON p.id = b.product_id
     LEFT JOIN locations l ON l.id = b.location_id
     WHERE b.status='quarantine'
     ORDER BY b.id DESC
     LIMIT 12"
);

// ---- recent movements ----
$activity = all($pdo,
    "SELECT m.id, m.movement_type, m.quantity, m.timestamp, m.reference,
            p.name AS product_name, u.username
     FROM stock_movements m
     LEFT JOIN products p ON p.id = m.product_id
     LEFT JOIN users    u ON u.id = m.user_id
     ORDER BY m.id DESC
     LIMIT 12"
);

// ---- warehouse performance ----
$perWh = all($pdo,
    "SELECT w.id, w.name, w.code, w.city,
            (SELECT COUNT(DISTINCT i.product_id) FROM inventory i JOIN locations l ON l.id=i.location_id WHERE l.warehouse_id=w.id AND i.quantity>0) AS skus,
            COALESCE((SELECT SUM(i.quantity) FROM inventory i JOIN locations l ON l.id=i.location_id WHERE l.warehouse_id=w.id),0) AS units,
            (SELECT COUNT(*) FROM stock_movements m JOIN locations l ON l.id=COALESCE(m.from_location,m.to_location) WHERE l.warehouse_id=w.id AND DATE(m.timestamp)=CURDATE()) AS moves_today
     FROM warehouses w
     ORDER BY units DESC"
);

// ---- team activity ----
$team = all($pdo,
    "SELECT u.id, u.username, u.role,
            (SELECT COUNT(*) FROM stock_movements m WHERE m.user_id=u.id AND m.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS moves_7d,
            (SELECT COUNT(*) FROM stock_movements m WHERE m.user_id=u.id AND DATE(m.timestamp)=CURDATE()) AS moves_today
     FROM users u
     ORDER BY moves_7d DESC"
);
?>

<!-- KPI row -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['Pending POs',       $pendingPOs,   'bi-cart-plus'],
    ['Pending shipments', $pendingShips, 'bi-send'],
    ['Expiring 30d',      $expiring30,   'bi-calendar-week'],
    ['Expired',           $expired,      'bi-calendar-x'],
    ['Quarantine',        $quarantine,   'bi-shield-exclamation'],
    ['Stock value',       number_format($stockValue, 2, ',', ' ') . ' €', 'bi-currency-euro'],
  ];
  foreach ($kpis as [$label, $val, $icon]): ?>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi <?= $icon ?>"></i>
      </div>
      <div>
        <?php if (is_numeric($val)): ?>
          <div style="font-size:1.5rem;font-weight:800;" class="counter" data-target="<?= (int)$val ?>">0</div>
        <?php else: ?>
          <div style="font-size:1.3rem;font-weight:800;"><?= htmlspecialchars($val) ?></div>
        <?php endif; ?>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;"><?= htmlspecialchars($label) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Today's throughput -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:14px;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Inbound today</div>
        <div style="font-size:1.5rem;font-weight:800;color:#16a34a;"><?= $inboundToday ?></div>
      </div>
      <i class="bi bi-box-arrow-in-down" style="font-size:1.6rem;color:#16a34a;opacity:.6;"></i>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:14px;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Outbound today</div>
        <div style="font-size:1.5rem;font-weight:800;color:#2563eb;"><?= $outboundToday ?></div>
      </div>
      <i class="bi bi-box-arrow-up" style="font-size:1.6rem;color:#2563eb;opacity:.6;"></i>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:14px;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Transfers today</div>
        <div style="font-size:1.5rem;font-weight:800;color:#7c3aed;"><?= $transferToday ?></div>
      </div>
      <i class="bi bi-arrow-left-right" style="font-size:1.6rem;color:#7c3aed;opacity:.6;"></i>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:14px;display:flex;justify-content:space-between;align-items:center;">
      <div>
        <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Adjustments today</div>
        <div style="font-size:1.5rem;font-weight:800;color:#dc2626;"><?= $adjustToday ?></div>
      </div>
      <i class="bi bi-sliders" style="font-size:1.6rem;color:#dc2626;opacity:.6;"></i>
    </div>
  </div>
</div>

<!-- ============ TODAY'S TEAM (NEW) ============ -->
<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
    <h5 style="margin:0;"><i class="bi bi-people-fill"></i> Today's Team — <?= htmlspecialchars(date('d-M-Y')) ?></h5>
    <div style="display:flex;gap:10px;align-items:center;">
      <span class="status-badge status-active" style="font-size:.8rem;">
        <i class="bi bi-person-check-fill"></i> <?= $clockedInNow ?> on shift now
      </span>
      <span class="status-badge status-pending" style="font-size:.8rem;">
        <i class="bi bi-calendar-check"></i> <?= $shiftsCovered ?> of 3 shifts covered
      </span>
      <a href="shifts.php" class="btn-add" style="font-size:.8rem;padding:4px 10px;text-decoration:none;">
        <i class="bi bi-arrow-right"></i> Manage shifts
      </a>
    </div>
  </div>

  <div class="row g-3">
    <?php
    $shiftMeta = [
      'morning' => ['label' => 'Morning', 'time' => '06:00 – 14:00', 'icon' => 'bi-sunrise',     'color' => '#2563eb'],
      'evening' => ['label' => 'Evening', 'time' => '14:00 – 22:00', 'icon' => 'bi-sunset',      'color' => '#f59e0b'],
      'night'   => ['label' => 'Night',   'time' => '22:00 – 06:00', 'icon' => 'bi-moon-stars',  'color' => '#7c3aed'],
    ];
    foreach ($shiftMeta as $type => $meta):
        $staff = $teamByType[$type] ?? [];
    ?>
    <div class="col-md-4">
      <div style="padding:14px;border:1px solid var(--border);border-radius:12px;background:var(--card-bg);height:100%;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:10px;border-bottom:1px solid var(--border);">
          <div style="width:38px;height:38px;border-radius:10px;background:<?= $meta['color'] ?>22;color:<?= $meta['color'] ?>;display:grid;place-items:center;font-size:1.15rem;">
            <i class="bi <?= $meta['icon'] ?>"></i>
          </div>
          <div>
            <div style="font-weight:700;"><?= $meta['label'] ?></div>
            <div style="font-size:.75rem;color:var(--text-muted);"><?= $meta['time'] ?></div>
          </div>
        </div>

        <?php if (!$staff): ?>
          <p style="color:#dc2626;font-size:.85rem;margin:0;">
            <i class="bi bi-exclamation-triangle"></i> Nobody assigned
          </p>
        <?php else: ?>
          <?php foreach ($staff as $s): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px dashed var(--border);">
              <div>
                <strong><?= htmlspecialchars($s['username']) ?></strong>
                <span style="color:var(--text-muted);font-size:.75rem;">(<?= htmlspecialchars($s['user_role']) ?>)</span>
              </div>
              <div>
                <?php if ((int)$s['clocked_in_now'] > 0): ?>
                  <span class="status-badge status-active" style="font-size:.7rem;">
                    ✓ in <?= htmlspecialchars(date('H:i', strtotime($s['last_clock_in']))) ?>
                  </span>
                <?php elseif ($s['last_clock_out']): ?>
                  <span style="color:var(--text-muted);font-size:.75rem;">
                    out <?= htmlspecialchars(date('H:i', strtotime($s['last_clock_out']))) ?>
                  </span>
                <?php else: ?>
                  <span class="status-badge status-pending" style="font-size:.7rem;">not in yet</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Pending POs + Shipments side by side -->
<div class="row g-3 mb-4">
  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h5 style="margin:0;"><i class="bi bi-cart-plus"></i> Purchase Orders Waiting</h5>
        <a href="purchase_orders.php" style="font-size:.85rem;color:var(--primary);text-decoration:none;">View all →</a>
      </div>
      <?php if (!$pos): ?>
        <p style="color:var(--text-muted);">No pending POs.</p>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>PO</th><th>Supplier</th><th>Expected</th><th>Total</th></tr></thead>
          <tbody>
          <?php foreach ($pos as $po): ?>
            <tr>
              <td>PO-<?= (int)$po['id'] ?></td>
              <td><?= htmlspecialchars($po['supplier_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($po['expected_date'] ?? '—') ?></td>
              <td><?= number_format((float)$po['total_amount'], 2, ',', ' ') ?> €</td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h5 style="margin:0;"><i class="bi bi-send"></i> Shipments Waiting to Pick</h5>
        <a href="shipments.php" style="font-size:.85rem;color:var(--primary);text-decoration:none;">View all →</a>
      </div>
      <?php if (!$ships): ?>
        <p style="color:var(--text-muted);">No pending shipments.</p>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>Shipment</th><th>Customer</th><th>Ship date</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($ships as $s): ?>
            <tr>
              <td>SH-<?= (int)$s['id'] ?></td>
              <td><?= htmlspecialchars($s['customer_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($s['shipment_date'] ?? '—') ?></td>
              <td><a class="btn-add" href="pick_shipment.php?id=<?= (int)$s['id'] ?>" style="padding:4px 10px;font-size:.8rem;"><i class="bi bi-box-arrow-up"></i> Pick</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Warehouse Performance + Team Activity side by side -->
<div class="row g-3 mb-4">
  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <h5><i class="bi bi-building"></i> Warehouse Performance</h5>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>Code</th><th>City</th><th>SKUs</th><th>Units</th><th>Today</th></tr></thead>
          <tbody>
          <?php foreach ($perWh as $w): ?>
            <tr>
              <td><code><?= htmlspecialchars($w['code']) ?></code></td>
              <td><?= htmlspecialchars($w['city'] ?? '—') ?></td>
              <td><?= (int)$w['skus'] ?></td>
              <td><?= number_format((int)$w['units'], 0, ',', ' ') ?></td>
              <td>
                <?php if ((int)$w['moves_today'] > 0): ?>
                  <span class="status-badge status-active"><?= (int)$w['moves_today'] ?></span>
                <?php else: ?>
                  <span class="status-badge status-pending">0</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <h5><i class="bi bi-people-fill"></i> Team Activity</h5>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>User</th><th>Role</th><th>7d</th><th>Today</th></tr></thead>
          <tbody>
          <?php foreach ($team as $t): ?>
            <tr>
              <td><strong><?= htmlspecialchars($t['username']) ?></strong></td>
              <td><span class="status-badge status-<?= htmlspecialchars($t['role']) ?>"><?= htmlspecialchars($t['role']) ?></span></td>
              <td><?= (int)$t['moves_7d'] ?></td>
              <td>
                <?php if ((int)$t['moves_today'] > 0): ?>
                  <span class="status-badge status-active"><?= (int)$t['moves_today'] ?></span>
                <?php else: ?>
                  <span style="color:var(--text-muted);">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Low stock + Expiring soon side by side -->
<div class="row g-3 mb-4">
  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <h5><i class="bi bi-exclamation-triangle"></i> Reorder Alerts</h5>
      <?php if (!$lowStock): ?>
        <p style="color:var(--text-muted);">All products above reorder point.</p>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>SKU</th><th>Product</th><th>On hand</th><th>Reorder at</th></tr></thead>
          <tbody>
          <?php foreach ($lowStock as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['sku']) ?></td>
              <td><?= htmlspecialchars($p['name']) ?></td>
              <td><span class="status-badge status-pending"><?= (int)$p['on_hand'] ?></span></td>
              <td><?= (int)$p['reorder_point'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <h5><i class="bi bi-calendar-week"></i> Batches Expiring ≤ 30 days</h5>
      <?php if (!$expiring): ?>
        <p style="color:var(--text-muted);">No batches expiring soon.</p>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>Batch</th><th>Product</th><th>Qty</th><th>Expires</th></tr></thead>
          <tbody>
          <?php foreach ($expiring as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['batch_number']) ?></td>
              <td><?= htmlspecialchars($b['product_name'] ?? '—') ?></td>
              <td><?= (int)$b['quantity'] ?></td>
              <td><span class="status-badge status-pending"><?= htmlspecialchars($b['expiry_date']) ?></span></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Quarantine + Recent movements side by side -->
<div class="row g-3 mb-4">
  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h5 style="margin:0;"><i class="bi bi-shield-exclamation"></i> Quarantine</h5>
        <a href="quarantine.php" style="font-size:.85rem;color:var(--primary);text-decoration:none;">Manage →</a>
      </div>
      <?php if (!$quar): ?>
        <p style="color:var(--text-muted);">Nothing in quarantine.</p>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>Batch</th><th>Product</th><th>Qty</th><th>Expiry</th></tr></thead>
          <tbody>
          <?php foreach ($quar as $b): ?>
            <tr>
              <td><?= htmlspecialchars($b['batch_number']) ?></td>
              <td><?= htmlspecialchars($b['product_name'] ?? '—') ?></td>
              <td><?= (int)$b['quantity'] ?></td>
              <td><?= htmlspecialchars($b['expiry_date'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-md-6 d-flex">
    <div class="table-card" style="width:100%;">
      <h5><i class="bi bi-activity"></i> Recent Stock Movements</h5>
      <?php if (!$activity): ?>
        <p style="color:var(--text-muted);">No movements yet.</p>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>User</th><th>Type</th><th>Product</th><th>Qty</th><th>When</th></tr></thead>
          <tbody>
          <?php foreach ($activity as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['username'] ?? '—') ?></td>
              <td><span class="status-badge status-active"><?= htmlspecialchars($a['movement_type']) ?></span></td>
              <td><?= htmlspecialchars($a['product_name'] ?? '—') ?></td>
              <td><?= (int)$a['quantity'] ?></td>
              <td><?= htmlspecialchars($a['timestamp']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'warehouse_footer.php'; ?>
