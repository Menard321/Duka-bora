<?php
/**
 * sales_history.php – Sales Transaction History
 *
 * Displays all sales records with JOIN to product names.
 * Sorted newest first. Includes total revenue summary.
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Sales History';

/* ── Fetch sales with product name JOIN ─────────────────────── */
$conn   = getConnection();
$result = $conn->query("
    SELECT
        s.sale_id,
        p.name       AS product_name,
        s.qty_sold,
        s.total_price,
        s.sale_date,
        p.price      AS unit_price
    FROM  sales    s
    JOIN  products p ON p.product_id = s.product_id
    ORDER BY s.sale_date DESC
");

$sales = [];
$grandTotal = 0.0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sales[]    = $row;
        $grandTotal += (float) $row['total_price'];
    }
} else {
    error_log('[DukaBora] sales_history query: ' . $conn->error);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-history"></i> Sales History
        </h1>
        <p class="page-subtitle">
            <?= count($sales) ?> transaction<?= count($sales) !== 1 ? 's' : '' ?> recorded
        </p>
    </div>
    <a href="record_sale.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Sale
    </a>
</div>

<?= renderFlash() ?>

<!-- ── Grand Total Banner ─────────────────────────────────────── -->
<div class="recently-viewed" style="background:linear-gradient(135deg,var(--success-100),#ecfdf5);
     border-left-color:var(--success-500); border-color:#a7f3d0; margin-bottom:var(--space-6);">
    <i class="fas fa-coins" style="color:var(--success-500);"></i>
    <span>
        All-time total revenue:
        <strong style="color:var(--success-600); font-size:var(--text-lg);">
            <?= formatTZS($grandTotal) ?>
        </strong>
    </span>
</div>

<!-- ── Filter Bar ─────────────────────────────────────────────── -->
<div class="filter-bar">
    <input
        type="text"
        id="tableFilter"
        class="form-control"
        placeholder="&#128269; Search sales…"
        data-table="salesTable"
        aria-label="Search sales">
</div>

<!-- ── Sales Table ────────────────────────────────────────────── -->
<div class="table-wrapper">
    <table class="data-table" id="salesTable" aria-label="Sales history">
        <thead>
            <tr>
                <th>#</th>
                <th>Sale ID</th>
                <th>Product Name</th>
                <th>Unit Price (TZS)</th>
                <th>Qty Sold</th>
                <th>Total Price (TZS)</th>
                <th>Sale Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sales)): ?>
                <tr>
                    <td colspan="7" class="table-empty">
                        <i class="fas fa-receipt"></i>
                        No sales recorded yet.
                        <a href="record_sale.php">Record the first sale!</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($sales as $i => $sale): ?>
                    <tr>
                        <td class="text-muted text-small"><?= $i + 1 ?></td>
                        <td>
                            <span class="badge badge-info">#<?= (int) $sale['sale_id'] ?></span>
                        </td>
                        <td class="product-name"><?= e($sale['product_name']) ?></td>
                        <td class="price-value"><?= number_format((float) $sale['unit_price'], 2) ?></td>
                        <td>
                            <strong><?= (int) $sale['qty_sold'] ?></strong>
                            <span class="text-muted text-small">unit<?= $sale['qty_sold'] != 1 ? 's' : '' ?></span>
                        </td>
                        <td class="price-value fw-bold">
                            <?= number_format((float) $sale['total_price'], 2) ?>
                        </td>
                        <td class="text-muted text-small">
                            <?= date('d M Y, g:i A', strtotime($sale['sale_date'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($sales)): ?>
        <tfoot>
            <tr style="background:var(--primary-50);">
                <td colspan="5" class="fw-bold text-right" style="padding:var(--space-4) var(--space-5);">
                    Grand Total:
                </td>
                <td class="price-value fw-bold" style="padding:var(--space-4) var(--space-5);">
                    <?= number_format($grandTotal, 2) ?>
                </td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
