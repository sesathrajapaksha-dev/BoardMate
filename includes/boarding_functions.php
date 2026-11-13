<?php
/**
 * BoardMate - boarding queries and display helpers
 */
require_once __DIR__ . '/functions.php';

const DEFAULT_LOCATIONS = ['Katubedda', 'Moratuwa', 'Rawathawatta', 'Egodauyana', 'Angulana', 'Rathmalana'];

const SORT_LABELS = [
    'newest'     => 'Newest first',
    'price_asc'  => 'Price: low to high',
    'price_desc' => 'Price: high to low',
    'rating'     => 'Highest rated',
];

const SORT_SQL = [
    'newest'     => 'b.created_at DESC, b.boarding_id DESC',
    'price_asc'  => 'b.price_per_person ASC, b.boarding_id DESC',
    'price_desc' => 'b.price_per_person DESC, b.boarding_id DESC',
    'rating'     => 'COALESCE(rs.avg_rating, 0) DESC, COALESCE(rs.review_count, 0) DESC, b.boarding_id DESC',
];

/** Boardings joined with their average rating (one pass, no GROUP BY needed). */
const BOARDING_FROM = ' FROM boardings b
    LEFT JOIN (
        SELECT boarding_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
        FROM reviews GROUP BY boarding_id
    ) rs ON rs.boarding_id = b.boarding_id ';

/** Default areas plus any other location owners have used. */
function get_locations(): array
{
    $rows = getDB()->query('SELECT DISTINCT location FROM boardings ORDER BY location')->fetchAll(PDO::FETCH_COLUMN);
    $all  = array_values(array_unique(array_merge(DEFAULT_LOCATIONS, $rows)));
    sort($all, SORT_NATURAL | SORT_FLAG_CASE);
    return $all;
}

/** Clean and validate the search/filter inputs coming from the URL. */
function parse_filters(array $src): array
{
    $num = function ($v) {
        $v = trim((string)($v ?? ''));
        return ($v !== '' && is_numeric($v) && (float)$v >= 0) ? (float)$v : null;
    };

    $min = $num($src['min_price'] ?? null);
    $max = $num($src['max_price'] ?? null);
    if ($min !== null && $max !== null && $min > $max) {
        [$min, $max] = [$max, $min];
    }

    $sort = $src['sort'] ?? 'newest';
    if (!is_string($sort) || !array_key_exists($sort, SORT_SQL)) {
        $sort = 'newest';
    }

    return [
        'q'          => mb_substr(trim((string)($src['q'] ?? '')), 0, 100),
        'location'   => mb_substr(trim((string)($src['location'] ?? '')), 0, 100),
        'min_price'  => $min,
        'max_price'  => $max,
        'beds'       => max(0, min(20, (int)($src['beds'] ?? 0))),
        'min_rating' => max(0, min(5, (int)($src['min_rating'] ?? 0))),
        'sort'       => $sort,
        'page'       => max(1, (int)($src['page'] ?? 1)),
    ];
}

/** Build the WHERE clause. Always uses ? placeholders, never string concatenation of values. */
function boarding_where(array $f): array
{
    $where = [];
    $params = [];

    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $where[] = '(b.boarding_name LIKE ? OR b.description LIKE ? OR b.location LIKE ? OR b.full_address LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if ($f['location'] !== '') {
        $where[] = 'b.location = ?';
        $params[] = $f['location'];
    }
    if ($f['min_price'] !== null) {
        $where[] = 'b.price_per_person >= ?';
        $params[] = $f['min_price'];
    }
    if ($f['max_price'] !== null) {
        $where[] = 'b.price_per_person <= ?';
        $params[] = $f['max_price'];
    }
    if ($f['beds'] > 0) {
        $where[] = 'b.available_beds >= ?';
        $params[] = $f['beds'];
    }
    if ($f['min_rating'] > 0) {
        $where[] = 'COALESCE(rs.avg_rating, 0) >= ?';
        $params[] = $f['min_rating'];
    }

    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function count_boardings(array $f): int
{
    [$where, $params] = boarding_where($f);
    $stmt = getDB()->prepare('SELECT COUNT(*)' . BOARDING_FROM . $where);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function fetch_boardings(array $f, string $sort = 'newest', int $limit = 9, int $offset = 0): array
{
    [$where, $params] = boarding_where($f);
    $order = SORT_SQL[$sort] ?? SORT_SQL['newest'];

    $sql = 'SELECT b.*,
                   COALESCE(rs.avg_rating, 0)   AS avg_rating,
                   COALESCE(rs.review_count, 0) AS review_count,
                   (SELECT i.image_path FROM boarding_images i
                     WHERE i.boarding_id = b.boarding_id ORDER BY i.image_id LIMIT 1) AS cover_image'
         . BOARDING_FROM . $where
         . ' ORDER BY ' . $order
         . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset; // integers cast above, safe

    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Numbers for the home page strip. */
function site_stats(): array
{
    $pdo = getDB();
    return [
        'boardings' => (int)$pdo->query('SELECT COUNT(*) FROM boardings')->fetchColumn(),
        'students'  => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
        'owners'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'owner'")->fetchColumn(),
        'reviews'   => (int)$pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
    ];
}

/** [location => number of boardings] */
function location_counts(): array
{
    $rows = getDB()->query('SELECT location, COUNT(*) AS c FROM boardings GROUP BY location')->fetchAll();
    $out = [];
    foreach ($rows as $r) {
        $out[$r['location']] = (int)$r['c'];
    }
    return $out;
}

/** URL of the cover image, or the placeholder. Paths in the DB are file names inside uploads/boardings/. */
function cover_url(?string $path): string
{
    return $path ? UPLOAD_URL . rawurlencode(basename($path)) : url('assets/images/placeholder.svg');
}

function format_price($amount): string
{
    return 'LKR ' . number_format((float)$amount, 0);
}

/** Star icons (full / half / empty) for an average rating. */
function render_stars(float $avg): string
{
    $html = '<span class="stars" aria-label="' . e(number_format($avg, 1)) . ' out of 5 stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($avg >= $i - 0.25) {
            $html .= '<i class="fa-solid fa-star"></i>';
        } elseif ($avg >= $i - 0.75) {
            $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star"></i>';
        }
    }
    return $html . '</span>';
}