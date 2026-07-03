<?php
/**
 * functions.php
 * Global Reusable Helper Functions
 *
 * Centralizes all utility logic so no code is duplicated across pages.
 *
 * @package DukaBora
 */

require_once __DIR__ . '/../config/database.php';

// ── Output / Security Helpers ────────────────────────────────────────

/**
 * Safely echoes an HTML-escaped string (prevents XSS).
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitise user input: trim + escape for HTML output.
 * Use MySQLi prepared statements for DB — do NOT rely on this alone for SQL.
 *
 * @param string $input
 * @return string
 */
function sanitize(string $input): string
{
    return trim(htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
}

// ── Flash Message Helpers ────────────────────────────────────────────

/**
 * Sets a one-time flash message in the session.
 *
 * @param string $type    'success' | 'error' | 'warning' | 'info'
 * @param string $message
 */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Renders and clears any pending flash message.
 *
 * @return string HTML
 */
function renderFlash(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['flash'])) {
        return '';
    }

    $type    = e($_SESSION['flash']['type']);
    $message = e($_SESSION['flash']['message']);
    unset($_SESSION['flash']);

    $icons = [
        'success' => '✔',
        'error'   => '✖',
        'warning' => '⚠',
        'info'    => 'ℹ',
    ];
    $icon = $icons[$_SESSION['flash']['type'] ?? 'info'] ?? 'ℹ';

    return <<<HTML
    <div class="alert alert-{$type}" role="alert">
        <span class="alert-icon">{$icon}</span>
        <span>{$message}</span>
    </div>
    HTML;
}

// ── Stock Badge Helper ────────────────────────────────────────────────

/**
 * Returns an HTML badge based on stock quantity.
 *
 * Green  : qty >= 10
 * Yellow : qty 1–9
 * Red    : qty = 0
 *
 * @param int $qty
 * @return string HTML badge element
 */
function stockBadge(int $qty): string
{
    if ($qty === 0) {
        $class = 'badge badge-danger';
        $label = 'Out of Stock';
    } elseif ($qty < 10) {
        $class = 'badge badge-warning';
        $label = 'Low Stock';
    } else {
        $class = 'badge badge-success';
        $label = 'In Stock';
    }

    return "<span class=\"{$class}\">{$label}</span>";
}

// ── Category Helpers ─────────────────────────────────────────────────

/**
 * Fetches all categories ordered by name.
 *
 * @return array
 */
function getAllCategories(): array
{
    $conn   = getConnection();
    $result = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");

    if (!$result) {
        error_log('[DukaBora] getAllCategories(): ' . $conn->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// ── Supplier Helpers ─────────────────────────────────────────────────

/**
 * Fetches all suppliers ordered by name.
 *
 * @return array
 */
function getAllSuppliers(): array
{
    $conn   = getConnection();
    $result = $conn->query("SELECT * FROM suppliers ORDER BY supplier_name ASC");

    if (!$result) {
        error_log('[DukaBora] getAllSuppliers(): ' . $conn->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// ── Product Helpers ───────────────────────────────────────────────────

/**
 * Fetches a single product by ID with joined category and supplier names.
 *
 * @param int $productId
 * @return array|null
 */
function getProductById(int $productId): ?array
{
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT p.*, c.category_name, s.supplier_name
        FROM   products  p
        JOIN   categories c ON c.category_id = p.category_id
        JOIN   suppliers  s ON s.supplier_id = p.supplier_id
        WHERE  p.product_id = ?
    ");

    if (!$stmt) {
        error_log('[DukaBora] getProductById prepare: ' . $conn->error);
        return null;
    }

    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Returns all products with category and supplier name (for listings).
 *
 * @return array
 */
function getAllProducts(): array
{
    $conn   = getConnection();
    $result = $conn->query("
        SELECT p.*, c.category_name, s.supplier_name
        FROM   products  p
        JOIN   categories c ON c.category_id = p.category_id
        JOIN   suppliers  s ON s.supplier_id = p.supplier_id
        ORDER  BY p.name ASC
    ");

    if (!$result) {
        error_log('[DukaBora] getAllProducts(): ' . $conn->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Returns products with stock below the threshold (default 5).
 *
 * @param int $threshold
 * @return array
 */
function getLowStockProducts(int $threshold = 5): array
{
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT p.*, c.category_name
        FROM   products   p
        JOIN   categories c ON c.category_id = p.category_id
        WHERE  p.stock_qty < ?
        ORDER  BY p.stock_qty ASC
    ");

    if (!$stmt) {
        error_log('[DukaBora] getLowStockProducts prepare: ' . $conn->error);
        return [];
    }

    $stmt->bind_param('i', $threshold);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

// ── Cookie Helpers (Last Viewed Product) ─────────────────────────────

/**
 * Sets a cookie recording the last viewed product.
 *
 * @param int    $productId
 * @param string $productName
 */
function setLastViewedProduct(int $productId, string $productName): void
{
    $payload = json_encode(['id' => $productId, 'name' => $productName]);
    // Cookie expires in 30 days
    setcookie('last_viewed_product', $payload, time() + (30 * 24 * 60 * 60), '/');
    $_COOKIE['last_viewed_product'] = $payload; // available in current request
}

/**
 * Retrieves the last viewed product from cookies.
 *
 * @return array|null  ['id' => int, 'name' => string] or null
 */
function getLastViewedProduct(): ?array
{
    if (!isset($_COOKIE['last_viewed_product'])) {
        return null;
    }

    $data = json_decode($_COOKIE['last_viewed_product'], true);

    if (!is_array($data) || empty($data['id']) || empty($data['name'])) {
        return null;
    }

    return $data;
}

// ── Dashboard Stats ───────────────────────────────────────────────────

/**
 * Returns an associative array of dashboard KPIs.
 *
 * @return array
 */
function getDashboardStats(): array
{
    $conn = getConnection();

    $stats = [
        'total_products'    => 0,
        'total_categories'  => 0,
        'total_suppliers'   => 0,
        'total_sales_value' => 0.00,
        'sales_today'       => 0.00,
        'low_stock_count'   => 0,
        'out_of_stock'      => 0,
    ];

    // Count products
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM products");
    if ($r) $stats['total_products'] = (int) $r->fetch_assoc()['cnt'];

    // Count categories
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
    if ($r) $stats['total_categories'] = (int) $r->fetch_assoc()['cnt'];

    // Count suppliers
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM suppliers");
    if ($r) $stats['total_suppliers'] = (int) $r->fetch_assoc()['cnt'];

    // Total all-time sales value
    $r = $conn->query("SELECT COALESCE(SUM(total_price),0) AS total FROM sales");
    if ($r) $stats['total_sales_value'] = (float) $r->fetch_assoc()['total'];

    // Today's sales value
    $r = $conn->query("SELECT COALESCE(SUM(total_price),0) AS today
                        FROM sales
                        WHERE DATE(sale_date) = CURDATE()");
    if ($r) $stats['sales_today'] = (float) $r->fetch_assoc()['today'];

    // Low stock (< 5, excluding out-of-stock)
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock_qty > 0 AND stock_qty < 5");
    if ($r) $stats['low_stock_count'] = (int) $r->fetch_assoc()['cnt'];

    // Out of stock
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE stock_qty = 0");
    if ($r) $stats['out_of_stock'] = (int) $r->fetch_assoc()['cnt'];

    return $stats;
}

/**
 * Returns the top N best-selling products by total quantity sold.
 *
 * @param int $limit
 * @return array
 */
function getTopSellingProducts(int $limit = 3): array
{
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT   p.name, SUM(s.qty_sold) AS total_qty, SUM(s.total_price) AS total_revenue
        FROM     sales    s
        JOIN     products p ON p.product_id = s.product_id
        GROUP BY s.product_id
        ORDER BY total_qty DESC
        LIMIT    ?
    ");

    if (!$stmt) {
        error_log('[DukaBora] getTopSellingProducts prepare: ' . $conn->error);
        return [];
    }

    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

// ── Number Formatting ─────────────────────────────────────────────────

/**
 * Formats a number as Tanzanian Shillings.
 *
 * @param float $amount
 * @return string
 */
function formatTZS(float $amount): string
{
    return 'TZS ' . number_format($amount, 2);
}
