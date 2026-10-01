<?php
// batches.php — Product batches & expiry (modern, FEFO-ready)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('batches');

$page_title = 'Batches & Expiry';
include 'warehouse_header.php';

$q      = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$wh     = (int)($_GET['wh'] ?? 0);
$exp    = trim($_GET['exp'] ?? ''); // '', '30', '60', '90', 'expired'

$where = []; $args = [];
if ($q !== '') { $where[] = "(b.batch_number LIKE ? OR p.name LIKE ? OR p.sku LIKE ?)"; array_push($args, "%$q%", "%$q%", "%$q%"); }
if ($status !== '') { $where[] = "b.status = ?"; $args[] = $status; }
if ($wh > 0) { $where[] = "l.warehouse_id = ?"; $args[] = $wh; }

if ($exp === 'expired') {
    $where[] = "(b.status='expired' OR (b.expiry_date IS NOT NULL AND b.expiry_date < CURDATE()))";
} elseif ($exp !== '') {
    $where[] = "b.expiry_date IS NOT NULL AND b.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $args[]  = (int)$exp;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT b.id, b.batch_number, b.lot_number, b.manufacture_date, b.expiry_date,
               b.quantity, b.cost_price, b.status, b.created_at,
               p.name AS product_name, p.sku,
               l.code AS bin_code, l.name AS bin_name,
               w.name AS warehouse_name, w.city,
               DATEDIFF(b.expiry_date, CURDATE()) AS days_to_expiry
        FROM product_batches b
        JOIN products  p ON p.id = b.product_id
        LEFT JOIN locations l ON l.id = b.location_id
        LEFT JOIN warehouses w ON w.id = l.warehouse_id
        $whereSql
        ORDER BY (b.expiry_date IS NULL), b.expiry_date ASC, b.id DESC
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

$warehouses = $pdo->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();

$counts = ['available'=>0,'quarantine'=>0,'expired'=>0,'depleted'=>0];
$expiring30 = 0; $expiredCount = 0;
foreach ($rows as $r) {
    if (isset($counts[$r['status']])) $counts[$r['status']]++;
    if ($r['days_to_expiry'] !== null) {
        if ($r['days_to_expiry'] < 0 && $r['status'] !== 'expired') $expiredCount++;
        elseif ($r['days_to_expiry'] >= 0 && $r['days_to_expiry'] <= 30) $expiring30++;
    }
}
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-check-circle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['available'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Available</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-shield-exclamation"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['quarantine'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Quarantine</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-calendar-week"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $expiring30 ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Expiring ≤ 30d</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-calendar-x"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $counts['expired'] + $expiredCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Expired</div>
      </div>
    </div>
  </div>
</div>

<div class="table-card">
  <h5><i class="bi bi-calendar-week"></i> Batches (<?= count($rows) ?>)</h5>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search batch, product, SKU…"
           style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All statuses</option>
      <?php foreach (['available','quarantine','expired','depleted'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <select name="exp" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">Any expiry</option>
      <option value="30" <?= $exp === '30' ? 'selected' : '' ?>>Expiring ≤ 30d</option>
      <option value="60" <?= $exp === '60' ? 'selected' : '' ?>>Expiring ≤ 60d</option>
      <option value="90" <?= $exp === '90' ? 'selected' : '' ?>>Expiring ≤ 90d</option>
      <option value="expired" <?= $exp === 'expired' ? 'selected' : '' ?>>Expired only</option>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="batches.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No batches match.</p>
  <?php else: ?>
 <div style="overflow:auto;max-height:70vh;">
  <table style="min-width:1100px;white-space:nowrap;">
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th style="white-space:nowrap;">Batch</th>
          <th style="white-space:nowrap;">Product</th>
          <th style="white-space:nowrap;">Warehouse</th>
          <th style="white-space:nowrap;">Bin</th>
          <th style="white-space:nowrap;text-align:right;">Qty</th>
          <th style="white-space:nowrap;">Expiry</th>
          <th style="white-space:nowrap;text-align:right;">Days left</th>
          <th style="white-space:nowrap;">Status</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $b): ?>
        <?php
          $days = $b['days_to_expiry'];
          $expBadge = 'status-active';
          if ($days !== null) {
              if ($days < 0)      $expBadge = 'status-expired';
              elseif ($days <= 30) $expBadge = 'status-pending';
          }
        ?>
        <tr>
          <td><code><?= htmlspecialchars($b['batch_number']) ?></code></td>
          <td><?= htmlspecialchars($b['product_name'] ?? '—') ?><div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($b['sku'] ?? '') ?></div></td>
          <td><?= htmlspecialchars($b['warehouse_name'] ?? '—') ?><?= $b['city'] ? ' (' . htmlspecialchars($b['city']) . ')' : '' ?></td>
          <td><?= $b['bin_code'] ? '<code>' . htmlspecialchars($b['bin_code']) . '</code>' : '—' ?></td>
          <td><?= number_format((int)$b['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($b['expiry_date'] ?? '—') ?></td>
          <td><?= $days === null ? '—' : '<span class="status-badge ' . $expBadge . '">' . (int)$days . ' d</span>' ?></td>
          <td><span class="status-badge status-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
