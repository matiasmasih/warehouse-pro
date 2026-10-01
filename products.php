<?php
// products.php — Products list (modern, admin CRUD entry)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('products');

$page_title = 'Products';
include 'warehouse_header.php';

$q      = trim($_GET['q'] ?? '');
$cat    = (int)($_GET['cat'] ?? 0);
$status = trim($_GET['status'] ?? '');
$sort   = $_GET['sort'] ?? 'name';
$dirRaw = strtolower($_GET['dir'] ?? 'asc');
$dir    = $dirRaw === 'desc' ? 'DESC' : 'ASC';

$allowedSort = ['name','sku','category','supplier','stock','reorder','price','status'];
if (!in_array($sort, $allowedSort, true)) $sort = 'name';

$map = [
    'name'     => 'p.name',
    'sku'      => 'p.sku',
    'category' => 'c.name',
    'supplier' => 's.name',
    'stock'    => 'on_hand',
    'reorder'  => 'p.reorder_point',
    'price'    => 'p.unit_price',
    'status'   => 'p.status',
];
$sortCol = $map[$sort];

$where = [];
$args  = [];
if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
    array_push($args, "%$q%", "%$q%", "%$q%");
}
if ($cat > 0)       { $where[] = "p.category_id = ?"; $args[] = $cat; }
if ($status !== '') { $where[] = "p.status = ?";      $args[] = $status; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT p.id, p.name, p.sku, p.barcode, p.unit_price, p.reorder_point, p.status,
               c.name AS category_name,
               s.name AS supplier_name,
               COALESCE((SELECT SUM(i.quantity) FROM inventory i WHERE i.product_id = p.id), 0) AS on_hand
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN suppliers  s ON s.id = p.supplier_id
        $whereSql
        ORDER BY $sortCol $dir
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$products = $stmt->fetchAll();

$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

function sort_link($key, $label, $current, $dirRaw) {
    $next  = ($current === $key && $dirRaw === 'asc') ? 'desc' : 'asc';
    $arrow = ($current === $key) ? ($dirRaw === 'asc' ? ' ▲' : ' ▼') : '';
    $qs = http_build_query(array_merge($_GET, ['sort' => $key, 'dir' => $next]));
    return '<a href="?' . $qs . '" style="color:inherit;text-decoration:none;">' . htmlspecialchars($label) . $arrow . '</a>';
}
?>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-box"></i> Products (<?= count($products) ?>)</h5>
    <?php if (my_access('products') === 'full'): ?>
      <a href="admin_add_product.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Add Product
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Product saved successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['discontinued']) && $_GET['discontinued'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Product discontinued.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':     echo 'Invalid product ID.'; break;
          case 'not_found':      echo 'Product not found.'; break;
          case 'already_disc':   echo 'Product is already discontinued.'; break;
          case 'dup_sku':        echo 'That SKU is already in use.'; break;
          case 'failed':         echo 'Operation failed. Please try again.'; break;
          default:               echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name, SKU, barcode…"
           style="flex:1;min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <select name="cat" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All categories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All statuses</option>
      <?php foreach (['active','pending','discontinued'] as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="products.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$products): ?>
    <p style="color:var(--text-muted);">No products match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table>
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th><?= sort_link('sku',      'SKU',      $sort, $dirRaw) ?></th>
          <th><?= sort_link('name',     'Product',  $sort, $dirRaw) ?></th>
          <th><?= sort_link('category', 'Category', $sort, $dirRaw) ?></th>
          <th><?= sort_link('supplier', 'Supplier', $sort, $dirRaw) ?></th>
          <th><?= sort_link('stock',    'On hand',  $sort, $dirRaw) ?></th>
          <th><?= sort_link('reorder',  'Reorder',  $sort, $dirRaw) ?></th>
          <th><?= sort_link('price',    'Price',    $sort, $dirRaw) ?></th>
          <th><?= sort_link('status',   'Status',   $sort, $dirRaw) ?></th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($products as $p): ?>
        <?php
          $onHand  = (int)$p['on_hand'];
          $reorder = (int)$p['reorder_point'];
          $stockBadge = ($reorder > 0 && $onHand <= $reorder) ? 'status-pending' : 'status-active';
        ?>
        <tr style="white-space:nowrap;">
          <td><code><?= htmlspecialchars($p['sku']) ?></code></td>
          <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
          <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($p['supplier_name'] ?? '—') ?></td>
          <td><span class="status-badge <?= $stockBadge ?>"><?= $onHand ?></span></td>
          <td><?= $reorder ?></td>
          <td><?= number_format((float)$p['unit_price'], 2, ',', ' ') ?> €</td>
          <td><span class="status-badge status-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
          <td style="text-align:center;white-space:nowrap;">
            <?php if (my_access('products') === 'full'): ?>
              <a href="admin_edit_product.php?id=<?= (int)$p['id'] ?>"
                 class="btn-add" style="padding:4px 10px;font-size:.8rem;text-decoration:none;">
                <i class="bi bi-pencil-fill"></i> Edit
              </a>
              <?php if ($p['status'] !== 'discontinued'): ?>
                <a href="admin_delete_product.php?id=<?= (int)$p['id'] ?>"
                   class="btn-add"
                   style="padding:4px 10px;font-size:.8rem;text-decoration:none;background:#dc2626;color:#fff;"
                   onclick="return confirm('Discontinue <?= htmlspecialchars(addslashes($p['name'])) ?>? Existing stock stays; no new reorders will be suggested.');">
                  <i class="bi bi-slash-circle"></i> Discontinue
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
