<?php
// my_tasks.php — Staff work queue
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$uid   = (int)$_SESSION['user_id'];
$uname = $_SESSION['username'] ?? '';

function one(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function all(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }

// POs to receive
$posToReceive = all($pdo,
    "SELECT po.id, po.order_date, po.expected_date,
            s.name AS supplier_name, w.name AS warehouse_name,
            (SELECT COUNT(*) FROM purchase_order_items poi WHERE poi.purchase_order_id = po.id) AS `lines`
     FROM purchase_orders po
     LEFT JOIN suppliers  s ON s.id = po.supplier_id
     LEFT JOIN warehouses w ON w.id = po.warehouse_id
     WHERE po.status = 'pending'
     ORDER BY po.expected_date ASC, po.id DESC
     LIMIT 20"
);

// Shipments to pick
$shipsToPick = all($pdo,
    "SELECT sh.id, sh.customer_name, sh.shipment_date,
            w.name AS warehouse_name,
            (SELECT COUNT(*) FROM shipment_items si WHERE si.shipment_id = sh.id) AS `lines`
     FROM shipments sh
     LEFT JOIN warehouses w ON w.id = sh.warehouse_id
     WHERE sh.status = 'pending'
     ORDER BY sh.shipment_date ASC, sh.id DESC
     LIMIT 20"
);

// Bins due for count
$binsToCount = all($pdo,
    "SELECT l.id, l.code, l.name, w.name AS warehouse_name,
            (SELECT COUNT(*) FROM inventory i WHERE i.location_id = l.id AND i.quantity > 0) AS skus,
            MAX(i.last_counted_at) AS last_counted
     FROM locations l
     JOIN warehouses w ON w.id = l.warehouse_id
     JOIN inventory  i ON i.location_id = l.id
     WHERE l.type = 'storage' AND i.quantity > 0
     GROUP BY l.id, l.code, l.name, w.name
     HAVING MAX(i.last_counted_at) IS NULL OR MAX(i.last_counted_at) < DATE_SUB(NOW(), INTERVAL 30 DAY)
     ORDER BY w.name, l.code
     LIMIT 20"
);

// Quarantine queue
$quarQueue = all($pdo,
    "SELECT b.id, b.batch_number, b.quantity, b.expiry_date,
            p.name AS product_name, l.code AS bin_code, w.name AS warehouse_name
     FROM product_batches b
     LEFT JOIN products  p ON p.id = b.product_id
     LEFT JOIN locations l ON l.id = b.location_id
     LEFT JOIN warehouses w ON w.id = l.warehouse_id
     WHERE b.status = 'quarantine'
     ORDER BY b.expiry_date IS NULL, b.expiry_date ASC
     LIMIT 20"
);

// My pending approvals
$myPending = all($pdo,
    "SELECT m.id, m.movement_type, m.quantity, m.reason, m.reference, m.timestamp,
            p.name AS product_name
     FROM stock_movements m
     LEFT JOIN products p ON p.id = m.product_id
     WHERE m.user_id = ? AND m.approval_status = 'pending'
     ORDER BY m.id DESC
     LIMIT 20",
    [$uid]
);

// My recent activity
$myRecent = all($pdo,
    "SELECT m.id, m.movement_type, m.quantity, m.reference, m.timestamp,
            p.name AS product_name
     FROM stock_movements m
     LEFT JOIN products p ON p.id = m.product_id
     WHERE m.user_id = ?
     ORDER BY m.id DESC
     LIMIT 15",
    [$uid]
);

$page_title = 'My Tasks';
include 'warehouse_header.php';
?>

<div style="padding:16px 20px;border-radius:12px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);margin-bottom:20px;">
  <strong style="color:var(--text);"><i class="bi bi-list-check" style="color:var(--primary);"></i> Welcome back, <?= htmlspecialchars($uname) ?>.</strong>
  <span style="color:var(--text-muted);margin-left:8px;">Here's your work queue.</span>
</div>

<!-- Counters -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['POs to receive',    count($posToReceive), 'bi-box-arrow-in-down',  '#16a34a'],
    ['Shipments to pick', count($shipsToPick),  'bi-box-arrow-up',       '#2563eb'],
    ['Bins to count',     count($binsToCount),  'bi-clipboard-check',    '#f59e0b'],
    ['In quarantine',     count($quarQueue),    'bi-shield-exclamation', '#7c3aed'],
    ['Awaiting approval', count($myPending),    'bi-hourglass-split',    '#dc2626'],
  ];
  foreach ($cards as [$label, $val, $icon, $color]): ?>
  <div class="col-6 col-md-4 col-lg">
    <div class="table-card" style="padding:14px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(0,0,0,.05);color:<?= $color ?>;display:grid;place-items:center;font-size:1.2rem;">
        <i class="bi <?= $icon ?>"></i>
      </div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $val ?></div>
        <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;"><?= htmlspecialchars($label) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- POs -->
<div class="table-card">
  <h5><i class="bi bi-box-arrow-in-down"></i> Purchase Orders to Receive (<?= count($posToReceive) ?>)</h5>
  <?php if (!$posToReceive): ?>
    <p style="color:var(--text-muted);">No POs waiting to receive. 👍</p>
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
          <td><a class="btn-add" href="receive_po.php?id=<?= (int)$po['id'] ?>" style="padding:4px 10px;font-size:.8rem;"><i class="bi bi-box-arrow-in-down"></i> Receive</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Shipments -->
<div class="table-card">
  <h5><i class="bi bi-box-arrow-up"></i> Shipments to Pick (<?= count($shipsToPick) ?>)</h5>
  <?php if (!$shipsToPick): ?>
    <p style="color:var(--text-muted);">No shipments waiting. 👍</p>
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
          <td><a class="btn-add" href="pick_shipment.php?id=<?= (int)$s['id'] ?>" style="padding:4px 10px;font-size:.8rem;"><i class="bi bi-box-arrow-up"></i> Pick</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Bins to count -->
<div class="table-card">
  <h5><i class="bi bi-clipboard-check"></i> Bins Due for Cycle Count (<?= count($binsToCount) ?>)</h5>
  <?php if (!$binsToCount): ?>
    <p style="color:var(--text-muted);">All bins counted recently. 👍</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Bin</th><th>Name</th><th>Warehouse</th><th>SKUs</th><th>Last counted</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($binsToCount as $b): ?>
        <tr>
          <td><code><?= htmlspecialchars($b['code']) ?></code></td>
          <td><?= htmlspecialchars($b['name']) ?></td>
          <td><?= htmlspecialchars($b['warehouse_name']) ?></td>
          <td><?= (int)$b['skus'] ?></td>
          <td><?= $b['last_counted'] ? htmlspecialchars($b['last_counted']) : '<span class="status-badge status-pending">never</span>' ?></td>
          <td><a class="btn-add" href="cycle_count.php?loc=<?= (int)$b['id'] ?>" style="padding:4px 10px;font-size:.8rem;"><i class="bi bi-clipboard-check"></i> Count</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Quarantine -->
<div class="table-card">
  <h5><i class="bi bi-shield-exclamation"></i> Quarantine Queue (<?= count($quarQueue) ?>)</h5>
  <?php if (!$quarQueue): ?>
    <p style="color:var(--text-muted);">Quarantine is empty. 👍</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>Batch</th><th>Product</th><th>Bin</th><th>Warehouse</th><th>Qty</th><th>Expiry</th><th>Action</th></tr></thead>
      <tbody>
      <?php foreach ($quarQueue as $q): ?>
        <tr>
          <td><code><?= htmlspecialchars($q['batch_number']) ?></code></td>
          <td><?= htmlspecialchars($q['product_name'] ?? '—') ?></td>
          <td><?= $q['bin_code'] ? '<code>' . htmlspecialchars($q['bin_code']) . '</code>' : '—' ?></td>
          <td><?= htmlspecialchars($q['warehouse_name'] ?? '—') ?></td>
          <td><?= (int)$q['quantity'] ?></td>
          <td><?= htmlspecialchars($q['expiry_date'] ?? '—') ?></td>
          <td><a class="btn-add" href="quarantine.php" style="padding:4px 10px;font-size:.8rem;"><i class="bi bi-shield-exclamation"></i> Review</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php if ($myPending): ?>
<div class="table-card">
  <h5><i class="bi bi-hourglass-split"></i> My Actions Awaiting Manager Approval (<?= count($myPending) ?>)</h5>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>When</th><th>Type</th><th>Product</th><th>Qty</th><th>Reason</th><th>Reference</th></tr></thead>
      <tbody>
      <?php foreach ($myPending as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['timestamp']) ?></td>
          <td><span class="status-badge status-pending"><?= htmlspecialchars($m['movement_type']) ?></span></td>
          <td><?= htmlspecialchars($m['product_name'] ?? '—') ?></td>
          <td><?= (int)$m['quantity'] ?></td>
          <td><?= htmlspecialchars($m['reason'] ?? '—') ?></td>
          <td><?= htmlspecialchars($m['reference'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="table-card">
  <h5><i class="bi bi-clock-history"></i> My Recent Activity</h5>
  <?php if (!$myRecent): ?>
    <p style="color:var(--text-muted);">You have no movements yet.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead><tr><th>When</th><th>Type</th><th>Product</th><th>Qty</th><th>Reference</th></tr></thead>
      <tbody>
      <?php foreach ($myRecent as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['timestamp']) ?></td>
          <td><span class="status-badge status-<?= htmlspecialchars($m['movement_type']) ?>"><?= htmlspecialchars($m['movement_type']) ?></span></td>
          <td><?= htmlspecialchars($m['product_name'] ?? '—') ?></td>
          <td><?= (int)$m['quantity'] ?></td>
          <td><?= htmlspecialchars($m['reference'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>

