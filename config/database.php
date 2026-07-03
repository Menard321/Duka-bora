<?php
/**
 * database.php
 * Database Configuration & Connection
 *
 * Provides a single MySQLi connection instance for the entire application.
 * Never exposes raw SQL errors to the end user.
 *
 * @package DukaBora
 */

// ── Database credentials ────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');           // Default XAMPP password is empty
define('DB_NAME',    'dukabora_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns an active MySQLi connection (singleton pattern).
 *
 * @return mysqli
 */
function getConnection(): mysqli
{
    static $connection = null;

    if ($connection === null) {
        // Create connection
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check connection
        if ($connection->connect_errno) {
            // Log the real error server-side (never show to user)
            error_log('[DukaBora DB Error] ' . $connection->connect_error);
            // Show a friendly message and halt
            die(renderDbError('Unable to connect to the database. Please contact the administrator.'));
        }

        // Set charset for proper UTF-8 handling
        $connection->set_charset(DB_CHARSET);
    }

    return $connection;
}

/**
 * Renders a styled, user-friendly database error message.
 *
 * @param string $message
 * @return string HTML string
 */
function renderDbError(string $message): string
{
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>System Error – Duka Bora</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f1f5f9; display: flex;
                   align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .error-box { background: #fff; border-left: 5px solid #e74c3c; padding: 2rem 3rem;
                         border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,.1); max-width: 480px; }
            h2 { color: #e74c3c; margin-top: 0; }
            p  { color: #555; line-height: 1.6; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2>⚠ Database Error</h2>
            <p>{$message}</p>
        </div>
    </body>
    </html>
    HTML;
}

// Establish connection immediately when file is included
getConnection();
