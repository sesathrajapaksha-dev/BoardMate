<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/owner_functions.php';

$user    = require_role('owner');
$reviews = get_owner_reviews($user['user_id']);
$stats   = get_owner_stats($user['user_id']);

$pageTitle = 'Reviews on My Boardings';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>Reviews on My Boardings</h1>
            <p>
                <?= $stats['reviews'] ?> review<?= $stats['reviews'] === 1 ? '' : 's' ?>
                <?php if ($stats['reviews']): ?>&middot; average <?= e(number_format($stats['avg'], 1)) ?> / 5<?php endif; ?>
            </p>
        </div>
        <a href="<?= url('owner/dashboard.php') ?>" class="btn btn-light"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
    </div>
</section>

<section class="page-section">
    <div class="container narrow">
        <?php if ($reviews): ?>
            <div class="panel">
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
                                        <?= $r['updated_at'] > $r['created_at'] ? ' (edited)' : '' ?>
                                    </div>
                                </div>
                                <div class="review-stars"><?= render_stars((float)$r['rating']) ?></div>
                            </div>
                            <p><?= nl2br(e($r['review_text'])) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-comments"></i>
                <h3>No reviews yet</h3>
                <p>When students review your boardings, they will appear here.</p>
                <a href="<?= url('owner/my_boardings.php') ?>" class="btn btn-primary">My advertisements</a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>