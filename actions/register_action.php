<?php
require_once __DIR__ . '/../includes/auth.php';

require_guest();
require_post_csrf('register.php');

// ---------- Collect input ----------
$full_name = trim($_POST['full_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = preg_replace('/[\s\-]/', '', trim($_POST['phone'] ?? ''));
$password  = $_POST['password'] ?? '';
$confirm   = $_POST['confirm_password'] ?? '';
$role      = $_POST['role'] ?? '';

$errors = [];

// ---------- Validate ----------
if (!preg_match('/^[\p{L}][\p{L}\s.\'\-]{1,99}$/u', $full_name)) {
    $errors['full_name'] = 'Enter your full name (letters only, 2-100 characters).';
}
if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $username)) {
    $errors['username'] = 'Username must be 4-30 characters: letters, numbers or underscore.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
    $errors['email'] = 'Enter a valid email address.';
}
if (!preg_match('/^(\+94|0)\d{9}$/', $phone)) {
    $errors['phone'] = 'Enter a valid Sri Lankan phone number, e.g. 0771234567.';
}
if (strlen($password) < 8 || strlen($password) > 72) {
    $errors['password'] = 'Password must be 8-72 characters long.';
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $errors['password'] = 'Password must contain at least one letter and one number.';
}
if ($password !== $confirm) {
    $errors['confirm_password'] = 'Passwords do not match.';
}
if (!in_array($role, ['student', 'owner'], true)) {
    $errors['role'] = 'Please choose Student or Boarding Owner.';
}

$pdo = getDB();

// ---------- Uniqueness checks ----------
if (!isset($errors['username'])) {
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->fetchColumn()) {
        $errors['username'] = 'This username is already taken.';
    }
}
if (!isset($errors['email'])) {
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
        $errors['email'] = 'An account with this email already exists.';
    }
}

// ---------- On errors: go back with messages and old input (never the password) ----------
if ($errors) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old'] = compact('full_name', 'username', 'email', 'phone', 'role');
    flash('error', 'Please fix the highlighted fields and try again.');
    redirect('register.php');
}

// ---------- Save ----------
try {
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, username, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$full_name, $username, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $role]);
    $newId = (int)$pdo->lastInsertId();
} catch (PDOException $ex) {
    // 23000 = duplicate key (two people registering the same username at the same moment)
    $_SESSION['old'] = compact('full_name', 'username', 'email', 'phone', 'role');
    flash('error', $ex->getCode() === '23000'
        ? 'That username or email was just taken. Please choose another.'
        : 'Something went wrong while creating your account. Please try again.');
    redirect('register.php');
}

log_activity($newId, 'REGISTER', "New {$role} account: {$username}");
flash('success', 'Account created successfully! You can now log in.');
redirect('login.php');
