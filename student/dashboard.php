<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/review_functions.php';

$user  = require_role('student');
$stmt  = getDB()->prepare('SELECT phone, created_at FROM users WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$extra = $stmt->fetch();

$stats   = get_student_review_stats($user['user_id']);
$reviews = get_student_reviews($user['user_id'], 5);
$recent  = array_slice($reviews, 0, 3);

$pageTitle    = 'Student Dashboard';
$extraScripts = ['assets/js/owner.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-section">
    <div class="container">

        <div class="dash-welcome">
            <h1>Welcome back, <?= e($user['full_name']) ?> 👋</h1>
            <p>Find, compare and review boarding places near the University of Moratuwa.</p>
        </div>

        <div class="stat-grid">
            <div class="stat-card"><div class="stat-label">Reviews submitted</div><div class="stat-value"><?= $stats['count'] ?></div></div>
            <div class="stat-card"><div class="stat-label">Average rating you give</div>
                <div class="stat-value"><?= $stats['count'] ? e(number_format($stats['avg'], 1)) . ' <small>/ 5</small>' : '–' ?></div></div>
            <div class="stat-card"><div class="stat-label">Member since</div>
                <div class="stat-value stat-small"><?= e(date('M Y', strtotime($extra['created_at']))) ?></div></div>
        </div>

        <div class="dash-grid">
            <div class="panel">
                <h3>Account information</h3>
                <ul class="info-list">
                    <li><span>Full name</span><strong><?= e($user['full_name']) ?></strong></li>
                    <li><span>Username</span><strong><?= e($user['username']) ?></strong></li>
                    <li><span>Email</span><strong><?= e($user['email']) ?></strong></li>
                    <li><span>Phone</span><strong><?= e($extra['phone']) ?></strong></li>
                </ul>
                <a href="<?= url('student/profile.php') ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-user-pen"></i> Manage account</a>
            </div>

            <div class="panel">
                <h3>Quick links</h3>
                <div class="quick-links">
                    <a href="<?= url('student/browse.php') ?>"><i class="fa-solid fa-magnifying-glass"></i> Browse boardings</a>
                    <a href="<?= url('student/compare.php') ?>"><i class="fa-solid fa-scale-balanced"></i> Compare boardings</a>
                    <a href="<?= url('student/my_reviews.php') ?>"><i class="fa-solid fa-star"></i> My reviews</a>
                    <a href="<?= url('student/profile.php') ?>"><i class="fa-solid fa-user-gear"></i> Account settings</a>
                </div>
            </div>
        </div>

        <div class="panel dash-block">
            <h3>Recently reviewed boardings</h3>
            <?php if ($recent): ?>
                <div class="mini-grid">
                    <?php foreach ($recent as $r): ?>
                        <a class="mini-card" href="<?= url('student/boarding_details.php?id=' . (int)$r['boarding_id']) ?>">
                            <img src="<?= e(cover_url($r['cover_image'])) ?>" alt="">
                            <div>
                                <strong><?= e($r['boarding_name']) ?></strong>
                                <span class="muted small"><?= e($r['location']) ?></span>
                                <?= render_stars((float)$r['rating']) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">You have not reviewed any boarding yet.</p>
                <a href="<?= url('student/browse.php') ?>" class="btn btn-primary btn-sm">Find a boarding to review</a>
            <?php endif; ?>
        </div>

        <div class="panel dash-block">
            <div class="panel-head">
                <h3>My reviews</h3>
                <?php if ($stats['count'] > 5): ?><a href="<?= url('student/my_reviews.php') ?>">View all (<?= $stats['count'] ?>)</a><?php endif; ?>
            </div>
            <?php if ($reviews): ?>
                <div class="rv-list">
                    <?php foreach ($reviews as $r) { $from = 'dashboard'; $short = true; include __DIR__ . '/../includes/review_item.php'; } ?>
                </div>
            <?php else: ?>
                <p class="muted">Your reviews will appear here.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>