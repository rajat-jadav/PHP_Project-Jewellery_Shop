<?php
require_once __DIR__ . '/config/config.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name, b.name AS branch_name, b.id AS branch_id
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.id
                        LEFT JOIN branches b ON p.branch_id = b.id
                        WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    flash('error', 'Product not found.');
    redirect('/products.php');
}

$pageTitle = $product['name'];

// images
$images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$images->execute([$id]);
$images = $images->fetchAll();

// materials
$materials = $pdo->prepare("SELECT * FROM product_materials WHERE product_id = ?");
$materials->execute([$id]);
$materials = $materials->fetchAll();

// gemstones
$gemstones = $pdo->prepare("SELECT * FROM product_gemstones WHERE product_id = ?");
$gemstones->execute([$id]);
$gemstones = $gemstones->fetchAll();

// certificate
$cert = $pdo->prepare("SELECT * FROM certificates WHERE product_id = ?");
$cert->execute([$id]);
$cert = $cert->fetch();

// reviews
$reviews = $pdo->prepare("SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? AND r.status = 'visible' ORDER BY r.created_at DESC");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();
$rating = get_avg_rating($pdo, $id);

// is it wishlisted by current user?
$isWishlisted = false;
if (is_logged_in()) {
    $w = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $w->execute([current_user_id(), $id]);
    $isWishlisted = (bool) $w->fetch();
}

/* Handle new review submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    require_login();
    $ratingVal = (int) $_POST['rating'];
    $text = clean($_POST['review']);
    if ($ratingVal >= 1 && $ratingVal <= 5) {
        $ins = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
        $ins->execute([$id, current_user_id(), $ratingVal, $text]);
        flash('success', 'Thanks for your review!');
        redirect('/product-details.php?id=' . $id);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
  <!-- LEFT: Images / 3D -->
  <div class="col-md-6">
    <ul class="nav nav-tabs" id="mediaTabs">
      <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#photosTab">Photos</button></li>
      <?php if ($product['model_3d']): ?>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#model3dTab">3D Preview</button></li>
      <?php endif; ?>
    </ul>
    <div class="tab-content border border-top-0 p-2">
      <div class="tab-pane fade show active" id="photosTab">
        <div id="productCarousel" class="carousel slide">
          <div class="carousel-inner">
            <?php if (empty($images)): ?>
              <div class="carousel-item active">
                <img src="https://via.placeholder.com/500x400?text=Jewellery" class="d-block w-100 rounded">
              </div>
            <?php else: foreach ($images as $i => $img): ?>
              <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= UPLOAD_URL ?>/products/<?= clean($img['image_path']) ?>" class="d-block w-100 rounded" style="max-height:450px;object-fit:cover;">
              </div>
            <?php endforeach; endif; ?>
          </div>
          <?php if (count($images) > 1): ?>
            <button class="carousel-control-prev" data-bs-target="#productCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
            <button class="carousel-control-next" data-bs-target="#productCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($product['model_3d']): ?>
        <div class="tab-pane fade" id="model3dTab">
          <model-viewer id="productModelViewer" src="<?= UPLOAD_URL ?>/models/<?= clean($product['model_3d']) ?>"
                        camera-controls auto-rotate shadow-intensity="1" ar
                        style="width:100%;height:420px;background:#f8f8f8;border-radius:.375rem;">
          </model-viewer>

          <div class="d-flex align-items-center gap-2 mt-3 flex-wrap" id="bgSwatches">
            <span class="small text-muted me-1">Background:</span>
            <button type="button" class="bg-swatch active" data-color="#f8f8f8" style="background:#f8f8f8" title="Light grey"></button>
            <button type="button" class="bg-swatch" data-color="#ffffff" style="background:#ffffff" title="White"></button>
            <button type="button" class="bg-swatch" data-color="#201c18" style="background:#201c18" title="Black"></button>
            <button type="button" class="bg-swatch" data-color="#2b2f3a" style="background:#2b2f3a" title="Navy"></button>
            <button type="button" class="bg-swatch" data-color="#e9d6b8" style="background:#e9d6b8" title="Gold"></button>
            <label class="bg-swatch bg-swatch-custom" title="Custom color">
              <input type="color" id="bgColorPicker" value="#f8f8f8">
            </label>
          </div>

          <p class="small text-muted mt-2">Drag to rotate &bull; Scroll/pinch to zoom</p>
        </div>
        <script>
        (function () {
          var viewer = document.getElementById('productModelViewer');
          var picker = document.getElementById('bgColorPicker');
          var swatches = document.querySelectorAll('#bgSwatches .bg-swatch[data-color]');
          if (!viewer) return;

          function setBackground(color) {
            viewer.style.backgroundColor = color;
          }

          swatches.forEach(function (btn) {
            btn.addEventListener('click', function () {
              setBackground(btn.dataset.color);
              swatches.forEach(function (b) { b.classList.remove('active'); });
              btn.classList.add('active');
              if (picker) picker.value = btn.dataset.color;
            });
          });

          if (picker) {
            picker.addEventListener('input', function () {
              setBackground(picker.value);
              swatches.forEach(function (b) { b.classList.remove('active'); });
            });
          }
        })();
        </script>
      <?php endif; ?>
    </div>

    <?php if ($product['tryon_type'] !== 'none' && $product['tryon_asset']): ?>
      <a href="<?= BASE_URL ?>/tryon.php?product_id=<?= $product['id'] ?>" class="btn btn-outline-dark w-100 mt-3">
        <i class="bi bi-camera"></i> Virtual Try-On
      </a>
    <?php endif; ?>
  </div>

  <!-- RIGHT: Details -->
  <div class="col-md-6">
    <span class="badge bg-<?= status_badge_class($product['status']) ?> mb-2"><?= ucfirst($product['status']) ?></span>
    <h3><?= clean($product['name']) ?></h3>
    <p class="text-muted">SKU: <?= clean($product['sku']) ?> &bull; <?= clean($product['category_name']) ?></p>

    <p class="mb-1">
      <?php if ($rating['total'] > 0): ?>
        <span class="star-rating"><i class="bi bi-star-fill"></i></span> <?= $rating['avg'] ?>/5 (<?= $rating['total'] ?> reviews)
      <?php else: ?>
        <span class="text-muted small">No reviews yet</span>
      <?php endif; ?>
    </p>

    <h4 class="text-dark mb-3">₹<?= number_format($product['final_price'], 2) ?></h4>

    <p><?= nl2br(clean($product['description'])) ?></p>

    <table class="table table-sm">
      <?php if ($product['weight']): ?><tr><th>Weight</th><td><?= $product['weight'] ?> g</td></tr><?php endif; ?>
      <?php foreach ($materials as $m): ?>
        <tr><th>Material</th><td><?= clean($m['material_name']) ?> (<?= clean($m['purity']) ?>, <?= $m['weight'] ?> g)</td></tr>
      <?php endforeach; ?>
      <?php foreach ($gemstones as $g): ?>
        <tr><th>Gemstone</th><td><?= clean($g['gemstone_name']) ?> - <?= $g['carat'] ?> ct x<?= $g['quantity'] ?></td></tr>
      <?php endforeach; ?>
      <tr><th>Available At</th><td><?= clean($product['branch_name']) ?></td></tr>
      <?php if ($cert): ?>
        <tr><th>Certificate</th><td><?= clean($cert['certificate_no']) ?> <a href="<?= BASE_URL ?>/certificate-verify.php?cert=<?= clean($cert['certificate_no']) ?>">(Verify)</a></td></tr>
      <?php endif; ?>
    </table>

    <div class="d-flex gap-2 mt-3">
      <?php if ($product['status'] === 'available'): ?>
        <a href="<?= BASE_URL ?>/appointment.php?product_id=<?= $product['id'] ?>" class="btn btn-dark flex-fill">
          <i class="bi bi-calendar-check"></i> Book Appointment
        </a>
      <?php else: ?>
        <button class="btn btn-secondary flex-fill" disabled>Currently <?= ucfirst($product['status']) ?></button>
      <?php endif; ?>

      <form method="post" action="<?= BASE_URL ?>/wishlist.php" class="flex-fill">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="hidden" name="redirect_to" value="/product-details.php?id=<?= $product['id'] ?>">
        <button type="submit" name="toggle_wishlist" class="btn btn-outline-danger w-100">
          <i class="bi bi-heart<?= $isWishlisted ? '-fill' : '' ?>"></i> <?= $isWishlisted ? 'Wishlisted' : 'Add to Wishlist' ?>
        </button>
      </form>
    </div>

    <a href="<?= BASE_URL ?>/compare.php?add=<?= $product['id'] ?>" class="btn btn-outline-secondary btn-sm w-100 mt-2">
      <i class="bi bi-arrow-left-right"></i> Add to Compare
    </a>
  </div>
</div>

<!-- Reviews -->
<section class="mt-5">
  <h5>Customer Reviews</h5>
  <?php if (is_logged_in()): ?>
    <form method="post" class="card p-3 mb-3">
      <label class="form-label small">Your Rating</label>
      <select name="rating" class="form-select form-select-sm mb-2" style="max-width:150px;" required>
        <option value="">Select</option>
        <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?> Star</option><?php endfor; ?>
      </select>
      <textarea name="review" class="form-control form-control-sm mb-2" rows="2" placeholder="Write your review..." required></textarea>
      <button type="submit" name="submit_review" class="btn btn-dark btn-sm" style="max-width:150px;">Submit Review</button>
    </form>
  <?php else: ?>
    <p class="small text-muted"><a href="<?= BASE_URL ?>/auth/login.php">Login</a> to write a review.</p>
  <?php endif; ?>

  <?php foreach ($reviews as $r): ?>
    <div class="border-bottom py-2">
      <strong><?= clean($r['user_name']) ?></strong>
      <span class="star-rating">
        <?php for ($i = 0; $i < $r['rating']; $i++): ?><i class="bi bi-star-fill"></i><?php endfor; ?>
      </span>
      <p class="mb-1"><?= clean($r['review']) ?></p>
      <?php if ($r['admin_reply']): ?>
        <div class="bg-light p-2 rounded small"><strong>Store reply:</strong> <?= clean($r['admin_reply']) ?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (empty($reviews)): ?><p class="text-muted small">No reviews yet. Be the first!</p><?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>