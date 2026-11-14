<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/review_functions.php';

$user = require_role('student');
require_post_csrf('student/browse.php');

$bid = (int)($_POST['boarding_id'] ?? 0);
if ($bid <= 0 || !get_boarding($bid)) {
    flash('error', 'That boarding could not be found.');
    redirect('student/browse.php');
}
$back = 'student/boarding_details.php?id=' . $bid . '#reviews';

[$errors, $rating, $text] = validate_review($_POST);
if ($errors) {
    $_SESSION['old'] = ['rating' => $rating, 'review_text' => $text];
    flash('error', implode(' ', $errors));
    redirect($back);
}

// One review per student per boarding: if one exists, update it instead
$existing = get_user_review($user['user_id'], $bid);
$pdo = getDB();

try {
    if ($existing) {
        $stmt = $pdo->prepare('UPDATE reviews SET rating = ?, review_text = ? WHERE review_id = ? AND student_id = ?');
        $stmt->execute([$rating, $text, $existing['review_id'], $user['user_id']]);
        log_activity($user['user_id'], 'REVIEW_UPDATE', 'Updated review on boarding #' . $bid);
        flash('success', 'You had already reviewed this boarding, so your review was updated.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO reviews (student_id, boarding_id, rating, review_text) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['user_id'], $bid, $rating, $text]);
        log_activity($user['user_id'], 'REVIEW_ADD', 'Reviewed boarding #' . $bid . ' (' . $rating . ' stars)');
        flash('success', 'Thank you! Your review has been published.');
    }
} catch (PDOException $ex) {
    flash('error', 'Could not save your review. Please try again.');
}
redirect($back);