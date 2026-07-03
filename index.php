<?php
/**
 * index.php – Dashboard
 *
 * The main landing page. Displays:
 *  - KPI stat cards
 *  - Low stock alert panel
 *  - Recently viewed product (PHP cookie)
 *  - Top selling products summary
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle  = 'Dashboard';
$stats      = getDashboardStats();
$lowStock   = getLowStockProducts(5);      // all products with qty < 5
$topSellers = getTopSellingProducts(3);
$lastViewed = getLastViewedProduct();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-tachometer-alt"></i>
            Dashboard
        </h1>
        <p class="page-subtitle">
            Welcome back! Here's your inventory snapshot for
            <strong><?= date('d F Y') ?></strong>.
        </p>
    </div>
    <a href="record_sale.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Record New Sale
    </a>
</div>

<!-- ── Recently Viewed Product (Cookie) ───────────────────────── -->
<?php if ($lastViewed): ?>
    <div class="recently-viewed" role="complementary" aria-label="Recently Viewed">
        <i class="fas fa-eye"></i>
        <span>
            <strong>Recently Viewed:</strong>
            <a href="products.php"><?= e($lastViewed['name']) ?></a>
        </span>
    </div>
<?php endif; ?>

<!-- ── Flash Message ──────────────────────────────────────────── -->
<?= renderFlash() ?>

<!-- ── KPI Stat Cards ─────────────────────────────────────────── -->
<div class="stat-grid" aria-label="Key Performance Indicators">

    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-boxes"></i></div>
        <div class="stat-info">
            <div class="stat-value"
                 data-counter="<?= $stats['total_products'] ?>">
                <?= $stats['total_products'] ?>
            </div>
            <div class="stat-label">Total Products</div>
            <div class="stat-sublabel">In catalogue</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-tags"></i></div>
        <div class="stat-info">
            <div class="stat-value"
                 data-counter="<?= $stats['total_categories'] ?>">
                <?= $stats['total_categories'] ?>
            </div>
            <div class="stat-label">Categories</div>
            <div class="stat-sublabel">Product groups</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-truck"></i></div>
        <div class="stat-info">
            <div class="stat-value"
                 data-counter="<?= $stats['total_suppliers'] ?>">
                <?= $stats['total_suppliers'] ?>
            </div>
            <div class="stat-label">Suppliers</div>
            <div class="stat-sublabel">Active partners</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-coins"></i></div>
        <div class="stat-info">
            <div class="stat-value"
                 data-counter="<?= $stats['total_sales_value'] ?>"
                 data-currency="true">
                <?= formatTZS($stats['total_sales_value']) ?>
            </div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-sublabel">All-time sales</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info">
            <div class="stat-value"
                 data-counter="<?= $stats['sales_today'] ?>"
                 data-currency="true">
                <?= formatTZS($stats['sales_today']) ?>
            </div>
            <div class="stat-label">Today's Sales</div>
            <div class="stat-sublabel"><?= date('d M Y') ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon yellow"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <div class="stat-value"
                 data-counter="<?= $stats['low_stock_count'] ?>">
                <?= $stats['low_stock_count'] ?>
            </div>
            <div class="stat-label">Low Stock Items</div>
            <div class="stat-sublabel">Below 5 units</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"
                 data-counter="<?= $stats['out_of_stock'] ?>">
                <?= $stats['out_of_stock'] ?>
            </div>
            <div class="stat-label">Out of Stock</div>
            <div class="stat-sublabel">Need restocking</div>
        </div>
    </div>

</div><!-- /.stat-grid -->

<!-- ── Low Stock Alert ─────────────────────────────────────────── -->
<?php if (!empty($lowStock)): ?>
    <div class="low-stock-alert" role="alert" aria-label="Low Stock Warning">
        <div class="low-stock-alert-title">
            <i class="fas fa-exclamation-triangle"></i>
            Low Stock Warning – <?= count($lowStock) ?> product(s) require attention
        </div>
        <div class="low-stock-list">
            <?php foreach ($lowStock as $item): ?>
                <span class="badge <?= $item['stock_qty'] === 0 ? 'badge-danger' : 'badge-warning' ?>">
                    <?= e($item['name']) ?>
                    (<?= (int) $item['stock_qty'] ?> left)
                </span>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- ── Dashboard Grid: Top Sellers + Quick Links ─────────────── -->
<div class="dashboard-grid">

    <!-- Top Selling Products -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-fire"></i> Top Selling Products
            </h2>
            <a href="report.php" class="btn btn-sm btn-outline-primary">
                Full Report <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-wrapper" style="border:none; box-shadow:none; border-radius:0;">
            <table class="data-table top-sellers-table" aria-label="Top selling products">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Qty Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topSellers)): ?>
                        <tr>
                            <td colspan="4" class="table-empty">
                                <i class="fas fa-chart-bar"></i>
                                No sales data yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topSellers as $i => $product): ?>
                            <tr>
                                <td><span class="rank-num"><?= $i + 1 ?></span></td>
                                <td class="product-name"><?= e($product['name']) ?></td>
                                <td><?= (int) $product['total_qty'] ?></td>
                                <td class="price-value"><?= formatTZS((float)$product['total_revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div><!-- /.card -->

    <!-- Quick Links Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction:column; gap:var(--space-3);">
                <a href="add_product.php"    class="btn btn-primary btn-lg">
                    <i class="fas fa-plus-circle"></i>  Add New Product
                </a>
                <a href="record_sale.php"    class="btn btn-success btn-lg">
                    <i class="fas fa-cash-register"></i> Record a Sale
                </a>
                <a href="sales_history.php"  class="btn btn-secondary btn-lg">
                    <i class="fas fa-history"></i>       Sales History
                </a>
                <a href="categories.php"     class="btn btn-secondary btn-lg">
                    <i class="fas fa-tags"></i>          Manage Categories
                </a>
                <a href="suppliers.php"      class="btn btn-secondary btn-lg">
                    <i class="fas fa-truck"></i>         Manage Suppliers
                </a>
                <a href="report.php"         class="btn btn-secondary btn-lg">
                    <i class="fas fa-chart-bar"></i>     View Reports
                </a>
            </div>
        </div>
    </div><!-- /.card -->

</div><!-- /.dashboard-grid -->

<?php require_once __DIR__ . '/includes/footer.php'; ?>
