<?php
/** Section tabs shown at the top of every admin page. */
$adminLinks = [
    'dashboard.php' => ['Dashboard', 'fa-gauge-high'],
    'users.php'     => ['Users', 'fa-users'],
    'boardings.php' => ['Boardings', 'fa-house'],
    'reviews.php'   => ['Reviews', 'fa-star'],
    'activity.php'  => ['Activity log', 'fa-clock-rotate-left'],
];
$adminCurrent = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="admin-tabs" aria-label="Admin sections">
    <?php foreach ($adminLinks as $file => [$label, $icon]): ?>
        <a href="<?= url('admin/' . $file) ?>" class="<?= $adminCurrent === $file ? 'active' : '' ?>">
            <i class="fa-solid <?= e($icon) ?>"></i> <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</nav>
