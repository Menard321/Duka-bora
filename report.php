<?php
/**
 * report.php – Reports Dashboard
 *
 * Displays analytical reports for inventory manager:
 *  - Today's Sales amount
 *  - Current Inventory Count
 *  - Low Stock Products Count
 *  - Out of Stock Products Count
 *  - Top 3 Best Selling Products
 *  - Table listing Low Stock Products
 *  - Table listing Out of Stock Products
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Reports';

$conn = getConnection();
$stats = getDashboardStats();
$topSellers = getTopSellingProducts(3);

// Fetch low stock items for display in detailed tables
$lowStockProducts = getLowStockProducts(5); 

// Out of stock products
$outOfStockResult = $conn->query("
    SELECT p.*, c.category_name, s.supplier_name
    FROM products p
    JOIN categories c ON c.category_id = p.category_id
    JOIN suppliers s ON s.supplier_id = p.supplier_id
    WHERE p.stock_qty = 0
    ORDER BY p.name ASC
");

$outOfStockProducts = [];
if ($outOfStockResult) {
    $outOfStockProducts = $outOfStockResult->fetch_all(MYSQLI_ASSOC);
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/nav.php';
?>

<!-- ── Page Header ─────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i class="fas fa-chart-bar"></i> Inventory & Sales Reports
        </h1>
        <p class="page-subtitle">Analyze inventory levels and sales metrics for Duka Bora.</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">
        <i class="fas fa-print"></i> Print Report
    </button>
</div>

<!-- ── Report KPI Grid ─────────────────────────────────────────── -->
<div class="report-grid">

    <!-- Total Sales Today -->
    <div class="report-card">
        <div class="report-card-header">
            <span class="report-card-title">Sales Today</span>
            <div class="report-card-icon" style="background:var(--success-100); color:var(--success-600);">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
        <div class="report-card-value text-success" data-counter="<?= $stats['sales_today'] ?>" data-currency="true">
            <?= formatTZS($stats['sales_today']) ?>
        </div>
        <p class="text-xs text-muted" style="margin-top:4px;">Transactions completed today</p>
    </div>

    <!-- Total Revenue All-Time -->
    <div class="report-card">
        <div class="report-card-header">
            <span class="report-card-title">Total Revenue</span>
            <div class="report-card-icon" style="background:var(--primary-100); color:var(--primary-600);">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="report-card-value text-primary" data-counter="<?= $stats['total_sales_value'] ?>" data-currency="true">
            <?= formatTZS($stats['total_sales_value']) ?>
        </div>
        <p class="text-xs text-muted" style="margin-top:4px;">Cumulative system sales revenue</p>
    </div>

    <!-- Current Inventory Count -->
    <div class="report-card">
        <div class="report-card-header">
            <span class="report-card-title">Catalogue Count</span>
            <div class="report-card-icon" style="background:var(--info-100); color:var(--info-600);">
                <i class="fas fa-boxes"></i>
            </div>
        </div>
        <div class="report-card-value text-primary" data-counter="<?= $stats['total_products'] ?>">
            <?= $stats['total_products'] ?>
        </div>
        <p class="text-xs text-muted" style="margin-top:4px;">Unique products in system</p>
    </div>

    <!-- Low Stock Count -->
    <div class="report-card">
        <div class="report-card-header">
            <span class="report-card-title">Low Stock Alert</span>
            <div class="report-card-icon" style="background:var(--warning-100); color:var(--warning-600);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
        <div class="report-card-value text-warning" data-counter="<?= $stats['low_stock_count'] ?>">
            <?= $stats['low_stock_count'] ?>
        </div>
        <p class="text-xs text-muted" style="margin-top:4px;">Product quantity 1 to 4 units</p>
    </div>

    <!-- Out of Stock Count -->
    <div class="report-card">
        <div class="report-card-header">
            <span class="report-card-title">Out of Stock</span>
            <div class="report-card-icon" style="background:var(--danger-100); color:var(--danger-600);">
                <i class="fas fa-times-circle"></i>
            </div>
        </div>
        <div class="report-card-value text-danger" data-counter="<?= $stats['out_of_stock'] ?>">
            <?= $stats['out_of_stock'] ?>
        </div>
        <p class="text-xs text-muted" style="margin-top:4px;">Product quantity is exactly 0</p>
    </div>

</div>

<!-- ── Detailed Analytical Reports ────────────────────────────── -->
<div class="dashboard-grid">

    <!-- Top Selling Products Analysis -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-trophy"></i> Top 3 Best Selling Products
            </h2>
        </div>
        <div class="table-wrapper" style="border:none; box-shadow:none; border-radius:0;">
            <table class="data-table" aria-label="Top Selling Products">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product Name</th>
                        <th>Units Sold</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topSellers)): ?>
                        <tr>
                            <td colspan="4" class="table-empty">
                                <i class="fas fa-info-circle"></i> No sales recorded.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topSellers as $i => $product): ?>
                            <tr>
                                <td><span class="rank-num"><?= $i + 1 ?></span></td>
                                <td class="product-name"><?= e($product['name']) ?></td>
                                <td><strong><?= (int) $product['total_qty'] ?></strong></td>
                                <td class="price-value fw-bold"><?= formatTZS((float) $product['total_revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Critical Restock Requirements (Low & Out of stock lists) -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title" style="color:var(--danger-600);">
                <i class="fas fa-hand-holding-usd"></i> Critical Stock Status
            </h2>
        </div>
        <div class="card-body">
            <div style="display:flex; flex-direction:column; gap:var(--space-4);">
                
                <div>
                    <h3 class="text-small fw-semibold mb-2" style="color:var(--danger-600);">
                        Out of Stock List (<?= count($outOfStockProducts) ?>)
                    </h3>
                    <?php if (empty($outOfStockProducts)): ?>
                        <p class="text-muted text-xs">All products have stock active.</p>
                    <?php else: ?>
                        <div class="d-flex gap-2" style="flex-wrap:wrap;">
                            <?php foreach ($outOfStockProducts as $p): ?>
                                <span class="badge badge-danger"><?= e($p['name']) ?> (0 qty)</span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <hr class="divider">

                <div>
                    <h3 class="text-small fw-semibold mb-2" style="color:var(--warning-600);">
                        Low Stock List (<?= count($lowStockProducts) - count($outOfStockProducts) ?>)
                    </h3>
                    <?php 
                        $actualLow = array_filter($lowStockProducts, function($item) {
                            return (int)$item['stock_qty'] > 0;
                        });
                    ?>
                    <?php if (empty($actualLow)): ?>
                        <p class="text-muted text-xs">No products are currently in low-stock status.</p>
                    <?php else: ?>
                        <div class="d-flex gap-2" style="flex-wrap:wrap;">
                            <?php foreach ($actualLow as $p): ?>
                                <span class="badge badge-warning"><?= e($p['name']) ?> (<?= (int)$p['stock_qty'] ?> left)</span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

</div>

<!-- ── Low/Out of Stock Detailed Table ────────────────────────── -->
<div class="card mt-6">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-clipboard-list"></i> Full Restocking Sheet
        </h2>
    </div>
    <div class="table-wrapper" style="border:none; box-shadow:none; border-radius:0;">
        <table class="data-table" aria-label="Restocking Sheet">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lowStockProducts)): ?>
                    <tr>
                        <td colspan="4" class="table-empty">
                            <i class="fas fa-check-circle" style="color:var(--success-500);"></i>
                            All items are well stocked! No restocking actions required.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $p): ?>
                        <tr>
                            <td class="product-name"><?= e($p['name']) ?></td>
                            <td><span class="badge badge-primary"><?= e($p['category_name']) ?></span></td>
                            <td><strong><?= (int) $p['stock_qty'] ?></strong> units</td>
                            <td>
                                <?= stockBadge((int) $p['stock_qty']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
