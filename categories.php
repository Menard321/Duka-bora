<?php
/**
 * categories.php – Manage Categories
 *
 * Provides inline add, rename, and delete functionality for product categories.
 * All mutations use prepared statements.
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Categories';
$errors    = [];

/* ── Handle POST actions ────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Add Category ─────────────────────────────────────────
    if ($action === 'add') {
        $name = trim($_POST['category_name'] ?? '');

        if ($name === '') {
            $errors['add'] = 'Category name cannot be empty.';
        } elseif (strlen($name) > 100) {
            $errors['add'] = 'Category name must not exceed 100 characters.';
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");

            if ($stmt) {
                $stmt->bind_param('s', $name);
                try {
                    if ($stmt->execute()) {
                        $stmt->close();
                        setFlash('success', "Category \"{$name}\" added.");
                        header('Location: categories.php');
                        exit;
                    } else {
                        $stmt->close();
                        $errors['add'] = 'Failed to add category. Please try again.';
                    }
                } catch (mysqli_sql_exception $e) {
                    $stmt->close();
                    $errors['add'] = ($conn->errno === 1062 || $e->getCode() === 1062 || str_contains($e->getMessage(), '1062'))
                        ? "Category \"{$name}\" already exists."
                        : 'Failed to add category. Please try again.';
                }
            } else {
                error_log('[DukaBora] categories add prepare: ' . $conn->error);
                $errors['add'] = 'A system error occurred.';
            }
        }
    }

    // ── Edit Category ─────────────────────────────────────────
    elseif ($action === 'edit') {
        $id   = intval($_POST['category_id'] ?? 0);
        $name = trim($_POST['category_name'] ?? '');

        if ($id <= 0) {
            setFlash('error', 'Invalid category.');
        } elseif ($name === '') {
            setFlash('error', 'Category name cannot be empty.');
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");

            if ($stmt) {
                $stmt->bind_param('si', $name, $id);
                try {
                    if ($stmt->execute()) {
                        $stmt->close();
                        setFlash('success', "Category updated to \"{$name}\".");
                    } else {
                        $stmt->close();
                        setFlash('error', 'Failed to update category.');
                    }
                } catch (mysqli_sql_exception $e) {
                    $stmt->close();
                    setFlash('error',
                        ($conn->errno === 1062 || $e->getCode() === 1062 || str_contains($e->getMessage(), '1062'))
                            ? "Category \"{$name}\" already exists."
                            : 'Failed to update category.'
                    );
                }
            } else {
                error_log('[DukaBora] categories edit prepare: ' . $conn->error);
                setFlash('error', 'A system error occurred.');
            }
        }
        header('Location: categories.php');
        exit;
    }

    // ── Delete Category ───────────────────────────────────────
    elseif ($action === 'delete') {
        $id = intval($_POST['category_id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid category.');
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");

            if ($stmt) {
                $stmt->bind_param('i', $id);
                try {
                    if ($stmt->execute()) {
                        $stmt->close();
                        setFlash('success', 'Category deleted.');
                    } else {
                        $stmt->close();
                        setFlash('error', 'Failed to delete category.');
                    }
                } catch (mysqli_sql_exception $e) {
                    $errno = $conn->errno ?: $e->getCode();
                    $stmt->close();
                    if ($errno == 1451 || str_contains($e->getMessage(), '1451')) {
                        setFlash('error', 'Cannot delete – products are linked to this category.');
                    } else {
                        error_log('[DukaBora] categories delete exception: ' . $e->getMessage());
                        setFlash('error', 'Failed to delete category.');
                    }
                }
            } else {
                error_log('[DukaBora] categories delete prepare: ' . $conn->error);
                setFlash('error', 'A system error occurred.');
            }
        }
        header('Location: categories.php');
        exit;
    }
}

/* ── Load categories ────────────────────────────────────────── */
$categories = getAllCategories();

// Enrich: count products per category
$conn        = getConnection();
$countResult = $conn->query("
    SELECT category_id, COUNT(*) AS product_count
    FROM products
    GROUP BY category_id
");
$productCounts = [];
if ($countResult) {
    while ($row = $countResult->fetch_assoc()) {
        $productCounts[$row['category_id']] = (int) $row['product_count'];
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-tags"></i> Categories
        </h1>
        <p class="page-subtitle">
            <?= count($categories) ?> categor<?= count($categories) !== 1 ? 'ies' : 'y' ?> registered
        </p>
    </div>
</div>

<?= renderFlash() ?>

<div style="display:grid; grid-template-columns:1fr 1.6fr; gap:var(--space-6); align-items:start;">

    <!-- ── Add Category Form ──────────────────────────────────── -->
    <div class="form-section">
        <div class="form-section-header">
            <i class="fas fa-plus-circle"></i>
            <h2>Add New Category</h2>
        </div>
        <div class="form-body">
            <form method="POST" action="categories.php" data-validate novalidate id="addCategoryForm">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label class="form-label" for="category_name">
                        Category Name <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="category_name"
                        name="category_name"
                        class="form-control <?= isset($errors['add']) ? 'is-invalid' : '' ?>"
                        placeholder="e.g. Electronics"
                        maxlength="100"
                        required
                        autocomplete="off">
                    <?php if (isset($errors['add'])): ?>
                        <span class="form-error">✖ <?= e($errors['add']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-actions" style="padding-top:var(--space-4); border-top:none;">
                    <button type="submit" class="btn btn-primary" id="btnAddCategory">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Categories Table ───────────────────────────────────── -->
    <div class="table-wrapper">
        <table class="data-table" id="mainTable" aria-label="Categories list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Products</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="4" class="table-empty">
                            <i class="fas fa-tags"></i>
                            No categories yet. Add the first one!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $i => $cat): ?>
                        <tr>
                            <td class="text-muted text-small"><?= $i + 1 ?></td>
                            <td class="fw-medium"><?= e($cat['category_name']) ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?= $productCounts[$cat['category_id']] ?? 0 ?> products
                                </span>
                            </td>
                            <td>
                                <div class="action-group">
                                    <!-- Edit trigger -->
                                    <button
                                        class="btn btn-sm btn-warning"
                                        id="btnEditCat-<?= (int)$cat['category_id'] ?>"
                                        onclick="openEditModal(<?= (int)$cat['category_id'] ?>, '<?= addslashes(e($cat['category_name'])) ?>')"
                                        aria-label="Edit <?= e($cat['category_name']) ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>

                                    <!-- Delete form -->
                                    <form method="POST" action="categories.php" style="display:inline;">
                                        <input type="hidden" name="action"      value="delete">
                                        <input type="hidden" name="category_id" value="<?= (int)$cat['category_id'] ?>">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            id="btnDeleteCat-<?= (int)$cat['category_id'] ?>"
                                            data-confirm="Delete category &quot;<?= e($cat['category_name']) ?>&quot;?"
                                            aria-label="Delete <?= e($cat['category_name']) ?>">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /grid -->

<!-- ── Edit Modal ─────────────────────────────────────────────── -->
<div id="editModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
     z-index:2000; align-items:center; justify-content:center;">
    <div style="background:var(--white); border-radius:var(--radius-xl); padding:var(--space-8);
                min-width:360px; box-shadow:var(--shadow-xl); max-width:90vw;">
        <h3 style="margin-bottom:var(--space-6); color:var(--gray-800);">
            <i class="fas fa-edit" style="color:var(--warning-500);"></i> Edit Category
        </h3>
        <form method="POST" action="categories.php" id="editCategoryForm">
            <input type="hidden" name="action"      value="edit">
            <input type="hidden" name="category_id" id="editCategoryId">
            <div class="form-group" style="margin-bottom:var(--space-6);">
                <label class="form-label" for="editCategoryName">Category Name</label>
                <input type="text" id="editCategoryName" name="category_name"
                       class="form-control" maxlength="100" required>
            </div>
            <div style="display:flex; gap:var(--space-3); justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-warning" id="btnSaveCategory">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name) {
    document.getElementById('editCategoryId').value   = id;
    document.getElementById('editCategoryName').value = name;
    const modal = document.getElementById('editModal');
    modal.style.display = 'flex';
    modal.querySelector('input[type="text"]').focus();
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
// Close on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
