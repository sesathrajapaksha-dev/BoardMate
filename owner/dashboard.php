<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/owner_functions.php';

$user      = require_role('owner');
$stats     = get_owner_stats($user['user_id']);
$boardings = get_owner_boardings($user['user_id']);
$shown     = array_slice($boardings, 0, 5);
$reviews   = get_owner_reviews($user['user_id'], 4);
$breakdown = get_owner_rating_breakdown($user['user_id']);

$pageTitle    = 'Owner Dashboard';
$extraScripts = ['assets/js/owner.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-section">
    <div class="container">

        <div class="dash-welcome dash-welcome-flex">
            <div>
                <h1>Welcome, <?= e($user['full_name']) ?></h1>
                <p>Manage your boarding advertisements and see what students say about them.</p>
            </div>
            <a href="<?= url('owner/add_boarding.php') ?>" class="btn btn-accent btn-lg"><i class="fa-solid fa-plus"></i> Add New Boarding</a>
        </div>

        <div class="stat-grid">
            <div class="stat-card stat-icon">
                <span class="si si-blue"><i class="fa-solid fa-house"></i></span>
                <div><div class="stat-label">Advertisements</div><div class="stat-value"><?= $stats['boardings'] ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si si-green"><i class="fa-solid fa-bed"></i></span>
                <div><div class="stat-label">Total available beds</div><div class="stat-value"><?= $stats['beds'] ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si si-amber"><i class="fa-solid fa-star"></i></span>
                <div><div class="stat-label">Average rating</div>
                    <div class="stat-value"><?= $stats['reviews'] ? e(number_format($stats['avg'], 1)) . ' <small>/ 5</small>' : '–' ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si si-navy"><i class="fa-solid fa-comments"></i></span>
                <div><div class="stat-label">Total reviews</div><div class="stat-value"><?= $stats['reviews'] ?></div></div>
            </div>
        </div>

        <div class="panel dash-block">
            <div class="panel-head">
                <h3>My Boarding Advertisements</h3>
                <?php if (count($boardings) > count($shown)): ?>
                    <a href="<?= url('owner/my_boardings.php') ?>">View all (<?= count($boardings) ?>)</a>
                <?php endif; ?>
            </div>
            <?php if ($shown): ?>
                <?php $rows = $shown; include __DIR__ . '/../includes/owner_boarding_table.php'; ?>
            <?php else: ?>
                <div class="empty-state empty-inline">
                    <i class="fa-solid fa-house-circle-plus"></i>
                    <h3>No advertisements yet</h3>
                    <p>Publish your first boarding so students can find it.</p>
                    <a href="<?= url('owner/add_boarding.php') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Boarding</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="dash-grid dash-grid-reviews">
            <div class="panel">
                <div class="panel-head">
                    <h3>Recent reviews</h3>
                    <?php if ($stats['reviews'] > count($reviews)): ?>
                        <a href="<?= url('owner/reviews.php') ?>">View all (<?= $stats['reviews'] ?>)</a>
                    <?php endif; ?>
                </div>
                <?php if ($reviews): ?>
                    <ul class="review-list">
                        <?php foreach ($reviews as $r): ?>
                            <li class="review-item">
                                <div class="review-top">
                                    <span class="avatar"><?= e(strtoupper(mb_substr($r['username'], 0, 1))) ?></span>
                                    <div>
                                        <strong><?= e($r['username']) ?></strong>
                                        <div class="muted">
                                            on <a href="<?= url('student/boarding_details.php?id=' . (int)$r['boarding_id'] . '#reviews') ?>"><?= e($r['boarding_name']) ?></a>
                                            &middot; <?= e(date('M j, Y', strtotime($r['created_at']))) ?>
                                        </div>
                                    </div>
                                    <div class="review-stars"><?= render_stars((float)$r['rating']) ?></div>
                                </div>
                                <p><?= nl2br(e(mb_strimwidth(trim($r['review_text']), 0, 220, '…', 'UTF-8'))) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No reviews yet. They will appear here when students rate your boardings.</p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3>Rating breakdown</h3>
                <?php if ($stats['reviews']): ?>
                    <ul class="bars">
                        <?php foreach ($breakdown as $star => $n):
                            $pct = $stats['reviews'] ? round($n / $stats['reviews'] * 100) : 0; ?>
                            <li>
                                <span class="bar-label"><?= $star ?> <i class="fa-solid fa-star"></i></span>
                                <span class="bar-track"><span class="bar-fill" style="width: <?= (int)$pct ?>%"></span></span>
                                <span class="bar-count"><?= $n ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">The breakdown appears after your first review.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
