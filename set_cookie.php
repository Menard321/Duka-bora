<?php
/**
 * set_cookie.php – AJAX Cookie Setter
 *
 * Small endpoint called by products.php to set the
 * "last_viewed_product" PHP cookie server-side.
 *
 * @package DukaBora
 */

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId   = intval($_POST['product_id']   ?? 0);
    $productName = trim(strip_tags($_POST['product_name'] ?? ''));

    if ($productId > 0 && $productName !== '') {
        setLastViewedProduct($productId, $productName);
        echo json_encode(['status' => 'ok']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
