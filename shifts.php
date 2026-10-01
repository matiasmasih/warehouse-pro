<?php
// shifts.php — Shift roster (manager/admin manage, staff view own)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('shifts');

$role   = current_role();
$uid    = (int)$_SESSION['user_id'];
$isMgr  = in_array($role, ['admin','manager'], true);

$page_title = 'Shifts';
include 'warehouse_header.php';

// ---------- Filters ----------
$from   = trim($_GET['from'] ?? date('Y-m-d'));
$to     = trim($_GET['to']   ?? date('Y-m-d', strtotime('+13 days')));
$type   = trim($_GET['type'] ?? '');
$user   = (int)($_GET['user'] ?? 0);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d', strtotime('+13 days'));

// ---------- Build query ----------
$where = ["s.shift_date BETWEEN ? AND ?"];
$args  = [$from, $to];

if (!empty($type) && in_array($type, ['morning','evening','night'], true)) {
    $where[] = "s.shift_type = ?";
    $args[]  = $type;
}
if ($isMgr) {
    if ($user > 0) { $where[] = "s.user_id = ?"; $args[] = $user; }
} else {
    $where[] = "s.user_id = ?";
    $args[]  = $uid;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT s.id, s.shift_date, s.shift_type, s.starts_at, s.ends_at,
               s.notes, s.created_at,
               u.username, u.role AS user_role,
               a.username AS assigned_by_name
        FROM shifts s
        LEFT JOIN users u ON u.id = s.user_id
        LEFT JOIN users a ON a.id = s.assigned_by
        $whereSql
        ORDER BY s.shift_date ASC, s.starts_at ASC, u.username ASC
        LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

// Staff dropdown for filter
$users = $isMgr
    ? $pdo->query("SELECT id, username, role FROM users WHERE is_active = 1 ORDER BY username")->fetchAll()
    : [];

// KPI counts
$today = date('Y-m-d');
$todayCount = 0; $morningCount = 0; $eveningCount = 0; $nightCount = 0;
foreach ($rows as $r) {
    if ($r['shift_date'] === $today) $todayCount++;
    if ($r['shift_type'] === 'morning')   $morningCount++;
    elseif ($r['shift_type'] === 'evening') $eveningCount++;
    elseif ($r['shift_type'] === 'night')   $nightCount++;
}
?>

<!-- ============ KPI tiles ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-calendar-check"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $todayCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Today</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.15);color:#2563eb;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-sunrise"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $morningCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Morning</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-sunset"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $eveningCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Evening</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-moon-stars"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $nightCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Night</div>
      </div>
    </div>
  </div>
</div>

<!-- ============ ROSTER ============ -->
<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;">
      <i class="bi bi-calendar-week"></i>
      <?= $isMgr ? 'Shift Roster' : 'My Shifts' ?> (<?= count($rows) ?>)
    </h5>
    <?php if ($isMgr): ?>
      <a href="admin_add_shift.php" class="btn-add" style="text-decoration:none;">
        <i class="bi bi-plus-circle-fill"></i> Assign Shift
      </a>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved']) && $_GET['saved'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Shift saved successfully.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'success'): ?>
    <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
      Shift removed.
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
      <?php
        switch ($_GET['error']) {
          case 'invalid_id': echo 'Invalid shift ID.'; break;
          case 'not_found':  echo 'Shift not found.'; break;
          case 'failed':     echo 'Operation failed. Please try again.'; break;
          default:           echo 'An unknown error occurred.'; break;
        }
      ?>
    </div>
  <?php endif; ?>

  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:center;">
    <label style="display:flex;align-items:center;gap:6px;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.9rem;">
      From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" style="border:0;background:transparent;color:inherit;padding:0;height:100%;font-size:.9rem;">
    </label>
    <label style="display:flex;align-items:center;gap:6px;height:40px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.9rem;">
      To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" style="border:0;background:transparent;color:inherit;padding:0;height:100%;font-size:.9rem;">
    </label>
    <select name="type" style="height:40px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.9rem;">
      <option value="">All types</option>
      <option value="morning"   <?= $type === 'morning'   ? 'selected' : '' ?>>Morning</option>
      <option value="evening"   <?= $type === 'evening'   ? 'selected' : '' ?>>Evening</option>
      <option value="night"     <?= $type === 'night'     ? 'selected' : '' ?>>Night</option>
    </select>
    <?php if ($isMgr): ?>
      <select name="user" style="height:40px;padding:0 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.9rem;">
        <option value="0">All staff</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int)$u['id'] ?>" <?= $user === (int)$u['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['role']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <button type="submit" class="btn-add" style="height:40px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;box-sizing:border-box;font-size:.9rem;line-height:1;">
      <i class="bi bi-search"></i> Filter
    </button>
    <a href="shifts.php" class="btn-secondary-custom" style="height:40px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;box-sizing:border-box;font-size:.9rem;text-decoration:none;line-height:1;">
      <i class="bi bi-x-circle"></i> Reset
    </a>
  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No shifts in this range.</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table style="min-width:1000px;white-space:nowrap;">
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th style="white-space:nowrap;">Date</th>
          <th style="white-space:nowrap;">Day</th>
          <th style="white-space:nowrap;">Shift</th>
          <th style="white-space:nowrap;">Time</th>
          <?php if ($isMgr): ?><th style="white-space:nowrap;">Staff</th><?php endif; ?>
          <th style="white-space:nowrap;">Notes</th>
          <?php if ($isMgr): ?><th style="white-space:nowrap;">Assigned by</th><?php endif; ?>
          <?php if ($isMgr): ?><th style="text-align:center;white-space:nowrap;">Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $s): ?>
        <?php
          $typeClass = 'status-pending';
          if ($s['shift_type'] === 'morning')   $typeClass = 'status-shipped';
          elseif ($s['shift_type'] === 'evening') $typeClass = 'status-pending';
          elseif ($s['shift_type'] === 'night')   $typeClass = 'status-quarantine';
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($s['shift_date']) ?></strong></td>
          <td><?= htmlspecialchars(date('D', strtotime($s['shift_date']))) ?></td>
          <td><span class="status-badge <?= $typeClass ?>"><?= htmlspecialchars($s['shift_type']) ?></span></td>
          <td><code><?= htmlspecialchars(substr($s['starts_at'], 0, 5)) ?> – <?= htmlspecialchars(substr($s['ends_at'], 0, 5)) ?></code></td>
          <?php if ($isMgr): ?>
            <td><strong><?= htmlspecialchars($s['username'] ?? '—') ?></strong>
                <span style="color:var(--text-muted);font-size:.75rem;">(<?= htmlspecialchars($s['user_role'] ?? '') ?>)</span></td>
          <?php endif; ?>
          <td><?= htmlspecialchars($s['notes'] ?? '—') ?></td>
          <?php if ($isMgr): ?>
            <td><?= htmlspecialchars($s['assigned_by_name'] ?? '—') ?></td>
            <td style="text-align:center;white-space:nowrap;">
              <a href="admin_edit_shift.php?id=<?= (int)$s['id'] ?>" class="btn-add"
                 style="padding:4px 10px;font-size:.8rem;text-decoration:none;">
                <i class="bi bi-pencil-fill"></i> Edit
              </a>
              <a href="admin_delete_shift.php?id=<?= (int)$s['id'] ?>" class="btn-add"
                 style="padding:4px 10px;font-size:.8rem;text-decoration:none;background:#dc2626;color:#fff;"
                 onclick="return confirm('Remove this shift?');">
                <i class="bi bi-trash-fill"></i> Remove
              </a>
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
