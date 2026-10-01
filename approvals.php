<?php
// approvals.php — Manager approval inbox
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (current_role() === 'staff') { header('Location: staff_dashboard.php'); exit; }

$uid = (int)$_SESSION['user_id'];
$isManager = in_array(current_role(), ['admin','manager'], true);

$formMsg = $_SESSION['ap_msg'] ?? '';
unset($_SESSION['ap_msg']);
$formErr = '';

// ============================================================
// APPROVE / REJECT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_action']) && $isManager) {
    $movementId = (int)($_POST['movement_id'] ?? 0);
    $action     = $_POST['action'] ?? '';

    if (!in_array($action, ['approve','reject'], true)) {
        $formErr = 'Invalid action.';
    } elseif ($movementId <= 0) {
        $formErr = 'No movement selected.';
    } else {
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare("SELECT id, approval_status, movement_type, quantity, product_id FROM stock_movements WHERE id = ? FOR UPDATE");
            $st->execute([$movementId]);
            $m = $st->fetch();
            if (!$m) throw new RuntimeException('Movement not found.');
            if ($m['approval_status'] !== 'pending') throw new RuntimeException('This movement is not pending.');

            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $pdo->prepare("UPDATE stock_movements SET approval_status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?")
                ->execute([$newStatus, $uid, $movementId]);

            $pdo->prepare("INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)")
                ->execute([$uid, 'approval_' . $action, "Movement #{$movementId} ({$m['movement_type']}, qty {$m['quantity']}) " . ($action === 'approve' ? 'approved' : 'rejected')]);

            $pdo->commit();
            $_SESSION['ap_msg'] = "Movement #{$movementId} " . ($action === 'approve' ? 'approved' : 'rejected') . '.';
            header('Location: approvals.php'); exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $formErr = $e->getMessage();
        }
    }
}

// ============================================================
// DATA: pending movements
// ============================================================
$pending = $pdo->query(
    "SELECT m.id, m.movement_type, m.quantity, m.reason, m.reference, m.timestamp,
            m.from_location, m.to_location, m.approval_status,
            p.name AS product_name, p.sku,
            fl.code AS from_bin, tl.code AS to_bin,
            u.username AS requested_by,
            wf.name AS from_wh, wt.name AS to_wh
     FROM stock_movements m
     LEFT JOIN products  p  ON p.id  = m.product_id
     LEFT JOIN locations fl ON fl.id = m.from_location
     LEFT JOIN locations tl ON tl.id = m.to_location
     LEFT JOIN warehouses wf ON wf.id = fl.warehouse_id
     LEFT JOIN warehouses wt ON wt.id = tl.warehouse_id
     LEFT JOIN users     u  ON u.id  = m.user_id
     WHERE m.approval_status = 'pending'
     ORDER BY m.id DESC
     LIMIT 100"
)->fetchAll();

// ============================================================
// DATA: recently approved/rejected
// ============================================================
$history = $pdo->query(
    "SELECT m.id, m.movement_type, m.quantity, m.approval_status, m.approved_at,
            p.name AS product_name, u.username AS decided_by,
            fl.code AS from_bin, tl.code AS to_bin
     FROM stock_movements m
     LEFT JOIN products  p ON p.id = m.product_id
     LEFT JOIN locations fl ON fl.id = m.from_location
     LEFT JOIN locations tl ON tl.id = m.to_location
     LEFT JOIN users     u ON u.id = m.approved_by
     WHERE m.approval_status IN ('approved','rejected')
     ORDER BY m.approved_at DESC
     LIMIT 25"
)->fetchAll();

$page_title = 'Approvals';
include 'warehouse_header.php';
?>

<?php if ($formMsg): ?>
  <div style="padding:12px 16px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($formMsg) ?>
  </div>
<?php endif; ?>
<?php if ($formErr): ?>
  <div style="padding:12px 16px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($formErr) ?>
  </div>
<?php endif; ?>

<!-- KPI strip -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= count($pending) ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Pending approvals</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-check-circle"></i></div>
      <div>
        <?php $approvedCount = 0; foreach ($history as $h) if ($h['approval_status'] === 'approved') $approvedCount++; ?>
        <div style="font-size:1.5rem;font-weight:800;"><?= $approvedCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Recently approved</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-x-circle"></i></div>
      <div>
        <?php $rejectedCount = 0; foreach ($history as $h) if ($h['approval_status'] === 'rejected') $rejectedCount++; ?>
        <div style="font-size:1.5rem;font-weight:800;"><?= $rejectedCount ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Recently rejected</div>
      </div>
    </div>
  </div>
</div>

<!-- Pending queue -->
<div class="table-card" style="margin-bottom:20px;">
  <h5><i class="bi bi-hourglass-split"></i> Pending Approvals (<?= count($pending) ?>)</h5>
  <?php if (!$pending): ?>
    <p style="color:var(--text-muted);">No pending approvals. Queue is empty. 👍</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table style="min-width:1300px;white-space:nowrap;">
      <thead>
        <tr>
          <th>When</th>
          <th>Type</th>
          <th>Product</th>
          <th>From</th>
          <th>To</th>
          <th>Qty</th>
          <th>Reason</th>
          <th>Reference</th>
          <th>Requested by</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pending as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['timestamp']) ?></td>
          <td><span class="status-badge status-<?= htmlspecialchars($m['movement_type']) ?>"><?= htmlspecialchars($m['movement_type']) ?></span></td>
          <td><?= htmlspecialchars($m['product_name'] ?? '—') ?><?= $m['sku'] ? ' · ' . htmlspecialchars($m['sku']) : '' ?></td>
          <td>
            <?= $m['from_bin'] ? '<code>' . htmlspecialchars($m['from_bin']) . '</code>' : '—' ?>
            <?php if ($m['from_wh']): ?><div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($m['from_wh']) ?></div><?php endif; ?>
          </td>
          <td>
            <?= $m['to_bin'] ? '<code>' . htmlspecialchars($m['to_bin']) . '</code>' : '—' ?>
            <?php if ($m['to_wh']): ?><div style="font-size:.72rem;color:var(--text-muted);"><?= htmlspecialchars($m['to_wh']) ?></div><?php endif; ?>
          </td>
          <td><?= number_format((int)$m['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($m['reason'] ?? '—') ?></td>
          <td><?= htmlspecialchars($m['reference'] ?? '—') ?></td>
          <td><?= htmlspecialchars($m['requested_by'] ?? '—') ?></td>
          <td style="white-space:nowrap;">
            <form method="post" style="display:inline;">
              <input type="hidden" name="movement_id" value="<?= (int)$m['id'] ?>">
              <input type="hidden" name="action" value="approve">
              <button type="submit" name="do_action" value="1" class="btn-add" style="padding:4px 10px;font-size:.78rem;">
                <i class="bi bi-check2"></i> Approve
              </button>
            </form>
            <form method="post" style="display:inline;margin-left:4px;">
              <input type="hidden" name="movement_id" value="<?= (int)$m['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <button type="submit" name="do_action" value="1" class="btn-delete" style="padding:4px 10px;font-size:.78rem;">
                <i class="bi bi-x"></i> Reject
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- History -->
<div class="table-card">
  <h5><i class="bi bi-clock-history"></i> Recent Decisions</h5>
  <?php if (!$history): ?>
    <p style="color:var(--text-muted);">No decisions yet.</p>
  <?php else: ?>
  <div style="overflow-x:auto;">
    <table style="min-width:1000px;white-space:nowrap;">
      <thead>
        <tr><th>Decided</th><th>Status</th><th>Type</th><th>Product</th><th>From</th><th>To</th><th>Qty</th><th>By</th></tr>
      </thead>
      <tbody>
      <?php foreach ($history as $h): ?>
        <tr>
          <td><?= htmlspecialchars($h['approved_at'] ?? '—') ?></td>
          <td>
            <?php if ($h['approval_status'] === 'approved'): ?>
              <span class="status-badge status-active"><i class="bi bi-check2"></i> approved</span>
            <?php else: ?>
              <span class="status-badge status-cancelled"><i class="bi bi-x"></i> rejected</span>
            <?php endif; ?>
          </td>
          <td><span class="status-badge status-<?= htmlspecialchars($h['movement_type']) ?>"><?= htmlspecialchars($h['movement_type']) ?></span></td>
          <td><?= htmlspecialchars($h['product_name'] ?? '—') ?></td>
          <td><?= $h['from_bin'] ? '<code>' . htmlspecialchars($h['from_bin']) . '</code>' : '—' ?></td>
          <td><?= $h['to_bin']   ? '<code>' . htmlspecialchars($h['to_bin'])   . '</code>' : '—' ?></td>
          <td><?= number_format((int)$h['quantity'], 0, ',', ' ') ?></td>
          <td><?= htmlspecialchars($h['decided_by'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
