<?php
/**
 * products.php – Product Listing
 *
 * Displays all products in a data table with:
 *  - Category & Supplier (via JOIN)
 *  - Price & Stock
 *  - Status badge (green/yellow/red)
 *  - Edit & Delete actions
 *  - Sets "Last Viewed Product" cookie on row click (via JS data attrs)
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Products';
$products  = getAllProducts();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-boxes"></i> Products
        </h1>
        <p class="page-subtitle">
            <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?> in catalogue
        </p>
    </div>
    <a href="add_product.php" class="btn btn-primary" id="btnAddProduct">
        <i class="fas fa-plus"></i> Add Product
    </a>
</div>

<?= renderFlash() ?>

<!-- ── Filter Bar ─────────────────────────────────────────────── -->
<div class="filter-bar">
    <input
        type="text"
        id="tableFilter"
        class="form-control"
        placeholder="&#128269; Search products…"
        data-table="mainTable"
        aria-label="Filter products">
</div>

<!-- ── Products Table ─────────────────────────────────────────── -->
<div class="table-wrapper">
    <table class="data-table" id="mainTable" aria-label="Products list">
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Price (TZS)</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Added On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="9" class="table-empty">
                        <i class="fas fa-boxes"></i>
                        No products found. <a href="add_product.php">Add the first one!</a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $i => $product): ?>
                    <?php
                        $pid  = (int)  $product['product_id'];
                        $name = e($product['name']);
                    ?>
                    <tr data-product-id="<?= $pid ?>"
                        data-product-name="<?= $name ?>"
                        onclick="recordLastViewed(<?= $pid ?>, '<?= addslashes($name) ?>')"
                        title="Click to mark as last viewed">

                        <td class="text-muted text-small"><?= $i + 1 ?></td>
                        <td class="product-name"><?= $name ?></td>
                        <td>
                            <span class="badge badge-primary">
                                <?= e($product['category_name']) ?>
                            </span>
                        </td>
                        <td><?= e($product['supplier_name']) ?></td>
                        <td class="price-value"><?= number_format((float) $product['price'], 2) ?></td>
                        <td><strong><?= (int) $product['stock_qty'] ?></strong></td>
                        <td><?= stockBadge((int) $product['stock_qty']) ?></td>
                        <td class="text-muted text-small">
                            <?= date('d M Y', strtotime($product['created_at'])) ?>
                        </td>
                        <td>
                            <div class="action-group">
                                <a href="edit_product.php?id=<?= $pid ?>"
                                   class="btn btn-sm btn-warning"
                                   id="btnEdit-<?= $pid ?>"
                                   title="Edit product"
                                   aria-label="Edit <?= $name ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_product.php?id=<?= $pid ?>"
                                   class="btn btn-sm btn-danger"
                                   id="btnDelete-<?= $pid ?>"
                                   data-confirm="Delete &quot;<?= $name ?>&quot;? This cannot be undone."
                                   title="Delete product"
                                   aria-label="Delete <?= $name ?>">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
/**
 * Sends a PHP cookie set request via AJAX (POST to a small endpoint)
 * when a product row is clicked, recording the "last viewed" product.
 * Falls back to setting the cookie directly in JS as well.
 */
function recordLastViewed(id, name) {
    // Set via JS cookie as immediate feedback
    CookieUtil.set('last_viewed_product', JSON.stringify({ id, name }), 30);

    // Also POST to server so PHP cookie is set properly
    fetch('set_cookie.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    `product_id=${encodeURIComponent(id)}&product_name=${encodeURIComponent(name)}`
    }).catch(() => { /* silently ignore network errors */ });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
