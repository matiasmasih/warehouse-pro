<?php
// reports.php — Operational + inventory reports (real warehouse)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('reports');

$page_title = 'Reports';
include 'warehouse_header.php';

function one(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function all(PDO $pdo, $sql, $p = []) { $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }

// ---------- Headline KPIs ----------
$totalUnits   = (int) one($pdo, 'SELECT COALESCE(SUM(quantity),0) FROM inventory');
$totalValue   = (float) one($pdo, 'SELECT COALESCE(SUM(i.quantity * COALESCE(p.unit_price,0)),0) FROM inventory i JOIN products p ON p.id = i.product_id');
$totalSKUs    = (int) one($pdo, 'SELECT COUNT(DISTINCT product_id) FROM inventory WHERE quantity > 0');
$totalBatches = (int) one($pdo, 'SELECT COUNT(*) FROM product_batches');
$mvLast30     = (int) one($pdo, "SELECT COUNT(*) FROM stock_movements WHERE timestamp >= NOW() - INTERVAL 30 DAY");
$mvPerDay     = (int) one($pdo, "SELECT ROUND(COUNT(*) / 30) FROM stock_movements WHERE timestamp >= NOW() - INTERVAL 30 DAY");
$deadStockCnt = (int) one($pdo,
    "SELECT COUNT(*) FROM (
        SELECT p.id FROM products p
        JOIN inventory i ON i.product_id = p.id
        LEFT JOIN stock_movements m ON m.product_id = p.id AND m.timestamp >= NOW() - INTERVAL 90 DAY
        WHERE i.quantity > 0 AND m.id IS NULL
        GROUP BY p.id
    ) t");

$lowStockCnt  = (int) one($pdo,
    "SELECT COUNT(*) FROM (
        SELECT p.id, p.reorder_point AS rp, COALESCE(SUM(i.quantity),0) AS on_hand
        FROM products p
        LEFT JOIN inventory i ON i.product_id = p.id
        WHERE p.reorder_point IS NOT NULL
        GROUP BY p.id, p.reorder_point
        HAVING on_hand <= rp
    ) t");

// ---------- Stock by warehouse ----------
$byWh = all($pdo,
    "SELECT w.id, w.name, w.city,
            COUNT(DISTINCT i.product_id) AS skus,
            COALESCE(SUM(i.quantity),0) AS units,
            COALESCE(SUM(i.quantity * COALESCE(p.unit_price,0)),0) AS value
     FROM warehouses w
     LEFT JOIN locations l ON l.warehouse_id = w.id
     LEFT JOIN inventory i ON i.location_id = l.id
     LEFT JOIN products p ON p.id = i.product_id
     GROUP BY w.id, w.name, w.city
     ORDER BY units DESC"
);

// ---------- Stock by category ----------
$byCat = all($pdo,
    "SELECT c.id, c.name,
            COUNT(DISTINCT p.id) AS products,
            COALESCE(SUM(i.quantity),0) AS units,
            COALESCE(SUM(i.quantity * COALESCE(p.unit_price,0)),0) AS value
     FROM categories c
     LEFT JOIN products p ON p.category_id = c.id
     LEFT JOIN inventory i ON i.product_id = p.id
     GROUP BY c.id, c.name
     ORDER BY units DESC"
);

// ---------- Movements last 30 days by type ----------
$byType = all($pdo,
    "SELECT movement_type, COUNT(*) AS cnt, COALESCE(SUM(quantity),0) AS units
     FROM stock_movements
     WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY movement_type"
);

// ---------- Expiry buckets ----------
$exp = [
    'expired'   => (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())"),
    'd30'       => (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='available' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"),
    'd60'       => (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='available' AND expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)"),
    'd90'       => (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='available' AND expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 61 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)"),
    'safe'      => (int) one($pdo, "SELECT COUNT(*) FROM product_batches WHERE status='available' AND (expiry_date IS NULL OR expiry_date > DATE_ADD(CURDATE(), INTERVAL 90 DAY))"),
];

// ---------- Reorder alerts ----------
$reorder = all($pdo,
    "SELECT p.id, p.name, p.sku, p.reorder_point,
            COALESCE(SUM(i.quantity),0) AS on_hand
     FROM products p
     LEFT JOIN inventory i ON i.product_id = p.id
     WHERE p.reorder_point IS NOT NULL
     GROUP BY p.id, p.name, p.sku, p.reorder_point
     HAVING on_hand <= p.reorder_point
     ORDER BY on_hand ASC
     LIMIT 20"
);

// ---------- NEW: Dead stock (no movement in 90 days) ----------
$deadStock = all($pdo,
    "SELECT p.sku, p.name,
            COALESCE(SUM(i.quantity),0) AS on_hand,
            COALESCE(SUM(i.quantity * COALESCE(p.unit_price,0)),0) AS value
     FROM products p
     JOIN inventory i ON i.product_id = p.id
     LEFT JOIN stock_movements m
            ON m.product_id = p.id
           AND m.timestamp >= NOW() - INTERVAL 90 DAY
     WHERE i.quantity > 0 AND m.id IS NULL
     GROUP BY p.id, p.sku, p.name
     ORDER BY value DESC
     LIMIT 15"
);

// ---------- NEW: Top moving products (last 30 days) ----------
$topMovers = all($pdo,
    "SELECT p.sku, p.name,
            SUM(CASE WHEN m.movement_type='inbound'  THEN m.quantity ELSE 0 END) AS inbound,
            SUM(CASE WHEN m.movement_type='outbound' THEN m.quantity ELSE 0 END) AS outbound,
            COUNT(*) AS moves
     FROM stock_movements m
     JOIN products p ON p.id = m.product_id
     WHERE m.timestamp >= NOW() - INTERVAL 30 DAY
     GROUP BY p.id, p.sku, p.name
     ORDER BY moves DESC, inbound DESC, outbound DESC
     LIMIT 10"
);

// ---------- NEW: Movement trends, last 14 days ----------
$trends = all($pdo,
    "SELECT DATE(timestamp) AS d,
            SUM(movement_type='inbound')    AS inbound,
            SUM(movement_type='outbound')   AS outbound,
            SUM(movement_type='transfer')   AS transfers,
            SUM(movement_type='adjustment') AS adjustments,
            COUNT(*) AS total
     FROM stock_movements
     WHERE timestamp >= NOW() - INTERVAL 14 DAY
     GROUP BY DATE(timestamp)
     ORDER BY d DESC"
);
?>

<!-- ============ KPI ROW (headline) ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-stack"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($totalUnits, 0, ',', ' ') ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Total units</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-currency-euro"></i></div>
      <div>
        <div style="font-size:1.3rem;font-weight:800;"><?= number_format($totalValue, 0, ',', ' ') ?> €</div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Stock value</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-box"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $totalSKUs ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Distinct SKUs</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-calendar-week"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $totalBatches ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Batches</div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-activity"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($mvLast30) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Movements (30d)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-speedometer2"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($mvPerDay) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Avg moves / day</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(107,114,128,.15);color:#4b5563;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-archive"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($deadStockCnt) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Dead stock (90d)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-exclamation-triangle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($lowStockCnt) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Low stock alerts</div>
      </div>
    </div>
  </div>
</div>

<!-- ============ MOVEMENT TRENDS (14 days) ============ -->
<div class="table-card">
  <h5><i class="bi bi-graph-up"></i> Movement Trends (last 14 days)</h5>
  <?php if (!$trends): ?>
    <p style="color:var(--text-muted);">No movements in the last 14 days.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:450px;">
    <table style="min-width:600px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr>
          <th>Date</th>
          <th style="text-align:right;">Inbound</th>
          <th style="text-align:right;">Outbound</th>
          <th style="text-align:right;">Transfers</th>
          <th style="text-align:right;">Adjustments</th>
          <th style="text-align:right;">Total</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($trends as $t): ?>
        <tr>
          <td><strong><?= htmlspecialchars($t['d']) ?></strong></td>
          <td style="text-align:right;color:#16a34a;font-weight:600;"><?= (int)$t['inbound'] ?></td>
          <td style="text-align:right;color:#2563eb;font-weight:600;"><?= (int)$t['outbound'] ?></td>
          <td style="text-align:right;color:#7c3aed;font-weight:600;"><?= (int)$t['transfers'] ?></td>
          <td style="text-align:right;color:#dc2626;font-weight:600;"><?= (int)$t['adjustments'] ?></td>
          <td style="text-align:right;"><strong><?= (int)$t['total'] ?></strong></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ============ TOP MOVERS (30 days) ============ -->
<div class="table-card">
  <h5><i class="bi bi-fire"></i> Top Moving Products (last 30 days)</h5>
  <?php if (!$topMovers): ?>
    <p style="color:var(--text-muted);">No movements in the last 30 days.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:500px;">
    <table style="min-width:700px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr>
          <th>SKU</th>
          <th>Product</th>
          <th style="text-align:right;">Inbound</th>
          <th style="text-align:right;">Outbound</th>
          <th style="text-align:right;">Total moves</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($topMovers as $t): ?>
        <tr>
          <td><code><?= htmlspecialchars($t['sku']) ?></code></td>
          <td><?= htmlspecialchars($t['name']) ?></td>
          <td style="text-align:right;color:#16a34a;font-weight:600;"><?= (int)$t['inbound'] ?></td>
          <td style="text-align:right;color:#2563eb;font-weight:600;"><?= (int)$t['outbound'] ?></td>
          <td style="text-align:right;"><span class="status-badge status-active"><?= (int)$t['moves'] ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ============ DEAD STOCK (no movement 90 days) ============ -->
<div class="table-card">
  <h5><i class="bi bi-archive"></i> Dead Stock — no movement in 90 days</h5>
  <?php if (!$deadStock): ?>
    <p style="color:var(--text-muted);">No dead stock — everything with stock has moved recently. 👍</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:500px;">
    <table style="min-width:700px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr>
          <th>SKU</th>
          <th>Product</th>
          <th style="text-align:right;">On hand</th>
          <th style="text-align:right;">Value tied up</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($deadStock as $d): ?>
        <tr>
          <td><code><?= htmlspecialchars($d['sku']) ?></code></td>
          <td><?= htmlspecialchars($d['name']) ?></td>
          <td style="text-align:right;"><span class="status-badge status-cancelled"><?= (int)$d['on_hand'] ?></span></td>
          <td style="text-align:right;font-weight:600;"><?= number_format((float)$d['value'], 0, ',', ' ') ?> €</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ============ STOCK BY WAREHOUSE ============ -->
<div class="table-card">
  <h5><i class="bi bi-building"></i> Stock by Warehouse</h5>
  <div style="overflow:auto;max-height:500px;">
    <table style="min-width:700px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr><th>Warehouse</th><th>City</th><th style="text-align:right;">SKUs</th><th style="text-align:right;">Units</th><th style="text-align:right;">Value</th></tr>
      </thead>
      <tbody>
      <?php foreach ($byWh as $w): ?>
        <tr>
          <td><?= htmlspecialchars($w['name']) ?></td>
          <td><?= htmlspecialchars($w['city'] ?? '—') ?></td>
          <td style="text-align:right;"><?= (int)$w['skus'] ?></td>
          <td style="text-align:right;"><?= number_format((int)$w['units'], 0, ',', ' ') ?></td>
          <td style="text-align:right;"><?= number_format((float)$w['value'], 0, ',', ' ') ?> €</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============ STOCK BY CATEGORY ============ -->
<div class="table-card">
  <h5><i class="bi bi-tags"></i> Stock by Category</h5>
  <div style="overflow:auto;max-height:500px;">
    <table style="min-width:700px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr><th>Category</th><th style="text-align:right;">Products</th><th style="text-align:right;">Units</th><th style="text-align:right;">Value</th></tr>
      </thead>
      <tbody>
      <?php foreach ($byCat as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['name']) ?></td>
          <td style="text-align:right;"><?= (int)$c['products'] ?></td>
          <td style="text-align:right;"><?= number_format((int)$c['units'], 0, ',', ' ') ?></td>
          <td style="text-align:right;"><?= number_format((float)$c['value'], 0, ',', ' ') ?> €</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============ MOVEMENTS BY TYPE ============ -->
<div class="table-card">
  <h5><i class="bi bi-activity"></i> Movements by Type (last 30 days)</h5>
  <?php if (!$byType): ?>
    <p style="color:var(--text-muted);">No movements in the last 30 days.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:400px;">
    <table style="min-width:500px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr><th>Type</th><th style="text-align:right;">Count</th><th style="text-align:right;">Units</th></tr>
      </thead>
      <tbody>
      <?php foreach ($byType as $t): ?>
        <tr>
          <td><span class="status-badge status-<?= htmlspecialchars($t['movement_type']) ?>"><?= htmlspecialchars($t['movement_type']) ?></span></td>
          <td style="text-align:right;"><?= (int)$t['cnt'] ?></td>
          <td style="text-align:right;"><?= number_format((int)$t['units'], 0, ',', ' ') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- ============ EXPIRY OUTLOOK ============ -->
<div class="table-card">
  <h5><i class="bi bi-calendar-week"></i> Batch Expiry Outlook</h5>
  <div style="overflow:auto;max-height:400px;">
    <table style="min-width:500px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr><th>Bucket</th><th style="text-align:right;">Batches</th></tr>
      </thead>
      <tbody>
        <tr><td><span class="status-badge status-expired">Expired</span></td><td style="text-align:right;"><?= $exp['expired'] ?></td></tr>
        <tr><td><span class="status-badge status-pending">≤ 30 days</span></td><td style="text-align:right;"><?= $exp['d30'] ?></td></tr>
        <tr><td><span class="status-badge status-pending">31–60 days</span></td><td style="text-align:right;"><?= $exp['d60'] ?></td></tr>
        <tr><td><span class="status-badge status-available">61–90 days</span></td><td style="text-align:right;"><?= $exp['d90'] ?></td></tr>
        <tr><td><span class="status-badge status-active">Safe (&gt; 90d)</span></td><td style="text-align:right;"><?= $exp['safe'] ?></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ============ REORDER ALERTS ============ -->
<div class="table-card">
  <h5><i class="bi bi-exclamation-triangle"></i> Reorder Alerts</h5>
  <?php if (!$reorder): ?>
    <p style="color:var(--text-muted);">All products above reorder point. 👍</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:500px;">
    <table style="min-width:700px;white-space:nowrap;">
      <thead style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
        <tr><th>SKU</th><th>Product</th><th style="text-align:right;">On hand</th><th style="text-align:right;">Reorder at</th></tr>
      </thead>
      <tbody>
      <?php foreach ($reorder as $r): ?>
        <tr>
          <td><code><?= htmlspecialchars($r['sku']) ?></code></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td style="text-align:right;"><span class="status-badge status-pending"><?= (int)$r['on_hand'] ?></span></td>
          <td style="text-align:right;"><?= (int)$r['reorder_point'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
