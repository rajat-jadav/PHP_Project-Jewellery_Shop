<?php
$pageTitle = 'Edit Product';
require_once __DIR__ . '/includes/admin_header.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    flash('error', 'Product not found.');
    redirect('/admin/products.php');
}

$categories = $pdo->query("SELECT * FROM categories WHERE status='active'")->fetchAll();
$collections = $pdo->query("SELECT * FROM collections WHERE status='active'")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches WHERE status='active'")->fetchAll();

$materials = $pdo->prepare("SELECT * FROM product_materials WHERE product_id = ?");
$materials->execute([$id]);
$materials = $materials->fetchAll();

$gemstones = $pdo->prepare("SELECT * FROM product_gemstones WHERE product_id = ?");
$gemstones->execute([$id]);
$gemstones = $gemstones->fetchAll();

$images = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
$images->execute([$id]);
$images = $images->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $categoryId = (int) $_POST['category_id'];
    $collectionId = $_POST['collection_id'] ? (int) $_POST['collection_id'] : null;
    $branchId = (int) $_POST['branch_id'];
    $occasion = clean($_POST['occasion']);
    $gender = $_POST['gender'];
    $description = clean($_POST['description']);
    $weight = (float) $_POST['weight'];
    $basePrice = (float) $_POST['base_price'];
    $makingCharges = (float) $_POST['making_charges'];
    $gstPercent = (float) $_POST['gst_percent'];
    $discount = (float) $_POST['discount'];
    $tryonType = $_POST['tryon_type'];
    $finalPrice = calculate_final_price($basePrice, $makingCharges, $gstPercent, $discount);

    $thumbnail = handle_file_upload('thumbnail', 'products', ['jpg','jpeg','png','webp']) ?? $product['thumbnail'];
    $model3d = handle_file_upload('model_3d', 'models', ['glb']) ?? $product['model_3d'];
    $tryonAsset = handle_file_upload('tryon_asset', 'tryon', ['png']) ?? $product['tryon_asset'];
    $newGalleryImages = handle_multi_file_upload('gallery_images', 'products', ['jpg','jpeg','png','webp']);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE products SET name=?, category_id=?, collection_id=?, branch_id=?, occasion=?, gender=?,
            description=?, weight=?, base_price=?, making_charges=?, gst_percent=?, discount=?, final_price=?,
            thumbnail=?, model_3d=?, tryon_type=?, tryon_asset=? WHERE id=?");
        $stmt->execute([$name, $categoryId, $collectionId, $branchId, $occasion, $gender, $description, $weight,
            $basePrice, $makingCharges, $gstPercent, $discount, $finalPrice, $thumbnail, $model3d, $tryonType, $tryonAsset, $id]);

        foreach ($newGalleryImages as $img) {
            $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)")->execute([$id, $img]);
        }

        // Replace materials
        $pdo->prepare("DELETE FROM product_materials WHERE product_id = ?")->execute([$id]);
        if (!empty($_POST['material_name'])) {
            foreach ($_POST['material_name'] as $i => $matName) {
                if (trim($matName) === '') continue;
                $pdo->prepare("INSERT INTO product_materials (product_id, material_name, purity, weight) VALUES (?,?,?,?)")
                    ->execute([$id, clean($matName), clean($_POST['purity'][$i]), (float) $_POST['material_weight'][$i]]);
            }
        }

        // Replace gemstones
        $pdo->prepare("DELETE FROM product_gemstones WHERE product_id = ?")->execute([$id]);
        if (!empty($_POST['gemstone_name'])) {
            foreach ($_POST['gemstone_name'] as $i => $gemName) {
                if (trim($gemName) === '') continue;
                $pdo->prepare("INSERT INTO product_gemstones (product_id, gemstone_name, carat, quantity) VALUES (?,?,?,?)")
                    ->execute([$id, clean($gemName), (float) $_POST['carat'][$i], (int) $_POST['quantity'][$i]]);
            }
        }

        $pdo->commit();
        log_activity($pdo, "Edited product '$name' (#$id)");
        flash('success', 'Product updated successfully.');
        redirect('/admin/products.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Failed to update: ' . $e->getMessage());
    }
}
?>

<form method="post" enctype="multipart/form-data" class="card p-4">
  <input type="hidden" name="id" value="<?= $product['id'] ?>">
  <h5 class="mb-3">Basic Information</h5>
  <div class="row g-3 mb-3">
    <div class="col-md-6"><label class="form-label">Product Name</label><input type="text" name="name" class="form-control" value="<?= clean($product['name']) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Category</label>
      <select name="category_id" class="form-select" required>
        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $product['category_id'] ? 'selected' : '' ?>><?= clean($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Collection</label>
      <select name="collection_id" class="form-select">
        <option value="">None</option>
        <?php foreach ($collections as $c): ?><option value="<?= $c['id'] ?>" <?= $c['id'] == $product['collection_id'] ? 'selected' : '' ?>><?= clean($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Branch</label>
      <select name="branch_id" class="form-select" required>
        <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>" <?= $b['id'] == $product['branch_id'] ? 'selected' : '' ?>><?= clean($b['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Occasion</label><input type="text" name="occasion" class="form-control" value="<?= clean($product['occasion']) ?>"></div>
    <div class="col-md-3"><label class="form-label">Gender</label>
      <select name="gender" class="form-select">
        <?php foreach (['unisex','women','men'] as $g): ?><option value="<?= $g ?>" <?= $product['gender'] === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Weight (g)</label><input type="number" step="0.01" name="weight" class="form-control" value="<?= $product['weight'] ?>"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"><?= clean($product['description']) ?></textarea></div>
  </div>

  <h5 class="mb-3">Materials</h5>
  <div id="materialsWrap">
    <?php if (empty($materials)) $materials = [['material_name'=>'','purity'=>'','weight'=>'']]; ?>
    <?php foreach ($materials as $m): ?>
      <div class="row g-2 mb-2 material-row">
        <div class="col-md-4"><input type="text" name="material_name[]" class="form-control form-control-sm" value="<?= clean($m['material_name']) ?>" placeholder="Material"></div>
        <div class="col-md-4"><input type="text" name="purity[]" class="form-control form-control-sm" value="<?= clean($m['purity']) ?>" placeholder="Purity"></div>
        <div class="col-md-3"><input type="number" step="0.01" name="material_weight[]" class="form-control form-control-sm" value="<?= clean($m['weight']) ?>" placeholder="Weight"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.material-row').remove()">×</button></div>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addMaterialRow()">+ Add Material</button>

  <h5 class="mb-3">Gemstones</h5>
  <div id="gemstonesWrap">
    <?php if (empty($gemstones)) $gemstones = [['gemstone_name'=>'','carat'=>'','quantity'=>1]]; ?>
    <?php foreach ($gemstones as $g): ?>
      <div class="row g-2 mb-2 gemstone-row">
        <div class="col-md-4"><input type="text" name="gemstone_name[]" class="form-control form-control-sm" value="<?= clean($g['gemstone_name']) ?>" placeholder="Gemstone"></div>
        <div class="col-md-3"><input type="number" step="0.01" name="carat[]" class="form-control form-control-sm" value="<?= clean($g['carat']) ?>" placeholder="Carat"></div>
        <div class="col-md-3"><input type="number" name="quantity[]" class="form-control form-control-sm" value="<?= clean($g['quantity']) ?>" placeholder="Qty"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.gemstone-row').remove()">×</button></div>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addGemstoneRow()">+ Add Gemstone</button>

  <h5 class="mb-3">Pricing</h5>
  <div class="row g-3 mb-3">
    <div class="col-md-3"><label class="form-label">Base Price (₹)</label><input type="number" step="0.01" name="base_price" id="base_price" class="form-control price-input" value="<?= $product['base_price'] ?>" required></div>
    <div class="col-md-3"><label class="form-label">Making Charges (₹)</label><input type="number" step="0.01" name="making_charges" id="making_charges" class="form-control price-input" value="<?= $product['making_charges'] ?>"></div>
    <div class="col-md-3"><label class="form-label">GST (%)</label><input type="number" step="0.01" name="gst_percent" id="gst_percent" class="form-control price-input" value="<?= $product['gst_percent'] ?>"></div>
    <div class="col-md-3"><label class="form-label">Discount (₹)</label><input type="number" step="0.01" name="discount" id="discount" class="form-control price-input" value="<?= $product['discount'] ?>"></div>
  </div>
  <p class="mb-3">Final Price (auto-calculated): <strong id="finalPriceDisplay">₹<?= number_format($product['final_price'], 2) ?></strong></p>

  <h5 class="mb-3">Media</h5>
  <?php if (!empty($images)): ?>
    <div class="d-flex gap-2 mb-2 flex-wrap">
      <?php foreach ($images as $img): ?><img src="<?= UPLOAD_URL ?>/products/<?= clean($img['image_path']) ?>" width="70" height="70" style="object-fit:cover" class="rounded border"><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Replace Thumbnail</label><input type="file" name="thumbnail" class="form-control" accept="image/*"></div>
    <div class="col-md-4"><label class="form-label">Add More Gallery Images</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
    <div class="col-md-4"><label class="form-label">Replace 3D Model (.glb)</label><input type="file" name="model_3d" class="form-control" accept=".glb"></div>
  </div>

  <h5 class="mb-3">Virtual Try-On</h5>
  <div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Try-On Type</label>
      <select name="tryon_type" class="form-select">
        <?php foreach (['none','ring','necklace','earring','bracelet'] as $t): ?>
          <option value="<?= $t ?>" <?= $product['tryon_type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-8"><label class="form-label">Replace Try-On Asset (PNG)</label><input type="file" name="tryon_asset" class="form-control" accept=".png"></div>
  </div>

  <button class="btn btn-dark">Update Product</button>
</form>

<script>
function addMaterialRow() {
  const wrap = document.getElementById('materialsWrap');
  const row = wrap.firstElementChild.cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = '');
  wrap.appendChild(row);
}
function addGemstoneRow() {
  const wrap = document.getElementById('gemstonesWrap');
  const row = wrap.firstElementChild.cloneNode(true);
  row.querySelectorAll('input').forEach(i => i.value = i.name === 'quantity[]' ? '1' : '');
  wrap.appendChild(row);
}
function recalcFinalPrice() {
  const base = parseFloat(document.getElementById('base_price').value) || 0;
  const making = parseFloat(document.getElementById('making_charges').value) || 0;
  const gst = parseFloat(document.getElementById('gst_percent').value) || 0;
  const discount = parseFloat(document.getElementById('discount').value) || 0;
  const subtotal = base + making;
  const final = subtotal + (subtotal * gst / 100) - discount;
  document.getElementById('finalPriceDisplay').textContent = '₹' + Math.max(final, 0).toFixed(2);
}
document.querySelectorAll('.price-input').forEach(el => el.addEventListener('input', recalcFinalPrice));
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
