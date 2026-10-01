<?php
// admin_edit_supplier.php — Edit an existing supplier (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('suppliers');

if (my_access('suppliers') !== 'full') {
    header('Location: suppliers.php?error=not_allowed');
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    header('Location: suppliers.php?error=invalid_id');
    exit;
}
$supplier_id = (int)$_GET['id'];

// Load supplier
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    header('Location: suppliers.php?error=not_found');
    exit;
}

// Usage summary (info banner)
$useStmt = $pdo->prepare(
    "SELECT COUNT(*) AS products,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_products
     FROM products WHERE supplier_id = ?"
);
$useStmt->execute([$supplier_id]);
$usage = $useStmt->fetch(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');

    if ($name === '') {
        $error = 'Supplier company name is required.';
    } elseif (mb_strlen($name) > 255) {
        $error = 'Supplier name must be 255 characters or less.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($phone !== '' && !preg_match('/^\+?[0-9\s\-]{7,20}$/', $phone)) {
        $error = 'Invalid phone number format.';
    } else {
        $dup = $pdo->prepare("SELECT id FROM suppliers WHERE name = ? AND id <> ?");
        $dup->execute([$name, $supplier_id]);
        if ($dup->fetchColumn()) {
            $error = 'Another supplier already uses that name.';
        }
    }

    if (!$error) {
        try {
            $changed = [];
            if ($name !== $s['name'])                                        $changed[] = 'name';
            if (($contact_person ?: null) !== $s['contact_person'])          $changed[] = 'contact_person';
            if (($email ?: null) !== $s['email'])                            $changed[] = 'email';
            if (($phone ?: null) !== $s['phone'])                            $changed[] = 'phone';
            if (($address ?: null) !== $s['address'])                        $changed[] = 'address';

            $stmt = $pdo->prepare(
                "UPDATE suppliers
                 SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $name,
                $contact_person ?: null,
                $email          ?: null,
                $phone          ?: null,
                $address        ?: null,
                $supplier_id,
            ]);

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'supplier_updated',
                "Updated supplier #{$supplier_id} ({$name})" .
                    ($changed ? ' — changed: ' . implode(', ', $changed) : ' — no changes')
            ]);

            header('Location: suppliers.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }

    // Repopulate on error
    $s['name']           = $name;
    $s['contact_person'] = $contact_person;
    $s['email']          = $email;
    $s['phone']          = $phone;
    $s['address']        = $address;
}

$page_title = 'Edit Supplier';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:720px;margin:0 auto;">
  <h5><i class="bi bi-pencil-square"></i> Edit Supplier — <strong><?= htmlspecialchars($s['name']) ?></strong></h5>

  <?php if ((int)$usage['products'] > 0): ?>
    <div class="status-badge status-pending" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <i class="bi bi-info-circle"></i>
      Supplies <strong><?= (int)$usage['products'] ?></strong> product(s) —
      <strong><?= (int)$usage['active_products'] ?></strong> active.
    </div>
  <?php endif; ?>

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
               value="<?= htmlspecialchars($s['name']) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Contact Person</label>
        <input type="text" name="contact_person" maxlength="100"
               value="<?= htmlspecialchars($s['contact_person'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Phone</label>
        <input type="tel" name="phone" maxlength="50"
               value="<?= htmlspecialchars($s['phone'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Email</label>
        <input type="email" name="email" maxlength="150"
               value="<?= htmlspecialchars($s['email'] ?? '') ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Address</label>
        <textarea name="address" rows="3"
                  style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($s['address'] ?? '') ?></textarea>
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="suppliers.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Back
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-save"></i> Update Supplier
      </button>
    </div>
  </form>
</div>

<?php include 'warehouse_footer.php'; ?>
