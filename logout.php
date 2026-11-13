<?php
require_once __DIR__ . '/includes/auth.php';

// Logout only works via POST with a valid CSRF token (the navbar button does this)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect('index.php');
}

if (is_logged_in()) {
    log_activity((int)$_SESSION['user']['user_id'], 'LOGOUT', 'User logged out');
}

end_session();
flash('success', 'You have been logged out.');
redirect('login.php');
