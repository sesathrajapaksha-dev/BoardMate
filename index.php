<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container">
        <h1>Find Your Perfect Boarding Near Campus.</h1>
        <p>BoardMate helps University of Moratuwa students find, compare and review boarding places in Katubedda, Moratuwa and nearby areas.</p>
        <div class="hero-actions">
            <a href="<?= url('student/browse.php') ?>" class="btn btn-accent btn-lg"><i class="fa-solid fa-magnifying-glass"></i> Find a Boarding</a>
            <a href="<?= url('register.php?type=owner') ?>" class="btn btn-light btn-lg"><i class="fa-solid fa-plus"></i> List Your Boarding</a>
        </div>
    </div>
</section>
<section class="page-section">
    <div class="container text-center">
        <p>The full home page arrives in Phase 3.</p>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
