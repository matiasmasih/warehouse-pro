<?php
// failed_logins.php — Security audit: failed login attempts (admin + manager)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('failed_logins');

$page_title = 'Failed Logins';
include 'warehouse_header.php';

// Filters
$q      = trim($_GET['q'] ?? '');
$from   = trim($_GET['from'] ?? date('Y-m-d', strtotime('-7 days')));
$to     = trim($_GET['to']   ?? date('Y-m-d'));
$reason = trim($_GET['reason'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-7 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

$where = ["created_at BETWEEN ? AND ?"];
$args  = [$from . ' 00:00:00', $to . ' 23:59:59'];

if ($q !== '') {
    $where[] = "(username_attempted LIKE ? OR ip_address LIKE ?)";
    array_push($args, "%$q%", "%$q%");
}
if (!empty($reason) && in_array($reason, ['unknown_user','bad_password','inactive_account'], true)) {
    $where[] = "reason = ?";
    $args[]  = $reason;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT id, username_attempted, ip_address, user_agent, reason, created_at
     FROM failed_logins
     $whereSql
     ORDER BY created_at DESC, id DESC
     LIMIT 500"
);
$stmt->execute($args);
$rows = $stmt->fetchAll();

// KPI counts
$today = date('Y-m-d');

$kStmt = $pdo->prepare("SELECT COUNT(*) FROM failed_logins WHERE DATE(created_at) = ?");
$kStmt->execute([$today]);
$kpiToday = (int)$kStmt->fetchColumn();

$kStmt = $pdo->prepare("SELECT COUNT(*) FROM failed_logins WHERE created_at >= NOW() - INTERVAL 7 DAY");
$kStmt->execute();
$kpiWeek = (int)$kStmt->fetchColumn();

$kStmt = $pdo->prepare("SELECT COUNT(*) FROM failed_logins WHERE reason = 'unknown_user' AND created_at >= NOW() - INTERVAL 7 DAY");
$kStmt->execute();
$kpiUnknown = (int)$kStmt->fetchColumn();

$kStmt = $pdo->prepare("SELECT COUNT(*) FROM failed_logins WHERE reason = 'bad_password' AND created_at >= NOW() - INTERVAL 7 DAY");
$kStmt->execute();
$kpiBadPass = (int)$kStmt->fetchColumn();
?>

<!-- ============ KPI tiles ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-shield-exclamation"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $kpiToday ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Today</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-calendar-week"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $kpiWeek ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Last 7 days</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(139,92,246,.15);color:#7c3aed;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-person-x"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $kpiUnknown ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Unknown users (7d)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-key"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= $kpiBadPass ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Wrong password (7d)</div>
      </div>
    </div>
  </div>
</div>

<!-- ============ LOG ============ -->
<div class="table-card">
  <h5><i class="bi bi-shield-exclamation"></i> Failed Login Attempts (<?= count($rows) ?>)</h5>

  <form method="get" style="display:flex;gap:8px;margin-bottom:16px;align-items:center;flex-wrap:wrap;">

    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search…"
           style="width:160px;height:38px;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.85rem;">

    <label style="display:flex;align-items:center;gap:4px;height:38px;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.85rem;white-space:nowrap;">
      From <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
                  style="border:0;background:transparent;color:inherit;padding:0;font-size:.85rem;width:110px;">
    </label>

    <label style="display:flex;align-items:center;gap:4px;height:38px;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.85rem;white-space:nowrap;">
      To <input type="date" name="to" value="<?= htmlspecialchars($to) ?>"
                style="border:0;background:transparent;color:inherit;padding:0;font-size:.85rem;width:110px;">
    </label>

    <select name="reason"
            style="height:38px;padding:0 10px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);box-sizing:border-box;font-size:.85rem;">
      <option value="">All reasons</option>
      <option value="unknown_user"     <?= $reason === 'unknown_user'     ? 'selected' : '' ?>>Unknown user</option>
      <option value="bad_password"     <?= $reason === 'bad_password'     ? 'selected' : '' ?>>Bad password</option>
      <option value="inactive_account" <?= $reason === 'inactive_account' ? 'selected' : '' ?>>Inactive account</option>
    </select>

    <button type="submit" class="btn-add"
            style="height:38px;padding:0 14px;display:inline-flex;align-items:center;gap:6px;box-sizing:border-box;font-size:.85rem;line-height:1;white-space:nowrap;">
      <i class="bi bi-search"></i> Filter
    </button>

    <a href="failed_logins.php" class="btn-secondary-custom"
       style="height:38px;padding:0 14px;display:inline-flex;align-items:center;gap:6px;box-sizing:border-box;font-size:.85rem;text-decoration:none;line-height:1;white-space:nowrap;">
      <i class="bi bi-x-circle"></i> Reset
    </a>

  </form>

  <?php if (!$rows): ?>
    <p style="color:var(--text-muted);">No failed attempts in this range. 👍</p>
  <?php else: ?>
  <div style="overflow:auto;max-height:70vh;">
    <table style="min-width:1000px;white-space:nowrap;">
      <thead>
        <tr style="position:sticky;top:0;background:var(--card-bg);z-index:2;">
          <th style="white-space:nowrap;">#</th>
          <th style="white-space:nowrap;">When</th>
          <th style="white-space:nowrap;">Username attempted</th>
          <th style="white-space:nowrap;">IP address</th>
          <th style="white-space:nowrap;">Reason</th>
          <th style="white-space:nowrap;">User agent</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php
          $reasonClass = 'status-cancelled';
          if ($r['reason'] === 'bad_password')          $reasonClass = 'status-pending';
          elseif ($r['reason'] === 'inactive_account')  $reasonClass = 'status-quarantine';
        ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['created_at']) ?></td>
          <td><strong><?= htmlspecialchars($r['username_attempted']) ?></strong></td>
          <td><code><?= htmlspecialchars($r['ip_address']) ?></code></td>
          <td><span class="status-badge <?= $reasonClass ?>"><?= htmlspecialchars(str_replace('_',' ', $r['reason'])) ?></span></td>
          <td style="color:var(--text-muted);font-size:.8rem;max-width:400px;overflow:hidden;text-overflow:ellipsis;">
            <?= htmlspecialchars($r['user_agent'] ?? '—') ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
