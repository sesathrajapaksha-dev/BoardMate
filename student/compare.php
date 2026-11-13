<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/review_functions.php';

// ids come from the URL: ?ids=3,5,7  (max 3)
$ids = [];
foreach (explode(',', (string)($_GET['ids'] ?? '')) as $part) {
    $n = (int)$part;
    if ($n > 0) {
        $ids[$n] = $n;
    }
}
$ids   = array_slice(array_values($ids), 0, 3);
$items = get_boardings_by_ids($ids);
$count = count($items);

$allFacilities = get_all_facilities();
$facMap        = get_facilities_for_boardings(array_column($items, 'boarding_id'));

// What to highlight (only when comparing 2 or more)
$multi     = $count > 1;
$minPrice  = $count ? min(array_map(fn($x) => (float)$x['price_per_person'], $items)) : 0;
$ratedAvgs = array_map(fn($x) => (float)$x['avg_rating'], array_filter($items, fn($x) => (int)$x['review_count'] > 0));
$maxRating = $ratedAvgs ? max($ratedAvgs) : null;

$pageTitle    = 'Compare Boardings';
$bodyClass    = 'page-compare';
$extraScripts = ['assets/js/compare_page.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container head-flex">
        <div>
            <h1>Compare Boardings</h1>
            <p>Place up to 3 boarding places side by side.</p>
        </div>
        <?php if ($count): ?>
            <button type="button" class="btn btn-light" id="clearCompare"><i class="fa-solid fa-trash-can"></i> Clear all</button>
        <?php endif; ?>
    </div>
</section>

<section class="page-section" id="compareRoot">
    <div class="container">
        <?php if ($count === 0): ?>
            <div class="empty-state" id="compareEmpty">
                <i class="fa-solid fa-scale-balanced"></i>
                <h3>Nothing to compare yet</h3>
                <p>Press <strong>Compare</strong> on any boarding card to add it here.</p>
                <a href="<?= url('student/browse.php') ?>" class="btn btn-primary">Browse boardings</a>
            </div>
        <?php else: ?>
            <?php if ($count === 1): ?>
                <div class="notice"><i class="fa-solid fa-circle-info"></i>
                    <span>Add at least one more boarding to see a comparison.</span></div>
            <?php endif; ?>

            <div class="compare-wrap">
                <table class="compare-table">
                    <thead>
                        <tr>
                            <th class="ct-label"><span class="sr-only">Feature</span></th>
                            <?php foreach ($items as $b): ?>
                                <th class="ct-head" data-id="<?= (int)$b['boarding_id'] ?>" data-name="<?= e($b['boarding_name']) ?>">
                                    <button type="button" class="ct-remove" data-id="<?= (int)$b['boarding_id'] ?>"
                                            aria-label="Remove <?= e($b['boarding_name']) ?>"><i class="fa-solid fa-xmark"></i></button>
                                    <img src="<?= e(cover_url($b['cover_image'])) ?>" alt="">
                                    <a href="<?= url('student/boarding_details.php?id=' . (int)$b['boarding_id']) ?>"><?= e($b['boarding_name']) ?></a>
                                </th>
                            <?php endforeach; ?>
                            <?php if ($count < 3): ?>
                                <th class="ct-add">
                                    <a href="<?= url('student/browse.php') ?>"><i class="fa-solid fa-plus"></i><span>Add another</span></a>
                                </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Location</th>
                            <?php foreach ($items as $b): ?><td><?= e($b['location']) ?></td><?php endforeach; ?>
                            <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                        </tr>
                        <tr>
                            <th scope="row">Price per person</th>
                            <?php foreach ($items as $b): $best = $multi && (float)$b['price_per_person'] === $minPrice; ?>
                                <td class="<?= $best ? 'best' : '' ?>">
                                    <strong><?= e(format_price($b['price_per_person'])) ?></strong>
                                    <?php if ($best): ?><span class="best-tag"><i class="fa-solid fa-tag"></i> Lowest price</span><?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                        </tr>
                        <tr>
                            <th scope="row">Available beds</th>
                            <?php foreach ($items as $b): $n = (int)$b['available_beds']; ?>
                                <td><?= $n > 0 ? $n : '<span class="bad-text">Full</span>' ?></td>
                            <?php endforeach; ?>
                            <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                        </tr>
                        <tr>
                            <th scope="row">Average rating</th>
                            <?php foreach ($items as $b):
                                $has  = (int)$b['review_count'] > 0;
                                $best = $multi && $has && $maxRating !== null && (float)$b['avg_rating'] === $maxRating; ?>
                                <td class="<?= $best ? 'best' : '' ?>">
                                    <?php if ($has): ?>
                                        <?= render_stars((float)$b['avg_rating']) ?> <strong><?= e(number_format((float)$b['avg_rating'], 1)) ?></strong>
                                        <?php if ($best): ?><span class="best-tag"><i class="fa-solid fa-trophy"></i> Top rated</span><?php endif; ?>
                                    <?php else: ?>
                                        <span class="muted">No ratings yet</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                            <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                        </tr>
                        <tr>
                            <th scope="row">Number of reviews</th>
                            <?php foreach ($items as $b): ?><td><?= (int)$b['review_count'] ?></td><?php endforeach; ?>
                            <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                        </tr>
                        <tr>
                            <th scope="row">Contact number</th>
                            <?php foreach ($items as $b): ?>
                                <td><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $b['contact_number'])) ?>"><?= e($b['contact_number']) ?></a></td>
                            <?php endforeach; ?>
                            <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                        </tr>

                        <tr class="ct-group"><th colspan="<?= $count + 1 + ($count < 3 ? 1 : 0) ?>">Facilities</th></tr>
                        <?php foreach ($allFacilities as $f): ?>
                            <tr>
                                <th scope="row"><i class="fa-solid <?= e(facility_icon($f['facility_name'])) ?>"></i> <?= e($f['facility_name']) ?></th>
                                <?php foreach ($items as $b):
                                    $yes = in_array($f['facility_name'], $facMap[(int)$b['boarding_id']] ?? [], true); ?>
                                    <td class="ct-fac">
                                        <?php if ($yes): ?><i class="fa-solid fa-circle-check yes" title="Available"></i>
                                        <?php else: ?><i class="fa-solid fa-circle-xmark no" title="Not available"></i><?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                            </tr>
                        <?php endforeach; ?>

                        <tr class="ct-actions">
                            <th scope="row"></th>
                            <?php foreach ($items as $b): ?>
                                <td><a href="<?= url('student/boarding_details.php?id=' . (int)$b['boarding_id']) ?>" class="btn btn-primary btn-sm btn-block">View Details</a></td>
                            <?php endforeach; ?>
                            <?php if ($count < 3): ?><td class="ct-blank"></td><?php endif; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>