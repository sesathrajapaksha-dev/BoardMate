<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/boarding_functions.php';

$locations = get_locations();
$stats     = site_stats();
$counts    = location_counts();
$featured  = fetch_boardings(parse_filters([]), 'rating', 6, 0);

$areaIcons = [
    'Katubedda' => 'fa-graduation-cap', 'Moratuwa' => 'fa-city', 'Rawathawatta' => 'fa-tree',
    'Egodauyana' => 'fa-house-chimney', 'Angulana' => 'fa-train', 'Rathmalana' => 'fa-plane',
];

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>

<!-- ============ HERO ============ -->
<section class="home-hero">
    <div class="container">
        <span class="hero-pill"><i class="fa-solid fa-location-dot"></i> Built for University of Moratuwa students</span>
        <h1>Find Your Perfect Boarding Near Campus.</h1>
        <p>Stop searching through Facebook posts and WhatsApp groups. Browse, compare and review trusted boarding places around Katubedda, Moratuwa and nearby areas, all in one place.</p>

        <form action="<?= url('student/browse.php') ?>" method="get" class="search-box">
            <div class="search-field">
                <i class="fa-solid fa-location-dot"></i>
                <div>
                    <label for="hs_location">Location</label>
                    <select id="hs_location" name="location">
                        <option value="">Any location</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= e($loc) ?>"><?= e($loc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="search-field">
                <i class="fa-solid fa-wallet"></i>
                <div>
                    <label for="hs_budget">Max budget (LKR)</label>
                    <input type="number" id="hs_budget" name="max_price" min="0" step="500" placeholder="e.g. 10000">
                </div>
            </div>
            <button type="submit" class="btn btn-accent btn-lg"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        </form>
    </div>
</section>

<!-- ============ STATS ============ -->
<section class="stats-strip">
    <div class="container">
        <div class="stats-box">
            <div><strong><?= $stats['boardings'] ?></strong><span>Boardings listed</span></div>
            <div><strong><?= $stats['students'] ?></strong><span>Students joined</span></div>
            <div><strong><?= $stats['owners'] ?></strong><span>Boarding owners</span></div>
            <div><strong><?= $stats['reviews'] ?></strong><span>Student reviews</span></div>
        </div>
    </div>
</section>

<!-- ============ FEATURED ============ -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>Featured Boardings</h2>
            <p>Top rated places near the university, loaded live from our listings.</p>
        </div>

        <?php if ($featured): ?>
            <div class="card-grid">
                <?php foreach ($featured as $b): include __DIR__ . '/includes/boarding_card.php'; endforeach; ?>
            </div>
            <div class="text-center section-more">
                <a href="<?= url('student/browse.php') ?>" class="btn btn-outline btn-lg">View all boardings <i class="fa-solid fa-arrow-right"></i></a>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-regular fa-building"></i>
                <h3>No boardings listed yet</h3>
                <p>Be the first owner to advertise a room.</p>
                <a href="<?= url('register.php?type=owner') ?>" class="btn btn-primary">List Your Boarding</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ POPULAR LOCATIONS ============ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2>Popular Locations Around Moratuwa</h2>
            <p>Pick the area that suits your daily travel and budget.</p>
        </div>
        <div class="location-grid">
            <?php foreach (DEFAULT_LOCATIONS as $area): $c = $counts[$area] ?? 0; ?>
                <a class="loc-card" href="<?= url('student/browse.php?location=' . rawurlencode($area)) ?>">
                    <span class="loc-icon"><i class="fa-solid <?= e($areaIcons[$area] ?? 'fa-location-dot') ?>"></i></span>
                    <strong><?= e($area) ?></strong>
                    <small><?= $c ?> boarding<?= $c === 1 ? '' : 's' ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="section">
    <div class="container">
        <div class="section-head">
            <h2>How BoardMate Works</h2>
            <p>From searching to settling in, in three simple steps.</p>
        </div>
        <div class="steps">
            <div class="step">
                <span class="step-num">1</span>
                <h3>Search &amp; Filter</h3>
                <p>Filter by location, price, available beds and rating to see only what fits you.</p>
            </div>
            <div class="step">
                <span class="step-num">2</span>
                <h3>Compare Side by Side</h3>
                <p>Add up to three boardings to the compare list and see prices and facilities in one table.</p>
            </div>
            <div class="step">
                <span class="step-num">3</span>
                <h3>Read Reviews &amp; Decide</h3>
                <p>Real students share honest ratings and reviews so you can choose with confidence.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ BENEFITS ============ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <h2>Why BoardMate?</h2>
            <p>Made to help both sides of the boarding search.</p>
        </div>
        <div class="benefits">
            <div class="benefit-card">
                <h3><i class="fa-solid fa-graduation-cap"></i> For Students</h3>
                <ul>
                    <li><i class="fa-solid fa-circle-check"></i> All boardings near campus in one place</li>
                    <li><i class="fa-solid fa-circle-check"></i> Compare price, facilities and ratings quickly</li>
                    <li><i class="fa-solid fa-circle-check"></i> Honest reviews from other students</li>
                    <li><i class="fa-solid fa-circle-check"></i> Direct contact numbers, no middlemen</li>
                </ul>
                <a href="<?= url('student/browse.php') ?>" class="btn btn-primary">Find a Boarding</a>
            </div>
            <div class="benefit-card">
                <h3><i class="fa-solid fa-building-user"></i> For Boarding Owners</h3>
                <ul>
                    <li><i class="fa-solid fa-circle-check"></i> Advertise your rooms for free</li>
                    <li><i class="fa-solid fa-circle-check"></i> Reach hundreds of students searching daily</li>
                    <li><i class="fa-solid fa-circle-check"></i> Upload photos and list your facilities</li>
                    <li><i class="fa-solid fa-circle-check"></i> Build trust with ratings and reviews</li>
                </ul>
                <a href="<?= url('register.php?type=owner') ?>" class="btn btn-accent">List Your Boarding</a>
            </div>
        </div>
    </div>
</section>

<!-- ============ CALL TO ACTION ============ -->
<section class="section">
    <div class="container">
        <div class="cta-banner">
            <div>
                <h2>Ready to find your next home away from home?</h2>
                <p>Join BoardMate today. It takes less than a minute.</p>
            </div>
            <div class="cta-buttons">
                <a href="<?= url('student/browse.php') ?>" class="btn btn-accent btn-lg">Find a Boarding</a>
                <a href="<?= url('register.php?type=owner') ?>" class="btn btn-light btn-lg">List Your Boarding</a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>