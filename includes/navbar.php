<?php
$navUser     = current_user();
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$roleLabels  = ['student' => 'Student', 'owner' => 'Owner', 'admin' => 'Admin'];
?>
<header class="navbar">
    <div class="container nav-inner">
        <a href="<?= url('index.php') ?>" class="logo" aria-label="BoardMate home">
            <span class="logo-icon"><i class="fa-solid fa-house-chimney"></i></span>
            <span class="logo-text">Board<span>Mate</span></span>
        </a>

        <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>

        <nav class="nav-menu" id="navMenu">
            <ul class="nav-links">
                <li><a href="<?= url('index.php') ?>" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= url('student/browse.php') ?>" class="<?= $currentPage === 'browse.php' ? 'active' : '' ?>">Browse</a></li>
                <li><a href="<?= url('student/compare.php') ?>" class="<?= $currentPage === 'compare.php' ? 'active' : '' ?>">Compare</a></li>
            </ul>

            <div class="nav-actions">
                <?php if ($navUser): ?>
                    <a href="<?= url(dashboard_path($navUser['role'])) ?>" class="nav-user">
                        <i class="fa-regular fa-circle-user"></i>
                        <span><?= e($navUser['username']) ?></span>
                        <span class="role-badge role-<?= e($navUser['role']) ?>"><?= e($roleLabels[$navUser['role']] ?? '') ?></span>
                    </a>
                    <form action="<?= url('logout.php') ?>" method="post" class="inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline btn-sm">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?= url('login.php') ?>" class="btn btn-outline btn-sm">Login</a>
                    <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
