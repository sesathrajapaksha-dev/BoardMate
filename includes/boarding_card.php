<?php
/**
 * Reusable boarding card. Expects a $b array from fetch_boardings().
 */
$bid   = (int)$b['boarding_id'];
$avg   = (float)$b['avg_rating'];
$cnt   = (int)$b['review_count'];
$beds  = (int)$b['available_beds'];
$desc  = mb_strimwidth(trim(preg_replace('/\s+/', ' ', $b['description'])), 0, 110, '…', 'UTF-8');
$link  = url('student/boarding_details.php?id=' . $bid);
?>
<article class="b-card">
    <a class="b-img" href="<?= e($link) ?>" tabindex="-1" aria-hidden="true">
        <img src="<?= e(cover_url($b['cover_image'])) ?>" alt="<?= e($b['boarding_name']) ?>" loading="lazy">
        <span class="b-price-tag"><?= e(format_price($b['price_per_person'])) ?> <small>/ person</small></span>
        <?php if ($beds > 0): ?>
            <span class="b-badge ok"><i class="fa-solid fa-bed"></i> <?= $beds ?> bed<?= $beds > 1 ? 's' : '' ?> available</span>
        <?php else: ?>
            <span class="b-badge full">Fully occupied</span>
        <?php endif; ?>
    </a>

    <div class="b-body">
        <div class="b-loc"><i class="fa-solid fa-location-dot"></i> <?= e($b['location']) ?></div>
        <h3><a href="<?= e($link) ?>"><?= e($b['boarding_name']) ?></a></h3>

        <div class="b-rating">
            <?php if ($cnt > 0): ?>
                <?= render_stars($avg) ?>
                <strong><?= e(number_format($avg, 1)) ?></strong>
                <span>(<?= $cnt ?> review<?= $cnt > 1 ? 's' : '' ?>)</span>
            <?php else: ?>
                <?= render_stars(0) ?>
                <span>No reviews yet</span>
            <?php endif; ?>
        </div>

        <p class="b-desc"><?= e($desc) ?></p>

        <div class="b-actions">
            <a href="<?= e($link) ?>" class="btn btn-primary btn-sm">View Details</a>
            <button type="button" class="btn btn-outline btn-sm compare-btn"
                    data-id="<?= $bid ?>" data-name="<?= e($b['boarding_name']) ?>">
                <i class="fa-solid fa-scale-balanced"></i> <span>Compare</span>
            </button>
        </div>
    </div>
</article>