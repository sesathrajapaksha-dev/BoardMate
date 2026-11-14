<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$admin   = require_role('admin');
$filters = parse_log_filters($_GET);
$total   = count_activity_logs($filters);
$pages   = max(1, (int)ceil($total / LOG_PER_PAGE));
$page    = min(admin_page(), $pages);
$logs    = get_activity_logs($filters, LOG_PER_PAGE, ($page - 1) * LOG_PER_PAGE);
$actions = get_log_actions();
$hasFilter = $filters['q'] !== '' || $filters['action'] !== '';

$pageTitle = 'Activity Log';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>Activity Log</h1>
            <p><?= $total ?> event<?= $total === 1 ? '' : 's' ?><?= $hasFilter ? ' match your filters' : ' recorded' ?></p>
        </div>
    </div>
</section>

<section class="page-section">
    <div class="container">
        <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

        <form method="get" class="admin-filter">
            <div class="af-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="<?= e($filters['q']) ?>" maxlength="100" placeholder="Search user, details or IP address...">
            </div>
            <select name="action" aria-label="Filter by action">
                <option value="">All actions</option>
                <?php foreach ($actions as $a): ?>
                    <option value="<?= e($a) ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
            <?php if ($hasFilter): ?>
                <a href="<?= url('admin/activity.php') ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="panel">
            <?php if ($logs): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP address</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="muted small nowrap"><?= e(date('M j, Y H:i:s', strtotime($l['created_at']))) ?></td>
                                <td>
                                    <?php if ($l['username'] !== null): ?>
                                        <strong><?= e($l['username']) ?></strong>
                                        <span class="role-badge role-<?= e($l['role']) ?>"><?= e(ucfirst($l['role'])) ?></span>
                                    <?php else: ?>
                                        <span class="muted">Guest / deleted user</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="pill <?= e(activity_pill($l['action'])) ?>"><?= e($l['action']) ?></span></td>
                                <td class="dt-text"><?= e($l['details'] ?? '') ?></td>
                                <td class="muted small"><?= e($l['ip_address'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php render_admin_pagination($page, $pages); ?>
            <?php else: ?>
                <div class="empty-state empty-inline">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <h3>No activity found</h3>
                    <p>Try a different search or action.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
