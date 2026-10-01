<?php
// login.php — WarehousePro login (glass style, amber/navy theme)
// Records failed attempts to failed_logins table, updates users.last_login on success.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!empty($_SESSION['user_id'])) {
    switch (current_role()) {
        case 'admin':   header('Location: admin_dashboard.php');   exit;
        case 'manager': header('Location: manager_dashboard.php'); exit;
        case 'staff':   header('Location: staff_dashboard.php');   exit;
    }
}

$error = '';

// Helper: record a failed login attempt (never blocks login flow)
function record_failed_login(PDO $pdo, $username, $reason) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $ins = $pdo->prepare(
            "INSERT INTO failed_logins
                (username_attempted, ip_address, user_agent, reason)
             VALUES (?, ?, ?, ?)"
        );
        $ins->execute([mb_substr($username, 0, 100), $ip, $ua ?: null, $reason]);
    } catch (PDOException $e) {
        // Silent — login flow must not break if audit table is unavailable
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username/email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {

            // Disabled account → clear message, no session, record attempt
            if ((int)$user['is_active'] !== 1) {
                record_failed_login($pdo, $username, 'inactive_account');
                $error = 'Your account has been disabled. Contact an administrator.';

            } else {
                // Successful login — update last_login (non-blocking)
                try {
                    $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $upd->execute([(int)$user['id']]);
                } catch (PDOException $e) {
                    // Non-blocking
                }

                session_regenerate_id(true);
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = strtolower(trim($user['role']));

                switch ($_SESSION['role']) {
                    case 'admin':   header('Location: admin_dashboard.php');   exit;
                    case 'manager': header('Location: manager_dashboard.php'); exit;
                    case 'staff':
                    case 'user':    header('Location: staff_dashboard.php');   exit;
                    default:
                        session_destroy();
                        $error = 'Unknown role. Contact administrator.';
                }
            }

        } else {
            // Distinguish unknown user vs bad password (for security forensics)
            $reason = $user ? 'bad_password' : 'unknown_user';
            record_failed_login($pdo, $username, $reason);
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>WarehousePro | Sign In</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

body {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: url('https://images.unsplash.com/photo-1553413077-190dd305871c?auto=format&fit=crop&w=2070&q=80') center/cover;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

body::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 20% 80%, rgba(245, 158, 11, 0.35) 0%, transparent 55%),
    radial-gradient(circle at 80% 20%, rgba(217, 119, 6, 0.35) 0%, transparent 55%),
    linear-gradient(135deg, rgba(15, 23, 42, 0.75), rgba(15, 23, 42, 0.85));
  animation: float 8s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50%      { transform: translateY(-20px); }
}

.login-container {
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 50px 40px;
  border-radius: 24px;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.08);
  width: 100%;
  max-width: 440px;
  position: relative;
  z-index: 2;
  transition: all 0.4s ease;
}

.login-container:hover {
  transform: translateY(-5px);
  box-shadow: 0 35px 60px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(245, 158, 11, 0.25);
}

.brand-header { text-align: center; margin-bottom: 35px; }

.brand-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  box-shadow: 0 10px 25px rgba(245, 158, 11, 0.45);
}

.brand-icon i { font-size: 2.5rem; color: #0f172a; }

.brand-header h1 {
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 8px;
  letter-spacing: -0.5px;
}

.brand-header p { color: rgba(255,255,255,0.85); font-size: 0.95rem; font-weight: 400; }

.input-group { position: relative; margin-bottom: 22px; }

.input-group > i.field-icon {
  position: absolute;
  left: 18px;
  top: 50%;
  transform: translateY(-50%);
  color: #fbbf24;
  font-size: 1.15rem;
  z-index: 2;
  transition: all 0.3s ease;
  pointer-events: none;
}

.input-group input {
  width: 100%;
  padding: 16px 50px 16px 50px;
  border: 2px solid rgba(255,255,255,0.15);
  border-radius: 14px;
  font-size: 1rem;
  font-weight: 400;
  background: rgba(255,255,255,0.06);
  transition: all 0.3s ease;
  color: #ffffff;
}

.input-group input::placeholder { color: rgba(255,255,255,0.55); }

.input-group input:focus {
  outline: none;
  border-color: #f59e0b;
  background: rgba(255,255,255,0.10);
  box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.18);
}

.input-group input.error {
  border-color: #ef4444;
  background: rgba(239, 68, 68, 0.08);
}

.password-toggle {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #fbbf24;
  cursor: pointer;
  font-size: 1.1rem;
  transition: all 0.3s ease;
  padding: 4px;
}

.password-toggle:hover { color: #f59e0b; transform: translateY(-50%) scale(1.12); }

.login-btn {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #0f172a;
  border: none;
  border-radius: 14px;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 6px;
  position: relative;
  overflow: hidden;
  letter-spacing: 0.3px;
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 28px rgba(245, 158, 11, 0.5);
}

.login-btn:disabled { opacity: 0.75; cursor: not-allowed; transform: none; }

.spinner {
  display: none;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(15, 23, 42, 0.25);
  border-top-color: #0f172a;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
  margin: 0 auto;
}

@keyframes spin { to { transform: rotate(360deg); } }

.error-message {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  padding: 14px 18px;
  border-radius: 12px;
  margin-bottom: 22px;
  font-weight: 500;
  font-size: 0.92rem;
  text-align: center;
  box-shadow: 0 5px 15px rgba(239, 68, 68, 0.35);
  display: none;
}

.error-message.show { display: block; animation: slideDown 0.3s ease; }

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.login-footer {
  margin-top: 28px;
  text-align: center;
  color: rgba(255,255,255,0.7);
  font-size: 0.88rem;
}

.login-footer .brand-mark {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #fbbf24;
  font-weight: 600;
}

@media (max-width: 480px) {
  .login-container { padding: 35px 25px; margin: 20px; }
  .brand-header h1 { font-size: 1.7rem; }
  .brand-icon { width: 64px; height: 64px; }
  .brand-icon i { font-size: 2rem; }
}
</style>
</head>
<body>

<div class="login-container">
  <div class="brand-header">
    <div class="brand-icon"><i class="bi bi-box-seam"></i></div>
    <h1>WarehousePro</h1>
    <p>Multi-site Warehouse Management</p>
  </div>

  <div class="error-message" id="errorMessage">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><span id="errorText"></span>
  </div>

  <form method="POST" action="" id="loginForm">
    <div class="input-group">
      <i class="bi bi-person-fill field-icon"></i>
      <input type="text" name="username" id="username" placeholder="Username or email" required autofocus
             value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="input-group">
      <i class="bi bi-lock-fill field-icon"></i>
      <input type="password" name="password" id="password" placeholder="Password" required>
      <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password">
        <i class="bi bi-eye"></i>
      </button>
    </div>

    <button type="submit" class="login-btn" id="loginButton">
      <span id="buttonText">Sign In</span>
      <div class="spinner" id="spinner"></div>
    </button>
  </form>

  <div class="login-footer">
    <span class="brand-mark"><i class="bi bi-shield-check"></i> Secure access</span>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('loginForm');
  const usernameInput = document.getElementById('username');
  const passwordInput = document.getElementById('password');
  const passwordToggle = document.getElementById('passwordToggle');
  const loginButton = document.getElementById('loginButton');
  const buttonText = document.getElementById('buttonText');
  const spinner = document.getElementById('spinner');
  const errorMessage = document.getElementById('errorMessage');
  const errorText = document.getElementById('errorText');

  <?php if ($error !== ''): ?>
  errorText.textContent = <?= json_encode($error, JSON_UNESCAPED_UNICODE) ?>;
  errorMessage.classList.add('show');
  <?php endif; ?>

  passwordToggle.addEventListener('click', function() {
    const isPwd = passwordInput.getAttribute('type') === 'password';
    passwordInput.setAttribute('type', isPwd ? 'text' : 'password');
    this.innerHTML = isPwd ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
  });

  form.addEventListener('submit', function(e) {
    errorMessage.classList.remove('show');
    usernameInput.classList.remove('error');
    passwordInput.classList.remove('error');

    let ok = true, msg = '';
    if (!usernameInput.value.trim()) { usernameInput.classList.add('error'); msg = 'Please enter your username or email.'; ok = false; }
    else if (!passwordInput.value)    { passwordInput.classList.add('error');   msg = 'Please enter your password.'; ok = false; }

    if (!ok) { e.preventDefault(); errorText.textContent = msg; errorMessage.classList.add('show'); return; }

    buttonText.style.display = 'none';
    spinner.style.display = 'block';
    loginButton.disabled = true;
  });

  [usernameInput, passwordInput].forEach(el => {
    el.addEventListener('input', () => el.classList.remove('error'));
  });

  usernameInput.focus();
});
</script>
</body>
</html>
