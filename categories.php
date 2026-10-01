<?php
// categories.php — Categories list (modern, admin CRUD entry)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('categories');

$page_title = 'Categories';
include 'warehouse_header.php';

$q = trim($_GET['q'] ?? '');
$where = ''; $args = [];
if ($q !== '') {
    $where  = "WHERE c.name LIKE ?";
    $args[] = "%$q%";
}

$sql = "SELECT c.id, c.name, c.description, c.status,
               COUNT(p.id) AS product_count,
               COALESCE(SUM((SELECT SUM(i.quantity) FROM inventory i WHERE i.product_id = p.id)),0) AS total_units
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        $where
        GROUP BY c.id, c.name, c.description, c.status
        ORDER BY c.status DESC, c.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$cats = $stmt->fetchAll();
?>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-tags"></i> Categories (<?= count($cats) ?>)</h5>
    <?php if (my_access('categories') === 'full'): ?>
      <a href="admin_add_category.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Add Category
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Category saved successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['deactivated']) && $_GET['deactivated'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Category deactivated.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':   echo 'Invalid category ID.'; break;
          case 'not_found':    echo 'Category not found.'; break;
          case 'already_inactive': echo 'Category is already inactive.'; break;
          case 'dup_name':     echo 'A category with that name already exists.'; break;
          case 'failed':       echo 'Operation failed. Please try again.'; break;
          default:             echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search category…"
           style="flex:1;min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Search</button>
    <a href="categories.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$cats): ?>
    <p style="color:var(--text-muted);">No categories match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:65vh;">
    <table>
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th>#</th>
          <th>Category</th>
          <th>Description</th>
          <th>Products</th>
          <th>Units in stock</th>
          <th>Status</th>
          <?php if (my_access('categories') === 'full'): ?><th style="text-align:center;">Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr style="white-space:nowrap;">
          <td><?= (int)$c['id'] ?></td>
          <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
          <td style="color:var(--text-muted);font-size:.85rem;"><?= htmlspecialchars($c['description'] ?? '—') ?></td>
          <td><span class="status-badge status-active"><?= (int)$c['product_count'] ?></span></td>
          <td><?= number_format((int)$c['total_units'], 0, ',', ' ') ?></td>
          <td>
            <span class="status-badge status-<?= $c['status'] === 'active' ? 'active' : 'cancelled' ?>">
              <?= htmlspecialchars($c['status']) ?>
            </span>
          </td>
          <?php if (my_access('categories') === 'full'): ?>
          <td style="text-align:center;white-space:nowrap;">
            <a href="admin_edit_category.php?id=<?= (int)$c['id'] ?>"
               class="btn-add" style="padding:4px 10px;font-size:.8rem;text-decoration:none;">
              <i class="bi bi-pencil-fill"></i> Edit
            </a>
            <?php if ($c['status'] === 'active'): ?>
              <a href="admin_delete_category.php?id=<?= (int)$c['id'] ?>"
                 class="btn-add"
                 style="padding:4px 10px;font-size:.8rem;text-decoration:none;background:#dc2626;color:#fff;"
                 onclick="return confirm('Deactivate category <?= htmlspecialchars(addslashes($c['name'])) ?>?');">
                <i class="bi bi-slash-circle"></i> Deactivate
              </a>
            <?php else: ?>
              <span class="status-badge status-cancelled" style="font-size:.7rem;">inactive</span>
            <?php endif; ?>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
