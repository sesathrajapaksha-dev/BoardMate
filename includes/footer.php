</main>

<footer class="footer">
    <div class="container footer-grid">
        <div class="footer-col">
            <a href="<?= url('index.php') ?>" class="logo logo-light">
                <span class="logo-icon"><i class="fa-solid fa-house-chimney"></i></span>
                <span class="logo-text">Board<span>Mate</span></span>
            </a>
            <p class="footer-text">Find. Compare. Stay. The easiest way for University of Moratuwa students to discover trusted boarding places near campus.</p>
        </div>

        <div class="footer-col">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= url('index.php') ?>">Home</a></li>
                <li><a href="<?= url('student/browse.php') ?>">Find a Boarding</a></li>
                <li><a href="<?= url('student/compare.php') ?>">Compare</a></li>
                <li><a href="<?= url('register.php?type=owner') ?>">List Your Boarding</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Popular Areas</h4>
            <ul>
                <li>Katubedda</li>
                <li>Moratuwa</li>
                <li>Rawathawatta</li>
                <li>Egodauyana</li>
                <li>Angulana</li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Contact</h4>
            <ul class="footer-contact">
                <li><i class="fa-solid fa-location-dot"></i> Katubedda, Moratuwa, Sri Lanka</li>
                <li><i class="fa-solid fa-envelope"></i> hello@boardmate.lk</li>
                <li><i class="fa-solid fa-phone"></i> +94 11 000 0000</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">&copy; <?= date('Y') ?> BoardMate. All rights reserved.</div>
    </div>
</footer>

<script>window.BM = { base: "<?= e(BASE_URL) ?>" };</script>
<script src="<?= url('assets/js/script.js') ?>"></script>
<script src="<?= url('assets/js/compare.js') ?>"></script>
<?php foreach (($extraScripts ?? []) as $src): ?>
<script src="<?= url($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
