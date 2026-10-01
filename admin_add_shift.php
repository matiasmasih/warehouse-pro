<?php
// admin_add_shift.php — Assign a shift to a staff member (admin or manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('shifts');

if (my_access('shifts') !== 'full') {
    header('Location: shifts.php?error=not_allowed');
    exit;
}

$error = '';

// Auto-defaults per shift type
$defaults = [
    'morning' => ['06:00', '14:00'],
    'evening' => ['14:00', '22:00'],
    'night'   => ['22:00', '06:00'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id    = (int)($_POST['user_id'] ?? 0);
    $shift_date = trim($_POST['shift_date'] ?? date('Y-m-d'));
    $shift_type = $_POST['shift_type'] ?? '';
    $starts_at  = trim($_POST['starts_at'] ?? '');
    $ends_at    = trim($_POST['ends_at'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    if ($user_id <= 0) {
        $error = 'Please choose a staff member.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $shift_date)) {
        $error = 'Invalid shift date.';
    } elseif (!in_array($shift_type, ['morning','evening','night'], true)) {
        $error = 'Invalid shift type.';
    } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $starts_at)) {
        $error = 'Invalid start time.';
    } elseif (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $ends_at)) {
        $error = 'Invalid end time.';
    } else {
        // Validate user
        $chk = $pdo->prepare("SELECT id, role FROM users WHERE id = ? AND is_active = 1");
        $chk->execute([$user_id]);
        $target = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$target) {
            $error = 'Selected staff member does not exist or is inactive.';
        } elseif ($target['role'] === 'admin') {
            $error = 'Admins cannot be assigned to shifts.';
        } else {
            // Duplicate check — same user, same date, same shift type
            $dup = $pdo->prepare(
                "SELECT id FROM shifts WHERE user_id = ? AND shift_date = ? AND shift_type = ?"
            );
            $dup->execute([$user_id, $shift_date, $shift_type]);
            if ($dup->fetchColumn()) {
                $error = 'This staff member already has a ' . $shift_type . ' shift on ' . $shift_date . '.';
            }
        }
    }

    if (!$error) {
        try {
            // Normalise times to HH:MM:00
            $starts_at = strlen($starts_at) === 5 ? $starts_at . ':00' : $starts_at;
            $ends_at   = strlen($ends_at)   === 5 ? $ends_at   . ':00' : $ends_at;

            $stmt = $pdo->prepare(
                "INSERT INTO shifts
                    (user_id, shift_date, shift_type, starts_at, ends_at, notes, assigned_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $user_id,
                $shift_date,
                $shift_type,
                $starts_at,
                $ends_at,
                $notes ?: null,
                $_SESSION['user_id'],
            ]);
            $newId = (int)$pdo->lastInsertId();

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([
                $_SESSION['user_id'],
                'shift_assigned',
                "Assigned {$shift_type} shift #{$newId} to user #{$user_id} on {$shift_date}"
            ]);

            header('Location: shifts.php?saved=success');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Staff dropdown — exclude admins, only active
$staff = $pdo->query(
    "SELECT id, username, role FROM users
     WHERE is_active = 1 AND role IN ('staff','manager')
     ORDER BY username"
)->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Assign Shift';
include 'warehouse_header.php';
?>

<div class="table-card" style="max-width:720px;margin:0 auto;">
  <h5><i class="bi bi-calendar-plus"></i> Assign Shift</h5>

  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off" id="shiftForm">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Staff Member *</label>
        <select name="user_id" id="userSelect" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— Select staff —</option>
          <?php foreach ($staff as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= ((int)($_POST['user_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['username']) ?> (<?= htmlspecialchars($s['role']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Shift Date *</label>
        <input type="date" name="shift_date" required
               value="<?= htmlspecialchars($_POST['shift_date'] ?? date('Y-m-d')) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Shift Type *</label>
        <select name="shift_type" id="shiftType" required
                style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
          <option value="">— Select type —</option>
          <option value="morning" <?= (($_POST['shift_type'] ?? '') === 'morning') ? 'selected' : '' ?>>Morning (06:00 – 14:00)</option>
          <option value="evening" <?= (($_POST['shift_type'] ?? '') === 'evening') ? 'selected' : '' ?>>Evening (14:00 – 22:00)</option>
          <option value="night"   <?= (($_POST['shift_type'] ?? '') === 'night')   ? 'selected' : '' ?>>Night (22:00 – 06:00)</option>
        </select>
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">Start Time *</label>
        <input type="time" name="starts_at" id="startTime" required
               value="<?= htmlspecialchars(substr($_POST['starts_at'] ?? '06:00', 0, 5)) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div>
        <label style="display:block;font-weight:600;margin-bottom:6px;">End Time *</label>
        <input type="time" name="ends_at" id="endTime" required
               value="<?= htmlspecialchars(substr($_POST['ends_at'] ?? '14:00', 0, 5)) ?>"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>

      <div style="grid-column:span 2;">
        <label style="display:block;font-weight:600;margin-bottom:6px;">Notes</label>
        <input type="text" name="notes" maxlength="255"
               value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>"
               placeholder="Optional notes for this shift"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;">
      </div>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:22px;">
      <a href="shifts.php" class="btn-add" style="background:#e5e7eb;color:#0f172a;text-decoration:none;">
        <i class="bi bi-arrow-left"></i> Cancel
      </a>
      <button type="submit" class="btn-add">
        <i class="bi bi-calendar-check"></i> Assign Shift
      </button>
    </div>
  </form>
</div>

<script>
(function() {
  const defaults = {
    morning: ['06:00', '14:00'],
    evening: ['14:00', '22:00'],
    night:   ['22:00', '06:00']
  };
  const typeSel  = document.getElementById('shiftType');
  const startIn  = document.getElementById('startTime');
  const endIn    = document.getElementById('endTime');

  typeSel.addEventListener('change', function() {
    const d = defaults[this.value];
    if (d) {
      startIn.value = d[0];
      endIn.value   = d[1];
    }
  });
})();
</script>

<?php include 'warehouse_footer.php'; ?>
