<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
$pdo = getDB();
$user = current_user();

// ---- Read filters from GET ----
$q          = trim($_GET['q'] ?? '');
$categoryId = $_GET['category_id'] ?? '';
$areaId     = $_GET['area_id'] ?? '';
$condition  = $_GET['condition'] ?? '';
$minPrice   = $_GET['min_price'] ?? '';
$maxPrice   = $_GET['max_price'] ?? '';
$sortRaw    = $_GET['sort'] ?? 'newest';
$sort       = $sortRaw;
$radiusKm   = $_GET['radius_km'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));

if (array_key_exists('from_area_id', $_GET)) {
    $fromAreaId = $_GET['from_area_id'];
} else {
    $fromAreaId = ($user && !empty($user['area_id'])) ? (string)$user['area_id'] : '';
}

$nearestWarning = false;
if ($sort === 'nearest' && $fromAreaId === '') {
    $nearestWarning = true;
    $sort = 'newest';
}

// ---- Load categories & areas for filter dropdowns ----
$allCategories = $pdo->query(
    "SELECT category_id, category_name, icon FROM categories WHERE is_active = 1 ORDER BY category_name ASC"
)->fetchAll();

$allAreas = $pdo->query(
    "SELECT area_id, area_name, latitude, longitude FROM areas WHERE is_active = 1 ORDER BY area_name ASC"
)->fetchAll();

$areasById = [];
foreach ($allAreas as $a) {
    $areasById[$a['area_id']] = $a;
}

// ---- Compute distance map from reference area (if any) ----
$areaDistanceMap = []; // area_id => km (float) or null
if ($fromAreaId !== '' && isset($areasById[$fromAreaId])) {
    $fromArea = $areasById[$fromAreaId];
    foreach ($allAreas as $a) {
        $areaDistanceMap[$a['area_id']] = haversineDistanceKm(
            $fromArea['latitude'], $fromArea['longitude'],
            $a['latitude'], $a['longitude']
        );
    }
}

// ---- Build WHERE conditions ----
$conditions = ["p.status = 'Available'"];
$params = [];

if ($q !== '') {
    $conditions[] = "(p.title LIKE :kw OR p.description LIKE :kw)";
    $params[':kw'] = '%' . $q . '%';
}
if ($categoryId !== '' && ctype_digit((string)$categoryId)) {
    $conditions[] = "p.category_id = :category_id";
    $params[':category_id'] = (int)$categoryId;
}
if ($areaId !== '' && ctype_digit((string)$areaId)) {
    $conditions[] = "p.area_id = :area_id";
    $params[':area_id'] = (int)$areaId;
}
if ($condition !== '' && in_array($condition, ['New', 'Like New', 'Good', 'Fair'], true)) {
    $conditions[] = "p.item_condition = :item_condition";
    $params[':item_condition'] = $condition;
}
if ($minPrice !== '' && is_numeric($minPrice)) {
    $conditions[] = "p.price >= :min_price";
    $params[':min_price'] = (float)$minPrice;
}
if ($maxPrice !== '' && is_numeric($maxPrice)) {
    $conditions[] = "p.price <= :max_price";
    $params[':max_price'] = (float)$maxPrice;
}

$noResultsForcedByRadius = false;
if ($radiusKm !== '' && is_numeric($radiusKm) && $fromAreaId !== '' && isset($areasById[$fromAreaId])) {
    $allowedIds = [];
    foreach ($areaDistanceMap as $aid => $dist) {
        if ($dist !== null && $dist <= (float)$radiusKm) {
            $allowedIds[] = (int)$aid;
        }
    }
    if (empty($allowedIds)) {
        $noResultsForcedByRadius = true;
        $conditions[] = "1 = 0";
    } else {
        $placeholders = [];
        foreach ($allowedIds as $i => $aid) {
            $ph = ":radarea{$i}";
            $placeholders[] = $ph;
            $params[$ph] = $aid;
        }
        $conditions[] = "p.area_id IN (" . implode(',', $placeholders) . ")";
    }
}

$whereSql = implode(' AND ', $conditions);

// ---- Count total ----
$countSql = "SELECT COUNT(*) FROM products p WHERE {$whereSql}";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k => $v) {
    $countStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$countStmt->execute();
$totalItems = (int)$countStmt->fetchColumn();

$perPage = 12;
[$offset, $page, $totalPages] = paginate($totalItems, $perPage, $page);

// ---- Order by ----
switch ($sort) {
    case 'price_low':
        $orderSql = 'p.price ASC';
        break;
    case 'price_high':
        $orderSql = 'p.price DESC';
        break;
    case 'nearest':
        $sortableAreas = $areaDistanceMap;
        asort($sortableAreas);
        $orderedIds = [];
        foreach ($sortableAreas as $aid => $dist) {
            if ($dist !== null) $orderedIds[] = (int)$aid;
        }
        foreach ($areaDistanceMap as $aid => $dist) {
            if ($dist === null) $orderedIds[] = (int)$aid;
        }
        if (!empty($orderedIds)) {
            $orderSql = 'FIELD(p.area_id, ' . implode(',', $orderedIds) . ') ASC, p.created_at DESC';
        } else {
            $orderSql = 'p.created_at DESC';
        }
        break;
    default:
        $orderSql = 'p.created_at DESC';
}

// ---- Final query ----
$sql = "SELECT p.*, c.category_name, c.icon AS category_icon, a.area_name,
        (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.product_id ORDER BY pi.is_main DESC, pi.image_id ASC LIMIT 1) AS main_image
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        JOIN areas a ON p.area_id = a.area_id
        WHERE {$whereSql}
        ORDER BY {$orderSql}
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// ---- Favorites lookup for heart buttons ----
$favoriteIds = [];
if ($user) {
    $favStmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = :uid");
    $favStmt->execute([':uid' => $user['user_id']]);
    $favoriteIds = array_map('intval', $favStmt->fetchAll(PDO::FETCH_COLUMN));
}

$pageTitle = 'Browse Products';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

  <h2 class="fw-bold mb-4">Browse Products</h2>

  <form method="get" action="<?= BASE_URL ?>products.php" class="cs-filter-form bg-white p-3 rounded-3 shadow-sm mb-4">
    <div class="row g-2">
      <div class="col-md-3">
        <label class="form-label small mb-1">Keyword</label>
        <input type="text" class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Search title or description">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Category</label>
        <select class="form-select form-select-sm" name="category_id">
          <option value="">All Categories</option>
          <?php foreach ($allCategories as $cat): ?>
            <option value="<?= (int)$cat['category_id'] ?>" <?= ((string)$categoryId === (string)$cat['category_id']) ? 'selected' : '' ?>><?= e($cat['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Condition</label>
        <select class="form-select form-select-sm" name="condition">
          <option value="">Any Condition</option>
          <?php foreach (['New', 'Like New', 'Good', 'Fair'] as $c): ?>
            <option value="<?= e($c) ?>" <?= ($condition === $c) ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Located In</label>
        <select class="form-select form-select-sm" name="area_id">
          <option value="">All Areas</option>
          <?php foreach ($allAreas as $a): ?>
            <option value="<?= (int)$a['area_id'] ?>" <?= ((string)$areaId === (string)$a['area_id']) ? 'selected' : '' ?>><?= e($a['area_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label small mb-1">Min ৳</label>
        <input type="number" min="0" step="1" class="form-control form-control-sm" name="min_price" value="<?= e($minPrice) ?>">
      </div>
      <div class="col-md-1">
        <label class="form-label small mb-1">Max ৳</label>
        <input type="number" min="0" step="1" class="form-control form-control-sm" name="max_price" value="<?= e($maxPrice) ?>">
      </div>
    </div>
    <hr>
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">Distance From</label>
        <select class="form-select form-select-sm" name="from_area_id">
          <option value="">-- Select your area --</option>
          <?php foreach ($allAreas as $a): ?>
            <option value="<?= (int)$a['area_id'] ?>" <?= ((string)$fromAreaId === (string)$a['area_id']) ? 'selected' : '' ?>><?= e($a['area_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Within</label>
        <select class="form-select form-select-sm" name="radius_km">
          <option value="">Any distance</option>
          <?php foreach ([2, 5, 10, 20] as $r): ?>
            <option value="<?= $r ?>" <?= ((string)$radiusKm === (string)$r) ? 'selected' : '' ?>><?= $r ?> km</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Sort By</label>
        <select class="form-select form-select-sm" name="sort">
          <option value="newest" <?= $sortRaw === 'newest' ? 'selected' : '' ?>>Newest First</option>
          <option value="price_low" <?= $sortRaw === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_high" <?= $sortRaw === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
          <option value="nearest" <?= $sortRaw === 'nearest' ? 'selected' : '' ?>>Nearest First</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn cs-btn-primary btn-sm"><i class="bi bi-funnel"></i> Apply Filters</button>
        <a href="<?= BASE_URL ?>products.php" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </div>
  </form>

  <?php if ($nearestWarning): ?>
    <div class="alert alert-warning">Please select a "Distance From" area to sort by nearest first. Showing newest listings instead.</div>
  <?php endif; ?>

  <?php if ($noResultsForcedByRadius): ?>
    <div class="alert alert-info">No areas were found within that radius from the selected location.</div>
  <?php endif; ?>

  <p class="text-muted"><?= (int)$totalItems ?> result<?= $totalItems === 1 ? '' : 's' ?> found</p>

  <?php if (empty($products)): ?>
    <div class="text-center py-5">
      <i class="bi bi-search display-1 text-muted"></i>
      <p class="mt-3 text-muted">No products match your filters.</p>
      <a href="<?= BASE_URL ?>products.php" class="btn cs-btn-primary">Clear Filters</a>
    </div>
  <?php else: ?>
    <div class="row">
      <?php foreach ($products as $p): ?>
        <div class="col-md-4 col-lg-3 mb-4">
          <div class="card cs-product-card h-100 position-relative">
            <?php if ($user): ?>
              <div class="fav-btn-wrap">
                <form method="post" action="<?= BASE_URL ?>favorites.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="product_id" value="<?= (int)$p['product_id'] ?>">
                  <input type="hidden" name="redirect_to" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                  <?php if (in_array((int)$p['product_id'], $favoriteIds, true)): ?>
                    <button type="submit" class="btn btn-danger" title="Remove from favorites"><i class="bi bi-heart-fill"></i></button>
                  <?php else: ?>
                    <button type="submit" class="btn btn-outline-secondary" title="Add to favorites"><i class="bi bi-heart"></i></button>
                  <?php endif; ?>
                </form>
              </div>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$p['product_id'] ?>">
              <img src="<?= e(getProductMainImage($p['main_image'])) ?>" class="card-img-top cs-product-img" alt="<?= e($p['title']) ?>">
            </a>
            <div class="card-body d-flex flex-column">
              <span class="badge bg-secondary-subtle text-secondary-emphasis mb-1"><i class="bi bi-<?= e($p['category_icon'] ?: 'tag') ?> me-1"></i><?= e($p['category_name']) ?></span>
              <h6 class="card-title mb-1">
                <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$p['product_id'] ?>" class="text-decoration-none text-dark"><?= e($p['title']) ?></a>
              </h6>
              <p class="cs-price fw-bold mb-1"><?= formatPrice($p['price']) ?></p>
              <p class="small text-muted mb-1">
                <i class="bi bi-geo-alt"></i> <?= e($p['area_name']) ?>
                <?php if (isset($areaDistanceMap[$p['area_id']])): ?>
                  <br><span class="text-info"><?= e(formatDistance($areaDistanceMap[$p['area_id']])) ?></span>
                <?php endif; ?>
              </p>
              <span class="badge bg-light text-dark border mb-2"><?= e($p['item_condition']) ?></span>
              <a href="<?= BASE_URL ?>product-details.php?id=<?= (int)$p['product_id'] ?>" class="btn btn-sm cs-btn-primary mt-auto">View Details</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav aria-label="Product pagination">
        <ul class="pagination justify-content-center">
          <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= BASE_URL ?>products.php<?= buildQueryString(['page' => $page - 1]) ?>">Previous</a>
          </li>
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="<?= BASE_URL ?>products.php<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= BASE_URL ?>products.php<?= buildQueryString(['page' => $page + 1]) ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>