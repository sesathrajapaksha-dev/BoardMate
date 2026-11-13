<?php
require_once __DIR__ . '/includes/auth.php';
require_guest();

$errors = pull_errors();
$old    = pull_old();

$role = $old['role'] ?? ($_GET['type'] ?? 'student');
if (!in_array($role, ['student', 'owner'], true)) {
    $role = 'student';
}

$pageTitle = 'Create Account';
$bodyClass = 'page-auth';
include __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="container">
        <div class="auth-layout">

            <aside class="auth-side">
                <h2>Join BoardMate</h2>
                <p>Whether you are looking for a room or listing one, BoardMate connects you in minutes.</p>
                <ul class="auth-points">
                    <li><i class="fa-solid fa-magnifying-glass"></i> Search and filter boardings near campus</li>
                    <li><i class="fa-solid fa-scale-balanced"></i> Compare prices and facilities side by side</li>
                    <li><i class="fa-solid fa-star"></i> Read honest reviews from real students</li>
                    <li><i class="fa-solid fa-bullhorn"></i> Owners can advertise rooms for free</li>
                </ul>
            </aside>

            <div class="auth-card">
                <h1>Create your account</h1>
                <p class="auth-sub">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>

                <form action="<?= url('actions/register_action.php') ?>" method="post" id="registerForm" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label>I am a</label>
                        <div class="role-select">
                            <label class="role-option">
                                <input type="radio" name="role" value="student" <?= $role === 'student' ? 'checked' : '' ?>>
                                <span class="role-card"><i class="fa-solid fa-graduation-cap"></i><strong>Student</strong><small>Find and review boardings</small></span>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="role" value="owner" <?= $role === 'owner' ? 'checked' : '' ?>>
                                <span class="role-card"><i class="fa-solid fa-building-user"></i><strong>Boarding Owner</strong><small>Advertise your rooms</small></span>
                            </label>
                        </div>
                        <?= field_error($errors, 'role') ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full name</label>
                            <input type="text" id="full_name" name="full_name" required maxlength="100"
                                   value="<?= e($old['full_name'] ?? '') ?>" placeholder="e.g. Kasun Perera"
                                   class="<?= isset($errors['full_name']) ? 'has-error' : '' ?>">
                            <?= field_error($errors, 'full_name') ?>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required minlength="4" maxlength="30"
                                   pattern="[A-Za-z0-9_]{4,30}" autocomplete="username"
                                   value="<?= e($old['username'] ?? '') ?>" placeholder="letters, numbers, _"
                                   class="<?= isset($errors['username']) ? 'has-error' : '' ?>">
                            <?= field_error($errors, 'username') ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email" required maxlength="120" autocomplete="email"
                                   value="<?= e($old['email'] ?? '') ?>" placeholder="you@example.com"
                                   class="<?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <?= field_error($errors, 'email') ?>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone number</label>
                            <input type="tel" id="phone" name="phone" required maxlength="20"
                                   pattern="[+0-9 \-]{10,15}" autocomplete="tel"
                                   value="<?= e($old['phone'] ?? '') ?>" placeholder="07XXXXXXXX"
                                   class="<?= isset($errors['phone']) ? 'has-error' : '' ?>">
                            <?= field_error($errors, 'phone') ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-wrap">
                                <input type="password" id="password" name="password" required minlength="8" maxlength="72"
                                       autocomplete="new-password" placeholder="Min 8 characters"
                                       class="<?= isset($errors['password']) ? 'has-error' : '' ?>">
                                <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Show password">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div class="strength"><span id="pwBar"></span></div>
                            <small class="hint" id="pwText">Use letters and numbers.</small>
                            <?= field_error($errors, 'password') ?>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm password</label>
                            <div class="password-wrap">
                                <input type="password" id="confirm_password" name="confirm_password" required
                                       autocomplete="new-password" placeholder="Re-enter password"
                                       class="<?= isset($errors['confirm_password']) ? 'has-error' : '' ?>">
                                <button type="button" class="pw-toggle" data-toggle-password="confirm_password" aria-label="Show password">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <small class="hint" id="confirmMsg"></small>
                            <?= field_error($errors, 'confirm_password') ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fa-solid fa-user-plus"></i> Create Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
