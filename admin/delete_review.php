<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_functions.php';

$admin = require_role('admin');
require_post_csrf('admin/reviews.php');

$rid  = (int)($_POST['review_id'] ?? 0);
$back = admin_back('admin/reviews.php');

$stmt = getDB()->prepare(
    'SELECT r.review_id, s.username, b.boarding_name
     FROM reviews r
     JOIN users s ON s.user_id = r.student_id
     JOIN boardings b ON b.boarding_id = r.boarding_id
     WHERE r.review_id = ?'
);
$stmt->execute([$rid]);
$review = $stmt->fetch();

if (!$review) {
    flash('error', 'Review not found. It may already have been deleted.');
    redirect($back);
}

getDB()->prepare('DELETE FROM reviews WHERE review_id = ?')->execute([$rid]);

log_activity($admin['user_id'], 'ADMIN_REVIEW_DELETE',
    'Deleted review #' . $rid . ' by ' . $review['username'] . ' on "' . $review['boarding_name'] . '"');
flash('success', 'The review by ' . $review['username'] . ' was deleted.');
redirect($back);
