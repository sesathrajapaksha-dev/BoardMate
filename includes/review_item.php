<?php
/**
 * One row in a student's review list.
 * Expects: $r (from get_student_reviews), $from ('dashboard'|'my_reviews'), optional $short (bool)
 */
$rid  = (int)$r['review_id'];
$bid  = (int)$r['boarding_id'];
$link = url('student/boarding_details.php?id=' . $bid . '#reviews');
$text = !empty($short)
    ? mb_strimwidth(trim(preg_replace('/\s+/', ' ', $r['review_text'])), 0, 150, '…', 'UTF-8')
    : $r['review_text'];
?>
<article class="rv-row">
    <a href="<?= e($link) ?>"><img class="rv-thumb" src="<?= e(cover_url($r['cover_image'])) ?>" alt="<?= e($r['boarding_name']) ?>"></a>
    <div class="rv-body">
        <div class="rv-head">
            <h4><a href="<?= e($link) ?>"><?= e($r['boarding_name']) ?></a></h4>
            <?= render_stars((float)$r['rating']) ?>
        </div>
        <div class="muted small">
            <i class="fa-solid fa-location-dot"></i> <?= e($r['location']) ?> &middot;
            <?= e(date('M j, Y', strtotime($r['created_at']))) ?><?= $r['updated_at'] > $r['created_at'] ? ' (edited)' : '' ?>
        </div>
        <p><?= nl2br(e($text)) ?></p>
    </div>
    <div class="rv-actions">
        <a href="<?= e($link) ?>" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
        <form action="<?= url('actions/delete_review.php') ?>" method="post" class="inline-form"
              data-confirm="Delete your review of &quot;<?= e($r['boarding_name']) ?>&quot;?">
            <?= csrf_field() ?>
            <input type="hidden" name="review_id" value="<?= $rid ?>">
            <input type="hidden" name="from" value="<?= e($from) ?>">
            <button type="submit" class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
    </div>
</article>