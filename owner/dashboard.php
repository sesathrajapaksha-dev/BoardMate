<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role('owner');

$pageTitle = 'Owner Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<section class="page-section">
    <div class="container">
        <div class="dash-welcome">
            <h1>Welcome, <?= e($user['full_name']) ?></h1>
            <p>You are logged in as a <strong>boarding owner</strong>. The full dashboard arrives in Phase 6.</p>
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
