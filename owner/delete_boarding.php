<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/boarding_manage.php';

$user = require_role(['owner', 'admin']);
$back = $user['role'] === 'admin' ? 'admin/boardings.php' : 'owner/my_boardings.php';

require_post_csrf($back);

$id = (int)($_POST['boarding_id'] ?? 0);
$b  = $id > 0 ? get_boarding($id) : null;

if (!$b) {
    flash('error', 'Advertisement not found.');
    redirect($back);
}

// Ownership check
if ($user['role'] === 'owner' && (int)$b['owner_id'] !== $user['user_id']) {
    log_activity($user['user_id'], 'UNAUTHORIZED', 'Tried to delete boarding #' . $id);
    flash('error', 'You can only delete your own advertisements.');
    redirect($back);
}

$files = array_column(get_boarding_images($id), 'image_path');

// ON DELETE CASCADE removes images, facility links and reviews from the database
$stmt = getDB()->prepare('DELETE FROM boardings WHERE boarding_id = ?');
$stmt->execute([$id]);

delete_image_files($files);

log_activity($user['user_id'], 'BOARDING_DELETE', 'Deleted boarding #' . $id . ': ' . $b['boarding_name']);
flash('success', 'Advertisement "' . $b['boarding_name'] . '" was deleted.');
redirect($back);