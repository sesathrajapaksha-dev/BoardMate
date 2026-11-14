<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$user   = require_role('admin');
$stats  = admin_stats();
$users  = get_admin_users(['q' => '', 'role' => ''], 5, 0);
$logs   = get_activity_logs(['q' => '', 'action' => ''], 8, 0);

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-section">
    <div class="container">

        <div class="dash-welcome dash-welcome-flex">
            <div>
                <h1>Administrator Panel</h1>
                <p>Logged in as <strong><?= e($user['username']) ?></strong>. Monitor and manage the whole BoardMate platform.</p>
            </div>
        </div>

        <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

        <div class="stat-grid">
            <div class="stat-card stat-icon">
                <span class="si si-navy"><i class="fa-solid fa-users"></i></span>
                <div><div class="stat-label">Registered users</div><div class="stat-value"><?= $stats['users'] ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si si-blue"><i class="fa-solid fa-user-graduate"></i></span>
                <div><div class="stat-label">Student accounts</div><div class="stat-value"><?= $stats['students'] ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si si-green"><i class="fa-solid fa-key"></i></span>
                <div><div class="stat-label">Boarding owners</div><div class="stat-value"><?= $stats['owners'] ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si si-amber"><i class="fa-solid fa-house"></i></span>
                <div><div class="stat-label">Advertisements</div><div class="stat-value"><?= $stats['boardings'] ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si si-slate"><i class="fa-solid fa-star"></i></span>
                <div><div class="stat-label">Reviews</div><div class="stat-value"><?= $stats['reviews'] ?></div></div>
            </div>
            <div class="stat-card stat-icon">
                <span class="si <?= $stats['failed_24h'] > 0 ? 'si-red' : 'si-green' ?>"><i class="fa-solid fa-shield-halved"></i></span>
                <div><div class="stat-label">Failed logins (24h)</div><div class="stat-value"><?= $stats['failed_24h'] ?></div></div>
            </div>
        </div>

        <div class="manage-grid">
            <a class="manage-card" href="<?= url('admin/users.php') ?>">
                <i class="fa-solid fa-users-gear"></i>
                <div><strong>Manage users</strong><span>View students and owners, remove accounts</span></div>
            </a>
            <a class="manage-card" href="<?= url('admin/boardings.php') ?>">
                <i class="fa-solid fa-house-circle-xmark"></i>
                <div><strong>Manage advertisements</strong><span>Remove inappropriate or unwanted listings</span></div>
            </a>
            <a class="manage-card" href="<?= url('admin/reviews.php') ?>">
                <i class="fa-solid fa-comment-slash"></i>
                <div><strong>Manage reviews</strong><span>Delete inappropriate reviews</span></div>
            </a>
            <a class="manage-card" href="<?= url('admin/activity.php') ?>">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <div><strong>Activity log</strong><span><?= $stats['activity_24h'] ?> event<?= $stats['activity_24h'] === 1 ? '' : 's' ?> in the last 24 hours</span></div>
            </a>
        </div>

        <div class="dash-grid dash-grid-reviews">
            <div class="panel">
                <div class="panel-head">
                    <h3>Recent activity</h3>
                    <a href="<?= url('admin/activity.php') ?>">View all</a>
                </div>
                <?php if ($logs): ?>
                    <ul class="log-list">
                        <?php foreach ($logs as $l): ?>
                            <li>
                                <span class="pill <?= e(activity_pill($l['action'])) ?>"><?= e($l['action']) ?></span>
                                <span class="log-text">
                                    <strong><?= e($l['username'] ?? 'Guest') ?></strong>
                                    <?php if ($l['details'] !== null && $l['details'] !== ''): ?>&middot; <?= e($l['details']) ?><?php endif; ?>
                                </span>
                                <span class="muted small log-time"><?= e(date('M j, H:i', strtotime($l['created_at']))) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">No activity recorded yet.</p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h3>Newest members</h3>
                    <a href="<?= url('admin/users.php') ?>">View all</a>
                </div>
                <ul class="member-list">
                    <?php foreach ($users as $u): ?>
                        <li>
                            <span class="avatar"><?= e(strtoupper(mb_substr($u['username'], 0, 1))) ?></span>
                            <div>
                                <strong><?= e($u['username']) ?></strong>
                                <span class="muted small"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></span>
                            </div>
                            <span class="role-badge role-<?= e($u['role']) ?>"><?= e(ucfirst($u['role'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
