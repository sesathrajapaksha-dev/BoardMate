<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$admin     = require_role('admin');
$filters   = parse_text_filter($_GET);
$total     = count_admin_boardings($filters);
$pages     = max(1, (int)ceil($total / ADMIN_PER_PAGE));
$page      = min(admin_page(), $pages);
$boardings = get_admin_boardings($filters, ADMIN_PER_PAGE, ($page - 1) * ADMIN_PER_PAGE);

$pageTitle    = 'Manage Advertisements';
$extraScripts = ['assets/js/owner.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>Manage Advertisements</h1>
            <p><?= $total ?> advertisement<?= $total === 1 ? '' : 's' ?><?= $filters['q'] !== '' ? ' match your search' : ' on the platform' ?></p>
        </div>
    </div>
</section>

<section class="page-section">
    <div class="container">
        <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

        <form method="get" class="admin-filter">
            <div class="af-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="<?= e($filters['q']) ?>" maxlength="100" placeholder="Search name, area, address or owner...">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Search</button>
            <?php if ($filters['q'] !== ''): ?>
                <a href="<?= url('admin/boardings.php') ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="panel">
            <?php if ($boardings): ?>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Boarding</th>
                                <th>Owner</th>
                                <th>Price</th>
                                <th>Beds</th>
                                <th>Rating</th>
                                <th>Listed</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($boardings as $b):
                            $bid  = (int)$b['boarding_id'];
                            $cnt  = (int)$b['review_count'];
                            $beds = (int)$b['available_beds'];
                        ?>
                            <tr>
                                <td>
                                    <div class="dt-boarding">
                                        <img src="<?= e(cover_url($b['cover_image'])) ?>" alt="">
                                        <div>
                                            <strong><?= e($b['boarding_name']) ?></strong>
                                            <span class="muted small"><i class="fa-solid fa-location-dot"></i> <?= e($b['location']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= e($b['owner_username']) ?></td>
                                <td><?= e(format_price($b['price_per_person'])) ?></td>
                                <td>
                                    <?php if ($beds > 0): ?><span class="pill pill-green"><?= $beds ?></span>
                                    <?php else: ?><span class="pill pill-red">Full</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cnt > 0): ?>
                                        <strong><?= e(number_format((float)$b['avg_rating'], 1)) ?></strong> <span class="muted small">(<?= $cnt ?>)</span>
                                    <?php else: ?><span class="muted small">No reviews</span><?php endif; ?>
                                </td>
                                <td class="muted small"><?= e(date('M j, Y', strtotime($b['created_at']))) ?></td>
                                <td class="col-actions">
                                    <div class="row-actions">
                                        <a href="<?= url('student/boarding_details.php?id=' . $bid) ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                                        <form action="<?= url('owner/delete_boarding.php') ?>" method="post" class="inline-form"
                                              data-confirm="Remove the advertisement &quot;<?= e($b['boarding_name']) ?>&quot; by <?= e($b['owner_username']) ?>? Its photos and reviews will be deleted. This cannot be undone.">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="boarding_id" value="<?= $bid ?>">
                                            <button type="submit" class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i> Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php render_admin_pagination($page, $pages); ?>
            <?php else: ?>
                <div class="empty-state empty-inline">
                    <i class="fa-solid fa-house-circle-xmark"></i>
                    <h3>No advertisements found</h3>
                    <p>Try a different search.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
