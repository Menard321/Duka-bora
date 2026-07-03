<?php
/**
 * record_sale.php – Record a Sale Transaction
 *
 * Features:
 *  - Product dropdown with stock info
 *  - Quantity field with JS live calculation
 *  - Prevents overselling (server + client)
 *  - Deducts stock atomically
 *  - Records sale in the sales table
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Record Sale';
$errors    = [];
$products  = getAllProducts();

/* ── Handle Sale Submission ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId  = intval($_POST['product_id']  ?? 0);
    $qtySold    = intval($_POST['qty_sold']     ?? 0);
    $totalPrice = floatval($_POST['total_price'] ?? 0);

    // ── Validation ──────────────────────────────────────────
    if ($productId <= 0) {
        $errors['product_id'] = 'Please select a product.';
    }

    if ($qtySold <= 0) {
        $errors['qty_sold'] = 'Quantity must be at least 1.';
    }

    // Load the product to check stock
    $selectedProduct = null;
    if ($productId > 0) {
        $selectedProduct = getProductById($productId);
        if (!$selectedProduct) {
            $errors['product_id'] = 'Selected product not found.';
        }
    }

    // Oversell check
    if ($selectedProduct && $qtySold > 0) {
        if ($qtySold > (int) $selectedProduct['stock_qty']) {
            $errors['qty_sold'] = "Insufficient stock. Only {$selectedProduct['stock_qty']} unit(s) available.";
        }
    }

    if (empty($errors)) {
        $conn = getConnection();

        // Recalculate total_price server-side for integrity
        $serverTotal = floatval($selectedProduct['price']) * $qtySold;

        // Begin transaction
        $conn->begin_transaction();

        try {
            // 1. Deduct stock
            $stmtDeduct = $conn->prepare("
                UPDATE products
                SET stock_qty = stock_qty - ?
                WHERE product_id = ? AND stock_qty >= ?
            ");

            if (!$stmtDeduct) throw new RuntimeException($conn->error);
            $stmtDeduct->bind_param('iii', $qtySold, $productId, $qtySold);
            $stmtDeduct->execute();

            if ($stmtDeduct->affected_rows === 0) {
                // Another request may have grabbed the stock (race condition)
                throw new RuntimeException('Not enough stock to complete the sale.');
            }
            $stmtDeduct->close();

            // 2. Insert sale record
            $stmtSale = $conn->prepare("
                INSERT INTO sales (product_id, qty_sold, total_price)
                VALUES (?, ?, ?)
            ");

            if (!$stmtSale) throw new RuntimeException($conn->error);
            $stmtSale->bind_param('iid', $productId, $qtySold, $serverTotal);
            $stmtSale->execute();
            $stmtSale->close();

            $conn->commit();

            $productName = $selectedProduct['name'];
            setFlash('success',
                "Sale recorded! {$qtySold} × \"{$productName}\" for " . formatTZS($serverTotal) . "."
            );
            header('Location: record_sale.php');
            exit;

        } catch (RuntimeException $ex) {
            $conn->rollback();
            error_log('[DukaBora] record_sale: ' . $ex->getMessage());
            $errors['general'] = 'Could not complete the sale: ' . $ex->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-cash-register"></i> Record Sale
        </h1>
        <p class="page-subtitle">Select a product, enter quantity, and confirm the sale.</p>
    </div>
    <a href="sales_history.php" class="btn btn-secondary">
        <i class="fas fa-history"></i> View Sales History
    </a>
</div>

<?= renderFlash() ?>

<?php if (isset($errors['general'])): ?>
    <div class="alert alert-error">
        <span class="alert-icon">✖</span><?= e($errors['general']) ?>
    </div>
<?php endif; ?>

<!-- ── Sale Form ──────────────────────────────────────────────── -->
<div style="display:grid; grid-template-columns:1.2fr 1fr; gap:var(--space-8); align-items:start;">

    <div class="form-section">
        <div class="form-section-header">
            <i class="fas fa-shopping-cart"></i>
            <h2>Sale Details</h2>
        </div>

        <div class="form-body">
            <form method="POST" action="record_sale.php" data-validate novalidate id="saleForm">

                <!-- Product Dropdown -->
                <div class="form-group" style="margin-bottom:var(--space-6);">
                    <label class="form-label" for="sale_product_id">
                        Product <span class="required">*</span>
                    </label>
                    <select
                        id="sale_product_id"
                        name="product_id"
                        class="form-control <?= isset($errors['product_id']) ? 'is-invalid' : '' ?>"
                        required>
                        <option value="">— Select a Product —</option>
                        <?php foreach ($products as $p): ?>
                            <option
                                value="<?= (int) $p['product_id'] ?>"
                                data-price="<?= (float) $p['price'] ?>"
                                data-stock="<?= (int) $p['stock_qty'] ?>"
                                <?= (isset($_POST['product_id']) && $_POST['product_id'] == $p['product_id']) ? 'selected' : '' ?>
                                <?= (int) $p['stock_qty'] === 0 ? 'disabled' : '' ?>>

                                <?= e($p['name']) ?>
                                (Stock: <?= (int)$p['stock_qty'] ?> |
                                 TZS <?= number_format((float)$p['price'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['product_id'])): ?>
                        <span class="form-error">✖ <?= e($errors['product_id']) ?></span>
                    <?php endif; ?>
                    <span id="stock_info" class="form-hint"></span>
                </div>

                <!-- Quantity -->
                <div class="form-group" style="margin-bottom:var(--space-6);">
                    <label class="form-label" for="sale_qty">
                        Quantity <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="sale_qty"
                        name="qty_sold"
                        class="form-control <?= isset($errors['qty_sold']) ? 'is-invalid' : '' ?>"
                        value="<?= isset($_POST['qty_sold']) ? (int)$_POST['qty_sold'] : '' ?>"
                        placeholder="e.g. 2"
                        min="1"
                        step="1"
                        data-type="qty"
                        required>
                    <?php if (isset($errors['qty_sold'])): ?>
                        <span class="form-error">✖ <?= e($errors['qty_sold']) ?></span>
                    <?php else: ?>
                        <span class="form-hint">Cannot exceed available stock.</span>
                    <?php endif; ?>
                </div>

                <!-- Hidden: auto-calculated total (JS updates this) -->
                <input type="hidden" id="sale_total_price" name="total_price" value="0.00">

                <div class="form-actions" style="padding-top:var(--space-4); border-top:none;">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success btn-lg" id="btnRecordSale">
                        <i class="fas fa-check-circle"></i> Confirm Sale
                    </button>
                </div>

            </form>
        </div>
    </div><!-- /.form-section -->

    <!-- ── Live Price Summary Card ────────────────────────────── -->
    <div class="card" style="position:sticky; top:calc(var(--navbar-height) + var(--space-6));">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-receipt"></i> Order Summary
            </h2>
        </div>
        <div class="card-body" style="text-align:center; padding:var(--space-8);">
            <p class="text-muted text-small" style="margin-bottom:var(--space-2);">Total Amount</p>
            <div id="sale_total_display"
                 style="font-size:var(--text-4xl); font-weight:var(--font-extrabold);
                        color:var(--primary-600); line-height:1; transition:color .3s;">
                TZS 0.00
            </div>
            <hr class="divider">
            <p class="text-small text-muted">
                Stock will be automatically deducted upon confirmation.
            </p>
        </div>
    </div>

</div><!-- /grid -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
