<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/review_functions.php';

$user = require_role('student');
require_post_csrf('student/my_reviews.php');

$rid = (int)($_POST['review_id'] ?? 0);

// The review must exist AND belong to the logged-in student
$stmt = getDB()->prepare('SELECT review_id, boarding_id FROM reviews WHERE review_id = ? AND student_id = ?');
$stmt->execute([$rid, $user['user_id']]);
$review = $stmt->fetch();

if (!$review) {
    log_activity($user['user_id'], 'UNAUTHORIZED', 'Tried to edit review #' . $rid);
    flash('error', 'Review not found, or you can only edit your own reviews.');
    redirect('student/my_reviews.php');
}

$back = 'student/boarding_details.php?id=' . (int)$review['boarding_id'] . '#reviews';

[$errors, $rating, $text] = validate_review($_POST);
if ($errors) {
    $_SESSION['old'] = ['rating' => $rating, 'review_text' => $text];
    flash('error', implode(' ', $errors));
    redirect($back);
}

$up = getDB()->prepare('UPDATE reviews SET rating = ?, review_text = ? WHERE review_id = ? AND student_id = ?');
$up->execute([$rating, $text, $rid, $user['user_id']]);

log_activity($user['user_id'], 'REVIEW_UPDATE', 'Updated review #' . $rid);
flash('success', 'Your review has been updated.');
redirect($back);