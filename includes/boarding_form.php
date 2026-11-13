<?php
/**
 * Shared add/edit form.
 * Expects: $mode ('add'|'edit'), $formAction, $values, $errors, $facilities,
 *          $existingImages (array), $removeIds (array)
 */
$existingImages = $existingImages ?? [];
$removeIds      = $removeIds ?? [];
$isEdit         = $mode === 'edit';
?>
<form action="<?= e($formAction) ?>" method="post" enctype="multipart/form-data"
      class="form-card" id="boardingForm" data-max-images="<?= MAX_IMAGES ?>"
      data-max-bytes="<?= MAX_IMAGE_SIZE ?>" novalidate>
    <?= csrf_field() ?>

    <div class="form-section">
        <h3><i class="fa-solid fa-circle-info"></i> Basic details</h3>

        <div class="form-group">
            <label for="boarding_name">Boarding name</label>
            <input type="text" id="boarding_name" name="boarding_name" required minlength="3" maxlength="150"
                   value="<?= e($values['boarding_name']) ?>" placeholder="e.g. Campus View Boarding House"
                   class="<?= isset($errors['boarding_name']) ? 'has-error' : '' ?>">
            <?= field_error($errors, 'boarding_name') ?>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="6" required minlength="20" maxlength="2000"
                      placeholder="Describe the rooms, surroundings, rules, distance to the university..."
                      class="<?= isset($errors['description']) ? 'has-error' : '' ?>"><?= e($values['description']) ?></textarea>
            <small class="hint"><span id="descCount">0</span> / 2000 characters</small>
            <?= field_error($errors, 'description') ?>
        </div>
    </div>

    <div class="form-section">
        <h3><i class="fa-solid fa-location-dot"></i> Location</h3>
        <div class="form-row">
            <div class="form-group">
                <label for="location">Area</label>
                <input type="text" id="location" name="location" required maxlength="100" list="areaList"
                       value="<?= e($values['location']) ?>" placeholder="e.g. Katubedda"
                       class="<?= isset($errors['location']) ? 'has-error' : '' ?>">
                <datalist id="areaList">
                    <?php foreach (get_locations() as $loc): ?><option value="<?= e($loc) ?>"><?php endforeach; ?>
                </datalist>
                <?= field_error($errors, 'location') ?>
            </div>
            <div class="form-group">
                <label for="full_address">Full address</label>
                <input type="text" id="full_address" name="full_address" required minlength="5" maxlength="255"
                       value="<?= e($values['full_address']) ?>" placeholder="No. 12, Katubedda Road, Moratuwa"
                       class="<?= isset($errors['full_address']) ? 'has-error' : '' ?>">
                <?= field_error($errors, 'full_address') ?>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3><i class="fa-solid fa-tags"></i> Price and contact</h3>
        <div class="form-row form-row-3">
            <div class="form-group">
                <label for="price_per_person">Price per person (LKR)</label>
                <input type="number" id="price_per_person" name="price_per_person" required min="500" max="500000" step="100"
                       value="<?= e($values['price_per_person']) ?>" placeholder="9500"
                       class="<?= isset($errors['price_per_person']) ? 'has-error' : '' ?>">
                <?= field_error($errors, 'price_per_person') ?>
            </div>
            <div class="form-group">
                <label for="available_beds">Available beds</label>
                <input type="number" id="available_beds" name="available_beds" required min="0" max="100" step="1"
                       value="<?= e($values['available_beds']) ?>" placeholder="4"
                       class="<?= isset($errors['available_beds']) ? 'has-error' : '' ?>">
                <?= field_error($errors, 'available_beds') ?>
            </div>
            <div class="form-group">
                <label for="contact_number">Contact phone</label>
                <input type="tel" id="contact_number" name="contact_number" required maxlength="20"
                       value="<?= e($values['contact_number']) ?>" placeholder="0771234567"
                       class="<?= isset($errors['contact_number']) ? 'has-error' : '' ?>">
                <?= field_error($errors, 'contact_number') ?>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3><i class="fa-solid fa-list-check"></i> Facilities</h3>
        <div class="check-grid">
            <?php foreach ($facilities as $f): $fid = (int)$f['facility_id']; ?>
                <label class="check-item">
                    <input type="checkbox" name="facilities[]" value="<?= $fid ?>"
                           <?= in_array($fid, array_map('intval', $values['facilities']), true) ? 'checked' : '' ?>>
                    <span><i class="fa-solid <?= e(facility_icon($f['facility_name'])) ?>"></i> <?= e($f['facility_name']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-section">
        <h3><i class="fa-solid fa-images"></i> Photographs</h3>

        <?php if ($existingImages): ?>
            <p class="hint">Current photos. Tick <strong>Remove</strong> on any you want to delete.</p>
            <div class="img-grid" id="existingImages">
                <?php foreach ($existingImages as $img): $iid = (int)$img['image_id']; ?>
                    <label class="img-tile <?= in_array($iid, $removeIds, true) ? 'marked' : '' ?>">
                        <img src="<?= e(cover_url($img['image_path'])) ?>" alt="Boarding photo">
                        <span class="img-remove">
                            <input type="checkbox" name="remove_images[]" value="<?= $iid ?>"
                                   <?= in_array($iid, $removeIds, true) ? 'checked' : '' ?>> Remove
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-group">
            <label for="images"><?= $isEdit ? 'Add more photos' : 'Upload photos' ?></label>
            <input type="file" id="images" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                   <?= $isEdit ? '' : 'required' ?> class="file-input <?= isset($errors['images']) ? 'has-error' : '' ?>">
            <small class="hint">JPG, PNG or WEBP, max <?= MAX_IMAGE_SIZE / 1024 / 1024 ?> MB each, up to <?= MAX_IMAGES ?> photos in total. The first photo is the cover image.</small>
            <?= field_error($errors, 'images') ?>
            <small class="hint bad" id="imgMsg"></small>
        </div>
        <div class="img-grid" id="newPreview"></div>
    </div>

    <div class="form-actions">
        <a href="<?= url('owner/my_boardings.php') ?>" class="btn btn-outline btn-lg">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid <?= $isEdit ? 'fa-floppy-disk' : 'fa-bullhorn' ?>"></i>
            <?= $isEdit ? 'Save changes' : 'Publish advertisement' ?>
        </button>
    </div>
</form>