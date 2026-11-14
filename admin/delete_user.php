<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$admin = require_role('admin');
require_post_csrf('admin/users.php');

[$ok, $message] = delete_user_account((int)($_POST['user_id'] ?? 0), $admin['user_id']);

flash($ok ? 'success' : 'error', $message);
redirect(admin_back('admin/users.php'));
