<?php
// timeclock.php — Staff shift attendance (clock in / clock out)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('timeclock');

$uid  = (int)$_SESSION['user_id'];
$role = current_role();
$isMgr = in_array($role, ['admin','manager'], true);

$error   = '';
$success = '';

// ---------- POST: Clock In ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clock_in') {
    try {
        // Guard: already open entry?
        $chk = $pdo->prepare("SELECT id FROM timeclock WHERE user_id = ? AND clock_out_at IS NULL");
        $chk->execute([$uid]);
        if ($chk->fetchColumn()) {
            $error = 'You are already clocked in.';
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO timeclock (user_id, clock_in_at, notes) VALUES (?, NOW(), ?)"
            );
            $ins->execute([$uid, trim($_POST['notes'] ?? '') ?: null]);
            $newId = (int)$pdo->lastInsertId();

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([$uid, 'clock_in', "Clocked in — entry #{$newId}"]);

            header('Location: timeclock.php?in=success');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// ---------- POST: Clock Out ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clock_out') {
    try {
        $chk = $pdo->prepare(
            "SELECT id, clock_in_at FROM timeclock
             WHERE user_id = ? AND clock_out_at IS NULL
             ORDER BY id DESC LIMIT 1"
        );
        $chk->execute([$uid]);
        $open = $chk->fetch(PDO::FETCH_ASSOC);

        if (!$open) {
            $error = 'You are not currently clocked in.';
        } else {
            $upd = $pdo->prepare(
                "UPDATE timeclock
                 SET clock_out_at = NOW(),
                     hours_worked = ROUND(TIMESTAMPDIFF(MINUTE, clock_in_at, NOW()) / 60, 2)
                 WHERE id = ?"
            );
            $upd->execute([(int)$open['id']]);

            $log = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
            );
            $log->execute([$uid, 'clock_out', "Clocked out — entry #{$open['id']}"]);

            header('Location: timeclock.php?out=success');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// ---------- Current open entry ----------
$stmt = $pdo->prepare(
    "SELECT id, clock_in_at FROM timeclock
     WHERE user_id = ? AND clock_out_at IS NULL
     ORDER BY id DESC LIMIT 1"
);
$stmt->execute([$uid]);
$openEntry = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

// ---------- History (last 30 days) ----------
$stmt = $pdo->prepare(
    "SELECT t.id, t.clock_in_at, t.clock_out_at, t.hours_worked, t.notes,
            u.username
     FROM timeclock t
     LEFT JOIN users u ON u.id = t.user_id
     WHERE t.user_id = ? AND t.clock_in_at >= NOW() - INTERVAL 30 DAY
     ORDER BY t.clock_in_at DESC
     LIMIT 100"
);
$stmt->execute([$uid]);
$history = $stmt->fetchAll();

// ---------- Totals (this week / this month) ----------
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(hours_worked),0) FROM timeclock
     WHERE user_id = ? AND clock_in_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
);
$stmt->execute([$uid]);
$weekHours = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(hours_worked),0) FROM timeclock
     WHERE user_id = ? AND clock_in_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"
);
$stmt->execute([$uid]);
$monthHours = (float)$stmt->fetchColumn();

$page_title = 'Time Clock';
include 'warehouse_header.php';
?>

<!-- ============ KPI tiles ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-clock-history"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($weekHours, 2, ',', ' ') ?> h</div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Last 7 days</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-calendar-month"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= number_format($monthHours, 2, ',', ' ') ?> h</div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Last 30 days</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(<?= $openEntry ? '34,197,94' : '107,114,128' ?>,.15);color:<?= $openEntry ? '#16a34a' : '#4b5563' ?>;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-<?= $openEntry ? 'person-check-fill' : 'person-dash' ?>"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $openEntry ? 'ON' : 'OFF' ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Shift status</div>
      </div>
    </div>
  </div>
</div>

<!-- ============ CLOCK CARD ============ -->
<div class="table-card">
  <?php if (isset($_GET['in']) && $_GET['in'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Clocked in. Have a good shift!
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['out']) && $_GET['out'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Clocked out. Hours recorded.
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <?php if ($openEntry): ?>
    <!-- Currently clocked in -->
    <div style="text-align:center;padding:24px 12px;">
      <div style="font-size:.8rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:1px;">Clocked in since</div>
      <div style="font-size:1.6rem;font-weight:800;margin:8px 0 4px;"><?= htmlspecialchars(date('d-M-Y H:i', strtotime($openEntry['clock_in_at']))) ?></div>
      <div style="font-size:2.4rem;font-weight:800;color:var(--primary);margin:14px 0;">
        <i class="bi bi-hourglass-split"></i>
        <span id="liveDuration">--:--</span>
      </div>

      <form method="POST" style="margin-top:20px;">
        <input type="hidden" name="action" value="clock_out">
        <button type="submit" class="btn-add"
                style="padding:14px 36px;font-size:1.1rem;background:#dc2626;color:#fff;"
                onclick="return confirm('Clock out now?');">
          <i class="bi bi-box-arrow-right"></i> Clock Out
        </button>
      </form>
    </div>

    <script>
    (function() {
      const startTime = new Date("<?= date('Y-m-d H:i:s', strtotime($openEntry['clock_in_at'])) ?>".replace(' ', 'T')).getTime();
      function tick() {
        const now = Date.now();
        const diffSec = Math.max(0, Math.floor((now - startTime) / 1000));
        const h = Math.floor(diffSec / 3600);
        const m = Math.floor((diffSec % 3600) / 60);
        const s = diffSec % 60;
        document.getElementById('liveDuration').textContent =
          String(h).padStart(2,'0') + ':' +
          String(m).padStart(2,'0') + ':' +
          String(s).padStart(2,'0');
      }
      tick(); setInterval(tick, 1000);
    })();
    </script>

  <?php else: ?>
    <!-- Not clocked in -->
    <div style="text-align:center;padding:24px 12px;">
      <div style="font-size:.8rem;text-transform:uppercase;color:var(--text-muted);font-weight:600;letter-spacing:1px;">You are not clocked in</div>
      <div style="font-size:2.4rem;font-weight:800;color:var(--text-muted);margin:14px 0;">
        <i class="bi bi-person-dash"></i>
      </div>
      <form method="POST" style="max-width:420px;margin:20px auto 0;">
        <input type="hidden" name="action" value="clock_in">
        <input type="text" name="notes" maxlength="255" placeholder="Optional note (e.g. Helsinki floor)"
               style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;margin-bottom:14px;">
        <button type="submit" class="btn-add" style="padding:14px 36px;font-size:1.1rem;">
          <i class="bi bi-box-arrow-in-right"></i> Clock In
        </button>
      </form>
    </div>
  <?php endif; ?>
</div>

<!-- ============ HISTORY ============ -->
<div class="table-card">
  <h5><i class="bi bi-clock-history"></i> My Recent Entries (last 30 days)</h5>

  <?php if (!$history): ?>
    <p style="color:var(--text-muted);">No entries yet. Clock in to start.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:60vh;">
    <table style="min-width:800px;white-space:nowrap;">
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th>#</th>
          <th>Date</th>
          <th>Clock in</th>
          <th>Clock out</th>
          <th>Hours</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($history as $h): ?>
        <?php
          $ci = strtotime($h['clock_in_at']);
          $co = $h['clock_out_at'] ? strtotime($h['clock_out_at']) : null;
        ?>
        <tr>
          <td><?= (int)$h['id'] ?></td>
          <td><strong><?= htmlspecialchars(date('d-M-Y', $ci)) ?></strong>
              <span style="color:var(--text-muted);font-size:.75rem;"><?= htmlspecialchars(date('D', $ci)) ?></span></td>
          <td><?= htmlspecialchars(date('H:i', $ci)) ?></td>
          <td><?= $co ? htmlspecialchars(date('H:i', $co)) : '<span class="status-badge status-active">on shift</span>' ?></td>
          <td><?= $h['hours_worked'] !== null ? number_format((float)$h['hours_worked'], 2, ',', ' ') . ' h' : '—' ?></td>
          <td style="color:var(--text-muted);font-size:.85rem;"><?= htmlspecialchars($h['notes'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
