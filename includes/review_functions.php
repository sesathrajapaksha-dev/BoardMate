<?php
/**
 * BoardMate - review and compare helpers
 */
require_once __DIR__ . '/boarding_manage.php';

/** A student's existing review for one boarding, or null. */
function get_user_review(int $studentId, int $boardingId): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM reviews WHERE student_id = ? AND boarding_id = ? LIMIT 1');
    $stmt->execute([$studentId, $boardingId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** Validate rating + text. Returns [list of error strings, rating, text]. */
function validate_review(array $in): array
{
    $errors = [];
    $rating = filter_var($in['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    $text   = trim((string)($in['review_text'] ?? ''));

    if ($rating === false) {
        $errors[] = 'Please choose a star rating from 1 to 5.';
    }
    $len = mb_strlen($text);
    if ($len < 5 || $len > 1000) {
        $errors[] = 'Your review must be 5-1000 characters long.';
    }
    return [$errors, $rating === false ? 0 : (int)$rating, $text];
}

/** A student's reviews (newest activity first) with boarding info. */
function get_student_reviews(int $studentId, ?int $limit = null): array
{
    $sql = 'SELECT r.review_id, r.rating, r.review_text, r.created_at, r.updated_at,
                   b.boarding_id, b.boarding_name, b.location,
                   (SELECT i.image_path FROM boarding_images i
                     WHERE i.boarding_id = b.boarding_id ORDER BY i.image_id LIMIT 1) AS cover_image
            FROM reviews r JOIN boardings b ON b.boarding_id = r.boarding_id
            WHERE r.student_id = ? ORDER BY r.updated_at DESC, r.review_id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int)$limit;
    }
    $stmt = getDB()->prepare($sql);
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

/** [count, average rating given] */
function get_student_review_stats(int $studentId): array
{
    $stmt = getDB()->prepare('SELECT COUNT(*) AS c, COALESCE(AVG(rating), 0) AS a FROM reviews WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $r = $stmt->fetch();
    return ['count' => (int)$r['c'], 'avg' => (float)$r['a']];
}

/** Boardings for the compare page, in the same order as $ids. */
function get_boardings_by_ids(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = getDB()->prepare(
        'SELECT b.*, COALESCE(rs.avg_rating, 0) AS avg_rating, COALESCE(rs.review_count, 0) AS review_count,
                (SELECT i.image_path FROM boarding_images i
                  WHERE i.boarding_id = b.boarding_id ORDER BY i.image_id LIMIT 1) AS cover_image'
        . BOARDING_FROM . ' WHERE b.boarding_id IN (' . $in . ')'
    );
    $stmt->execute($ids);
    $byId = [];
    foreach ($stmt->fetchAll() as $row) {
        $byId[(int)$row['boarding_id']] = $row;
    }
    $ordered = [];
    foreach ($ids as $id) {
        if (isset($byId[$id])) {
            $ordered[] = $byId[$id];
        }
    }
    return $ordered;
}

/** [boarding_id => [facility names]] */
function get_facilities_for_boardings(array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $out = [];
    if (!$ids) {
        return $out;
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = getDB()->prepare(
        'SELECT bf.boarding_id, f.facility_name FROM boarding_facilities bf
         JOIN facilities f ON f.facility_id = bf.facility_id WHERE bf.boarding_id IN (' . $in . ')'
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $r) {
        $out[(int)$r['boarding_id']][] = $r['facility_name'];
    }
    return $out;
}