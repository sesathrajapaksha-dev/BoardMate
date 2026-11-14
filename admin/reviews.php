<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$admin   = require_role('admin');
$filters = parse_review_filters($_GET);
$total   = count_admin_reviews($filters);
$pages   = max(1, (int)ceil($total / ADMIN_PER_PAGE));
$page    = min(admin_page(), $pages);
$reviews = get_admin_reviews($filters, ADMIN_PER_PAGE, ($page - 1) * ADMIN_PER_PAGE);
$qs      = $_SERVER['QUERY_STRING'] ?? '';
$hasFilter = $filters['q'] !== '' || $filters['rating'] > 0;

$pageTitle    = 'Manage Reviews';
$extraScripts = ['assets/js/owner.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>Manage Reviews</h1>
            <p><?= $total ?> review<?= $total === 1 ? '' : 's' ?><?= $hasFilter ? ' match your filters' : ' on the platform' ?></p>
        </div>
    </div>
</section>

<section class="page-section">
    <div class="container">
        <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

        <form method="get" class="admin-filter">
            <div class="af-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="<?= e($filters['q']) ?>" maxlength="100" placeholder="Search review text, boarding or student...">
            </div>
            <select name="rating" aria-label="Filter by rating">
                <option value="0">Any rating</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= $filters['rating'] === $i ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
            <?php if ($hasFilter): ?>
                <a href="<?= url('admin/reviews.php') ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="panel">
            <?php if ($reviews): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Boarding</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reviews as $r): $rid = (int)$r['review_id']; ?>
                            <tr>
                                <td><strong><?= e($r['student_username']) ?></strong></td>
                                <td><a href="<?= url('student/boarding_details.php?id=' . (int)$r['boarding_id'] . '#reviews') ?>"><?= e($r['boarding_name']) ?></a></td>
                                <td class="nowrap"><?= render_stars((float)$r['rating']) ?></td>
                                <td class="dt-text"><?= e(mb_strimwidth(trim(preg_replace('/\s+/', ' ', $r['review_text'])), 0, 160, '…', 'UTF-8')) ?></td>
                                <td class="muted small nowrap"><?= e(date('M j, Y', strtotime($r['created_at']))) ?><?= $r['updated_at'] > $r['created_at'] ? '<br>(edited)' : '' ?></td>
                                <td class="col-actions">
                                    <form action="<?= url('admin/delete_review.php') ?>" method="post" class="inline-form"
                                          data-confirm="Delete this review by <?= e($r['student_username']) ?> on &quot;<?= e($r['boarding_name']) ?>&quot;? This cannot be undone.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="review_id" value="<?= $rid ?>">
                                        <input type="hidden" name="qs" value="<?= e($qs) ?>">
                                        <button type="submit" class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php render_admin_pagination($page, $pages); ?>
            <?php else: ?>
                <div class="empty-state empty-inline">
                    <i class="fa-regular fa-comments"></i>
                    <h3>No reviews found</h3>
                    <p>Try a different search or rating.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
