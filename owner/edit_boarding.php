<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/boarding_manage.php';

$user = require_role('owner');
$pdo  = getDB();

$id = (int)($_GET['id'] ?? 0);
$b  = $id > 0 ? get_boarding($id) : null;

// Ownership check: owners can only edit their own advertisements
if (!$b || (int)$b['owner_id'] !== $user['user_id']) {
    flash('error', 'Advertisement not found, or you do not have permission to edit it.');
    redirect('owner/my_boardings.php');
}

$facilities     = get_all_facilities();
$existingImages = get_boarding_images($id);
$existingMap    = array_column($existingImages, 'image_path', 'image_id');
$errors         = [];
$removeIds      = [];
$values         = [
    'boarding_name'    => $b['boarding_name'],
    'description'      => $b['description'],
    'location'         => $b['location'],
    'full_address'     => $b['full_address'],
    'price_per_person' => (string)(float)$b['price_per_person'],
    'available_beds'   => (string)(int)$b['available_beds'],
    'contact_number'   => $b['contact_number'],
    'facilities'       => get_boarding_facility_ids($id),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        flash('error', 'The upload was too large for the server. Try fewer or smaller photos.');
    } elseif (!verify_csrf()) {
        flash('error', 'Security check failed. Please try again.');
    } else {
        [$errors, $clean] = validate_boarding_input($_POST, $facilities);
        $values = $clean;

        // Only image IDs that belong to THIS boarding can be removed
        $removeIds = [];
        foreach ((array)($_POST['remove_images'] ?? []) as $rid) {
            $rid = (int)$rid;
            if (isset($existingMap[$rid])) {
                $removeIds[$rid] = $rid;
            }
        }
        $removeIds = array_values($removeIds);

        $files = normalize_files($_FILES['images'] ?? null);
        $valid = [];
        $keep  = count($existingImages) - count($removeIds);

        if ($keep + count($files) < 1) {
            $errors['images'] = 'An advertisement needs at least one photo.';
        } elseif ($keep + count($files) > MAX_IMAGES) {
            $errors['images'] = 'You can have up to ' . MAX_IMAGES . ' photos in total (you would have ' . ($keep + count($files)) . ').';
        } elseif ($files) {
            [$imgErrors, $valid] = check_uploads($files);
            if ($imgErrors) {
                $errors['images'] = implode(' ', $imgErrors);
            }
        }

        if (!$errors) {
            $saved = [];
            try {
                $pdo->beginTransaction();

                // owner_id in WHERE = second layer of protection
                $stmt = $pdo->prepare(
                    'UPDATE boardings SET boarding_name = ?, description = ?, location = ?, full_address = ?,
                            price_per_person = ?, available_beds = ?, contact_number = ?
                     WHERE boarding_id = ? AND owner_id = ?'
                );
                $stmt->execute([
                    $clean['boarding_name'], $clean['description'], $clean['location'], $clean['full_address'],
                    (float)$clean['price_per_person'], (int)$clean['available_beds'], $clean['contact_number'],
                    $id, $user['user_id'],
                ]);

                $pdo->prepare('DELETE FROM boarding_facilities WHERE boarding_id = ?')->execute([$id]);
                $fIns = $pdo->prepare('INSERT INTO boarding_facilities (boarding_id, facility_id) VALUES (?, ?)');
                foreach ($clean['facilities'] as $fid) {
                    $fIns->execute([$id, $fid]);
                }

                $imgDel = $pdo->prepare('DELETE FROM boarding_images WHERE image_id = ? AND boarding_id = ?');
                foreach ($removeIds as $rid) {
                    $imgDel->execute([$rid, $id]);
                }

                store_images($pdo, $id, $valid, $saved);
                $pdo->commit();

                // Delete the old files only after the database change succeeded
                delete_image_files(array_map(fn($rid) => $existingMap[$rid], $removeIds));

                log_activity($user['user_id'], 'BOARDING_EDIT', 'Edited boarding #' . $id);
                flash('success', 'Advertisement updated successfully.');
                redirect('owner/my_boardings.php');
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                delete_image_files($saved);
                flash('error', 'Something went wrong while saving. Please try again.');
            }
        } else {
            flash('error', 'Please fix the highlighted fields. You will need to select new photos again.');
        }
    }
}

$pageTitle    = 'Edit Boarding';
$extraScripts = ['assets/js/boarding_form.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <h1>Edit Boarding</h1>
        <p><?= e($b['boarding_name']) ?></p>
    </div>
</section>
<section class="page-section">
    <div class="container narrow">
        <?php
        $mode       = 'edit';
        $formAction = url('owner/edit_boarding.php?id=' . $id);
        include __DIR__ . '/../includes/boarding_form.php';
        ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>