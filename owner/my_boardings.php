<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/owner_functions.php';

$user = require_role('owner');
$rows = get_owner_boardings($user['user_id']);

$pageTitle    = 'My Boarding Advertisements';
$extraScripts = ['assets/js/owner.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>My Boarding Advertisements</h1>
            <p><?= count($rows) ?> advertisement<?= count($rows) === 1 ? '' : 's' ?></p>
        </div>
        <div class="row-actions">
            <a href="<?= url('owner/dashboard.php') ?>" class="btn btn-light"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
            <a href="<?= url('owner/add_boarding.php') ?>" class="btn btn-accent"><i class="fa-solid fa-plus"></i> Add New Boarding</a>
        </div>
    </div>
</section>

<section class="page-section">
    <div class="container">
        <?php if ($rows): ?>
            <div class="panel">
                <?php include __DIR__ . '/../includes/owner_boarding_table.php'; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-house-circle-plus"></i>
                <h3>You have not published any advertisement yet</h3>
                <p>Add your first boarding so students can find it.</p>
                <a href="<?= url('owner/add_boarding.php') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Boarding</a>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
