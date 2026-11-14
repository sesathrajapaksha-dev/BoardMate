<?php
require_once __DIR__ . '/../includes/auth.php';

require_guest();
require_post_csrf('login.php');

const MAX_FAILS    = 5;    // failed attempts allowed
const LOCK_SECONDS = 300;  // lockout time (5 minutes)

// ---------- Simple brute-force protection ----------
$lockUntil = $_SESSION['login_lock_until'] ?? 0;
if ($lockUntil > time()) {
    $mins = (int)ceil(($lockUntil - time()) / 60);
    flash('error', "Too many failed attempts. Please try again in {$mins} minute(s).");
    redirect('login.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['old'] = ['username' => $username];
    flash('error', 'Please enter your username and password.');
    redirect('login.php');
}

// ---------- Server-side lockout ----------
// The session counter above is bypassed by simply discarding the cookie, so failures are also
// counted in the activity log: too many recent failures for this username from this IP = locked.
$failDetails = mb_substr("Failed login for: {$username}", 0, 255);
$recent = getDB()->prepare(
    "SELECT COUNT(*) FROM activity_logs
     WHERE action = 'LOGIN_FAILED' AND details = ? AND ip_address <=> ?
       AND created_at > (NOW() - INTERVAL " . (int)LOCK_SECONDS . " SECOND)"
);
$recent->execute([$failDetails, $_SERVER['REMOTE_ADDR'] ?? null]);
if ((int)$recent->fetchColumn() >= MAX_FAILS) {
    flash('error', 'Too many failed attempts. Please try again in a few minutes.');
    redirect('login.php');
}

// ---------- Look up the user (prepared statement) ----------
$stmt = getDB()->prepare(
    'SELECT user_id, full_name, username, email, password, role FROM users WHERE username = ? LIMIT 1'
);
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    $valid = password_verify($password, $user['password']);
} else {
    password_hash($password, PASSWORD_DEFAULT); // keeps timing similar so usernames can't be guessed
    $valid = false;
}

// ---------- Failure ----------
if (!$valid) {
    $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
    if ($_SESSION['login_fails'] >= MAX_FAILS) {
        $_SESSION['login_lock_until'] = time() + LOCK_SECONDS;
        $_SESSION['login_fails'] = 0;
    }
    log_activity($user ? (int)$user['user_id'] : null, 'LOGIN_FAILED', "Failed login for: {$username}");
    $_SESSION['old'] = ['username' => $username];
    flash('error', 'Invalid username or password.'); // same message either way
    redirect('login.php');
}

// ---------- Success ----------
unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);

if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
    $up = getDB()->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    $up->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
}

login_user($user);
log_activity((int)$user['user_id'], 'LOGIN', 'User logged in');
flash('success', 'Welcome back, ' . $user['full_name'] . '!');
redirect(dashboard_path($user['role']));
