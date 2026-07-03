<?php
/**
 * delete_product.php – Delete Product
 *
 * Displays a confirmation page before deleting the product.
 * Checks for existing sales records referencing this product
 * (FK constraint) and handles the error gracefully.
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$productId = intval($_GET['id'] ?? 0);

// Validate ID
if ($productId <= 0) {
    setFlash('error', 'Invalid product ID.');
    header('Location: products.php');
    exit;
}

// Load product
$product = getProductById($productId);

if (!$product) {
    setFlash('error', 'Product not found or already deleted.');
    header('Location: products.php');
    exit;
}

/* ── Handle confirmed deletion ──────────────────────────────── */
if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    $conn = getConnection();
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");

    if (!$stmt) {
        error_log('[DukaBora] delete_product prepare: ' . $conn->error);
        setFlash('error', 'A system error occurred while trying to delete the product.');
        header('Location: products.php');
        exit;
    }

    $stmt->bind_param('i', $productId);

    try {
        if ($stmt->execute()) {
            $stmt->close();
            setFlash('success', "Product \"{$product['name']}\" has been deleted.");
        } else {
            $stmt->close();
            setFlash('error', 'Failed to delete the product. Please try again.');
        }
    } catch (mysqli_sql_exception $e) {
        $errno = $conn->errno ?: $e->getCode();
        $stmt->close();
        if ($errno == 1451 || str_contains($e->getMessage(), '1451')) {
            setFlash('error',
                "Cannot delete \"{$product['name']}\" — it has existing sales records. " .
                "Consider adjusting the stock to 0 instead."
            );
        } else {
            error_log('[DukaBora] delete_product execute exception: ' . $e->getMessage());
            setFlash('error', 'Failed to delete the product: ' . $e->getMessage());
        }
    }

    header('Location: products.php');
    exit;
}

/* ── Render Confirmation Page ───────────────────────────────── */
$pageTitle = 'Delete Product';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-trash-alt" style="color:var(--danger-500);"></i>
        Delete Product
    </h1>
    <a href="products.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<!-- ── Confirmation Card ──────────────────────────────────────── -->
<div class="card" style="max-width:560px; margin:0 auto;">
    <div class="card-header" style="background:var(--danger-100);">
        <h2 class="card-title" style="color:var(--danger-600);">
            <i class="fas fa-exclamation-triangle"></i>
            Confirm Deletion
        </h2>
    </div>
    <div class="card-body">

        <div class="alert alert-warning">
            <span class="alert-icon">⚠</span>
            <span>
                This action is <strong>permanent</strong> and cannot be undone.
                The product will be removed from your inventory.
            </span>
        </div>

        <table class="data-table" style="margin-bottom:var(--space-6);" aria-label="Product to be deleted">
            <tbody style="cursor:default;">
                <tr>
                    <td class="fw-semibold" style="width:40%;">Product Name</td>
                    <td><?= e($product['name']) ?></td>
                </tr>
                <tr>
                    <td class="fw-semibold">Category</td>
                    <td><?= e($product['category_name']) ?></td>
                </tr>
                <tr>
                    <td class="fw-semibold">Supplier</td>
                    <td><?= e($product['supplier_name']) ?></td>
                </tr>
                <tr>
                    <td class="fw-semibold">Price (TZS)</td>
                    <td class="price-value"><?= number_format((float) $product['price'], 2) ?></td>
                </tr>
                <tr>
                    <td class="fw-semibold">Stock</td>
                    <td><?= (int) $product['stock_qty'] ?> units</td>
                </tr>
            </tbody>
        </table>

        <form method="POST" action="delete_product.php?id=<?= $productId ?>">
            <input type="hidden" name="confirm_delete" value="yes">
            <div class="form-actions" style="padding-top:0; border-top:none;">
                <a href="products.php" class="btn btn-secondary" id="btnCancelDelete">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="fas fa-trash-alt"></i> Yes, Delete Permanently
                </button>
            </div>
        </form>

    </div><!-- /.card-body -->
</div><!-- /.card -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
