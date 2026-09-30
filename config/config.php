<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stardust_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads/decks');
define('UPLOAD_URL', '/uploads/decks');

define('APP_NAME', 'Stardust');
define('MAX_DECK_SIZE_MB', 15);
define('ALLOWED_DECK_TYPES', ['pdf', 'pptx', 'ppt']);

define('PYTHON_BIN', 'python3');
define('ANALYZE_SCRIPT', BASE_PATH . '/python/analyze_deck.py');

if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
    $projectRoot = str_replace('\\', '/', realpath(BASE_PATH));
    $rel = $docRoot && str_starts_with($projectRoot, $docRoot) ? substr($projectRoot, strlen($docRoot)) : '';
    define('BASE_URL', rtrim($rel, '/'));
}

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
ini_set('display_errors', '1');
