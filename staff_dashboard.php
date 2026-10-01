<?php
// staff_dashboard.php — Staff work hub (real tasks + actions)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('staff_dashboard');

$page_title = 'Staff Dashboard — My Work';
include 'warehouse_header.php';

function one(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function all(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }

$uid = (int)$_SESSION['user_id'];

// ---------- My personal data (for new cards) ----------
$myNextShift = $pdo->prepare(
    "SELECT shift_date, shift_type, starts_at, ends_at, notes
     FROM shifts
     WHERE user_id = ? AND shift_date >= CURDATE()
     ORDER BY shift_date ASC, starts_at ASC
     LIMIT 1"
);
$myNextShift->execute([$uid]);
$myNextShift = $myNextShift->fetch(PDO::FETCH_ASSOC) ?: null;

$myOpenStmt = $pdo->prepare(
    "SELECT id, clock_in_at FROM timeclock
     WHERE user_id = ? AND clock_out_at IS NULL
     ORDER BY id DESC LIMIT 1"
);
$myOpenStmt->execute([$uid]);
$myOpen = $myOpenStmt->fetch(PDO::FETCH_ASSOC) ?: null;

$weekStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(hours_worked),0) FROM timeclock
     WHERE user_id = ? AND clock_in_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
);
$weekStmt->execute([$uid]);
$myWeekHours = (float)$weekStmt->fetchColumn();

$msgStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM messages WHERE user_id = ? AND status = 'pending'"
);
$msgStmt->execute([$uid]);
$myPendingMsgs = (int)$msgStmt->fetchColumn();

// ---------- Original data ----------
$posToReceive = all($pdo,
    "SELECT po.id, po.order_date, po.expected_date, s.name AS supplier_name, w.name AS warehouse_name,
               (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.purchase_order_id = po.id) AS `lines`
     FROM purchase_orders po
     LEFT JOIN suppliers  s ON s.id = po.supplier_id
     LEFT JOIN warehouses w ON w.id = po.warehouse_id
     WHERE po.status = 'pending'
     ORDER BY po.expected_date ASC, po.id DESC
     LIMIT 15"
);

$shipsToPick = all($pdo,
    "SELECT sh.id, sh.customer_name, sh.shipment_date, w.name AS warehouse_name,
            (SELECT COUNT(*) FROM shipment_items si WHERE si.shipment_id = sh.id) AS `lines`
     FROM shipments sh
     LEFT JOIN warehouses w ON w.id = sh.warehouse_id
     WHERE sh.status = 'pending'
     ORDER BY sh.shipment_date ASC, sh.id DESC
     LIMIT 15"
);

$lowStock = all($pdo,
    "SELECT p.id, p.name, p.sku, p.reorder_point,
            COALESCE((SELECT SUM(i.quantity) FROM inventory i WHERE i.product_id = p.id),0) AS on_hand
     FROM products p
     WHERE p.reorder_point IS NOT NULL
     HAVING on_hand <= p.reorder_point
     ORDER BY on_hand ASC
     LIMIT 10"
);

$expiring = all($pdo,
    "SELECT b.id, b.batch_number, b.expiry_date, b.quantity, b.location_id, p.name AS product_name
     FROM product_batches b
     LEFT JOIN products p ON p.id = b.product_id
     WHERE b.status = 'available'
       AND b.expiry_date IS NOT NULL
       AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
     ORDER BY b.expiry_date ASC
     LIMIT 10"
);

$myMovesCount = (int) one($pdo, 'SELECT COUNT(*) FROM stock_movements WHERE user_id = ?', [$uid]);
$openPOs      = (int) one($pdo, "SELECT COUNT(*) FROM purchase_orders WHERE status='pending'");
$openShips    = (int) one($pdo, "SELECT COUNT(*) FROM shipments WHERE status='pending'");
$quarantine   = (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='quarantine'");
$expired      = (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())");
?>

<!-- KPI row -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['My movements',    $myMovesCount, 'bi-arrow-repeat'],
    ['POs to receive',  $openPOs,      'bi-box-arrow-in-down'],
    ['Shipments to pick',$openShips,   'bi-box-arrow-up'],
    ['Quarantine',      $quarantine,   'bi-shield-exclamation'],
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

<!-- ============ MY SHIFT TODAY ============ -->
<div class="table-card">
  <h5><i class="bi bi-person-badge"></i> My Shift</h5>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:8px;">

    <div style="padding:12px 16px;background:var(--card-bg);border-radius:12px;border:1px solid var(--border);">
      <div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:.5px;">Next shift</div>
      <?php if ($myNextShift): ?>
        <div style="font-size:1.1rem;font-weight:800;margin-top:4px;text-transform:capitalize;">
          <?= htmlspecialchars($myNextShift['shift_type']) ?>
        </div>
        <div style="font-size:.85rem;color:var(--text-muted);margin-top:2px;">
          <?= htmlspecialchars(date('d-M-Y', strtotime($myNextShift['shift_date']))) ?>
          · <?= htmlspecialchars(substr($myNextShift['starts_at'],0,5)) ?>–<?= htmlspecialchars(substr($myNextShift['ends_at'],0,5)) ?>
        </div>
      <?php else: ?>
        <div style="font-size:1rem;font-weight:600;margin-top:6px;color:var(--text-muted);">No upcoming shift</div>
      <?php endif; ?>
    </div>

    <div style="padding:12px 16px;background:var(--card-bg);border-radius:12px;border:1px solid var(--border);">
      <div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:.5px;">Clock status</div>
      <?php if ($myOpen): ?>
        <div style="font-size:1.1rem;font-weight:800;margin-top:4px;color:#16a34a;">✓ Clocked in</div>
        <div style="font-size:.85rem;color:var(--text-muted);margin-top:2px;">
          since <?= htmlspecialchars(date('H:i', strtotime($myOpen['clock_in_at']))) ?>
        </div>
      <?php else: ?>
        <div style="font-size:1.1rem;font-weight:800;margin-top:4px;color:var(--text-muted);">Not clocked in</div>
        <div style="font-size:.85rem;color:var(--text-muted);margin-top:2px;">Ready to start?</div>
      <?php endif; ?>
    </div>

    <div style="padding:12px 16px;background:var(--card-bg);border-radius:12px;border:1px solid var(--border);">
      <div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:.5px;">Hours this week</div>
      <div style="font-size:1.4rem;font-weight:800;margin-top:4px;">
        <?= number_format($myWeekHours, 2, ',', ' ') ?> <span style="font-size:.9rem;font-weight:500;color:var(--text-muted);">h</span>
      </div>
    </div>

    <div style="padding:12px 16px;background:var(--card-bg);border-radius:12px;border:1px solid var(--border);">
      <div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:.5px;">Pending messages</div>
      <div style="font-size:1.4rem;font-weight:800;margin-top:4px;color:<?= $myPendingMsgs > 0 ? 'var(--primary)' : 'var(--text)'; ?>;">
        <?= $myPendingMsgs ?>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
    <a href="timeclock.php" class="btn-add" style="background:#16a34a;color:#fff;text-decoration:none;">
      <i class="bi bi-clock"></i> <?= $myOpen ? 'Clock Out' : 'Clock In' ?>
    </a>
    <a href="shifts.php" class="btn-add" style="background:#2563eb;color:#fff;text-decoration:none;">
      <i class="bi bi-calendar-week"></i> My Shifts
    </a>
    <a href="messages.php" class="btn-add" style="background:#7c3aed;color:#fff;text-decoration:none;">
      <i class="bi bi-chat-dots"></i> Messages<?= $myPendingMsgs > 0 ? ' (' . $myPendingMsgs . ')' : '' ?>
    </a>
  </div>
</div>

<!-- ============ MY RECENT ACTIVITY ============ -->
<?php
$recentStmt = $pdo->prepare(
    "SELECT m.id, m.movement_type, m.quantity, m.timestamp, m.reference,
            p.name AS product_name, p.sku
     FROM stock_movements m
     LEFT JOIN products p ON p.id = m.product_id
     WHERE m.user_id = ?
     ORDER BY m.id DESC
     LIMIT 10"
);
$recentStmt->execute([$uid]);
$myRecent = $recentStmt->fetchAll();
?>

<div class="table-card">
  <h5><i class="bi bi-activity"></i> My Recent Activity (last 10 movements)</h5>
  <?php if (!$myRecent): ?>
    <p style="color:var(--text-muted);">No activity yet.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th>When</th>
          <th>Type</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Reference</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($myRecent as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['timestamp']) ?></td>
          <td><span class="status-badge status-<?= htmlspecialchars($r['movement_type']) ?>"><?= htmlspecialchars($r['movement_type']) ?></span></td>
          <td><?= htmlspecialchars($r['product_name'] ?? '—') ?>
              <span style="color:var(--text-muted);font-size:.75rem;"><?= htmlspecialchars($r['sku'] ?? '') ?></span></td>
          <td><?= (int)$r['quantity'] ?></td>
          <td><?= htmlspecialchars($r['reference'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Inbound: POs to receive -->
<div class="table-card">
  <h5><i class="bi bi-box-arrow-in-down"></i> Inbound — Purchase Orders Waiting to Receive</h5>
  <?php if (!$posToReceive): ?>
    <p style="color:var(--text-muted);">No POs waiting. Good job. 👍</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>PO</th><th>Supplier</th><th>Warehouse</th><th>Expected</th><th>Lines</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($posToReceive as $po): ?>
        <tr>
          <td>PO-<?= (int)$po['id'] ?></td>
          <td><?= htmlspecialchars($po['supplier_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($po['warehouse_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($po['expected_date'] ?? '—') ?></td>
          <td><?= (int)$po['lines'] ?></td>
          <td><a class="btn-add" href="receive_po.php?id=<?= (int)$po['id'] ?>"><i class="bi bi-box-arrow-in-down"></i> Receive</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Outbound: shipments to pick -->
<div class="table-card">
  <h5><i class="bi bi-box-arrow-up"></i> Outbound — Shipments Waiting to Pick</h5>
  <?php if (!$shipsToPick): ?>
    <p style="color:var(--text-muted);">No pending shipments.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Shipment</th><th>Customer</th><th>Warehouse</th><th>Ship date</th><th>Lines</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($shipsToPick as $s): ?>
        <tr>
          <td>SH-<?= (int)$s['id'] ?></td>
          <td><?= htmlspecialchars($s['customer_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['warehouse_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($s['shipment_date'] ?? '—') ?></td>
          <td><?= (int)$s['lines'] ?></td>
          <td><a class="btn-add" href="pick_shipment.php?id=<?= (int)$s['id'] ?>"><i class="bi bi-box-arrow-up"></i> Pick</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Reorder alerts -->
<div class="table-card">
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

<!-- Expiring soon -->
<div class="table-card">
  <h5><i class="bi bi-calendar-week"></i> Batches Expiring in 30 Days</h5>
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

<?php include 'warehouse_footer.php'; ?>
