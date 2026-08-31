<?php
$pageTitle = 'Browse Jewellery';
require_once __DIR__ . '/includes/header.php';

$categories  = $pdo->query("SELECT * FROM categories WHERE status='active'")->fetchAll();
$collections = $pdo->query("SELECT * FROM collections WHERE status='active'")->fetchAll();
$materials   = $pdo->query("SELECT DISTINCT material_name FROM product_materials ORDER BY material_name")->fetchAll(PDO::FETCH_COLUMN);

// ---- Build dynamic filter query ----
$where  = ["p.status != 'hidden'"];
$params = [];

if (!empty($_GET['q'])) {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = '%' . $_GET['q'] . '%';
    $params[] = '%' . $_GET['q'] . '%';
}
if (!empty($_GET['category'])) {
    $where[] = "p.category_id = ?";
    $params[] = (int) $_GET['category'];
}
if (!empty($_GET['collection'])) {
    $where[] = "p.collection_id = ?";
    $params[] = (int) $_GET['collection'];
}
if (!empty($_GET['gender'])) {
    $where[] = "p.gender = ?";
    $params[] = $_GET['gender'];
}
if (!empty($_GET['occasion'])) {
    $where[] = "p.occasion LIKE ?";
    $params[] = '%' . $_GET['occasion'] . '%';
}
if (!empty($_GET['min_price'])) {
    $where[] = "p.final_price >= ?";
    $params[] = (float) $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where[] = "p.final_price <= ?";
    $params[] = (float) $_GET['max_price'];
}
if (!empty($_GET['material'])) {
    $where[] = "p.id IN (SELECT product_id FROM product_materials WHERE material_name = ?)";
    $params[] = $_GET['material'];
}

$sql = "SELECT p.* FROM products p WHERE " . implode(' AND ', $where) . " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="row">
  <!-- Filters -->
  <div class="col-md-3 mb-4">
    <form method="get" class="card p-3">
      <h6 class="mb-3">Filters</h6>
      <input type="hidden" name="q" value="<?= clean($_GET['q'] ?? '') ?>">

      <label class="form-label small">Category</label>
      <select name="category" class="form-select form-select-sm mb-3">
        <option value="">All</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (($_GET['category'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= clean($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label class="form-label small">Collection</label>
      <select name="collection" class="form-select form-select-sm mb-3">
        <option value="">All</option>
        <?php foreach ($collections as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (($_GET['collection'] ?? '') == $c['id']) ? 'selected' : '' ?>><?= clean($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label class="form-label small">Material</label>
      <select name="material" class="form-select form-select-sm mb-3">
        <option value="">All</option>
        <?php foreach ($materials as $m): ?>
          <option value="<?= clean($m) ?>" <?= (($_GET['material'] ?? '') == $m) ? 'selected' : '' ?>><?= clean($m) ?></option>
        <?php endforeach; ?>
      </select>

      <label class="form-label small">Gender</label>
      <select name="gender" class="form-select form-select-sm mb-3">
        <option value="">All</option>
        <option value="women" <?= (($_GET['gender'] ?? '') == 'women') ? 'selected' : '' ?>>Women</option>
        <option value="men" <?= (($_GET['gender'] ?? '') == 'men') ? 'selected' : '' ?>>Men</option>
        <option value="unisex" <?= (($_GET['gender'] ?? '') == 'unisex') ? 'selected' : '' ?>>Unisex</option>
      </select>

      <label class="form-label small">Occasion</label>
      <input type="text" name="occasion" class="form-control form-control-sm mb-3" placeholder="e.g. Wedding" value="<?= clean($_GET['occasion'] ?? '') ?>">

      <label class="form-label small">Price Range</label>
      <div class="d-flex gap-2 mb-3">
        <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min" value="<?= clean($_GET['min_price'] ?? '') ?>">
        <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max" value="<?= clean($_GET['max_price'] ?? '') ?>">
      </div>

      <button class="btn btn-dark btn-sm w-100">Apply Filters</button>
      <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Reset</a>
    </form>
  </div>

  <!-- Product Grid -->
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0"><?= count($products) ?> product(s) found</h5>
    </div>
    <div class="row g-4">
      <?php foreach ($products as $p): $rating = get_avg_rating($pdo, $p['id']); ?>
        <div class="col-6 col-md-4">
          <div class="card product-card h-100">
            <a href="<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>">
              <img src="<?= $p['thumbnail'] ? UPLOAD_URL . '/products/' . clean($p['thumbnail']) : 'https://via.placeholder.com/300x220?text=Jewellery' ?>" class="card-img-top" alt="<?= clean($p['name']) ?>">
            </a>
            <div class="card-body">
              <span class="badge bg-<?= status_badge_class($p['status']) ?> mb-1"><?= ucfirst($p['status']) ?></span>
              <h6 class="card-title mb-1"><?= clean($p['name']) ?></h6>
              <p class="small text-muted mb-1">
                <?php if ($rating['total'] > 0): ?>
                  <i class="bi bi-star-fill text-warning"></i> <?= $rating['avg'] ?> (<?= $rating['total'] ?>)
                <?php else: ?>
                  No reviews yet
                <?php endif; ?>
              </p>
              <p class="fw-bold mb-2">₹<?= number_format($p['final_price'], 2) ?></p>
              <a href="<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>" class="btn btn-outline-dark btn-sm w-100">View Details</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($products)): ?>
        <p class="text-muted">No products match your filters.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
