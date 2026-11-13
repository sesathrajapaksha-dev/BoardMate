<?php
require_once __DIR__ . '/../includes/auth.php';

$user = require_role('student');
require_post_csrf('student/my_reviews.php');

$rid = (int)($_POST['review_id'] ?? 0);

$stmt = getDB()->prepare('SELECT review_id, boarding_id FROM reviews WHERE review_id = ? AND student_id = ?');
$stmt->execute([$rid, $user['user_id']]);
$review = $stmt->fetch();

if (!$review) {
    log_activity($user['user_id'], 'UNAUTHORIZED', 'Tried to delete review #' . $rid);
    flash('error', 'Review not found, or you can only delete your own reviews.');
    redirect('student/my_reviews.php');
}

$del = getDB()->prepare('DELETE FROM reviews WHERE review_id = ? AND student_id = ?');
$del->execute([$rid, $user['user_id']]);

log_activity($user['user_id'], 'REVIEW_DELETE', 'Deleted review #' . $rid);
flash('success', 'Your review has been deleted.');

$from = $_POST['from'] ?? '';
if ($from === 'my_reviews') {
    redirect('student/my_reviews.php');
} elseif ($from === 'dashboard') {
    redirect('student/dashboard.php');
}
redirect('student/boarding_details.php?id=' . (int)$review['boarding_id'] . '#reviews');