<?php
// messages.php — Internal messaging (ticket inbox)
// Staff: send messages + view their own history
// Admin/Manager: view all messages + reply inline
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_access('messages');

$uid    = (int)$_SESSION['user_id'];
$role   = current_role();
$isMgr  = in_array($role, ['admin','manager'], true);

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$flash_error   = '';
$flash_success = '';

// ---------- POST handler ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $flash_error = 'Invalid security token. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // ---- Staff: send new message ----
        if ($action === 'send' && !$isMgr) {
            $text = trim($_POST['message'] ?? '');
            if ($text === '') {
                $flash_error = 'Message cannot be empty.';
            } elseif (mb_strlen($text) > 2000) {
                $flash_error = 'Message must be 2000 characters or less.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO messages (user_id, message, status) VALUES (?, ?, 'pending')"
                    );
                    $stmt->execute([$uid, $text]);
                    $msgId = (int)$pdo->lastInsertId();

                    $log = $pdo->prepare(
                        "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
                    );
                    $log->execute([
                        $uid, 'message_sent',
                        "Sent message #{$msgId}"
                    ]);

                    $flash_success = 'Message sent.';
                } catch (PDOException $e) {
                    $flash_error = 'Database error: ' . $e->getMessage();
                }
            }
        }

        // ---- Manager: reply to a message ----
        if ($action === 'reply' && $isMgr) {
            $msgId = (int)($_POST['message_id'] ?? 0);
            $reply = trim($_POST['reply'] ?? '');

            if ($msgId <= 0) {
                $flash_error = 'Invalid message ID.';
            } elseif ($reply === '') {
                $flash_error = 'Reply cannot be empty.';
            } elseif (mb_strlen($reply) > 2000) {
                $flash_error = 'Reply must be 2000 characters or less.';
            } else {
                try {
                    $chk = $pdo->prepare("SELECT id FROM messages WHERE id = ?");
                    $chk->execute([$msgId]);
                    if (!$chk->fetchColumn()) {
                        $flash_error = 'Message not found.';
                    } else {
                        $upd = $pdo->prepare(
                            "UPDATE messages
                             SET admin_reply = ?, replied_at = NOW(), status = 'replied'
                             WHERE id = ?"
                        );
                        $upd->execute([$reply, $msgId]);

                        $log = $pdo->prepare(
                            "INSERT INTO audit_logs (user_id, action, description) VALUES (?, ?, ?)"
                        );
                        $log->execute([
                            $uid, 'message_replied',
                            "Replied to message #{$msgId}"
                        ]);

                        $flash_success = 'Reply sent.';
                    }
                } catch (PDOException $e) {
                    $flash_error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

// ---------- Data for view ----------
$filterStatus = trim($_GET['status'] ?? '');
$filterUser   = (int)($_GET['user'] ?? 0);

if ($isMgr) {
    // Manager/Admin sees all messages
    $where = []; $args = [];
    if ($filterStatus !== '') { $where[] = "m.status = ?"; $args[] = $filterStatus; }
    if ($filterUser > 0)      { $where[] = "m.user_id = ?"; $args[] = $filterUser; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $stmt = $pdo->prepare(
        "SELECT m.*, u.username, u.role AS user_role
         FROM messages m
         LEFT JOIN users u ON u.id = m.user_id
         $whereSql
         ORDER BY m.sent_at DESC, m.id DESC
         LIMIT 200"
    );
    $stmt->execute($args);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $users = $pdo->query("SELECT id, username FROM users ORDER BY username")->fetchAll();

    $counts = ['pending'=>0, 'replied'=>0];
    $cStmt = $pdo->query("SELECT status, COUNT(*) AS cnt FROM messages GROUP BY status");
    foreach ($cStmt->fetchAll() as $row) {
        $counts[$row['status']] = (int)$row['cnt'];
    }
} else {
    // Staff sees only their own
    $stmt = $pdo->prepare(
        "SELECT m.* FROM messages m WHERE m.user_id = ? ORDER BY m.sent_at DESC, m.id DESC LIMIT 200"
    );
    $stmt->execute([$uid]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $users = [];
    $counts = ['pending'=>0, 'replied'=>0];
    foreach ($messages as $m) {
        if (isset($counts[$m['status']])) $counts[$m['status']]++;
    }
}

$page_title = 'Messages';
include 'warehouse_header.php';
?>

<?php if ($flash_error): ?>
  <div class="status-badge status-danger" style="display:block;padding:10px 14px;margin-bottom:14px;">
    <?= htmlspecialchars($flash_error) ?>
  </div>
<?php endif; ?>
<?php if ($flash_success): ?>
  <div class="status-badge status-active" style="display:block;padding:10px 14px;margin-bottom:14px;">
    <?= htmlspecialchars($flash_success) ?>
  </div>
<?php endif; ?>

<!-- ============ KPI tiles ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(245,158,11,.15);color:var(--primary);display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= (int)$counts['pending'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Pending</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="table-card" style="padding:16px;display:flex;align-items:center;gap:12px;">
      <div style="width:42px;height:42px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;display:grid;place-items:center;font-size:1.2rem;"><i class="bi bi-check-circle"></i></div>
      <div>
        <div style="font-size:1.5rem;font-weight:800;"><?= (int)$counts['replied'] ?></div>
        <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);font-weight:600;">Replied</div>
      </div>
    </div>
  </div>
</div>

<?php if (!$isMgr): ?>
<!-- ============ STAFF: new-message form ============ -->
<div class="table-card">
  <h5><i class="bi bi-pencil-square"></i> Send a Message</h5>
  <form method="POST" autocomplete="off">
    <input type="hidden" name="action" value="send">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <textarea name="message" rows="4" required maxlength="2000"
              placeholder="Describe your issue or question for the manager…"
              style="width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:1rem;font-family:inherit;resize:vertical;"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

    <div style="display:flex;justify-content:flex-end;margin-top:12px;">
      <button type="submit" class="btn-add">
        <i class="bi bi-send"></i> Send Message
      </button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- ============ INBOX ============ -->
<div class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <h5 style="margin:0;">
      <i class="bi bi-chat-dots"></i>
      <?= $isMgr ? 'All Messages' : 'My Messages' ?> (<?= count($messages) ?>)
    </h5>
  </div>

  <?php if ($isMgr): ?>
  <form method="get" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
    <select name="status" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="">All statuses</option>
      <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
      <option value="replied" <?= $filterStatus === 'replied' ? 'selected' : '' ?>>Replied</option>
    </select>
    <select name="user" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card-bg);color:var(--text);">
      <option value="0">All users</option>
      <?php foreach ($users as $u): ?>
        <option value="<?= (int)$u['id'] ?>" <?= $filterUser === (int)$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-add"><i class="bi bi-search"></i> Filter</button>
    <a href="messages.php" class="btn-secondary-custom"><i class="bi bi-x-circle"></i> Reset</a>
  </form>
  <?php endif; ?>

  <?php if (!$messages): ?>
    <p style="color:var(--text-muted);">No messages yet.</p>
  <?php else: ?>
    <?php foreach ($messages as $m): ?>
      <div style="border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:14px;background:var(--card-bg);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px;">
          <div>
            <?php if ($isMgr): ?>
              <strong><?= htmlspecialchars($m['username'] ?? 'user') ?></strong>
              <span style="color:var(--text-muted);font-size:.85rem;">
                (<?= htmlspecialchars($m['user_role'] ?? '') ?>)
              </span>
              <br>
            <?php endif; ?>
            <span style="color:var(--text-muted);font-size:.82rem;">
              <i class="bi bi-clock"></i> <?= htmlspecialchars($m['sent_at']) ?>
              · Message #<?= (int)$m['id'] ?>
            </span>
          </div>
          <span class="status-badge status-<?= $m['status'] === 'replied' ? 'active' : 'pending' ?>">
            <?= $m['status'] === 'replied' ? 'Replied' : 'Pending' ?>
          </span>
        </div>

        <div style="background:#f8fafc;padding:12px 14px;border-radius:10px;border-left:3px solid var(--border);white-space:pre-wrap;"><?= htmlspecialchars($m['message']) ?></div>

        <?php if ($m['status'] === 'replied' && $m['admin_reply']): ?>
          <div style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:12px 14px;margin-top:12px;">
            <div style="color:#16a34a;font-weight:700;font-size:.9rem;margin-bottom:6px;">
              <i class="bi bi-shield-check"></i> Manager Reply
            </div>
            <div style="white-space:pre-wrap;"><?= htmlspecialchars($m['admin_reply']) ?></div>
            <div style="color:var(--text-muted);font-size:.8rem;text-align:right;margin-top:6px;">
              Replied <?= htmlspecialchars($m['replied_at'] ?? '') ?>
            </div>
          </div>
        <?php elseif ($isMgr): ?>
          <form method="POST" style="margin-top:12px;" autocomplete="off">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <textarea name="reply" rows="3" required maxlength="2000"
                      placeholder="Type your reply…"
                      style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:.95rem;font-family:inherit;resize:vertical;"></textarea>
            <div style="display:flex;justify-content:flex-end;margin-top:8px;">
              <button type="submit" class="btn-add" style="padding:6px 14px;font-size:.85rem;">
                <i class="bi bi-send"></i> Send Reply
              </button>
            </div>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
