<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('student');

$pageTitle = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-section">
    <div class="container">
        <div class="dash-welcome">
            <h1>Welcome, <?= e($user['full_name']) ?></h1>
            <p>You are logged in as a <strong>student</strong>. The full dashboard arrives in Phase 5.</p>
        </div>
                <div class="panel" style="margin-bottom:20px">
            <h3>Quick actions</h3>
            <a href="<?= url('owner/add_boarding.php') ?>" class="btn btn-accent"><i class="fa-solid fa-plus"></i> Add New Boarding</a>
            <a href="<?= url('owner/my_boardings.php') ?>" class="btn btn-primary"><i class="fa-solid fa-list"></i> My Boardings</a>
        </div>
        <div class="panel">
            <h3>Your account</h3>
            <ul class="info-list">
                <li><span>Username</span><strong><?= e($user['username']) ?></strong></li>
                <li><span>Email</span><strong><?= e($user['email']) ?></strong></li>
                <li><span>Role</span><strong><?= e(ucfirst($user['role'])) ?></strong></li>
            </ul>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
