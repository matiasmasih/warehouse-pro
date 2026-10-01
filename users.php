<?php
// users.php — User management (list)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('users');

$page_title = 'Users';
include 'warehouse_header.php';

$role = current_role();

// Role-based visibility (matches old admin_users.php logic)
if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role IN ('admin','staff') ORDER BY is_active DESC, username ASC");
    $stmt->execute();
} else {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY is_active DESC, username ASC");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function role_badge($r) {
    $map = ['admin' => 'status-danger', 'manager' => 'status-warning', 'staff' => 'status-active'];
    return '<span class="status-badge ' . ($map[$r] ?? 'status-active') . '">' . htmlspecialchars($r) . '</span>';
}
?>

<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;"><i class="bi bi-people-fill"></i> Manage Users</h5>
    <a href="register.php" class="btn-add" style="text-decoration:none;">
      <i class="bi bi-person-plus-fill"></i> Add User
    </a>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      User saved successfully.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['disabled']) && $_GET['disabled'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      User disabled successfully.
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id':            echo 'Invalid user ID.'; break;
          case 'cannot_disable_self':   echo 'You cannot disable your own account.'; break;
          case 'user_not_found':        echo 'User not found.'; break;
          case 'not_allowed':           echo 'You are not allowed to modify this user.'; break;
          case 'failed':                echo 'Operation failed. Please try again.'; break;
          case 'already_disabled':      echo 'That user is already disabled.'; break;
          case 'last_admin':            echo 'Cannot disable the last active admin.'; break;
          default:                      echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <div style="overflow-x:auto;">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Username</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Role</th>
          <th>Last login</th>
          <th>Status</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);">No users found.</td></tr>
      <?php else: foreach ($users as $u): ?>
        <tr>
          <td><?= (int)$u['id'] ?></td>
          <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
          <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
          <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
          <td><?= role_badge($u['role']) ?></td>
          <td><?= $u['last_login'] ? htmlspecialchars($u['last_login']) : '<span style="color:var(--text-muted);">never</span>' ?></td>
          <td>
            <?php if ((int)$u['is_active'] === 1): ?>
              <span class="status-badge status-active">Active</span>
            <?php else: ?>
              <span class="status-badge status-cancelled">Disabled</span>
            <?php endif; ?>
          </td>
          <td style="text-align:center;white-space:nowrap;">
            <a href="admin_users_edit.php?id=<?= (int)$u['id'] ?>" class="btn-add" style="padding:4px 10px;font-size:.8rem;text-decoration:none;">
              <i class="bi bi-pencil-fill"></i> Edit
            </a>
            <?php if ((int)$u['is_active'] === 1 && (int)$u['id'] !== (int)$_SESSION['user_id']): ?>
              <a href="delete_user.php?id=<?= (int)$u['id'] ?>"
                 class="btn-add"
                 style="padding:4px 10px;font-size:.8rem;text-decoration:none;background:#dc2626;color:#fff;"
                 onclick="return confirm('Disable this user? They will not be able to log in.');">
                <i class="bi bi-person-slash"></i> Disable
              </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'warehouse_footer.php'; ?>
