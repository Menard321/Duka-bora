<?php
/**
 * suppliers.php – Manage Suppliers
 *
 * Full CRUD for supplier records: name, phone, location.
 * Uses prepared statements for all DB writes.
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Suppliers';
$errors    = [];

/* ── Handle POST actions ────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Add Supplier ──────────────────────────────────────────
    if ($action === 'add') {
        $name     = trim($_POST['supplier_name'] ?? '');
        $phone    = trim($_POST['phone']         ?? '');
        $location = trim($_POST['location']      ?? '');

        if ($name === '')     $errors['name']     = 'Supplier name is required.';
        if ($phone === '')    $errors['phone']    = 'Phone number is required.';
        if ($location === '') $errors['location'] = 'Location is required.';

        if (empty($errors)) {
            $conn = getConnection();
            $stmt = $conn->prepare("
                INSERT INTO suppliers (supplier_name, phone, location) VALUES (?, ?, ?)
            ");

            if ($stmt) {
                $stmt->bind_param('sss', $name, $phone, $location);
                try {
                    if ($stmt->execute()) {
                        $stmt->close();
                        setFlash('success', "Supplier \"{$name}\" added.");
                        header('Location: suppliers.php');
                        exit;
                    } else {
                        $stmt->close();
                        $errors['add'] = 'Failed to add supplier.';
                    }
                } catch (mysqli_sql_exception $e) {
                    $stmt->close();
                    $errors['add'] = ($conn->errno === 1062 || $e->getCode() === 1062 || str_contains($e->getMessage(), '1062'))
                        ? "Phone number {$phone} is already registered."
                        : 'Failed to add supplier.';
                }
            } else {
                error_log('[DukaBora] suppliers add prepare: ' . $conn->error);
                $errors['add'] = 'A system error occurred.';
            }
        }
    }

    // ── Edit Supplier ─────────────────────────────────────────
    elseif ($action === 'edit') {
        $id       = intval($_POST['supplier_id']   ?? 0);
        $name     = trim($_POST['supplier_name']   ?? '');
        $phone    = trim($_POST['phone']            ?? '');
        $location = trim($_POST['location']         ?? '');

        if ($id <= 0 || $name === '' || $phone === '' || $location === '') {
            setFlash('error', 'All supplier fields are required.');
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("
                UPDATE suppliers
                SET supplier_name = ?, phone = ?, location = ?
                WHERE supplier_id = ?
            ");

            if ($stmt) {
                $stmt->bind_param('sssi', $name, $phone, $location, $id);
                try {
                    if ($stmt->execute()) {
                        $stmt->close();
                        setFlash('success', "Supplier \"{$name}\" updated.");
                    } else {
                        $stmt->close();
                        setFlash('error', 'Failed to update supplier.');
                    }
                } catch (mysqli_sql_exception $e) {
                    $stmt->close();
                    setFlash('error',
                        ($conn->errno === 1062 || $e->getCode() === 1062 || str_contains($e->getMessage(), '1062'))
                            ? "Phone number {$phone} is already used."
                            : 'Failed to update supplier.'
                    );
                }
            } else {
                error_log('[DukaBora] suppliers edit prepare: ' . $conn->error);
                setFlash('error', 'A system error occurred.');
            }
        }
        header('Location: suppliers.php');
        exit;
    }

    // ── Delete Supplier ───────────────────────────────────────
    elseif ($action === 'delete') {
        $id = intval($_POST['supplier_id'] ?? 0);

        if ($id <= 0) {
            setFlash('error', 'Invalid supplier.');
        } else {
            $conn = getConnection();
            $stmt = $conn->prepare("DELETE FROM suppliers WHERE supplier_id = ?");

            if ($stmt) {
                $stmt->bind_param('i', $id);
                try {
                    if ($stmt->execute()) {
                        $stmt->close();
                        setFlash('success', 'Supplier deleted.');
                    } else {
                        $stmt->close();
                        setFlash('error', 'Failed to delete supplier.');
                    }
                } catch (mysqli_sql_exception $e) {
                    $errno = $conn->errno ?: $e->getCode();
                    $stmt->close();
                    if ($errno == 1451 || str_contains($e->getMessage(), '1451')) {
                        setFlash('error', 'Cannot delete – products are linked to this supplier.');
                    } else {
                        error_log('[DukaBora] suppliers delete exception: ' . $e->getMessage());
                        setFlash('error', 'Failed to delete supplier.');
                    }
                }
            } else {
                error_log('[DukaBora] suppliers delete prepare: ' . $conn->error);
                setFlash('error', 'A system error occurred.');
            }
        }
        header('Location: suppliers.php');
        exit;
    }
}

/* ── Load suppliers ─────────────────────────────────────────── */
$suppliers = getAllSuppliers();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-truck"></i> Suppliers
        </h1>
        <p class="page-subtitle">
            <?= count($suppliers) ?> supplier<?= count($suppliers) !== 1 ? 's' : '' ?> registered
        </p>
    </div>
</div>

<?= renderFlash() ?>

<?php if (isset($errors['add'])): ?>
    <div class="alert alert-error">
        <span class="alert-icon">✖</span><?= e($errors['add']) ?>
    </div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 2fr; gap:var(--space-6); align-items:start;">

    <!-- ── Add Supplier Form ──────────────────────────────────── -->
    <div class="form-section">
        <div class="form-section-header">
            <i class="fas fa-plus-circle"></i>
            <h2>Add New Supplier</h2>
        </div>
        <div class="form-body">
            <form method="POST" action="suppliers.php" data-validate novalidate id="addSupplierForm">
                <input type="hidden" name="action" value="add">

                <div class="form-group" style="margin-bottom:var(--space-5);">
                    <label class="form-label" for="supplier_name">
                        Supplier Name <span class="required">*</span>
                    </label>
                    <input type="text" id="supplier_name" name="supplier_name"
                           class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. TechLink Supplies"
                           maxlength="150" required autocomplete="off">
                    <?php if (isset($errors['name'])): ?>
                        <span class="form-error">✖ <?= e($errors['name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="margin-bottom:var(--space-5);">
                    <label class="form-label" for="phone">
                        Phone <span class="required">*</span>
                    </label>
                    <input type="tel" id="phone" name="phone"
                           class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                           placeholder="+254700000000"
                           maxlength="20" required>
                    <?php if (isset($errors['phone'])): ?>
                        <span class="form-error">✖ <?= e($errors['phone']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="margin-bottom:var(--space-5);">
                    <label class="form-label" for="location">
                        Location <span class="required">*</span>
                    </label>
                    <input type="text" id="location" name="location"
                           class="form-control <?= isset($errors['location']) ? 'is-invalid' : '' ?>"
                           placeholder="e.g. Nairobi, Kenya"
                           maxlength="200" required>
                    <?php if (isset($errors['location'])): ?>
                        <span class="form-error">✖ <?= e($errors['location']) ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary" id="btnAddSupplier">
                    <i class="fas fa-plus"></i> Add Supplier
                </button>

            </form>
        </div>
    </div>

    <!-- ── Suppliers Table ────────────────────────────────────── -->
    <div class="table-wrapper">
        <table class="data-table" id="mainTable" aria-label="Suppliers list">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Supplier Name</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                    <tr>
                        <td colspan="5" class="table-empty">
                            <i class="fas fa-truck"></i>
                            No suppliers yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($suppliers as $i => $sup): ?>
                        <tr>
                            <td class="text-muted text-small"><?= $i + 1 ?></td>
                            <td class="fw-medium"><?= e($sup['supplier_name']) ?></td>
                            <td><?= e($sup['phone']) ?></td>
                            <td><?= e($sup['location']) ?></td>
                            <td>
                                <div class="action-group">
                                    <button
                                        class="btn btn-sm btn-warning"
                                        id="btnEditSup-<?= (int)$sup['supplier_id'] ?>"
                                        onclick="openEditSupplier(
                                            <?= (int)$sup['supplier_id'] ?>,
                                            '<?= addslashes(e($sup['supplier_name'])) ?>',
                                            '<?= addslashes(e($sup['phone'])) ?>',
                                            '<?= addslashes(e($sup['location'])) ?>'
                                        )">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" action="suppliers.php" style="display:inline;">
                                        <input type="hidden" name="action"      value="delete">
                                        <input type="hidden" name="supplier_id" value="<?= (int)$sup['supplier_id'] ?>">
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            id="btnDeleteSup-<?= (int)$sup['supplier_id'] ?>"
                                            data-confirm="Delete supplier &quot;<?= e($sup['supplier_name']) ?>&quot;?">
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

<!-- ── Edit Supplier Modal ─────────────────────────────────────── -->
<div id="editSupModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
     z-index:2000; align-items:center; justify-content:center;">
    <div style="background:var(--white); border-radius:var(--radius-xl); padding:var(--space-8);
                min-width:400px; box-shadow:var(--shadow-xl); max-width:90vw;">
        <h3 style="margin-bottom:var(--space-6); color:var(--gray-800);">
            <i class="fas fa-edit" style="color:var(--warning-500);"></i> Edit Supplier
        </h3>
        <form method="POST" action="suppliers.php">
            <input type="hidden" name="action"      value="edit">
            <input type="hidden" name="supplier_id" id="editSupId">

            <div class="form-group" style="margin-bottom:var(--space-4);">
                <label class="form-label" for="editSupName">Supplier Name</label>
                <input type="text" id="editSupName" name="supplier_name"
                       class="form-control" maxlength="150" required>
            </div>
            <div class="form-group" style="margin-bottom:var(--space-4);">
                <label class="form-label" for="editSupPhone">Phone</label>
                <input type="tel" id="editSupPhone" name="phone"
                       class="form-control" maxlength="20" required>
            </div>
            <div class="form-group" style="margin-bottom:var(--space-6);">
                <label class="form-label" for="editSupLocation">Location</label>
                <input type="text" id="editSupLocation" name="location"
                       class="form-control" maxlength="200" required>
            </div>

            <div style="display:flex; gap:var(--space-3); justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeEditSupplier()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-warning" id="btnSaveSupplier">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditSupplier(id, name, phone, location) {
    document.getElementById('editSupId').value       = id;
    document.getElementById('editSupName').value     = name;
    document.getElementById('editSupPhone').value    = phone;
    document.getElementById('editSupLocation').value = location;
    const modal = document.getElementById('editSupModal');
    modal.style.display = 'flex';
}
function closeEditSupplier() {
    document.getElementById('editSupModal').style.display = 'none';
}
document.getElementById('editSupModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditSupplier();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
