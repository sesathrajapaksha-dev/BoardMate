<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/review_functions.php';

$user    = require_role('student');
$reviews = get_student_reviews($user['user_id']);

$pageTitle    = 'My Reviews';
$extraScripts = ['assets/js/owner.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>My Reviews</h1>
            <p><?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?> submitted</p>
        </div>
        <a href="<?= url('student/browse.php') ?>" class="btn btn-light"><i class="fa-solid fa-magnifying-glass"></i> Browse boardings</a>
    </div>
</section>
<section class="page-section">
    <div class="container narrow">
        <?php if ($reviews): ?>
            <div class="rv-list rv-list-card">
                <?php foreach ($reviews as $r) { $from = 'my_reviews'; $short = false; include __DIR__ . '/../includes/review_item.php'; } ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-star"></i>
                <h3>No reviews yet</h3>
                <p>Share your experience to help other students choose well.</p>
                <a href="<?= url('student/browse.php') ?>" class="btn btn-primary">Find a boarding to review</a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>