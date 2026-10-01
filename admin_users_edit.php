<?php
// admin_users_edit.php — Edit user (admin only)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('users');

if (current_role() !== 'admin') {
    header('Location: users.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: users.php?error=invalid_id');
    exit;
}
$user_id = (int)$_GET['id'];

// Load user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php?error=user_not_found');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $role      = $_POST['role'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Safety: don't let an admin disable their own account
    if ($user_id === (int)$_SESSION['user_id']) {
        $is_active = 1;
    }

    if ($username === '' || $email === '' || $role === '') {
        $error = 'Username, email and role are required.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters (letters, numbers, dot, dash, underscore).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($phone !== '' && !preg_match('/^\+?[0-9\s\-]{7,20}$/', $phone)) {
        $error = 'Invalid phone number format.';
    } elseif (!in_array($role, ['admin','manager','staff'], true)) {
        $error = 'Invalid role selected.';
    } else {
        // Duplicate username (excluding self)
        $dup = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id <> ?");
        $dup->execute([$username, $user_id]);
        if ($dup->fetchColumn()) {
            $error = 'That username is already taken.';
        } else {
            // Duplicate email (excluding self)
            $dup = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
            $dup->execute([$email, $user_id]);
            if ($dup->fetchColumn()) {
                $error = 'That email is already registered.';
            }
        }
    }

    // Password (optional)
    if (!$error && $password !== '') {
        if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        }
    }

    if (!$error) {
        try {
            $changed = [];
            if ($username  !== $user['username'])  $changed[] = 'username';
            if ($email     !== $user['email'])     $changed[] = 'email';
            if (($phone ?: null) !== $user['phone']) $changed[] = 'phone';
            if ($role      !== $user['role'])      $changed[] = 'role';
            if ($is_active !== (int)$user['is_active']) $changed[] = 'status';
            if ($password  !== '')                 $changed[] = 'password';

            if ($password !== '') {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    "UPDATE users SET username = ?, email = ?, phone = ?, role = ?, is_active = ?, password = ? WHERE id = ?"
                );
                $stmt->execute([$username, $email, $phone ?: null, $role, $is_active, $hashed, $user_id]);
            } else {
                $stmt = $pdo->prepare(
                    "UPDATE users SET username = ?, email = ?, phone = ?, role = ?, is_active = ? WHERE id = ?"
                );
                $stmt->execute([$username, $email, $phone ?: null, $role, $is_active, $user_id]);
            }

            // Audit log
            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'user_updated',
                "Updated user #{$user_id} ({$username})" . ($changed ? ' — changed: ' . implode(', ', $changed) : ' — no changes')
            ]);

            header('Location: users.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    // Repopulate from POST on error
    $user['username']  = $username;
    $user['email']     = $email;
    $user['phone']     = $phone;
    $user['role']      = $role;
    $user['is_active'] = $is_active;
}

$page_title = 'Edit User';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:720px;margin:0 auto;">
  <h5><i class="bi bi-person-gear"></i> Edit User — <strong><?= htmlspecialchars($user['username']) ?></strong></h5>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Username *</label>
      <input type="text" name="username" required
             value="<?= htmlspecialchars($user['username']) ?>"
             style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
    </div>

    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Email *</label>
      <input type="email" name="email" required
             value="<?= htmlspecialchars($user['email']) ?>"
             style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
    </div>

    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Phone</label>
      <input type="tel" name="phone"
             value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
             style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
    </div>

    <div style="margin-bottom:14px;">
      <label style="display:block;font-weight:600;margin-bottom:6px;">Role *</label>
      <select name="role" required
              style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
        <option value="admin"   <?= $user['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
        <option value="manager" <?= $user['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
        <option value="staff"   <?= $user['role'] === 'staff'   ? 'selected' : '' ?>>Staff</option>
      </select>
    </div>

    <div style="margin-bottom:20px;padding:12px 14px;background:#f1f5f9;border-radius:10px;">
      <label style="display:flex;align-items:center;gap:10px;font-weight:600;cursor:pointer;margin:0;">
        <input type="checkbox" name="is_active" value="1" <?= (int)$user['is_active'] === 1 ? 'checked' : '' ?>
               <?= $user_id === (int)$_SESSION['user_id'] ? 'disabled' : '' ?>>
        Account active
        <?php if ($user_id === (int)$_SESSION['user_id']): ?>
          <span style="color:var(--text-muted);font-weight:400;font-size:.85rem;">(you cannot disable your own account)</span>
        <?php endif; ?>
      </label>
    </div>

    <div style="padding:12px 14px;background:#fffbeb;border:1px dashed #fbbf24;border-radius:10px;margin-bottom:20px;">
      <div style="font-weight:600;margin-bottom:8px;">
        <i class="bi bi-key"></i> Change Password <span style="font-weight:400;color:var(--text-muted);">(leave blank to keep current)</span>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <input type="password" name="password" placeholder="New password" minlength="6"
               style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
        <input type="password" name="confirm_password" placeholder="Confirm new password" minlength="6"
               style="padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;">
      <a href="users.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Update User
      </button>
    </div>
  </form>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
  const p = document.querySelector('input[name="password"]').value;
  const c = document.querySelector('input[name="confirm_password"]').value;
  if (p !== '' && p !== c) {
    e.preventDefault();
    alert('Passwords do not match!');
  }
});
</script>

<?php include 'warehouse_footer.php'; ?>
