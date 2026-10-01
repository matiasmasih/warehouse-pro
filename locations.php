<?php
// locations.php — Locations/zones list (modern, admin CRUD entry)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('locations');

$page_title = 'Locations';
include 'warehouse_header.php';

$q    = trim($_GET['q'] ?? '');
$wh   = (int)($_GET['wh'] ?? 0);
$type = trim($_GET['type'] ?? '');

$where = []; $args = [];
if ($q !== '')    { $where[] = "(l.name LIKE ? OR l.code LIKE ?)"; array_push($args, "%$q%", "%$q%"); }
if ($wh > 0)      { $where[] = "l.warehouse_id = ?"; $args[] = $wh; }
if ($type !== '') { $where[] = "l.type = ?"; $args[] = $type; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT l.id, l.name, l.code, l.type, l.address, l.description, l.warehouse_id,
               w.name AS warehouse_name, w.city,
               (SELECT COUNT(*) FROM inventory i WHERE i.location_id = l.id) AS sku_count,
               COALESCE((SELECT SUM(i.quantity) FROM inventory i WHERE i.location_id = l.id), 0) AS unit_count
        FROM locations l
        LEFT JOIN warehouses w ON w.id = l.warehouse_id
        $whereSql
        ORDER BY w.name, l.type, l.name
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$locations = $stmt->fetchAll();

$warehouses = $pdo->query("SELECT id, name, city FROM warehouses ORDER BY name")->fetchAll();
$types = ['receiving','storage','dispatch','returns','quarantine'];

function type_badge($t) {
    $map = [
        'receiving'  => 'status-active',
        'storage'    => 'status-pending',
        'dispatch'   => 'status-warning',
        'returns'    => 'status-cancelled',
        'quarantine' => 'status-danger',
    ];
    return '<span class="status-badge ' . ($map[$t] ?? 'status-active') . '">' . htmlspecialchars($t) . '</span>';
}
?>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-geo-alt"></i> Locations (<?= count($locations) ?>)</h5>
    <?php if (my_access('locations') === 'full'): ?>
      <a href="admin_add_location.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Add Location
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Location saved successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Location deleted.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':  echo 'Invalid location ID.'; break;
          case 'not_found':   echo 'Location not found.'; break;
          case 'has_stock':   echo 'Cannot delete — location still holds stock. Move stock out first.'; break;
          case 'dup_code':    echo 'That location code is already in use.'; break;
          case 'failed':      echo 'Operation failed. Please try again.'; break;
          default:            echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name or code…"
           style="flex:1;min-width:200px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="wh" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All warehouses</option>
      <?php foreach ($warehouses as $w): ?>
        <option value="<?= (int)$w['id'] ?>" <?= $wh === (int)$w['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($w['name']) ?><?= $w['city'] ? ' (' . htmlspecialchars($w['city']) . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="type" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All types</option>
      <?php foreach ($types as $t): ?>
        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="locations.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$locations): ?>
    <p style="color:var(--text-muted);">No locations match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table>
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th>Code</th>
          <th>Location</th>
          <th>Warehouse</th>
          <th>Type</th>
          <th>SKUs</th>
          <th>Units</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($locations as $l): ?>
        <tr style="white-space:nowrap;">
          <td><?php if ($l['code']): ?><code><?= htmlspecialchars($l['code']) ?></code><?php else: ?><span style="color:var(--text-muted);">—</span><?php endif; ?></td>
          <td><strong><?= htmlspecialchars($l['name']) ?></strong></td>
          <td><?= htmlspecialchars($l['warehouse_name'] ?? '—') ?><?= $l['city'] ? ' (' . htmlspecialchars($l['city']) . ')' : '' ?></td>
          <td><?= type_badge($l['type']) ?></td>
          <td><?= (int)$l['sku_count'] ?></td>
          <td><?= number_format((int)$l['unit_count'], 0, ',', ' ') ?></td>
          <td style="text-align:center;white-space:nowrap;">
            <?php if (my_access('locations') === 'full'): ?>
              <a href="admin_edit_location.php?id=<?= (int)$l['id'] ?>"
                 class="btn-add" style="padding:4px 10px;font-size:.8rem;text-decoration:none;">
                <i class="bi bi-pencil-fill"></i> Edit
              </a>
              <?php if ((int)$l['unit_count'] === 0): ?>
                <a href="admin_delete_location.php?id=<?= (int)$l['id'] ?>"
                   class="btn-add"
                   style="padding:4px 10px;font-size:.8rem;text-decoration:none;background:#dc2626;color:#fff;"
                   onclick="return confirm('Delete location <?= htmlspecialchars(addslashes($l['name'])) ?>?');">
                  <i class="bi bi-trash-fill"></i> Delete
                </a>
              <?php else: ?>
                <span class="status-badge status-pending" style="font-size:.7rem;" title="Has stock — cannot delete">
                  <i class="bi bi-lock-fill"></i> locked
                </span>
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
