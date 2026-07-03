<?php
/**
 * nav.php
 * Main Navigation Bar
 *
 * Rendered on every page. Highlights the active link automatically
 * by comparing the current script name to each menu item path.
 *
 * @package DukaBora
 */

// Current script basename for active-state detection
$currentPage = basename($_SERVER['PHP_SELF']);

/**
 * Returns 'active' CSS class if the given filename matches the current page.
 *
 * @param string|array $page  Filename(s) to match
 * @return string
 */
function navActive(string|array $page): string
{
    global $currentPage;
    $pages = is_array($page) ? $page : [$page];
    return in_array($currentPage, $pages, true) ? 'active' : '';
}
?>

<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="navbar-container">

        <!-- Brand / Logo -->
        <a href="<?= basePath('index.php') ?>" class="navbar-brand" aria-label="Duka Bora Home">
            <span class="brand-icon"><i class="fas fa-store"></i></span>
            <span class="brand-name">Duka <strong>Bora</strong></span>
        </a>

        <!-- Mobile Hamburger -->
        <button class="navbar-toggler" id="navbarToggler" aria-label="Toggle navigation"
                aria-expanded="false" aria-controls="navbarMenu">
            <span></span><span></span><span></span>
        </button>

        <!-- Navigation Links -->
        <ul class="navbar-menu" id="navbarMenu" role="menubar">

            <li role="none">
                <a href="<?= basePath('index.php') ?>"
                   class="nav-link <?= navActive('index.php') ?>" role="menuitem">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <li role="none">
                <a href="<?= basePath('products.php') ?>"
                   class="nav-link <?= navActive(['products.php','add_product.php','edit_product.php']) ?>"
                   role="menuitem">
                    <i class="fas fa-boxes"></i> Products
                </a>
            </li>

            <li role="none">
                <a href="<?= basePath('categories.php') ?>"
                   class="nav-link <?= navActive('categories.php') ?>" role="menuitem">
                    <i class="fas fa-tags"></i> Categories
                </a>
            </li>

            <li role="none">
                <a href="<?= basePath('suppliers.php') ?>"
                   class="nav-link <?= navActive('suppliers.php') ?>" role="menuitem">
                    <i class="fas fa-truck"></i> Suppliers
                </a>
            </li>

            <li role="none">
                <a href="<?= basePath('record_sale.php') ?>"
                   class="nav-link <?= navActive('record_sale.php') ?>" role="menuitem">
                    <i class="fas fa-cash-register"></i> Record Sale
                </a>
            </li>

            <li role="none">
                <a href="<?= basePath('sales_history.php') ?>"
                   class="nav-link <?= navActive('sales_history.php') ?>" role="menuitem">
                    <i class="fas fa-history"></i> Sales History
                </a>
            </li>

            <li role="none">
                <a href="<?= basePath('report.php') ?>"
                   class="nav-link <?= navActive('report.php') ?>" role="menuitem">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>

        </ul>
    </div>
</nav>

<!-- Page wrapper opens here -->
<main class="page-wrapper" role="main">
