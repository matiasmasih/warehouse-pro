<?php
// receive_po.php — Receive a purchase order into stock (real inbound workflow)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/access_control.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (my_access('purchase_orders') === 'no') { header('Location: staff_dashboard.php'); exit; }

$uid = (int)$_SESSION['user_id'];

$id = (int)($_GET['id'] ?? $_POST['po_id'] ?? 0);
if ($id <= 0) { header('Location: staff_dashboard.php'); exit; }

// ---- load PO header ----
$st = $pdo->prepare(
    "SELECT po.*, s.name AS supplier_name, w.name AS warehouse_name, w.city
     FROM purchase_orders po
     LEFT JOIN suppliers  s ON s.id = po.supplier_id
     LEFT JOIN warehouses w ON w.id = po.warehouse_id
     WHERE po.id = ? LIMIT 1"
);
$st->execute([$id]);
$po = $st->fetch();

if (!$po) { header('Location: staff_dashboard.php'); exit; }

$errors  = [];
$success = false;

// ---- handle submit ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($po['status'] !== 'pending') {
        $errors[] = "This PO is already '{$po['status']}' — nothing to receive.";
    } else {
        $items = $pdo->prepare(
            "SELECT poi.*, p.name AS product_name
             FROM purchase_order_items poi
             LEFT JOIN products p ON p.id = poi.product_id
             WHERE poi.purchase_order_id = ?"
        );
        $items->execute([$id]);
        $items = $items->fetchAll();

        $postedQty    = $_POST['qty']     ?? [];
        $postedBatch  = $_POST['batch']   ?? [];
        $postedExpiry = $_POST['expiry']  ?? [];
        $postedLoc    = $_POST['loc']     ?? [];

        try {
            $pdo->beginTransaction();

            // re-check status with row lock (prevent double receive)
            $lock = $pdo->prepare("SELECT status FROM purchase_orders WHERE id = ? FOR UPDATE");
            $lock->execute([$id]);
            $cur = $lock->fetchColumn();
            if ($cur !== 'pending') {
                throw new RuntimeException("PO is no longer pending (now: $cur).");
            }

            $receivedLines = 0;

            foreach ($items as $it) {
                $lineId = (int)$it['id'];
                $qty    = (int)($postedQty[$lineId] ?? 0);
                if ($qty <= 0) continue; // skip lines with 0 received

                $batchNo = trim($postedBatch[$lineId] ?? '');
                if ($batchNo === '') {
                    $batchNo = 'PO' . $id . '-' . $it['product_id'] . '-' . date('Ymd');
                }

                $expiry = trim($postedExpiry[$lineId] ?? '');
                $expiry = $expiry === '' ? null : $expiry;

                $locId = (int)($postedLoc[$lineId] ?? 0);
                if ($locId <= 0) {
                    throw new RuntimeException("Line {$it['product_name']}: choose a put-away location.");
                }

                $productId = (int)$it['product_id'];
                if ($productId <= 0) {
                    throw new RuntimeException("Line {$it['product_name']}: invalid product.");
                }

                // 1) create batch
                $insBatch = $pdo->prepare(
                    "INSERT INTO product_batches
                        (product_id, batch_number, expiry_date, quantity, location_id, status)
                     VALUES (?, ?, ?, ?, ?, 'available')"
                );
                $insBatch->execute([$productId, $batchNo, $expiry, $qty, $locId]);
                $batchId = (int)$pdo->lastInsertId();

                // 2) upsert inventory (product + location)
                $invCheck = $pdo->prepare("SELECT id, quantity FROM inventory WHERE product_id = ? AND location_id = ? LIMIT 1");
                $invCheck->execute([$productId, $locId]);
                $inv = $invCheck->fetch();

                if ($inv) {
                    $pdo->prepare("UPDATE inventory SET quantity = quantity + ?, last_updated = NOW(), last_movement_at = NOW() WHERE id = ?")
                        ->execute([$qty, $inv['id']]);
                } else {
                    $pdo->prepare("INSERT INTO inventory (product_id, location_id, quantity, last_movement_at) VALUES (?, ?, ?, NOW())")
                        ->execute([$productId, $locId, $qty]);
                }

                // 3) write movement (inbound)
                $pdo->prepare(
                    "INSERT INTO stock_movements
                        (product_id, batch_id, from_location, to_location, quantity, movement_type, reason, reference, user_id)
                     VALUES (?, ?, NULL, ?, ?, 'inbound', ?, ?, ?)"
                )->execute([
                    $productId, $batchId, $locId, $qty,
                    'Received on PO-' . $id,
                    'PO-' . $id,
                    $uid,
                ]);

                // 4) update PO line with what we actually got
                $pdo->prepare("UPDATE purchase_order_items SET quantity = ?, batch_number = ?, expiry_date = ? WHERE id = ?")
                    ->execute([$qty, $batchNo, $expiry, $lineId]);

                $receivedLines++;
            }

            if ($receivedLines === 0) {
                throw new RuntimeException("No quantities entered — nothing received.");
            }

            // 5) close the PO
            $pdo->prepare("UPDATE purchase_orders SET status='received', received_at=NOW() WHERE id = ?")
                ->execute([$id]);

            // 6) audit
            $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, description) VALUES (?, 'receive_po', ?)"
            )->execute([$uid, "Received PO-{$id}: {$receivedLines} line(s)"]);

            $pdo->commit();
            $success = true;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}

// ---- load lines for display ----
$items = $pdo->prepare(
    "SELECT poi.*, p.name AS product_name, p.sku
     FROM purchase_order_items poi
     LEFT JOIN products p ON p.id = poi.product_id
     WHERE poi.purchase_order_id = ?"
);
$items->execute([$id]);
$items = $items->fetchAll();

// ---- put-away locations for this PO's warehouse ----
$locs = $pdo->prepare(
    "SELECT id, name, code, type
     FROM locations
     WHERE warehouse_id = ?
       AND type IN ('receiving','storage')
     ORDER BY type, name"
);
$locs->execute([(int)$po['warehouse_id']]);
$locations = $locs->fetchAll();

$page_title = 'Receive PO-' . $id . ($po['supplier_name'] ? ' — ' . $po['supplier_name'] : '');
include 'warehouse_header.php';
?>

<?php if ($success): ?>
  <div class="alert-soft status-received" style="padding:16px 20px;border-radius:12px;background:rgba(34,197,94,.15);color:#16a34a;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-check-circle-fill"></i>
    PO-<?= (int)$id ?> received. Stock is now in inventory, batches created, movements logged.
    <a href="staff_dashboard.php" style="color:#16a34a;text-decoration:underline;margin-left:8px;">Back to my dashboard</a>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert-soft status-cancelled" style="padding:16px 20px;border-radius:12px;background:rgba(239,68,68,.15);color:#dc2626;font-weight:600;margin-bottom:20px;">
    <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars(implode(' ', $errors)) ?>
  </div>
<?php endif; ?>

<div class="table-card">
  <h5><i class="bi bi-box-arrow-in-down"></i> PO-<?= (int)$id ?> · <?= htmlspecialchars($po['supplier_name'] ?? '—') ?></h5>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:18px;">
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Warehouse</div><div><?= htmlspecialchars($po['warehouse_name'] ?? '—') ?> <?= $po['city'] ? '(' . htmlspecialchars($po['city']) . ')' : '' ?></div></div>
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Order date</div><div><?= htmlspecialchars($po['order_date'] ?? '—') ?></div></div>
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Expected</div><div><?= htmlspecialchars($po['expected_date'] ?? '—') ?></div></div>
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Status</div><div><span class="status-badge status-<?= htmlspecialchars($po['status']) ?>"><?= htmlspecialchars($po['status']) ?></span></div></div>
    <div><div style="color:var(--text-muted);font-size:.75rem;text-transform:uppercase;font-weight:600;">Total</div><div><?= number_format((float)$po['total_amount'], 2, ',', ' ') ?> €</div></div>
  </div>

  <?php if ($po['status'] !== 'pending' && !$success): ?>
    <p style="color:var(--text-muted);">This PO is already <strong><?= htmlspecialchars($po['status']) ?></strong>. Nothing to receive.</p>
  <?php else: ?>
  <form method="post">
    <input type="hidden" name="po_id" value="<?= (int)$id ?>">
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>Ordered</th>
            <th>Receive qty</th>
            <th>Batch no.</th>
            <th>Expiry</th>
            <th>Put-away location</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): $lineId = (int)$it['id']; ?>
          <tr>
            <td><?= htmlspecialchars($it['sku'] ?? '—') ?></td>
            <td><?= htmlspecialchars($it['product_name'] ?? '—') ?></td>
            <td><?= (int)$it['quantity'] ?></td>
            <td><input type="number" min="0" name="qty[<?= $lineId ?>]" value="<?= (int)$it['quantity'] ?>" style="width:90px;padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);"></td>
            <td><input type="text" name="batch[<?= $lineId ?>]" value="<?= htmlspecialchars($it['batch_number'] ?? '') ?>" placeholder="auto" style="width:140px;padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);"></td>
            <td><input type="date" name="expiry[<?= $lineId ?>]" value="<?= htmlspecialchars($it['expiry_date'] ?? '') ?>" style="padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);"></td>
            <td>
              <select name="loc[<?= $lineId ?>]" required style="padding:6px 8px;border:1px solid var(--border);border-radius:6px;background:var(--card-bg);color:var(--text);">
                <option value="">— choose —</option>
                <?php foreach ($locations as $L): ?>
                  <option value="<?= (int)$L['id'] ?>"><?= htmlspecialchars(($L['code'] ? '[' . $L['code'] . '] ' : '') . $L['name'] . ' (' . $L['type'] . ')') ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="margin-top:18px;display:flex;gap:10px;">
      <button type="submit" class="btn-add" style="padding:10px 18px;font-size:.95rem;">
        <i class="bi bi-check2-circle"></i> Confirm Receipt
      </button>
      <a href="staff_dashboard.php" class="btn-secondary-custom" style="padding:10px 18px;font-size:.95rem;text-decoration:none;">
        Cancel
      </a>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php include 'warehouse_footer.php'; ?>
