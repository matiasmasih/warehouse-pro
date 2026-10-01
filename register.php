<?php
// register.php — Create a new user account (admin or manager only)
// Real-warehouse account provisioning: HR tells the manager, manager creates the account.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('users');

if (my_access('users') !== 'full') {
    header('Location: users.php?error=not_allowed');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirm === '' || $role === '') {
        $error = 'All fields except phone are required.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters (letters, numbers, dot, dash, underscore).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($phone !== '' && !preg_match('/^\+?[0-9\s\-]{7,20}$/', $phone)) {
        $error = 'Invalid phone number format.';
    } elseif (!in_array($role, ['admin','manager','staff'], true)) {
        $error = 'Invalid role selected.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $dup = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $dup->execute([$username]);
        if ($dup->fetchColumn()) {
            $error = 'That username is already taken.';
        } else {
            $dup = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $dup->execute([$email]);
            if ($dup->fetchColumn()) {
                $error = 'That email is already registered.';
            }
        }
    }

    if (!$error) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, phone, password, role, is_active)
                 VALUES (?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([$username, $email, $phone ?: null, $hashed, $role]);
            $newId = (int)$pdo->lastInsertId();

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'user_created',
                "Created user #{$newId} ({$username}) with role {$role}"
            ]);

            header('Location: users.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Register User';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:720px;margin:0 auto;">
  <h5><i class="bi bi-person-plus-fill"></i> Register New User</h5>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off" id="regForm">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Username *</label>
        <input type="text" name="username" id="username" required
               pattern="[A-Za-z0-9_.\-]{3,50}"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Email *</label>
        <input type="email" name="email" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Phone</label>
        <input type="tel" name="phone" placeholder="+358401234567"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Password * <span style="font-weight:400;color:var(--text-muted);font-size:.85rem;">(min 6 chars)</span></label>
        <input type="password" name="password" id="pw" required minlength="6"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Confirm Password *</label>
        <input type="password" name="confirm_password" id="pw2" required minlength="6"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Role *</label>
        <select name="role" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— Select role —</option>
          <option value="staff"   <?= (($_POST['role'] ?? '') === 'staff')   ? 'selected' : '' ?>>Staff</option>
          <option value="manager" <?= (($_POST['role'] ?? '') === 'manager') ? 'selected' : '' ?>>Manager</option>
          <option value="admin"   <?= (($_POST['role'] ?? '') === 'admin')   ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>
    </div>

    <div style="margin-top:14px;padding:12px 14px;background:#fffbeb;border:1px dashed #fbbf24;border-radius:10px;font-size:.85rem;">
      <i class="bi bi-info-circle"></i>
      The new user can log in immediately. To remove access later, edit the user and disable the account.
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="users.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-person-plus-fill"></i> Register User
      </button>
    </div>
  </form>
</div>

<script>
document.getElementById('regForm').addEventListener('submit', function(e) {
  const p1 = document.getElementById('pw').value;
  const p2 = document.getElementById('pw2').value;
  if (p1 !== p2) {
    e.preventDefault();
    alert('Passwords do not match!');
  }
});
</script>

<?php include 'warehouse_footer.php'; ?>
