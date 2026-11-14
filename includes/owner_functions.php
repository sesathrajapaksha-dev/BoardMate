<?php
/**
 * BoardMate - owner dashboard helpers (statistics and reviews on an owner's boardings)
 */
require_once __DIR__ . '/boarding_manage.php';

/**
 * Numbers for the owner dashboard cards.
 * Returns ['boardings' => int, 'beds' => int, 'reviews' => int, 'avg' => float]
 */
function get_owner_stats(int $ownerId): array
{
    $pdo = getDB();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS boardings, COALESCE(SUM(available_beds), 0) AS beds
         FROM boardings WHERE owner_id = ?'
    );
    $stmt->execute([$ownerId]);
    $b = $stmt->fetch();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS c, COALESCE(AVG(r.rating), 0) AS a
         FROM reviews r JOIN boardings b ON b.boarding_id = r.boarding_id
         WHERE b.owner_id = ?'
    );
    $stmt->execute([$ownerId]);
    $r = $stmt->fetch();

    return [
        'boardings' => (int)$b['boardings'],
        'beds'      => (int)$b['beds'],
        'reviews'   => (int)$r['c'],
        'avg'       => (float)$r['a'],
    ];
}

/** Reviews students wrote about this owner's boardings (newest first). */
function get_owner_reviews(int $ownerId, ?int $limit = null): array
{
    $sql = 'SELECT r.review_id, r.rating, r.review_text, r.created_at, r.updated_at,
                   u.username, b.boarding_id, b.boarding_name
            FROM reviews r
            JOIN boardings b ON b.boarding_id = r.boarding_id
            JOIN users u     ON u.user_id     = r.student_id
            WHERE b.owner_id = ?
            ORDER BY r.updated_at DESC, r.review_id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . (int)$limit; // integer cast, safe
    }
    $stmt = getDB()->prepare($sql);
    $stmt->execute([$ownerId]);
    return $stmt->fetchAll();
}

/** [5 => n, 4 => n, 3 => n, 2 => n, 1 => n] number of reviews per star rating. */
function get_owner_rating_breakdown(int $ownerId): array
{
    $out  = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $stmt = getDB()->prepare(
        'SELECT r.rating, COUNT(*) AS c
         FROM reviews r JOIN boardings b ON b.boarding_id = r.boarding_id
         WHERE b.owner_id = ? GROUP BY r.rating'
    );
    $stmt->execute([$ownerId]);
    foreach ($stmt->fetchAll() as $row) {
        $out[(int)$row['rating']] = (int)$row['c'];
    }
    return $out;
}
