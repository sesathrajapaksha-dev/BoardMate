<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/boarding_manage.php';

$id = (int)($_GET['id'] ?? 0);
$b  = $id > 0 ? get_boarding($id) : null;

if (!$b) {
    flash('error', 'That boarding could not be found. It may have been removed.');
    redirect('student/browse.php');
}

$images     = get_boarding_images($id);
$imageUrls  = array_map(fn($i) => cover_url($i['image_path']), $images);
if (!$imageUrls) {
    $imageUrls = [url('assets/images/placeholder.svg')];
}
$facilityNames = get_boarding_facility_names($id);
$reviews       = get_boarding_reviews($id);
$avg           = (float)$b['avg_rating'];
$cnt           = (int)$b['review_count'];
$beds          = (int)$b['available_beds'];
$viewer        = current_user();
$isOwnerView   = $viewer && $viewer['role'] === 'owner' && (int)$viewer['user_id'] === (int)$b['owner_id'];
$telHref       = 'tel:' . preg_replace('/[^\d+]/', '', $b['contact_number']);

$pageTitle    = $b['boarding_name'];
$extraScripts = ['assets/js/gallery.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-section detail-section">
    <div class="container">

        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= url('index.php') ?>">Home</a> <i class="fa-solid fa-chevron-right"></i>
            <a href="<?= url('student/browse.php') ?>">Browse</a> <i class="fa-solid fa-chevron-right"></i>
            <span><?= e($b['boarding_name']) ?></span>
        </nav>

        <div class="detail-title">
            <div>
                <h1><?= e($b['boarding_name']) ?></h1>
                <p class="detail-sub">
                    <span><i class="fa-solid fa-location-dot"></i> <?= e($b['full_address']) ?></span>
                    <span class="detail-rating">
                        <?php if ($cnt > 0): ?>
                            <?= render_stars($avg) ?> <strong><?= e(number_format($avg, 1)) ?></strong> (<?= $cnt ?> review<?= $cnt > 1 ? 's' : '' ?>)
                        <?php else: ?>
                            No reviews yet
                        <?php endif; ?>
                    </span>
                </p>
            </div>
            <?php if ($isOwnerView): ?>
                <a href="<?= url('owner/edit_boarding.php?id=' . $id) ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit advertisement</a>
            <?php endif; ?>
        </div>

        <div class="detail-layout">
            <div class="detail-main">

                <!-- Gallery -->
                <div class="gallery" id="gallery" data-images="<?= e(json_encode($imageUrls)) ?>">
                    <div class="gallery-main">
                        <img id="galleryMain" src="<?= e($imageUrls[0]) ?>" alt="<?= e($b['boarding_name']) ?>">
                        <?php if (count($imageUrls) > 1): ?>
                            <button type="button" class="g-nav g-prev" id="gPrev" aria-label="Previous photo"><i class="fa-solid fa-chevron-left"></i></button>
                            <button type="button" class="g-nav g-next" id="gNext" aria-label="Next photo"><i class="fa-solid fa-chevron-right"></i></button>
                        <?php endif; ?>
                        <span class="g-count" id="gCount"></span>
                    </div>
                    <?php if (count($imageUrls) > 1): ?>
                        <div class="gallery-thumbs">
                            <?php foreach ($imageUrls as $i => $src): ?>
                                <button type="button" class="g-thumb <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Show photo <?= $i + 1 ?>">
                                    <img src="<?= e($src) ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- About -->
                <div class="panel detail-block">
                    <h3>About this boarding</h3>
                    <p class="detail-desc"><?= nl2br(e($b['description'])) ?></p>
                </div>

                <!-- Facilities -->
                <div class="panel detail-block">
                    <h3>Facilities</h3>
                    <?php if ($facilityNames): ?>
                        <ul class="facility-list">
                            <?php foreach ($facilityNames as $fn): ?>
                                <li><i class="fa-solid <?= e(facility_icon($fn)) ?>"></i> <?= e($fn) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="muted">The owner has not listed any facilities.</p>
                    <?php endif; ?>
                </div>

                <!-- Reviews -->
                <div class="panel detail-block" id="reviews">
                    <h3>Reviews and ratings</h3>

                    <div class="review-summary">
                        <div class="rs-score"><?= $cnt > 0 ? e(number_format($avg, 1)) : '–' ?></div>
                        <div>
                            <?= render_stars($avg) ?>
                            <div class="muted"><?= $cnt ?> review<?= $cnt === 1 ? '' : 's' ?></div>
                        </div>
                    </div>

                    <!-- Review form is added in Phase 5 -->
                    <div id="review-form-slot">
                        <?php if (!$viewer): ?>
                            <div class="notice"><i class="fa-solid fa-circle-info"></i>
                                <span><a href="<?= url('login.php') ?>">Log in</a> as a student to rate and review this boarding.</span></div>
                        <?php elseif ($viewer['role'] === 'student'): ?>
                            <div class="notice"><i class="fa-solid fa-circle-info"></i>
                                <span>The review form will appear here after Phase 5.</span></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($reviews): ?>
                        <ul class="review-list">
                            <?php foreach ($reviews as $r): ?>
                                <li class="review-item">
                                    <div class="review-top">
                                        <span class="avatar"><?= e(strtoupper(mb_substr($r['username'], 0, 1))) ?></span>
                                        <div>
                                            <strong><?= e($r['username']) ?></strong>
                                            <div class="muted">
                                                <?= e(date('M j, Y', strtotime($r['created_at']))) ?>
                                                <?= $r['updated_at'] > $r['created_at'] ? ' (edited)' : '' ?>
                                            </div>
                                        </div>
                                        <div class="review-stars"><?= render_stars((float)$r['rating']) ?></div>
                                    </div>
                                    <p><?= nl2br(e($r['review_text'])) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="muted">No reviews yet. Be the first student to share your experience.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="detail-side">
                <div class="side-card">
                    <div class="side-price"><?= e(format_price($b['price_per_person'])) ?> <small>/ person / month</small></div>

                    <ul class="side-facts">
                        <li><i class="fa-solid fa-bed"></i>
                            <?php if ($beds > 0): ?><strong><?= $beds ?></strong> bed<?= $beds > 1 ? 's' : '' ?> available
                            <?php else: ?><span class="bad-text">Currently fully occupied</span><?php endif; ?>
                        </li>
                        <li><i class="fa-solid fa-location-dot"></i> <?= e($b['location']) ?></li>
                        <li><i class="fa-solid fa-graduation-cap"></i> <?= e($b['university']) ?></li>
                    </ul>

                    <a href="<?= e($telHref) ?>" class="btn btn-accent btn-block btn-lg">
                        <i class="fa-solid fa-phone"></i> <?= e($b['contact_number']) ?>
                    </a>
                    <button type="button" class="btn btn-outline btn-block compare-btn"
                            data-id="<?= $id ?>" data-name="<?= e($b['boarding_name']) ?>">
                        <i class="fa-solid fa-scale-balanced"></i> <span>Compare</span>
                    </button>
                </div>

                <div class="side-card">
                    <h4>Listed by</h4>
                    <div class="owner-box">
                        <span class="avatar avatar-lg"><?= e(strtoupper(mb_substr($b['owner_name'], 0, 1))) ?></span>
                        <div>
                            <strong><?= e($b['owner_name']) ?></strong>
                            <div class="muted">Boarding owner &middot; member since <?= e(date('M Y', strtotime($b['owner_since']))) ?></div>
                        </div>
                    </div>
                    <p class="muted small">Listed on <?= e(date('M j, Y', strtotime($b['created_at']))) ?></p>
                </div>
            </aside>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>