<?php
// ref.php — Short yearly reference numbers for stock movements
// Format: PREFIX-YYYY-NNNNN  (e.g. TR-2026-00001)

/**
 * Generate the next reference number for a given prefix.
 * Uses stock_movements as the counter source — counts rows with the same prefix this year, adds 1.
 *
 * @param PDO    $pdo
 * @param string $prefix    e.g. 'TR', 'ADJ', 'CC', 'QT', 'QR', 'QD'
 * @return string           e.g. 'TR-2026-00042'
 */
function next_ref(PDO $pdo, string $prefix): string {
    $year = date('Y');
    $like = $prefix . '-' . $year . '-%';

    $st = $pdo->prepare(
        "SELECT MAX(CAST(SUBSTRING_INDEX(reference, '-', -1) AS UNSIGNED)) AS max_seq
         FROM stock_movements
         WHERE reference LIKE ?"
    );
    $st->execute([$like]);
    $max = (int)$st->fetchColumn();
    $next = $max + 1;

    return sprintf('%s-%s-%05d', $prefix, $year, $next);
}
