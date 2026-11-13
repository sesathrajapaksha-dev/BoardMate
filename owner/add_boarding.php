<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/boarding_manage.php';

$user       = require_role('owner');
$pdo        = getDB();
$facilities = get_all_facilities();
$errors     = [];
$values     = [
    'boarding_name' => '', 'description' => '', 'location' => '', 'full_address' => '',
    'price_per_person' => '', 'available_beds' => '', 'contact_number' => '', 'facilities' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // Total upload exceeded post_max_size, PHP discards everything
        flash('error', 'The upload was too large for the server. Try fewer or smaller photos.');
    } elseif (!verify_csrf()) {
        flash('error', 'Security check failed. Please try again.');
    } else {
        [$errors, $clean] = validate_boarding_input($_POST, $facilities);
        $values = $clean;

        $files = normalize_files($_FILES['images'] ?? null);
        $valid = [];
        if (!$files) {
            $errors['images'] = 'Please upload at least one photo.';
        } elseif (count($files) > MAX_IMAGES) {
            $errors['images'] = 'You can upload up to ' . MAX_IMAGES . ' photos.';
        } else {
            [$imgErrors, $valid] = check_uploads($files);
            if ($imgErrors) {
                $errors['images'] = implode(' ', $imgErrors);
            }
        }

        if (!$errors) {
            $saved = [];
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO boardings (owner_id, boarding_name, description, location, full_address,
                                            price_per_person, available_beds, contact_number)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $user['user_id'], $clean['boarding_name'], $clean['description'], $clean['location'],
                    $clean['full_address'], (float)$clean['price_per_person'], (int)$clean['available_beds'],
                    $clean['contact_number'],
                ]);
                $bid = (int)$pdo->lastInsertId();

                $fIns = $pdo->prepare('INSERT INTO boarding_facilities (boarding_id, facility_id) VALUES (?, ?)');
                foreach ($clean['facilities'] as $fid) {
                    $fIns->execute([$bid, $fid]);
                }

                store_images($pdo, $bid, $valid, $saved);
                $pdo->commit();

                log_activity($user['user_id'], 'BOARDING_ADD', 'Added boarding #' . $bid . ': ' . $clean['boarding_name']);
                flash('success', 'Your advertisement has been published!');
                redirect('owner/my_boardings.php');
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                delete_image_files($saved);
                flash('error', 'Something went wrong while saving. Please try again.');
            }
        } else {
            flash('error', 'Please fix the highlighted fields. You will need to select your photos again.');
        }
    }
}

$pageTitle    = 'Add New Boarding';
$extraScripts = ['assets/js/boarding_form.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <h1>Add New Boarding</h1>
        <p>Fill in the details below to publish your advertisement for students.</p>
    </div>
</section>
<section class="page-section">
    <div class="container narrow">
        <?php
        $mode           = 'add';
        $formAction     = url('owner/add_boarding.php');
        $existingImages = [];
        $removeIds      = [];
        include __DIR__ . '/../includes/boarding_form.php';
        ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>