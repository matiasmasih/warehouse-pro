<?php
// attendance.php — Team attendance view (admin + manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('attendance');

if (!in_array(current_role(), ['admin','manager'], true)) {
    header('Location: timeclock.php');
    exit;
}

$page_title = 'Attendance';
include 'warehouse_header.php';

// ---------- Filters ----------
$from = trim($_GET['from'] ?? date('Y-m-d'));
$to   = trim($_GET['to']   ?? date('Y-m-d'));
$user = (int)($_GET['user'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

$where = ["t.clock_in_at BETWEEN ? AND ?"];
$args  = [$from . ' 00:00:00', $to . ' 23:59:59'];

if ($user > 0) {
    $where[] = "t.user_id = ?";
    $args[]  = $user;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT t.id, t.user_id, t.clock_in_at, t.clock_out_at, t.hours_worked, t.notes,
            u.username, u.role AS user_role
     FROM timeclock t
     LEFT JOIN users u ON u.id = t.user_id
     $whereSql
     ORDER BY t.clock_in_at DESC, t.id DESC
     LIMIT 500"
);
$stmt->execute($args);
$rows = $stmt->fetchAll();

// ---------- KPI ----------
// Currently on shift
$onShift = (int)$pdo->query(
    "SELECT COUNT(*) FROM timeclock WHERE clock_out_at IS NULL"
)->fetchColumn();

// Entries today
$today = date('Y-m-d');
$kStmt = $pdo->prepare("SELECT COUNT(*) FROM timeclock WHERE DATE(clock_in_at) = ?");
$kStmt->execute([$today]);
$entriesToday = (int)$kStmt->fetchColumn();

// Total hours in filter range
$hStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(hours_worked),0) FROM timeclock
     WHERE clock_in_at BETWEEN ? AND ?"
);
$hStmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
$totalHoursRange = (float)$hStmt->fetchColumn();

// Distinct staff in range
$sStmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT user_id) FROM timeclock
     WHERE clock_in_at BETWEEN ? AND ?"
);
$sStmt->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
$staffCount = (int)$sStmt->fetchColumn();

// Staff dropdown
$users = $pdo->query(
    "SELECT id, username FROM users WHERE is_active = 1 AND role IN ('staff','manager') ORDER BY username"
)->fetchAll();
?>

<!-- ============ KPI tiles ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-person-check-fill"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $onShift ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">On shift now</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-calendar-check"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $entriesToday ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Entries today</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-hourglass"></i></div>
      <div>
        <div style="font-size:1.4rem;font-weight:800;"><?= number_format($totalHoursRange, 2, ',', ' ') ?> h</div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Hours in range</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-people"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $staffCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Staff in range</div>
      </div>
    </div>
  </div>
</div>

<!-- ============ LIST ============ -->
<div class="table-card">
  <h5><i class="bi bi-clock-history"></i> Attendance Entries (<?= count($rows) ?>)</h5>

  <form method="get" style="display:flex;gap:8px;margin-bottom:16px;align-items:center;flex-wrap:wrap;">
    <label style="display:flex;align-items:center;gap:4px;height:38px;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.85rem;white-space:nowrap;">
      From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
                  style="border:0;background:transparent;color:inherit;padding:0;font-size:.85rem;width:110px;">
    </label>

    <label style="display:flex;align-items:center;gap:4px;height:38px;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.85rem;white-space:nowrap;">
      To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
                style="border:0;background:transparent;color:inherit;padding:0;font-size:.85rem;width:110px;">
    </label>

    <select name="user"
            style="height:38px;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.85rem;">
      <option value="0">All staff</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= $user === (int)$u['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($u['username']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn-add"
            style="height:38px;padding:0 14px;display:inline-flex;align-items:center;gap:6px;box-sizing:border-box;font-size:.85rem;line-height:1;white-space:nowrap;">
      <i class="bi bi-search"></i> Filter
    </button>

    <a href="attendance.php" class="btn-secondary-custom"
       style="height:38px;padding:0 14px;display:inline-flex;align-items:center;gap:6px;box-sizing:border-box;font-size:.85rem;text-decoration:none;line-height:1;white-space:nowrap;">
      <i class="bi bi-x-circle"></i> Reset
    </a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No attendance entries in this range.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table style="min-width:1100px;white-space:nowrap;">
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th>#</th>
          <th>Date</th>
          <th>Staff</th>
          <th>Role</th>
          <th>Clock in</th>
          <th>Clock out</th>
          <th>Hours</th>
          <th>Notes</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $ci = strtotime($r['clock_in_at']);
          $co = $r['clock_out_at'] ? strtotime($r['clock_out_at']) : null;
        ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><strong><?= htmlspecialchars(date('d-M-Y', $ci)) ?></strong></td>
          <td><strong><?= htmlspecialchars($r['username'] ?? '—') ?></strong></td>
          <td><span style="color:var(--text-muted);font-size:.85rem;"><?= htmlspecialchars($r['user_role'] ?? '') ?></span></td>
          <td><?= htmlspecialchars(date('H:i', $ci)) ?></td>
          <td><?= $co ? htmlspecialchars(date('H:i', $co)) : '<span class="status-badge status-active">on shift</span>' ?></td>
          <td><?= $r['hours_worked'] !== null ? number_format((float)$r['hours_worked'], 2, ',', ' ') . ' h' : '—' ?></td>
          <td style="color:var(--text-muted);font-size:.85rem;"><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
