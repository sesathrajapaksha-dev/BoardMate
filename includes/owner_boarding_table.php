<?php
/**
 * Table of an owner's advertisements (used on the dashboard and on My Boardings).
 * Expects: $rows (from get_owner_boardings)
 */
?>
<div class="table-scroll">
    <table class="data-table">
        <thead>
            <tr>
                <th>Boarding</th>
                <th>Price</th>
                <th>Beds</th>
                <th>Rating</th>
                <th>Listed</th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row):
            $rid  = (int)$row['boarding_id'];
            $beds = (int)$row['available_beds'];
            $cnt  = (int)$row['review_count'];
        ?>
            <tr>
                <td>
                    <div class="dt-boarding">
                        <img src="<?= e(cover_url($row['cover_image'])) ?>" alt="">
                        <div>
                            <strong><?= e($row['boarding_name']) ?></strong>
                            <span class="muted small"><i class="fa-solid fa-location-dot"></i> <?= e($row['location']) ?></span>
                        </div>
                    </div>
                </td>
                <td><?= e(format_price($row['price_per_person'])) ?></td>
                <td>
                    <?php if ($beds > 0): ?>
                        <span class="pill pill-green"><?= $beds ?> available</span>
                    <?php else: ?>
                        <span class="pill pill-red">Full</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($cnt > 0): ?>
                        <strong><?= e(number_format((float)$row['avg_rating'], 1)) ?></strong>
                        <span class="muted small">(<?= $cnt ?>)</span>
                    <?php else: ?>
                        <span class="muted small">No reviews</span>
                    <?php endif; ?>
                </td>
                <td class="muted small"><?= e(date('M j, Y', strtotime($row['created_at']))) ?></td>
                <td class="col-actions">
                    <div class="row-actions">
                        <a href="<?= url('student/boarding_details.php?id=' . $rid) ?>" class="btn btn-outline btn-sm" title="View advertisement">
                            <i class="fa-solid fa-eye"></i> View
                        </a>
                        <a href="<?= url('owner/edit_boarding.php?id=' . $rid) ?>" class="btn btn-outline btn-sm" title="Edit advertisement">
                            <i class="fa-solid fa-pen"></i> Edit
                        </a>
                        <form action="<?= url('owner/delete_boarding.php') ?>" method="post" class="inline-form"
                              data-confirm="Delete &quot;<?= e($row['boarding_name']) ?>&quot;? Its photos and reviews will be removed too. This cannot be undone.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="boarding_id" value="<?= $rid ?>">
                            <button type="submit" class="btn btn-danger-outline btn-sm" title="Delete advertisement">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
