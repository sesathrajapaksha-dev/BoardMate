<?php
require_once __DIR__ . '/includes/auth.php';
require_guest();

$old = pull_old();

$pageTitle = 'Login';
$bodyClass = 'page-auth';
include __DIR__ . '/includes/header.php';
?>
<section class="auth-section">
    <div class="container">
        <div class="auth-layout">

            <aside class="auth-side">
                <h2>Welcome back</h2>
                <p>Log in to continue finding, comparing and reviewing the best boarding places near campus.</p>
                <ul class="auth-points">
                    <li><i class="fa-solid fa-heart"></i> Pick up where you left off</li>
                    <li><i class="fa-solid fa-comments"></i> Manage your reviews</li>
                    <li><i class="fa-solid fa-house"></i> Owners: manage your advertisements</li>
                </ul>
            </aside>

            <div class="auth-card">
                <h1>Log in</h1>
                <p class="auth-sub">New to BoardMate? <a href="<?= url('register.php') ?>">Create an account</a></p>

                <form action="<?= url('actions/login_action.php') ?>" method="post" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autocomplete="username"
                               value="<?= e($old['username'] ?? '') ?>" placeholder="Your username" autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password" required
                                   autocomplete="current-password" placeholder="Your password">
                            <button type="button" class="pw-toggle" data-toggle-password="password" aria-label="Show password">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fa-solid fa-right-to-bracket"></i> Log in
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
