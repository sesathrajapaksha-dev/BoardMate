<?php
/**
 * Review form on the details page.
 * Expects: $id (boarding id), $myReview (array|null)
 */
$oldReview = pull_old();
$curRating = (int)($oldReview['rating'] ?? ($myReview['rating'] ?? 0));
$curText   = (string)($oldReview['review_text'] ?? ($myReview['review_text'] ?? ''));
$isUpdate  = $myReview !== null;
$labels    = [5 => 'Excellent', 4 => 'Very good', 3 => 'Good', 2 => 'Fair', 1 => 'Poor'];
?>
<form action="<?= url($isUpdate ? 'actions/update_review.php' : 'actions/add_review.php') ?>"
      method="post" class="review-form" id="reviewForm" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="boarding_id" value="<?= (int)$id ?>">
    <?php if ($isUpdate): ?>
        <input type="hidden" name="review_id" value="<?= (int)$myReview['review_id'] ?>">
    <?php endif; ?>

    <h4><?= $isUpdate ? 'Edit your review' : 'Rate and review this boarding' ?></h4>

    <div class="form-group">
        <label class="plain-label">Your rating</label>
        <div class="star-row">
            <div class="star-input" id="starInput">
                <?php foreach ($labels as $val => $text): ?>
                    <input type="radio" name="rating" id="star<?= $val ?>" value="<?= $val ?>"
                           <?= $curRating === $val ? 'checked' : '' ?> required>
                    <label for="star<?= $val ?>" title="<?= e($text) ?>"><i class="fa-solid fa-star"></i></label>
                <?php endforeach; ?>
            </div>
            <span class="rating-text" id="ratingText"></span>
        </div>
    </div>

    <div class="form-group">
        <label for="review_text">Your review</label>
        <textarea id="review_text" name="review_text" rows="4" required minlength="5" maxlength="1000"
                  placeholder="Share your experience: cleanliness, owner, water and electricity, distance to campus..."><?= e($curText) ?></textarea>
        <small class="hint"><span id="reviewCount">0</span> / 1000 characters</small>
    </div>

    <div class="review-form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid <?= $isUpdate ? 'fa-floppy-disk' : 'fa-paper-plane' ?>"></i>
            <?= $isUpdate ? 'Update review' : 'Submit review' ?>
        </button>
    </div>
</form>

<?php if ($isUpdate): ?>
    <form action="<?= url('actions/delete_review.php') ?>" method="post" class="review-delete"
          data-confirm="Delete your review of this boarding? This cannot be undone.">
        <?= csrf_field() ?>
        <input type="hidden" name="review_id" value="<?= (int)$myReview['review_id'] ?>">
        <input type="hidden" name="from" value="details">
        <button type="submit" class="btn btn-sm btn-danger-outline"><i class="fa-solid fa-trash"></i> Delete my review</button>
    </form>
<?php endif; ?>