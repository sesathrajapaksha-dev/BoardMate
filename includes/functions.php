<?php
/**
 * BoardMate - shared helper functions
 * Starts the session, and provides escaping, CSRF, flash messages and logging.
 */
require_once __DIR__ . '/../config/database.php';

// ---------- Basic security headers ----------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ---------- Secure session start ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Escape output (prevents XSS). Use this for EVERY piece of user data printed in HTML. */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Build a full URL path inside the project. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Redirect to a project path and stop the script. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Where each role lands after login. */
function dashboard_path(string $role): string
{
    switch ($role) {
        case 'admin': return 'admin/dashboard.php';
        case 'owner': return 'owner/dashboard.php';
        default:      return 'student/dashboard.php';
    }
}

// ---------- CSRF protection ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to place inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** For action scripts: only POST allowed, and the CSRF token must be valid. */
function require_post_csrf(string $backTo): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect($backTo);
    }
    if (!verify_csrf()) {
        flash('error', 'Security check failed. Please try again.');
        redirect($backTo);
    }
}

// ---------- Flash messages (one-time notices) ----------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function render_flash(): void
{
    if (empty($_SESSION['flash'])) {
        return;
    }
    $icons = [
        'success' => 'fa-circle-check',
        'error'   => 'fa-circle-exclamation',
        'warning' => 'fa-triangle-exclamation',
        'info'    => 'fa-circle-info',
    ];
    echo '<div class="container flash-wrap">';
    foreach ($_SESSION['flash'] as $f) {
        $type = isset($icons[$f['type']]) ? $f['type'] : 'info';
        echo '<div class="alert alert-' . $type . '" role="alert">'
           . '<i class="fa-solid ' . $icons[$type] . '"></i>'
           . '<span>' . e($f['message']) . '</span>'
           . '<button type="button" class="alert-close" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>'
           . '</div>';
    }
    echo '</div>';
    unset($_SESSION['flash']);
}

// ---------- Form helpers (errors and old input survive one redirect) ----------
function pull_errors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);
    return $errors;
}

function pull_old(): array
{
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    return $old;
}

function field_error(array $errors, string $field): string
{
    return isset($errors[$field])
        ? '<span class="field-error"><i class="fa-solid fa-circle-exclamation"></i> ' . e($errors[$field]) . '</span>'
        : '';
}

// ---------- Activity log (shown to admin later) ----------
function log_activity(?int $userId, string $action, string $details = ''): void
{
    try {
        $stmt = getDB()->prepare(
            'INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $action, mb_substr($details, 0, 255), $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        // Logging must never break the site
    }
}
