<?php
// admin_add_supplier.php — Create a new supplier (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('suppliers');

if (my_access('suppliers') !== 'full') {
    header('Location: suppliers.php?error=not_allowed');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');

    if ($name === '') {
        $error = 'Supplier name is required.';
    } elseif (mb_strlen($name) > 255) {
        $error = 'Supplier name must be 255 characters or less.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($phone !== '' && !preg_match('/^\+?[0-9\s\-]{7,20}$/', $phone)) {
        $error = 'Invalid phone number format.';
    } else {
        $dup = $pdo->prepare("SELECT id FROM suppliers WHERE name = ?");
        $dup->execute([$name]);
        if ($dup->fetchColumn()) {
            $error = 'A supplier with that name already exists.';
        }
    }

    if (!$error) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO suppliers (name, contact_person, email, phone, address, status)
                 VALUES (?, ?, ?, ?, ?, 'active')"
            );
            $stmt->execute([
                $name,
                $contact_person ?: null,
                $email          ?: null,
                $phone          ?: null,
                $address        ?: null,
            ]);
            $newId = (int)$pdo->lastInsertId();

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'supplier_created',
                "Created supplier #{$newId} ({$name})"
            ]);

            header('Location: suppliers.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$page_title = 'Add Supplier';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:720px;margin:0 auto;">
  <h5><i class="bi bi-plus-circle-fill"></i> Add Supplier</h5>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Supplier Company Name *</label>
        <input type="text" name="name" required maxlength="255"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               placeholder="e.g. Nordic Electronics Oy"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Contact Person</label>
        <input type="text" name="contact_person" maxlength="100"
               value="<?= htmlspecialchars($_POST['contact_person'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Phone</label>
        <input type="tel" name="phone" maxlength="50"
               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
               placeholder="+358401234567"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Email</label>
        <input type="email" name="email" maxlength="150"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="orders@supplier.fi"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Address</label>
        <textarea name="address" rows="3"
                  placeholder="Street, city, postal code, country"
                  style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="suppliers.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Create Supplier
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
