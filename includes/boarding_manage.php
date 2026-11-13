<?php
/**
 * BoardMate - boarding management helpers (queries, validation, image uploads)
 */
require_once __DIR__ . '/boarding_functions.php';

const MAX_IMAGES = 8;

const FACILITY_ICONS = [
    'Wi-Fi'              => 'fa-wifi',
    'Electricity'        => 'fa-bolt',
    'Water'              => 'fa-droplet',
    'Attached bathroom'  => 'fa-shower',
    'Shared bathroom'    => 'fa-toilet',
    'Kitchen'            => 'fa-kitchen-set',
    'Parking'            => 'fa-square-parking',
    'Laundry facilities' => 'fa-shirt',
    'Furnished rooms'    => 'fa-couch',
    'Air conditioning'   => 'fa-snowflake',
];

function facility_icon(string $name): string
{
    return FACILITY_ICONS[$name] ?? 'fa-circle-check';
}

function get_all_facilities(): array
{
    $rows = getDB()->query('SELECT facility_id, facility_name FROM facilities ORDER BY facility_id')->fetchAll();
    foreach ($rows as &$r) {
        $r['facility_id'] = (int)$r['facility_id'];
    }
    return $rows;
}

/** One boarding with owner info and rating summary, or null. */
function get_boarding(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT b.*, u.full_name AS owner_name, u.username AS owner_username, u.created_at AS owner_since,
                COALESCE(rs.avg_rating, 0) AS avg_rating, COALESCE(rs.review_count, 0) AS review_count'
        . BOARDING_FROM
        . ' JOIN users u ON u.user_id = b.owner_id WHERE b.boarding_id = ?'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_boarding_images(int $id): array
{
    $stmt = getDB()->prepare('SELECT image_id, image_path FROM boarding_images WHERE boarding_id = ? ORDER BY image_id');
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function get_boarding_facility_ids(int $id): array
{
    $stmt = getDB()->prepare('SELECT facility_id FROM boarding_facilities WHERE boarding_id = ?');
    $stmt->execute([$id]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function get_boarding_facility_names(int $id): array
{
    $stmt = getDB()->prepare(
        'SELECT f.facility_name FROM boarding_facilities bf
         JOIN facilities f ON f.facility_id = bf.facility_id
         WHERE bf.boarding_id = ? ORDER BY f.facility_id'
    );
    $stmt->execute([$id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function get_boarding_reviews(int $id): array
{
    $stmt = getDB()->prepare(
        'SELECT r.review_id, r.rating, r.review_text, r.created_at, r.updated_at, u.username
         FROM reviews r JOIN users u ON u.user_id = r.student_id
         WHERE r.boarding_id = ? ORDER BY r.updated_at DESC'
    );
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

/** All boardings of one owner, with rating summary and cover image. */
function get_owner_boardings(int $ownerId): array
{
    $stmt = getDB()->prepare(
        'SELECT b.*, COALESCE(rs.avg_rating, 0) AS avg_rating, COALESCE(rs.review_count, 0) AS review_count,
                (SELECT i.image_path FROM boarding_images i
                  WHERE i.boarding_id = b.boarding_id ORDER BY i.image_id LIMIT 1) AS cover_image'
        . BOARDING_FROM
        . ' WHERE b.owner_id = ? ORDER BY b.created_at DESC, b.boarding_id DESC'
    );
    $stmt->execute([$ownerId]);
    return $stmt->fetchAll();
}

// ======================================================================
// Validation
// ======================================================================

/**
 * Validate the advertisement form.
 * Returns [errors, clean]. "clean" always holds the (trimmed) typed values so the form can be refilled.
 */
function validate_boarding_input(array $in, array $facilities): array
{
    $errors = [];

    $name    = trim((string)($in['boarding_name'] ?? ''));
    $desc    = trim((string)($in['description'] ?? ''));
    $loc     = trim((string)($in['location'] ?? ''));
    $address = trim((string)($in['full_address'] ?? ''));
    $price   = trim((string)($in['price_per_person'] ?? ''));
    $beds    = trim((string)($in['available_beds'] ?? ''));
    $contact = preg_replace('/[\s\-]/', '', trim((string)($in['contact_number'] ?? '')));

    // Use the standard spelling for known areas (keeps filters consistent)
    foreach (DEFAULT_LOCATIONS as $known) {
        if (strcasecmp($loc, $known) === 0) {
            $loc = $known;
        }
    }

    if (mb_strlen($name) < 3 || mb_strlen($name) > 150) {
        $errors['boarding_name'] = 'Boarding name must be 3-150 characters.';
    }
    if (mb_strlen($desc) < 20 || mb_strlen($desc) > 2000) {
        $errors['description'] = 'Description must be 20-2000 characters.';
    }
    if (!preg_match('/^[\p{L}][\p{L}\s.\'\-]{1,99}$/u', $loc)) {
        $errors['location'] = 'Enter the area name, e.g. Katubedda (letters only).';
    }
    if (mb_strlen($address) < 5 || mb_strlen($address) > 255) {
        $errors['full_address'] = 'Full address must be 5-255 characters.';
    }
    if (!is_numeric($price) || (float)$price < 500 || (float)$price > 500000) {
        $errors['price_per_person'] = 'Price must be between LKR 500 and LKR 500,000.';
    }
    if (!ctype_digit($beds) || (int)$beds > 100) {
        $errors['available_beds'] = 'Enter a whole number of beds (0-100).';
    }
    if (!preg_match('/^(\+94|0)\d{9}$/', $contact)) {
        $errors['contact_number'] = 'Enter a valid Sri Lankan phone number, e.g. 0771234567.';
    }

    // Only accept facility IDs that really exist
    $valid = array_map('intval', array_column($facilities, 'facility_id'));
    $chosen = [];
    foreach ((array)($in['facilities'] ?? []) as $fid) {
        $fid = (int)$fid;
        if (in_array($fid, $valid, true)) {
            $chosen[$fid] = $fid;
        }
    }

    return [$errors, [
        'boarding_name'    => $name,
        'description'      => $desc,
        'location'         => $loc,
        'full_address'     => $address,
        'price_per_person' => $price,
        'available_beds'   => $beds,
        'contact_number'   => $contact,
        'facilities'       => array_values($chosen),
    ]];
}

// ======================================================================
// Image uploads
// ======================================================================

/** Turn PHP's awkward $_FILES['images'] structure into a simple list; skips empty slots. */
function normalize_files(?array $f): array
{
    $out = [];
    if (!$f || !isset($f['name']) || !is_array($f['name'])) {
        return $out;
    }
    foreach ($f['name'] as $i => $n) {
        $err = $f['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $out[] = [
            'name'     => (string)$n,
            'tmp_name' => (string)$f['tmp_name'][$i],
            'error'    => (int)$err,
            'size'     => (int)$f['size'][$i],
        ];
    }
    return $out;
}

/** Validate every uploaded file. Returns [list of error strings, list of [file, extension]]. */
function check_uploads(array $files): array
{
    $errors = [];
    $valid  = [];
    $map    = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo  = new finfo(FILEINFO_MIME_TYPE);

    foreach ($files as $f) {
        $label = '"' . mb_substr(basename($f['name']), 0, 40) . '"';

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $big = in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
            $errors[] = $label . ($big ? ' is too large.' : ' failed to upload.');
            continue;
        }
        if ($f['size'] > MAX_IMAGE_SIZE) {
            $errors[] = $label . ' is larger than ' . (MAX_IMAGE_SIZE / 1024 / 1024) . ' MB.';
            continue;
        }
        if (!is_uploaded_file($f['tmp_name'])) {
            $errors[] = $label . ' was not a valid upload.';
            continue;
        }
        // Real content check, not just the file name
        $mime = $finfo->file($f['tmp_name']);
        if (!isset($map[$mime]) || @getimagesize($f['tmp_name']) === false) {
            $errors[] = $label . ' is not a valid JPG, PNG or WEBP image.';
            continue;
        }
        $valid[] = [$f, $map[$mime]];
    }
    return [$errors, $valid];
}

/**
 * Move validated files into uploads/boardings/ with random names and insert their rows.
 * $saved is filled with the file names so the caller can clean up if something fails later.
 */
function store_images(PDO $pdo, int $boardingId, array $valid, array &$saved): void
{
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $ins = $pdo->prepare('INSERT INTO boarding_images (boarding_id, image_path) VALUES (?, ?)');

    foreach ($valid as [$file, $ext]) {
        $name = bin2hex(random_bytes(16)) . '.' . $ext;   // safe, unguessable name
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
            throw new RuntimeException('Could not save an uploaded image.');
        }
        @chmod(UPLOAD_DIR . $name, 0644);
        $saved[] = $name;
        $ins->execute([$boardingId, $name]);
    }
}

function delete_image_files(array $names): void
{
    foreach ($names as $n) {
        $path = UPLOAD_DIR . basename((string)$n);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}