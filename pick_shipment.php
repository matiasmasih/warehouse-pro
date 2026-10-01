<?php
// pick_shipment.php — Pick and ship a pending shipment (outbound workflow)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (my_access('shipments') === 'no') { header('Location: staff_dashboard.php'); exit; }

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? $_POST['shipment_id'] ?? 0);
if ($id <= 0) { header('Location: shipments.php'); exit; }

// Load shipment header
$st = $pdo->prepare(
    "SELECT sh.*, w.name AS warehouse_name, w.city
     FROM shipments sh
     LEFT JOIN warehouses w ON w.id = sh.warehouse_id
     WHERE sh.id = ? LIMIT 1"
);
$st->execute([$id]);
$shipment = $st->fetch();
if (!$shipment) { header('Location: shipments.php'); exit; }

$errors   = [];
$success  = false;
$tracking = '';

// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($shipment['status'] !== 'pending') {
        $errors[] = "This shipment is already '{$shipment['status']}' — nothing to pick.";
    } else {
        $lines = $pdo->prepare(
            "SELECT si.*, p.name AS product_name, p.sku
             FROM shipment_items si
             LEFT JOIN products p ON p.id = si.product_id
             WHERE si.shipment_id = ?"
        );
        $lines->execute([$id]);
        $lines = $lines->fetchAll();

        $postedQty   = $_POST['qty']   ?? [];
        $postedBatch = $_POST['batch'] ?? [];

        // Auto-generate a tracking number for this shipment
        $rand     = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
        $tracking = 'WH-' . date('Ymd') . '-SH' . $id . '-' . $rand;

        try {
            $pdo->beginTransaction();

            // Lock shipment row
            $lock = $pdo->prepare("SELECT status FROM shipments WHERE id = ? FOR UPDATE");
            $lock->execute([$id]);
            if ($lock->fetchColumn() !== 'pending') {
                throw new RuntimeException("Shipment is no longer pending.");
            }

            $pickedLines = 0;

            foreach ($lines as $L) {
                $lineId = (int)$L['id'];
                $qty    = (int)($postedQty[$lineId] ?? 0);
                if ($qty <= 0) continue;

                $batchId = (int)($postedBatch[$lineId] ?? 0);
                if ($batchId <= 0) {
                    throw new RuntimeException("Line {$L['product_name']}: pick a batch.");
                }

                $productId = (int)$L['product_id'];

                // Load batch + lock
                $bSt = $pdo->prepare(
                    "SELECT id, quantity, location_id, status
                     FROM product_batches
                     WHERE id = ? AND product_id = ? FOR UPDATE"
                );
                $bSt->execute([$batchId, $productId]);
                $batch = $bSt->fetch();
                if (!$batch) {
                    throw new RuntimeException("Line {$L['product_name']}: batch not found.");
                }
                if ($batch['status'] !== 'available') {
                    throw new RuntimeException("Line {$L['product_name']}: batch is '{$batch['status']}', cannot pick.");
                }
                if ((int)$batch['quantity'] < $qty) {
                    throw new RuntimeException("Line {$L['product_name']}: not enough in this batch ({$batch['quantity']} < {$qty}).");
                }

                $locId = (int)$batch['location_id'];

                // Deduct batch qty
                $newBatchQty = (int)$batch['quantity'] - $qty;
                $pdo->prepare("UPDATE product_batches SET quantity = ?, status = IF(? = 0, 'depleted', status) WHERE id = ?")
                    ->execute([$newBatchQty, $newBatchQty, $batchId]);

                // Deduct inventory at bin
                $invSt = $pdo->prepare("SELECT id, quantity FROM inventory WHERE product_id = ? AND location_id = ? FOR UPDATE");
                $invSt->execute([$productId, $locId]);
                $inv = $invSt->fetch();
                if (!$inv || (int)$inv['quantity'] < $qty) {
                    throw new RuntimeException("Line {$L['product_name']}: inventory at bin doesn't have enough.");
                }
                $pdo->prepare("UPDATE inventory SET quantity = quantity - ?, last_movement_at = NOW() WHERE id = ?")
                    ->execute([$qty, $inv['id']]);

                // Movement log
                $pdo->prepare(
                    "INSERT INTO stock_movements
                        (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                     VALUES (?, ?, ?, NULL, ?, 'outbound', ?, ?, ?)"
                )->execute([
                    $productId, $batchId, $locId, $qty,
                    'Picked for shipment SH-' . $id,
                    'SH-' . $id, $uid,
                ]);

                // Update shipment_items with batch + picked qty
                $pdo->prepare("UPDATE shipment_items SET quantity = ?, batch_id = ? WHERE id = ?")
                    ->execute([$qty, $batchId, $lineId]);

                $pickedLines++;
            }

            if ($pickedLines === 0) {
                throw new RuntimeException("No quantities entered — nothing picked.");
            }

            // Update shipment — status + auto tracking
            $pdo->prepare(
                "UPDATE shipments SET status='shipped', shipped_at=NOW(), tracking_number = ? WHERE id = ?"
            )->execute([$tracking, $id]);

            // Audit
            $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'pick_shipment', ?)"
            )->execute([$uid, "Shipped SH-{$id}: {$pickedLines} line(s) · tracking {$tracking}"]);

            $pdo->commit();
            $success = true;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
            $tracking = '';
        }
    }
}

// Load lines for display
$lines = $pdo->prepare(
    "SELECT si.*, p.name AS product_name, p.sku,
            (SELECT COALESCE(SUM(i.quantity),0)
             FROM inventory i JOIN locations l ON l.id = i.location_id
             WHERE l.warehouse_id = ? AND i.product_id = si.product_id) AS warehouse_on_hand
     FROM shipment_items si
     LEFT JOIN products p ON p.id = si.product_id
     WHERE si.shipment_id = ?"
);
$lines->execute([(int)$shipment['warehouse_id'], $id]);
$lines = $lines->fetchAll();

// FEFO batches per product, limited to that warehouse
$batchStmt = $pdo->prepare(
    "SELECT b.id, b.batch_number, b.expiry_date, b.quantity,
            l.code AS bin_code, l.name AS bin_name
     FROM product_batches b
     LEFT JOIN locations l ON l.id = b.location_id
     WHERE b.product_id = ?
       AND b.status = 'available'
       AND b.quantity > 0
       AND l.warehouse_id = ?
     ORDER BY (b.expiry_date IS NULL), b.expiry_date ASC, b.id ASC"
);

$batchesByProduct = [];
foreach ($lines as $L) {
    $batchStmt->execute([(int)$L['product_id'], (int)$shipment['warehouse_id']]);
    $batchesByProduct[(int)$L['product_id']] = $batchStmt->fetchAll();
}

$page_title = 'Pick & Ship SH-' . $id . ($shipment['customer_name'] ? ' — ' . $shipment['customer_name'] : '');
include 'warehouse_header.php';
?>

<?php if ($success): ?>
  <div class="alert-soft" style="padding:16px 20px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-check-circle-fill"></i>
    SH-<?= (int)$id ?> shipped. Inventory deducted, movements logged, batch quantities updated.
    <?php if ($tracking !== ''): ?>
      <br><span style="font-family:monospace;background:rgba(0,0,0,.06);padding:2px 8px;border-radius:6px;display:inline-block;margin-top:6px;">Tracking: <?= htmlspecialchars($tracking) ?></span>
    <?php endif; ?>
    <a href="shipments.php" style="color:#16a34a;text-decoration:underline;margin-left:8px;">Back to shipments</a>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert-soft" style="padding:16px 20px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars(implode(' ', $errors)) ?>
  </div>
<?php endif; ?>

<div class="table-card">
  <h5><i class="bi bi-box-arrow-up"></i> SH-<?= (int)$id ?> · <?= htmlspecialchars($shipment['customer_name'] ?? '—') ?></h5>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:18px;">
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Warehouse</div><div><?= htmlspecialchars($shipment['warehouse_name'] ?? '—') ?> <?= $shipment['city'] ? '(' . htmlspecialchars($shipment['city']) . ')' : '' ?></div></div>
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Ship date</div><div><?= htmlspecialchars($shipment['shipment_date'] ?? '—') ?></div></div>
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Address</div><div><?= htmlspecialchars($shipment['address'] ?? '—') ?></div></div>
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Status</div><div><span class="status-badge status-<?= htmlspecialchars($shipment['status']) ?>"><?= htmlspecialchars($shipment['status']) ?></span></div></div>
  </div>

  <?php if ($shipment['status'] !== 'pending' && !$success): ?>
    <p style="color:var(--text-muted);">This shipment is already <strong><?= htmlspecialchars($shipment['status']) ?></strong>. Nothing to pick.</p>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="shipment_id" value="<?= (int)$id ?>">
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>Needed</th>
            <th>Stock at WH</th>

            <th>Pick qty</th>
            <th>Batch (FEFO order)</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lines as $L): $lineId = (int)$L['id']; $pid = (int)$L['product_id']; ?>
          <tr>
            <td><code><?= htmlspecialchars($L['sku'] ?? '—') ?></code></td>
            <td><?= htmlspecialchars($L['product_name'] ?? '—') ?></td>
            <td><?= (int)$L['quantity'] ?></td>
            <td><?= (int)$L['warehouse_on_hand'] ?></td>
            <td>
              <input type="number" min="0" max="<?= (int)$L['warehouse_on_hand'] ?>"
                     name="qty[<?= $lineId ?>]" value="<?= (int)$L['quantity'] ?>"
                     style="width:90px;padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);">
            </td>
            <td>
              <?php $batches = $batchesByProduct[$pid] ?? []; ?>
              <?php if (!$batches): ?>
                <span style="color:#dc2626;font-weight:600;">No batch in stock at this warehouse</span>
              <?php else: ?>
                <select name="batch[<?= $lineId ?>]" required style="padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);min-width:280px;">
                  <?php foreach ($batches as $b): ?>
                    <?php
                      $exp = $b['expiry_date'] ?: 'no expiry';
                      $d = $b['expiry_date'] ? (int)((strtotime($b['expiry_date']) - time()) / 86400) : null;
                      $tag = '';
                      if ($d !== null) {
                          if ($d < 0) $tag = ' · EXPIRED';
                          elseif ($d <= 30) $tag = ' · ' . $d . 'd';
                      }
                    ?>
                    <option value="<?= (int)$b['id'] ?>">
                      <?= htmlspecialchars($b['batch_number']) ?> · exp <?= htmlspecialchars($exp) ?><?= $tag ?> · qty <?= (int)$b['quantity'] ?> · <?= htmlspecialchars($b['bin_code'] ?? '—') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="margin-top:16px;padding:12px 16px;border-radius:10px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);color:var(--text);font-size:.88rem;">
      <i class="bi bi-info-circle" style="color:var(--primary);"></i>
      A tracking number will be <strong>auto-generated</strong> when you confirm this shipment.
    </div>

    <div style="margin-top:18px;display:flex;gap:10px;">
      <button type="submit" class="btn-add" style="padding:10px 18px;font-size:.95rem;">
        <i class="bi bi-truck"></i> Confirm Pick &amp; Ship
      </button>
      <a href="shipments.php" class="btn-secondary-custom" style="padding:10px 18px;font-size:.95rem;text-decoration:none;">Cancel</a>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
