<?php
/**
 * header.php
 * HTML Page Head Section
 *
 * Included at the very top of every page.
 * Outputs DOCTYPE, <head>, and opens <body>.
 *
 * @package DukaBora
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// $pageTitle should be set by each page before including header
$pageTitle = isset($pageTitle) ? $pageTitle . ' – Duka Bora' : 'Duka Bora IMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Duka Bora Inventory Management System – Manage your retail inventory professionally.">
    <meta name="author" content="Duka Bora Dev Team">
    <title><?= e($pageTitle) ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons (CDN) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
          integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw=="
          crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Application Stylesheet -->
    <link rel="stylesheet" href="<?= basePath('css/style.css') ?>">
</head>
<body>
<?php
/**
 * Returns a root-relative path to a given asset.
 * Works regardless of folder depth.
 *
 * @param string $path
 * @return string
 */
function basePath(string $path): string
{
    // Determine depth from document root
    $scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $depth      = substr_count(trim($scriptDir, '/'), '/');

    // Build correct number of "../" prefixes
    // For root-level pages depth is 0 → no prefix needed
    $prefix = $depth > 0 ? str_repeat('../', $depth) : '';

    // Remove leading slashes from $path
    return $prefix . ltrim($path, '/');
}
?>
