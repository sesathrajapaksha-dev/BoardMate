<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/boarding_functions.php';

$filters   = parse_filters($_GET);
$perPage   = 9;
$total     = count_boardings($filters);
$pages     = max(1, (int)ceil($total / $perPage));
$page      = min($filters['page'], $pages);
$boardings = fetch_boardings($filters, $filters['sort'], $perPage, ($page - 1) * $perPage);
$locations = get_locations();

$hasFilters = $filters['q'] !== '' || $filters['location'] !== '' || $filters['min_price'] !== null
           || $filters['max_price'] !== null || $filters['beds'] > 0 || $filters['min_rating'] > 0;

/** Same filters, different page number. */
function page_link(int $p): string
{
    $params = $_GET;
    unset($params['page']);
    if ($p > 1) {
        $params['page'] = $p;
    }
    return '?' . http_build_query($params);
}

$pageTitle    = 'Browse Boardings';
$extraScripts = ['assets/js/browse.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <h1>Browse Boardings</h1>
        <p>Search and filter boarding places near the University of Moratuwa.</p>
    </div>
</section>

<section class="page-section browse-section">
    <div class="container">
        <form method="get" id="filterForm">

            <!-- Search + sort bar -->
            <div class="toolbar">
                <div class="toolbar-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" value="<?= e($filters['q']) ?>" maxlength="100"
                           placeholder="Search by name, area or keyword...">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </div>
                <div class="toolbar-sort">
                    <label for="sort"><i class="fa-solid fa-arrow-down-wide-short"></i></label>
                    <select name="sort" id="sort" data-autosubmit>
                        <?php foreach (SORT_LABELS as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-outline filter-toggle" id="filterToggle" aria-expanded="false" aria-controls="filters">
                    <i class="fa-solid fa-sliders"></i> Filters
                </button>
            </div>

            <div class="browse-layout">
                <!-- Filters -->
                <aside class="filters" id="filters">
                    <h3><i class="fa-solid fa-sliders"></i> Filters</h3>

                    <div class="form-group">
                        <label for="location">Location</label>
                        <select name="location" id="location">
                            <option value="">All locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= e($loc) ?>" <?= $filters['location'] === $loc ? 'selected' : '' ?>><?= e($loc) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price per person (LKR)</label>
                        <div class="price-range">
                            <input type="number" name="min_price" min="0" step="500" placeholder="Min"
                                   value="<?= $filters['min_price'] !== null ? e($filters['min_price']) : '' ?>" aria-label="Minimum price">
                            <span>to</span>
                            <input type="number" name="max_price" min="0" step="500" placeholder="Max"
                                   value="<?= $filters['max_price'] !== null ? e($filters['max_price']) : '' ?>" aria-label="Maximum price">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="beds">Available beds</label>
                        <select name="beds" id="beds">
                            <option value="0">Any</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= $filters['beds'] === $i ? 'selected' : '' ?>><?= $i ?>+ beds</option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="min_rating">Minimum rating</label>
                        <select name="min_rating" id="min_rating">
                            <option value="0">Any rating</option>
                            <?php foreach ([4, 3, 2] as $r): ?>
                                <option value="<?= $r ?>" <?= $filters['min_rating'] === $r ? 'selected' : '' ?>><?= $r ?> stars &amp; up</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= url('student/browse.php') ?>" class="btn btn-outline btn-block reset-btn"><i class="fa-solid fa-rotate-left"></i> Reset all</a>
                    <?php endif; ?>
                </aside>

                <!-- Results -->
                <div class="results">
                    <p class="result-count">
                        <strong><?= $total ?></strong> boarding<?= $total === 1 ? '' : 's' ?> found
                        <?php if ($filters['location'] !== ''): ?> in <strong><?= e($filters['location']) ?></strong><?php endif; ?>
                        <?php if ($filters['q'] !== ''): ?> for &ldquo;<?= e($filters['q']) ?>&rdquo;<?php endif; ?>
                    </p>

                    <?php if ($boardings): ?>
                        <div class="card-grid card-grid-browse">
                            <?php foreach ($boardings as $b): include __DIR__ . '/../includes/boarding_card.php'; endforeach; ?>
                        </div>

                        <?php if ($pages > 1): ?>
                            <nav class="pagination" aria-label="Pages">
                                <?php if ($page > 1): ?>
                                    <a href="<?= e(page_link($page - 1)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></a>
                                <?php endif; ?>
                                <?php for ($p = 1; $p <= $pages; $p++): ?>
                                    <?php if ($p === 1 || $p === $pages || abs($p - $page) <= 1): ?>
                                        <a href="<?= e(page_link($p)) ?>" class="<?= $p === $page ? 'current' : '' ?>"><?= $p ?></a>
                                    <?php elseif (abs($p - $page) === 2): ?>
                                        <span class="dots">&hellip;</span>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($page < $pages): ?>
                                    <a href="<?= e(page_link($page + 1)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-house-circle-xmark"></i>
                            <h3>No boardings match your search</h3>
                            <p>Try a different area, a higher budget or fewer filters.</p>
                            <a href="<?= url('student/browse.php') ?>" class="btn btn-primary">Clear filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>