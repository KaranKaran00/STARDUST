<?php
/**
 * Shared helper functions.
 */

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Security check failed. Please go back and try again.');
    }
}

function flash(string $key, ?string $msg = null)
{
    if ($msg !== null) {
        $_SESSION['flash'][$key] = $msg;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $val = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $val;
    }
    return null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Format money like ₹5,00,000 */
function money(int $amount): string
{
    return '₹' . number_format($amount);
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}

function status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Pending review',
        'in_review' => 'In review',
        'shortlisted' => 'Shortlisted',
        'rejected' => 'Not moving forward',
        default => ucfirst($status),
    };
}

/** Comma-string <-> array helpers for the preference / tag fields */
function csv_to_array(?string $csv): array
{
    if (!$csv) return [];
    return array_filter(array_map('trim', explode(',', $csv)));
}

function array_to_csv(array $arr): string
{
    return implode(',', array_filter(array_map('trim', $arr)));
}

/**
 * Runs the Python deck analyzer on an uploaded file and returns
 * ['excerpt' => string, 'keywords' => string]. Never throws — on any
 * failure (python missing, libs missing, bad file) it just returns
 * empty strings so the upload flow is never blocked by this step.
 */
function analyze_deck_file(string $absolutePath, string $ext): array
{
    $fallback = ['excerpt' => '', 'keywords' => ''];
    if (!is_file($absolutePath)) return $fallback;

    $cmd = escapeshellcmd(PYTHON_BIN) . ' ' . escapeshellarg(ANALYZE_SCRIPT)
        . ' ' . escapeshellarg($absolutePath) . ' ' . escapeshellarg($ext);
    $output = @shell_exec($cmd . ' 2>&1');
    if (!$output) return $fallback;

    $data = json_decode(trim($output), true);
    if (!is_array($data)) return $fallback;

    return [
        'excerpt' => $data['excerpt'] ?? '',
        'keywords' => $data['keywords'] ?? '',
    ];
}
