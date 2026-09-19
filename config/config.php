<?php
/**
 * Stardust — Startup Pitchdeck Reviewer Platform
 * Global configuration constants.
 */

// ---- Database ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'stardust_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---- Paths ----
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads/decks');
define('UPLOAD_URL', '/uploads/decks');

// ---- App ----
define('APP_NAME', 'Stardust');
define('MAX_DECK_SIZE_MB', 15);
define('ALLOWED_DECK_TYPES', ['pdf', 'pptx', 'ppt']);

// ---- Python analyzer ----
// Path to the python3 binary and the analyzer script used to pull an
// excerpt + keywords out of an uploaded deck right after upload.
define('PYTHON_BIN', 'python3');
define('ANALYZE_SCRIPT', BASE_PATH . '/python/analyze_deck.py');

// ---- Base URL ----
// Works whether the project sits at the web root (htdocs/) or in a
// subfolder (htdocs/stardust/) — computed from the document root so
// every page can link with BASE_URL . '/assets/...' etc.
if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $projectRoot = str_replace('\\', '/', realpath(BASE_PATH));
    $rel = $docRoot && str_starts_with($projectRoot, $docRoot) ? substr($projectRoot, strlen($docRoot)) : '';
    define('BASE_URL', rtrim($rel, '/'));
}

// ---- Session ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Kolkata');
error_reporting(E_ALL);
ini_set('display_errors', '1'); // set to 0 in production
