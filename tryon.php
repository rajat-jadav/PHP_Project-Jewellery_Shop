<?php
require_once __DIR__ . '/config/config.php';

$productId = (int) ($_GET['product_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product || $product['tryon_type'] === 'none' || !$product['tryon_asset']) {
    flash('error', $productId ? 'Virtual try-on is not available for this product.' : 'Choose a product first, then use its "Try On" button.');
    redirect($productId ? '/product-details.php?id=' . $productId : '/products.php');
}

$pageTitle = 'Virtual Try-On - ' . $product['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-8">
    <h4 class="mb-1">Virtual Try-On</h4>
    <p class="text-muted"><?= clean($product['name']) ?> &bull; Type: <?= ucfirst($product['tryon_type']) ?></p>

    <ul class="nav nav-pills mb-3">
      <li class="nav-item"><button class="nav-link active" id="modeLiveBtn" onclick="setMode('live')">Live Camera</button></li>
      <li class="nav-item"><button class="nav-link" id="modeUploadBtn" onclick="setMode('upload')">Upload Photo</button></li>
    </ul>

    <div id="liveModeUI">
      <button id="startCamBtn" class="btn btn-dark mb-2"><i class="bi bi-camera-video"></i> Start Camera</button>
      <button id="switchCamBtn" class="btn btn-outline-secondary mb-2 d-none"><i class="bi bi-arrow-repeat"></i> Switch Camera</button>
    </div>

    <?php if ($product['tryon_type'] === 'ring'): ?>
    <div class="mb-3">
      <label class="form-label small text-muted mb-1 d-block">Try on which finger?</label>
      <div class="btn-group" role="group" aria-label="Finger selection">
        <button type="button" class="btn btn-outline-dark btn-sm finger-btn" data-finger="thumb" onclick="setFinger('thumb')">Thumb</button>
        <button type="button" class="btn btn-outline-dark btn-sm finger-btn" data-finger="index" onclick="setFinger('index')">Index</button>
        <button type="button" class="btn btn-outline-dark btn-sm finger-btn" data-finger="middle" onclick="setFinger('middle')">Middle</button>
        <button type="button" class="btn btn-dark btn-sm finger-btn active" data-finger="ring" onclick="setFinger('ring')">Ring</button>
        <button type="button" class="btn btn-outline-dark btn-sm finger-btn" data-finger="pinky" onclick="setFinger('pinky')">Pinky</button>
      </div>
    </div>
    <?php endif; ?>

    <div id="uploadModeUI" class="d-none mb-2">
      <input type="file" id="photoInput" accept="image/*" class="form-control">
    </div>

    <div class="position-relative d-inline-block">
      <video id="tryon-video" autoplay playsinline muted class="d-none"></video>
      <canvas id="tryon-canvas"></canvas>
    </div>

    <p id="statusMsg" class="small text-muted mt-2">Click "Start Camera" and allow camera access. Position your <?= $product['tryon_type'] === 'ring' || $product['tryon_type'] === 'bracelet' ? 'hand' : 'face' ?> clearly in frame.</p>

    <a href="<?= BASE_URL ?>/product-details.php?id=<?= $product['id'] ?>" class="btn btn-outline-dark btn-sm">&larr; Back to Product</a>
  </div>
</div>

<script type="module">
  window.TRYON_CONFIG = {
    type: <?= json_encode($product['tryon_type']) ?>,
    assetUrl: <?= json_encode(UPLOAD_URL . '/tryon/' . $product['tryon_asset']) ?>
  };
</script>
<script type="module" src="<?= BASE_URL ?>/assets/js/tryon.js?v=<?= filemtime(__DIR__ . '/assets/js/tryon.js') ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>