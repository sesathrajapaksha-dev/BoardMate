<?php
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
$pdo  = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('error', 'Security check failed. Please try again.');
        redirect('student/profile.php');
    }

    $form   = $_POST['form'] ?? '';
    $errors = [];

    if ($form === 'profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = preg_replace('/[\s\-]/', '', trim($_POST['phone'] ?? ''));

        if (!preg_match('/^[\p{L}][\p{L}\s.\'\-]{1,99}$/u', $full_name)) {
            $errors['full_name'] = 'Enter your full name (letters only, 2-100 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (!preg_match('/^(\+94|0)\d{9}$/', $phone)) {
            $errors['phone'] = 'Enter a valid Sri Lankan phone number, e.g. 0771234567.';
        }
        if (!isset($errors['email'])) {
            $chk = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
            $chk->execute([$email, $user['user_id']]);
            if ($chk->fetchColumn()) {
                $errors['email'] = 'Another account already uses this email.';
            }
        }

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old'] = compact('full_name', 'email', 'phone');
            flash('error', 'Please fix the highlighted fields.');
            redirect('student/profile.php');
        }

        try {
            $up = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?');
            $up->execute([$full_name, $email, $phone, $user['user_id']]);
            log_activity($user['user_id'], 'PROFILE_UPDATE', 'Updated account details');
            flash('success', 'Your account details have been updated.');
        } catch (PDOException $ex) {
            flash('error', 'Could not update your details. Please try again.');
        }
        redirect('student/profile.php');
    }

    if ($form === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $row = $pdo->prepare('SELECT password FROM users WHERE user_id = ?');
        $row->execute([$user['user_id']]);
        $hash = (string)$row->fetchColumn();

        if (!password_verify($current, $hash)) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }
        if (strlen($new) < 8 || strlen($new) > 72) {
            $errors['new_password'] = 'Password must be 8-72 characters long.';
        } elseif (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $errors['new_password'] = 'Password must contain at least one letter and one number.';
        }
        if ($new !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            flash('error', 'Password not changed. Please fix the highlighted fields.');
            redirect('student/profile.php');
        }

        $up = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        $up->execute([password_hash($new, PASSWORD_DEFAULT), $user['user_id']]);
        session_regenerate_id(true);
        log_activity($user['user_id'], 'PASSWORD_CHANGE', 'Changed password');
        flash('success', 'Your password has been changed.');
        redirect('student/profile.php');
    }

    redirect('student/profile.php');
}

// ---------- Show the page ----------
$errors = pull_errors();
$old    = pull_old();

$stmt = $pdo->prepare('SELECT full_name, username, email, phone, created_at FROM users WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$row = $stmt->fetch();

$vName  = $old['full_name'] ?? $row['full_name'];
$vEmail = $old['email'] ?? $row['email'];
$vPhone = $old['phone'] ?? $row['phone'];

$pageTitle = 'My Account';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <h1>My Account</h1>
        <p>Update your details and password.</p>
    </div>
</section>
<section class="page-section">
    <div class="container narrow">

        <div class="form-card profile-card">
            <div class="form-section">
                <h3><i class="fa-solid fa-user"></i> Account details</h3>
                <form action="<?= url('student/profile.php') ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="profile">

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" value="<?= e($row['username']) ?>" disabled>
                        <small class="hint">Usernames cannot be changed.</small>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full name</label>
                        <input type="text" id="full_name" name="full_name" required maxlength="100" value="<?= e($vName) ?>"
                               class="<?= isset($errors['full_name']) ? 'has-error' : '' ?>">
                        <?= field_error($errors, 'full_name') ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" required maxlength="120" value="<?= e($vEmail) ?>"
                                   class="<?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <?= field_error($errors, 'email') ?>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone number</label>
                            <input type="tel" id="phone" name="phone" required maxlength="20" value="<?= e($vPhone) ?>"
                                   class="<?= isset($errors['phone']) ? 'has-error' : '' ?>">
                            <?= field_error($errors, 'phone') ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save details</button>
                </form>
            </div>

            <div class="form-section">
                <h3><i class="fa-solid fa-lock"></i> Change password</h3>
                <form action="<?= url('student/profile.php') ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="form" value="password">

                    <div class="form-group">
                        <label for="current_password">Current password</label>
                        <div class="password-wrap">
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                                   class="<?= isset($errors['current_password']) ? 'has-error' : '' ?>">
                            <button type="button" class="pw-toggle" data-toggle-password="current_password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                        <?= field_error($errors, 'current_password') ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New password</label>
                            <div class="password-wrap">
                                <input type="password" id="new_password" name="new_password" required minlength="8" maxlength="72" autocomplete="new-password"
                                       class="<?= isset($errors['new_password']) ? 'has-error' : '' ?>">
                                <button type="button" class="pw-toggle" data-toggle-password="new_password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <?= field_error($errors, 'new_password') ?>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm new password</label>
                            <div class="password-wrap">
                                <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"
                                       class="<?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                                <button type="button" class="pw-toggle" data-toggle-password="confirm_password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                            </div>
                            <?= field_error($errors, 'confirm_password') ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Change password</button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>