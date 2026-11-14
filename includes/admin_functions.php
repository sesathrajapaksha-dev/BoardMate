<?php
/**
 * BoardMate - administrator helpers
 * Statistics, searchable/paginated lists for users, boardings, reviews and the
 * activity log, plus safe user deletion.
 */
require_once __DIR__ . '/boarding_manage.php';

const ADMIN_PER_PAGE = 10;
const LOG_PER_PAGE   = 20;
const USER_ROLES     = ['student', 'owner', 'admin'];

// ======================================================================
// Small shared helpers
// ======================================================================

/** "%term%" for LIKE, with % _ and \ escaped so they are searched literally. */
function like_term(string $q): string
{
    return '%' . addcslashes($q, '%_\\') . '%';
}

/** Current page number from ?page=, always >= 1. */
function admin_page(): int
{
    return max(1, (int)($_GET['page'] ?? 1));
}

/** Same URL (keeping the current filters) with another page number. */
function admin_page_url(int $p): string
{
    $params = $_GET;
    unset($params['page']);
    if ($p > 1) {
        $params['page'] = $p;
    }
    return '?' . http_build_query($params);
}

/** Pagination links, same look as the browse page. */
function render_admin_pagination(int $page, int $pages): void
{
    if ($pages <= 1) {
        return;
    }
    echo '<nav class="pagination" aria-label="Pages">';
    if ($page > 1) {
        echo '<a href="' . e(admin_page_url($page - 1)) . '" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a>';
    }
    for ($p = 1; $p <= $pages; $p++) {
        if ($p === 1 || $p === $pages || abs($p - $page) <= 1) {
            echo '<a href="' . e(admin_page_url($p)) . '" class="' . ($p === $page ? 'current' : '') . '">' . $p . '</a>';
        } elseif (abs($p - $page) === 2) {
            echo '<span class="dots">&hellip;</span>';
        }
    }
    if ($page < $pages) {
        echo '<a href="' . e(admin_page_url($page + 1)) . '" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a>';
    }
    echo '</nav>';
}

/**
 * Where to go back to after an admin action. The list page sends its query string in a hidden
 * "qs" field; only known filter keys are kept, so nothing else can be injected into the redirect.
 */
function admin_back(string $page): string
{
    parse_str(ltrim((string)($_POST['qs'] ?? ''), '?'), $in);
    $keep = [];
    foreach (['q', 'role', 'rating', 'action', 'page'] as $k) {
        if (isset($in[$k]) && is_string($in[$k]) && $in[$k] !== '') {
            $keep[$k] = mb_substr($in[$k], 0, 100);
        }
    }
    return $page . ($keep ? '?' . http_build_query($keep) : '');
}

// ======================================================================
// Statistics
// ======================================================================

function admin_stats(): array
{
    $row = getDB()->query(
        "SELECT
            (SELECT COUNT(*) FROM users)                                   AS users,
            (SELECT COUNT(*) FROM users WHERE role = 'owner')              AS owners,
            (SELECT COUNT(*) FROM users WHERE role = 'student')            AS students,
            (SELECT COUNT(*) FROM boardings)                               AS boardings,
            (SELECT COUNT(*) FROM reviews)                                 AS reviews,
            (SELECT COUNT(*) FROM activity_logs
              WHERE created_at >= (NOW() - INTERVAL 1 DAY))                AS activity_24h,
            (SELECT COUNT(*) FROM activity_logs
              WHERE action = 'LOGIN_FAILED'
                AND created_at >= (NOW() - INTERVAL 1 DAY))                AS failed_24h"
    )->fetch();

    return array_map('intval', $row);
}

// ======================================================================
// Users
// ======================================================================

function parse_user_filters(array $src): array
{
    $role = $src['role'] ?? '';
    return [
        'q'    => mb_substr(trim((string)($src['q'] ?? '')), 0, 100),
        'role' => (is_string($role) && in_array($role, USER_ROLES, true)) ? $role : '',
    ];
}

function admin_user_where(array $f): array
{
    $where = [];
    $params = [];
    if ($f['q'] !== '') {
        $like = like_term($f['q']);
        $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if ($f['role'] !== '') {
        $where[] = 'u.role = ?';
        $params[] = $f['role'];
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function count_admin_users(array $f): int
{
    [$where, $params] = admin_user_where($f);
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM users u' . $where);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_admin_users(array $f, int $limit, int $offset): array
{
    [$where, $params] = admin_user_where($f);
    $sql = 'SELECT u.user_id, u.full_name, u.username, u.email, u.phone, u.role, u.created_at,
                   (SELECT COUNT(*) FROM boardings b WHERE b.owner_id   = u.user_id) AS boarding_count,
                   (SELECT COUNT(*) FROM reviews   r WHERE r.student_id = u.user_id) AS review_count
            FROM users u' . $where
         . ' ORDER BY u.created_at DESC, u.user_id DESC'
         . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset; // integers cast, safe
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Delete a student or owner account.
 * Rules (enforced here, not just in the page): admins can never be deleted, and nobody can delete themselves.
 * ON DELETE CASCADE removes the user's boardings, photos rows, facility links and reviews;
 * the photo FILES are removed from disk afterwards. Activity log rows are kept (user_id becomes NULL).
 * Returns [success (bool), message (string)].
 */
function delete_user_account(int $targetId, int $adminId): array
{
    if ($targetId <= 0) {
        return [false, 'User not found.'];
    }
    if ($targetId === $adminId) {
        return [false, 'You cannot delete your own account.'];
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT user_id, username, role FROM users WHERE user_id = ?');
    $stmt->execute([$targetId]);
    $u = $stmt->fetch();

    if (!$u) {
        return [false, 'User not found. They may already have been deleted.'];
    }
    if ($u['role'] === 'admin') {
        return [false, 'Administrator accounts cannot be deleted here.'];
    }

    // Remember the photo files (the rows disappear with the cascade)
    $img = $pdo->prepare(
        'SELECT i.image_path FROM boarding_images i
         JOIN boardings b ON b.boarding_id = i.boarding_id WHERE b.owner_id = ?'
    );
    $img->execute([$targetId]);
    $files = $img->fetchAll(PDO::FETCH_COLUMN);

    $del = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role <> 'admin'");
    $del->execute([$targetId]);

    if ($del->rowCount() === 0) {
        return [false, 'The account could not be deleted.'];
    }

    delete_image_files($files);
    log_activity($adminId, 'ADMIN_USER_DELETE', 'Deleted ' . $u['role'] . ' account: ' . $u['username'] . ' (#' . $targetId . ')');

    return [true, 'Account "' . $u['username'] . '" and all of its data were deleted.'];
}

// ======================================================================
// Boardings
// ======================================================================

function parse_text_filter(array $src): array
{
    return ['q' => mb_substr(trim((string)($src['q'] ?? '')), 0, 100)];
}

function admin_boarding_where(array $f): array
{
    $where = [];
    $params = [];
    if ($f['q'] !== '') {
        $like = like_term($f['q']);
        $where[] = '(b.boarding_name LIKE ? OR b.location LIKE ? OR b.full_address LIKE ? OR u.username LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function count_admin_boardings(array $f): int
{
    [$where, $params] = admin_boarding_where($f);
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM boardings b JOIN users u ON u.user_id = b.owner_id' . $where);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_admin_boardings(array $f, int $limit, int $offset): array
{
    [$where, $params] = admin_boarding_where($f);
    $sql = 'SELECT b.boarding_id, b.boarding_name, b.location, b.price_per_person, b.available_beds, b.created_at,
                   u.user_id AS owner_id, u.username AS owner_username,
                   COALESCE(rs.avg_rating, 0) AS avg_rating, COALESCE(rs.review_count, 0) AS review_count,
                   (SELECT i.image_path FROM boarding_images i
                     WHERE i.boarding_id = b.boarding_id ORDER BY i.image_id LIMIT 1) AS cover_image'
         . BOARDING_FROM
         . ' JOIN users u ON u.user_id = b.owner_id' . $where
         . ' ORDER BY b.created_at DESC, b.boarding_id DESC'
         . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ======================================================================
// Reviews
// ======================================================================

function parse_review_filters(array $src): array
{
    $rating = (int)($src['rating'] ?? 0);
    return [
        'q'      => mb_substr(trim((string)($src['q'] ?? '')), 0, 100),
        'rating' => ($rating >= 1 && $rating <= 5) ? $rating : 0,
    ];
}

function admin_review_where(array $f): array
{
    $where = [];
    $params = [];
    if ($f['q'] !== '') {
        $like = like_term($f['q']);
        $where[] = '(r.review_text LIKE ? OR b.boarding_name LIKE ? OR s.username LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($f['rating'] > 0) {
        $where[] = 'r.rating = ?';
        $params[] = $f['rating'];
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function count_admin_reviews(array $f): int
{
    [$where, $params] = admin_review_where($f);
    $stmt = getDB()->prepare(
        'SELECT COUNT(*) FROM reviews r
         JOIN users s ON s.user_id = r.student_id
         JOIN boardings b ON b.boarding_id = r.boarding_id' . $where
    );
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_admin_reviews(array $f, int $limit, int $offset): array
{
    [$where, $params] = admin_review_where($f);
    $sql = 'SELECT r.review_id, r.rating, r.review_text, r.created_at, r.updated_at,
                   s.username AS student_username, b.boarding_id, b.boarding_name
            FROM reviews r
            JOIN users s ON s.user_id = r.student_id
            JOIN boardings b ON b.boarding_id = r.boarding_id' . $where
         . ' ORDER BY r.created_at DESC, r.review_id DESC'
         . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ======================================================================
// Activity log
// ======================================================================

function parse_log_filters(array $src): array
{
    $action = $src['action'] ?? '';
    return [
        'q'      => mb_substr(trim((string)($src['q'] ?? '')), 0, 100),
        'action' => is_string($action) ? mb_substr(trim($action), 0, 60) : '',
    ];
}

function admin_log_where(array $f): array
{
    $where = [];
    $params = [];
    if ($f['q'] !== '') {
        $like = like_term($f['q']);
        $where[] = '(l.details LIKE ? OR u.username LIKE ? OR l.ip_address LIKE ?)';
        array_push($params, $like, $like, $like);
    }
    if ($f['action'] !== '') {
        $where[] = 'l.action = ?';
        $params[] = $f['action'];
    }
    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function count_activity_logs(array $f): int
{
    [$where, $params] = admin_log_where($f);
    $stmt = getDB()->prepare('SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.user_id = l.user_id' . $where);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function get_activity_logs(array $f, int $limit, int $offset): array
{
    [$where, $params] = admin_log_where($f);
    $sql = 'SELECT l.log_id, l.action, l.details, l.ip_address, l.created_at,
                   u.username, u.role
            FROM activity_logs l LEFT JOIN users u ON u.user_id = l.user_id' . $where
         . ' ORDER BY l.created_at DESC, l.log_id DESC'
         . ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Every action name that exists in the log (for the filter drop-down). */
function get_log_actions(): array
{
    return getDB()->query('SELECT DISTINCT action FROM activity_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
}

/** Colour of the pill for a log action. */
function activity_pill(string $action): string
{
    if (strpos($action, 'FAILED') !== false || strpos($action, 'UNAUTHORIZED') !== false || strpos($action, 'DELETE') !== false) {
        return 'pill-red';
    }
    if (strpos($action, 'ADD') !== false || $action === 'REGISTER') {
        return 'pill-green';
    }
    if (strpos($action, 'LOGIN') === 0 || strpos($action, 'LOGOUT') === 0) {
        return 'pill-gray';
    }
    return 'pill-blue';
}
