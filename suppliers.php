<?php
// suppliers.php — Suppliers list (modern, admin CRUD entry)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('suppliers');

$page_title = 'Suppliers';
include 'warehouse_header.php';

$q = trim($_GET['q'] ?? '');
$where = ''; $args = [];
if ($q !== '') {
    $where = "WHERE s.name LIKE ? OR s.contact_person LIKE ? OR s.email LIKE ?";
    array_push($args, "%$q%", "%$q%", "%$q%");
}

$sql = "SELECT s.id, s.name, s.contact_person, s.email, s.phone, s.address, s.status,
               (SELECT COUNT(*) FROM products p WHERE p.supplier_id = s.id) AS product_count
        FROM suppliers s
        $where
        ORDER BY s.status DESC, s.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$suppliers = $stmt->fetchAll();
?>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-truck"></i> Suppliers (<?= count($suppliers) ?>)</h5>
    <?php if (my_access('suppliers') === 'full'): ?>
      <a href="admin_add_supplier.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Add Supplier
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Supplier saved successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['deactivated']) && $_GET['deactivated'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Supplier deactivated.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':       echo 'Invalid supplier ID.'; break;
          case 'not_found':        echo 'Supplier not found.'; break;
          case 'already_inactive': echo 'Supplier is already inactive.'; break;
          case 'dup_name':         echo 'A supplier with that name already exists.'; break;
          case 'failed':           echo 'Operation failed. Please try again.'; break;
          default:                 echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name, contact, email…"
           style="flex:1;min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Search</button>
    <a href="suppliers.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>

  <?php if (!$suppliers): ?>
    <p style="color:var(--text-muted);">No suppliers match.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table>
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th>#</th>
          <th>Supplier</th>
          <th>Contact</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Products</th>
          <th>Status</th>
          <?php if (my_access('suppliers') === 'full'): ?><th style="text-align:center;">Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($suppliers as $s): ?>
        <tr style="white-space:nowrap;">
          <td><?= (int)$s['id'] ?></td>
          <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
          <td><?= htmlspecialchars($s['contact_person'] ?? '—') ?></td>
          <td>
            <?php if (!empty($s['email'])): ?>
              <a href="mailto:<?= htmlspecialchars($s['email']) ?>" style="color:var(--primary);text-decoration:none;">
                <?= htmlspecialchars($s['email']) ?>
              </a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
          <td><span class="status-badge status-active"><?= (int)$s['product_count'] ?></span></td>
          <td>
            <span class="status-badge status-<?= $s['status'] === 'active' ? 'active' : 'cancelled' ?>">
              <?= htmlspecialchars($s['status']) ?>
            </span>
          </td>
          <?php if (my_access('suppliers') === 'full'): ?>
          <td style="text-align:center;white-space:nowrap;">
            <a href="admin_edit_supplier.php?id=<?= (int)$s['id'] ?>"
               class="btn-add" style="padding:4px 10px;font-size:.8rem;text-decoration:none;">
              <i class="bi bi-pencil-fill"></i> Edit
            </a>
            <?php if ($s['status'] === 'active'): ?>
              <a href="admin_delete_supplier.php?id=<?= (int)$s['id'] ?>"
                 class="btn-add"
                 style="padding:4px 10px;font-size:.8rem;text-decoration:none;background:#dc2626;color:#fff;"
                 onclick="return confirm('Deactivate supplier <?= htmlspecialchars(addslashes($s['name'])) ?>?');">
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
