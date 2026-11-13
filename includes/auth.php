<?php
/**
 * BoardMate - authentication & authorization helpers
 */
require_once __DIR__ . '/functions.php';

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['user_id']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

/** Store the user in the session after a successful login. */
function login_user(array $u): void
{
    session_regenerate_id(true); // prevents session fixation
    $_SESSION['user'] = [
        'user_id'   => (int)$u['user_id'],
        'full_name' => $u['full_name'],
        'username'  => $u['username'],
        'email'     => $u['email'],
        'role'      => $u['role'],
    ];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/** Completely destroy the session, then open a clean one (so flash messages still work). */
function end_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

/**
 * Page guard: user must be logged in.
 * Re-checks the database on every request, so a user deleted by the admin
 * is logged out immediately and role changes take effect at once.
 */
function require_login(): array
{
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }

    $stmt = getDB()->prepare('SELECT user_id, full_name, username, email, role FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user']['user_id']]);
    $u = $stmt->fetch();

    if (!$u) {
        end_session();
        flash('error', 'Your account no longer exists.');
        redirect('login.php');
    }

    $_SESSION['user'] = [
        'user_id'   => (int)$u['user_id'],
        'full_name' => $u['full_name'],
        'username'  => $u['username'],
        'email'     => $u['email'],
        'role'      => $u['role'],
    ];
    return $_SESSION['user'];
}

/** Page guard: user must have one of the given roles, e.g. require_role('owner') or require_role(['student','admin']). */
function require_role($roles): array
{
    $roles = (array)$roles;
    $user  = require_login();

    if (!in_array($user['role'], $roles, true)) {
        flash('error', 'You do not have permission to access that page.');
        redirect(dashboard_path($user['role']));
    }
    return $user;
}

/** For login/register pages: logged-in users are sent to their dashboard. */
function require_guest(): void
{
    if (is_logged_in()) {
        redirect(dashboard_path($_SESSION['user']['role']));
    }
}
