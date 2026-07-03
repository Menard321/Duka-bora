<?php
/**
 * add_product.php – Add New Product
 *
 * Handles displaying and processing the add-product form.
 * Validates all fields server-side (and client-side via JS).
 * Uses MySQLi prepared statements to prevent SQL injection.
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Add Product';
$errors     = [];
$old        = [];   // repopulate form on error
$categories = getAllCategories();
$suppliers  = getAllSuppliers();

/* ── Process Form Submission ────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize
    $old['name']        = trim($_POST['name']        ?? '');
    $old['category_id'] = intval($_POST['category_id'] ?? 0);
    $old['supplier_id'] = intval($_POST['supplier_id'] ?? 0);
    $old['price']       = trim($_POST['price']       ?? '');
    $old['stock_qty']   = trim($_POST['stock_qty']   ?? '');

    // ── Validation ──────────────────────────────────────────
    if ($old['name'] === '') {
        $errors['name'] = 'Product name is required.';
    } elseif (strlen($old['name']) > 200) {
        $errors['name'] = 'Product name must not exceed 200 characters.';
    }

    if ($old['category_id'] <= 0) {
        $errors['category_id'] = 'Please select a category.';
    }

    if ($old['supplier_id'] <= 0) {
        $errors['supplier_id'] = 'Please select a supplier.';
    }

    $price = floatval($old['price']);
    if ($old['price'] === '' || !is_numeric($old['price'])) {
        $errors['price'] = 'Price is required and must be a number.';
    } elseif ($price <= 0) {
        $errors['price'] = 'Price must be greater than 0.';
    }

    $stock = intval($old['stock_qty']);
    if ($old['stock_qty'] === '' || !ctype_digit(ltrim($old['stock_qty'], '0') ?: '0')) {
        $errors['stock_qty'] = 'Stock quantity must be a non-negative whole number.';
    } elseif ($stock < 0) {
        $errors['stock_qty'] = 'Stock quantity cannot be negative.';
    }

    // ── Insert if valid ──────────────────────────────────────
    if (empty($errors)) {
        $conn = getConnection();
        $stmt = $conn->prepare("
            INSERT INTO products (name, category_id, supplier_id, price, stock_qty)
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            error_log('[DukaBora] add_product prepare: ' . $conn->error);
            $errors['db'] = 'A system error occurred. Please try again.';
        } else {
            $stmt->bind_param('siidi', $old['name'], $old['category_id'], $old['supplier_id'], $price, $stock);

            try {
                if ($stmt->execute()) {
                    $stmt->close();
                    setFlash('success', "Product \"{$old['name']}\" added successfully!");
                    header('Location: products.php');
                    exit;
                } else {
                    $stmt->close();
                    $errors['db'] = 'Failed to save the product. Please try again.';
                }
            } catch (mysqli_sql_exception $e) {
                error_log('[DukaBora] add_product execute exception: ' . $e->getMessage());
                $stmt->close();
                $errors['db'] = 'Failed to save the product: ' . $e->getMessage();
            }
        }
    }
}

/* ── Render Page ────────────────────────────────────────────── */
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-plus-circle"></i> Add Product
        </h1>
        <p class="page-subtitle">Fill in the form below to add a new product to your inventory.</p>
    </div>
    <a href="products.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<?= renderFlash() ?>

<!-- ── Database Error ─────────────────────────────────────────── -->
<?php if (isset($errors['db'])): ?>
    <div class="alert alert-error">
        <span class="alert-icon">✖</span>
        <?= e($errors['db']) ?>
    </div>
<?php endif; ?>

<!-- ── Add Product Form ───────────────────────────────────────── -->
<div class="form-section">
    <div class="form-section-header">
        <i class="fas fa-box-open"></i>
        <h2>Product Information</h2>
    </div>

    <div class="form-body">
        <form method="POST"
              action="add_product.php"
              data-validate
              novalidate
              id="addProductForm">

            <div class="form-grid">

                <!-- Product Name -->
                <div class="form-group full-width">
                    <label class="form-label" for="product_name">
                        Product Name <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="product_name"
                        name="name"
                        class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                        value="<?= e($old['name'] ?? '') ?>"
                        placeholder="e.g. Samsung Galaxy A54"
                        maxlength="200"
                        required
                        autocomplete="off">
                    <?php if (isset($errors['name'])): ?>
                        <span class="form-error">✖ <?= e($errors['name']) ?></span>
                    <?php else: ?>
                        <span class="form-hint">Maximum 200 characters.</span>
                    <?php endif; ?>
                </div>

                <!-- Category -->
                <div class="form-group">
                    <label class="form-label" for="category_id">
                        Category <span class="required">*</span>
                    </label>
                    <select
                        id="category_id"
                        name="category_id"
                        class="form-control <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                        required>
                        <option value="">— Select Category —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['category_id'] ?>"
                                <?= (isset($old['category_id']) && $old['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                <?= e($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?>
                        <span class="form-error">✖ <?= e($errors['category_id']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Supplier -->
                <div class="form-group">
                    <label class="form-label" for="supplier_id">
                        Supplier <span class="required">*</span>
                    </label>
                    <select
                        id="supplier_id"
                        name="supplier_id"
                        class="form-control <?= isset($errors['supplier_id']) ? 'is-invalid' : '' ?>"
                        required>
                        <option value="">— Select Supplier —</option>
                        <?php foreach ($suppliers as $sup): ?>
                            <option value="<?= (int) $sup['supplier_id'] ?>"
                                <?= (isset($old['supplier_id']) && $old['supplier_id'] == $sup['supplier_id']) ? 'selected' : '' ?>>
                                <?= e($sup['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['supplier_id'])): ?>
                        <span class="form-error">✖ <?= e($errors['supplier_id']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Price -->
                <div class="form-group">
                    <label class="form-label" for="price">
                        Price (TZS) <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="price"
                        name="price"
                        class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                        value="<?= e($old['price'] ?? '') ?>"
                        placeholder="e.g. 1500.00"
                        step="0.01"
                        min="0.01"
                        data-type="price"
                        required>
                    <?php if (isset($errors['price'])): ?>
                        <span class="form-error">✖ <?= e($errors['price']) ?></span>
                    <?php else: ?>
                        <span class="form-hint">Must be greater than 0.</span>
                    <?php endif; ?>
                </div>

                <!-- Stock Qty -->
                <div class="form-group">
                    <label class="form-label" for="stock_qty">
                        Stock Quantity <span class="required">*</span>
                    </label>
                    <input
                        type="number"
                        id="stock_qty"
                        name="stock_qty"
                        class="form-control <?= isset($errors['stock_qty']) ? 'is-invalid' : '' ?>"
                        value="<?= e($old['stock_qty'] ?? '') ?>"
                        placeholder="e.g. 50"
                        step="1"
                        min="0"
                        data-type="stock"
                        required>
                    <?php if (isset($errors['stock_qty'])): ?>
                        <span class="form-error">✖ <?= e($errors['stock_qty']) ?></span>
                    <?php else: ?>
                        <span class="form-hint">0 = out of stock. Must be a whole number.</span>
                    <?php endif; ?>
                </div>

            </div><!-- /.form-grid -->

            <div class="form-actions">
                <a href="products.php" class="btn btn-secondary" id="btnCancelAdd">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary" id="btnSubmitAdd">
                    <i class="fas fa-save"></i> Save Product
                </button>
            </div>

        </form>
    </div><!-- /.form-body -->
</div><!-- /.form-section -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
