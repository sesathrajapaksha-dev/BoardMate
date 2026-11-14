<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$admin   = require_role('admin');
$filters = parse_user_filters($_GET);
$total   = count_admin_users($filters);
$pages   = max(1, (int)ceil($total / ADMIN_PER_PAGE));
$page    = min(admin_page(), $pages);
$users   = get_admin_users($filters, ADMIN_PER_PAGE, ($page - 1) * ADMIN_PER_PAGE);
$qs      = $_SERVER['QUERY_STRING'] ?? '';

$pageTitle    = 'Manage Users';
$extraScripts = ['assets/js/owner.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>Manage Users</h1>
            <p><?= $total ?> account<?= $total === 1 ? '' : 's' ?><?= ($filters['q'] !== '' || $filters['role'] !== '') ? ' match your filters' : ' registered' ?></p>
        </div>
    </div>
</section>

<section class="page-section">
    <div class="container">
        <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

        <form method="get" class="admin-filter">
            <div class="af-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="<?= e($filters['q']) ?>" maxlength="100" placeholder="Search name, username, email or phone...">
            </div>
            <select name="role" aria-label="Filter by role">
                <option value="">All roles</option>
                <?php foreach (USER_ROLES as $r): ?>
                    <option value="<?= e($r) ?>" <?= $filters['role'] === $r ? 'selected' : '' ?>><?= e(ucfirst($r)) ?>s</option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
            <?php if ($filters['q'] !== '' || $filters['role'] !== ''): ?>
                <a href="<?= url('admin/users.php') ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="panel">
            <?php if ($users): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Contact</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Content</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u):
                            $uid    = (int)$u['user_id'];
                            $isSelf = $uid === (int)$admin['user_id'];
                            $pill   = ['admin' => 'pill-red', 'owner' => 'pill-green', 'student' => 'pill-blue'][$u['role']] ?? 'pill-gray';
                        ?>
                            <tr>
                                <td>
                                    <div class="dt-user">
                                        <span class="avatar"><?= e(strtoupper(mb_substr($u['username'], 0, 1))) ?></span>
                                        <div>
                                            <strong><?= e($u['username']) ?></strong><?= $isSelf ? ' <span class="muted small">(you)</span>' : '' ?>
                                            <span class="muted small"><?= e($u['full_name']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= e($u['email']) ?>
                                    <span class="muted small block"><?= e($u['phone']) ?></span>
                                </td>
                                <td><span class="pill <?= $pill ?>"><?= e(ucfirst($u['role'])) ?></span></td>
                                <td class="muted small"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                                <td class="small">
                                    <?php if ($u['role'] === 'owner'): ?>
                                        <?= (int)$u['boarding_count'] ?> ad<?= (int)$u['boarding_count'] === 1 ? '' : 's' ?>
                                    <?php elseif ($u['role'] === 'student'): ?>
                                        <?= (int)$u['review_count'] ?> review<?= (int)$u['review_count'] === 1 ? '' : 's' ?>
                                    <?php else: ?>
                                        <span class="muted">–</span>
                                    <?php endif; ?>
                                </td>
                                <td class="col-actions">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="muted small"><i class="fa-solid fa-lock"></i> Protected</span>
                                    <?php else: ?>
                                        <form action="<?= url('admin/delete_user.php') ?>" method="post" class="inline-form"
                                              data-confirm="Delete the account &quot;<?= e($u['username']) ?>&quot;?<?= $u['role'] === 'owner' ? ' All of their advertisements and photos will be removed too.' : ' All of their reviews will be removed too.' ?> This cannot be undone.">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= $uid ?>">
                                            <input type="hidden" name="qs" value="<?= e($qs) ?>">
                                            <button type="submit" class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-user-xmark"></i> Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php render_admin_pagination($page, $pages); ?>
            <?php else: ?>
                <div class="empty-state empty-inline">
                    <i class="fa-solid fa-user-slash"></i>
                    <h3>No users found</h3>
                    <p>Try a different search or role.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
