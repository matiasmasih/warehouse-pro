<?php
// warehouses.php — Warehouses list (modern, admin CRUD entry)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('warehouses');

$page_title = 'Warehouses';
include 'warehouse_header.php';

$q = trim($_GET['q'] ?? '');
$where = ''; $args = [];
if ($q !== '') {
    $where = "WHERE w.name LIKE ? OR w.city LIKE ? OR w.code LIKE ?";
    array_push($args, "%$q%", "%$q%", "%$q%");
}

$sql = "SELECT w.id, w.name, w.code, w.city, w.country, w.address,
               w.phone, w.email, w.status, w.manager_id,
               u.username AS manager_name,
               (SELECT COUNT(*) FROM locations l WHERE l.warehouse_id = w.id) AS zones,
               (SELECT COUNT(DISTINCT i.product_id) FROM inventory i JOIN locations l ON l.id = i.location_id WHERE l.warehouse_id = w.id AND i.quantity > 0) AS skus,
               COALESCE((SELECT SUM(i.quantity) FROM inventory i JOIN locations l ON l.id = i.location_id WHERE l.warehouse_id = w.id), 0) AS units
        FROM warehouses w
        LEFT JOIN users u ON u.id = w.manager_id
        $where
        ORDER BY w.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();
?>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-building"></i> Warehouses (<?= count($rows) ?>)</h5>
    <?php if (my_access('warehouses') === 'full'): ?>
      <a href="admin_add_warehouse.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Add Warehouse
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Warehouse saved successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['deactivated']) && $_GET['deactivated'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Warehouse deactivated.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':       echo 'Invalid warehouse ID.'; break;
          case 'not_found':        echo 'Warehouse not found.'; break;
          case 'already_inactive': echo 'Warehouse is already inactive.'; break;
          case 'has_stock':        echo 'Cannot deactivate — warehouse still holds stock.'; break;
          case 'failed':           echo 'Operation failed. Please try again.'; break;
          default:                 echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name, city, code…"
           style="flex:1;min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Search</button>
    <a href="warehouses.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No warehouses match.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Warehouse</th>
          <th>City</th>
          <th>Country</th>
          <th>Manager</th>
          <th>Contact</th>
          <th>Zones</th>
          <th>SKUs</th>
          <th>Units</th>
          <th>Status</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $w): ?>
        <tr style="white-space:nowrap;">
          <td><code><?= htmlspecialchars($w['code']) ?></code></td>
          <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
          <td><?= htmlspecialchars($w['city'] ?? '—') ?></td>
          <td><?= htmlspecialchars($w['country'] ?? '—') ?></td>
          <td><?= htmlspecialchars($w['manager_name'] ?? '—') ?></td>
          <td style="font-size:.85rem;">
            <?php if ($w['phone']): ?><i class="bi bi-telephone"></i> <?= htmlspecialchars($w['phone']) ?>
            <?php elseif ($w['email']): ?><i class="bi bi-envelope"></i> <?= htmlspecialchars($w['email']) ?>
            <?php else: ?><span style="color:var(--text-muted);">—</span><?php endif; ?>
          </td>
          <td><span class="status-badge status-active"><?= (int)$w['zones'] ?></span></td>
          <td><?= (int)$w['skus'] ?></td>
          <td><?= number_format((int)$w['units'], 0, ',', ' ') ?></td>
          <td>
            <span class="status-badge status-<?= $w['status'] === 'active' ? 'active' : 'cancelled' ?>">
              <?= htmlspecialchars($w['status']) ?>
            </span>
          </td>
          <td style="text-align:center;white-space:nowrap;">
            <?php if (my_access('warehouses') === 'full'): ?>
              <a href="admin_edit_warehouse.php?id=<?= (int)$w['id'] ?>"
                 class="btn-add" style="padding:4px 10px;font-size:.8rem;text-decoration:none;">
                <i class="bi bi-pencil-fill"></i> Edit
              </a>
              <?php if ($w['status'] === 'active'): ?>
                <a href="admin_delete_warehouse.php?id=<?= (int)$w['id'] ?>"
                   class="btn-add"
                   style="padding:4px 10px;font-size:.8rem;text-decoration:none;background:#dc2626;color:#fff;"
                   onclick="return confirm('Deactivate <?= htmlspecialchars(addslashes($w['name'])) ?>?');">
                  <i class="bi bi-slash-circle"></i> Deactivate
                </a>
              <?php endif; ?>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:.85rem;">view only</span>
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
