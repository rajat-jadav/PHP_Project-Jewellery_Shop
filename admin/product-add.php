<?php
$pageTitle = 'Add Product';
require_once __DIR__ . '/includes/admin_header.php';

$categories = $pdo->query("SELECT * FROM categories WHERE status='active'")->fetchAll();
$collections = $pdo->query("SELECT * FROM collections WHERE status='active'")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches WHERE status='active'")->fetchAll();

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
    $sku = generate_sku($pdo, $categoryId);

    // File uploads
    $thumbnail = handle_file_upload('thumbnail', 'products', ['jpg','jpeg','png','webp']);
    $galleryImages = handle_multi_file_upload('gallery_images', 'products', ['jpg','jpeg','png','webp']);
    $model3d = handle_file_upload('model_3d', 'models', ['glb']);
    $tryonAsset = handle_file_upload('tryon_asset', 'tryon', ['png']);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO products
            (sku, name, category_id, collection_id, branch_id, occasion, gender, description, weight,
             base_price, making_charges, gst_percent, discount, final_price, thumbnail, model_3d, tryon_type, tryon_asset, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'available')");
        $stmt->execute([$sku, $name, $categoryId, $collectionId, $branchId, $occasion, $gender, $description, $weight,
            $basePrice, $makingCharges, $gstPercent, $discount, $finalPrice, $thumbnail, $model3d, $tryonType, $tryonAsset]);
        $productId = $pdo->lastInsertId();

        foreach ($galleryImages as $img) {
            $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)")->execute([$productId, $img]);
        }

        // Materials
        if (!empty($_POST['material_name'])) {
            foreach ($_POST['material_name'] as $i => $matName) {
                if (trim($matName) === '') continue;
                $pdo->prepare("INSERT INTO product_materials (product_id, material_name, purity, weight) VALUES (?,?,?,?)")
                    ->execute([$productId, clean($matName), clean($_POST['purity'][$i]), (float) $_POST['material_weight'][$i]]);
            }
        }

        // Gemstones
        if (!empty($_POST['gemstone_name'])) {
            foreach ($_POST['gemstone_name'] as $i => $gemName) {
                if (trim($gemName) === '') continue;
                $pdo->prepare("INSERT INTO product_gemstones (product_id, gemstone_name, carat, quantity) VALUES (?,?,?,?)")
                    ->execute([$productId, clean($gemName), (float) $_POST['carat'][$i], (int) $_POST['quantity'][$i]]);
            }
        }

        // Auto-generate certificate
        $certNo = generate_certificate_number($pdo);
        $certFile = handle_file_upload('certificate_file', 'certificates', ['pdf','jpg','jpeg','png']);
        $pdo->prepare("INSERT INTO certificates (product_id, certificate_no, issue_date, file_path) VALUES (?, ?, CURDATE(), ?)")
            ->execute([$productId, $certNo, $certFile]);

        $pdo->commit();
        log_activity($pdo, "Added product '$name' (SKU: $sku), certificate $certNo generated");
        flash('success', "Product added successfully. Certificate $certNo generated automatically.");
        redirect('/admin/products.php');
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Failed to save product: ' . $e->getMessage());
    }
}
?>

<form method="post" enctype="multipart/form-data" class="card p-4">
  <h5 class="mb-3">Basic Information</h5>
  <div class="row g-3 mb-3">
    <div class="col-md-6"><label class="form-label">Product Name</label><input type="text" name="name" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Category</label>
      <select name="category_id" class="form-select" required>
        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= clean($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Collection</label>
      <select name="collection_id" class="form-select">
        <option value="">None</option>
        <?php foreach ($collections as $c): ?><option value="<?= $c['id'] ?>"><?= clean($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Branch</label>
      <select name="branch_id" class="form-select" required>
        <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= clean($b['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Occasion</label><input type="text" name="occasion" class="form-control" placeholder="Wedding, Festive..."></div>
    <div class="col-md-3"><label class="form-label">Gender</label>
      <select name="gender" class="form-select">
        <option value="unisex">Unisex</option><option value="women">Women</option><option value="men">Men</option>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Weight (g)</label><input type="number" step="0.01" name="weight" class="form-control"></div>
    <div class="col-12"><label class="form-label">Description</label><textarea name="description" rows="3" class="form-control"></textarea></div>
  </div>

  <h5 class="mb-3">Materials</h5>
  <div id="materialsWrap">
    <div class="row g-2 mb-2 material-row">
      <div class="col-md-4"><input type="text" name="material_name[]" class="form-control form-control-sm" placeholder="Material e.g. Gold"></div>
      <div class="col-md-4"><input type="text" name="purity[]" class="form-control form-control-sm" placeholder="Purity e.g. 22K"></div>
      <div class="col-md-3"><input type="number" step="0.01" name="material_weight[]" class="form-control form-control-sm" placeholder="Weight (g)"></div>
      <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.material-row').remove()">×</button></div>
    </div>
  </div>
  <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addMaterialRow()">+ Add Material</button>

  <h5 class="mb-3">Gemstones</h5>
  <div id="gemstonesWrap">
    <div class="row g-2 mb-2 gemstone-row">
      <div class="col-md-4"><input type="text" name="gemstone_name[]" class="form-control form-control-sm" placeholder="Gemstone e.g. Diamond"></div>
      <div class="col-md-3"><input type="number" step="0.01" name="carat[]" class="form-control form-control-sm" placeholder="Carat"></div>
      <div class="col-md-3"><input type="number" name="quantity[]" class="form-control form-control-sm" placeholder="Quantity" value="1"></div>
      <div class="col-md-1"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.gemstone-row').remove()">×</button></div>
    </div>
  </div>
  <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addGemstoneRow()">+ Add Gemstone</button>

  <h5 class="mb-3">Pricing</h5>
  <div class="row g-3 mb-3">
    <div class="col-md-3"><label class="form-label">Base Price (₹)</label><input type="number" step="0.01" name="base_price" id="base_price" class="form-control price-input" required></div>
    <div class="col-md-3"><label class="form-label">Making Charges (₹)</label><input type="number" step="0.01" name="making_charges" id="making_charges" class="form-control price-input" value="0"></div>
    <div class="col-md-3"><label class="form-label">GST (%)</label><input type="number" step="0.01" name="gst_percent" id="gst_percent" class="form-control price-input" value="3"></div>
    <div class="col-md-3"><label class="form-label">Discount (₹)</label><input type="number" step="0.01" name="discount" id="discount" class="form-control price-input" value="0"></div>
  </div>
  <p class="mb-3">Final Price (auto-calculated): <strong id="finalPriceDisplay">₹0.00</strong></p>

  <h5 class="mb-3">Media</h5>
  <div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Thumbnail Image</label><input type="file" name="thumbnail" class="form-control" accept="image/*"></div>
    <div class="col-md-4"><label class="form-label">Gallery Images (multiple)</label><input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple></div>
    <div class="col-md-4"><label class="form-label">3D Model (.glb)</label><input type="file" name="model_3d" class="form-control" accept=".glb"></div>
  </div>

  <h5 class="mb-3">Virtual Try-On</h5>
  <div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Try-On Type</label>
      <select name="tryon_type" class="form-select">
        <option value="none">None</option>
        <option value="ring">Ring</option>
        <option value="necklace">Necklace</option>
        <option value="earring">Earring</option>
        <option value="bracelet">Bracelet</option>
      </select>
    </div>
    <div class="col-md-8"><label class="form-label">Try-On Asset (transparent PNG)</label><input type="file" name="tryon_asset" class="form-control" accept=".png"></div>
  </div>

  <h5 class="mb-3">Certificate</h5>
  <p class="small text-muted">A certificate number is generated automatically when you save this product. Optionally attach a scanned certificate file (PDF/image).</p>
  <div class="mb-3"><input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>

  <button class="btn btn-dark">Save Product</button>
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
