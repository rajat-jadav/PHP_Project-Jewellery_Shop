<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Verify Certificate';

$result = null;
$searched = false;
$query = trim($_GET['cert'] ?? $_GET['query'] ?? '');

if ($query !== '') {
    $searched = true;
    $stmt = $pdo->prepare("SELECT c.*, p.name AS product_name, p.sku, p.thumbnail, p.weight, b.name AS branch_name
                            FROM certificates c
                            JOIN products p ON c.product_id = p.id
                            JOIN branches b ON p.branch_id = b.id
                            WHERE c.certificate_no = ? OR p.name LIKE ? OR p.sku = ?
                            LIMIT 1");
    $stmt->execute([$query, '%' . $query . '%', $query]);
    $result = $stmt->fetch();

    if ($result) {
        $mats = $pdo->prepare("SELECT * FROM product_materials WHERE product_id = (SELECT product_id FROM certificates WHERE id = ?)");
        $mats->execute([$result['id']]);
        $result['materials'] = $mats->fetchAll();

        $gems = $pdo->prepare("SELECT * FROM product_gemstones WHERE product_id = (SELECT product_id FROM certificates WHERE id = ?)");
        $gems->execute([$result['id']]);
        $result['gemstones'] = $gems->fetchAll();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-7">
    <h3 class="mb-3">Verify Certificate Authenticity</h3>
    <p class="text-muted">Enter the certificate number, product name, or product code (SKU) printed on your jewellery certificate.</p>

    <form method="get" class="d-flex gap-2 mb-4">
      <input type="text" name="query" class="form-control" placeholder="e.g. CERT-2026-00001 or RG-2026-0001" value="<?= clean($query) ?>" required>
      <button class="btn btn-dark">Verify</button>
    </form>

    <?php if ($searched): ?>
      <?php if ($result): ?>
        <div class="card p-4 border-success">
          <div class="d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-patch-check-fill text-success fs-3"></i>
            <div>
              <h5 class="mb-0">Certificate Verified</h5>
              <span class="badge bg-<?= $result['status'] === 'valid' ? 'success' : 'danger' ?>"><?= ucfirst($result['status']) ?></span>
            </div>
          </div>
          <table class="table table-sm">
            <tr><th>Certificate No.</th><td><?= clean($result['certificate_no']) ?></td></tr>
            <tr><th>Product</th><td><?= clean($result['product_name']) ?> (<?= clean($result['sku']) ?>)</td></tr>
            <tr><th>Weight</th><td><?= $result['weight'] ?> g</td></tr>
            <tr><th>Materials</th><td><?= implode(', ', array_map(fn($m) => $m['material_name'] . ' (' . $m['purity'] . ')', $result['materials'])) ?: '-' ?></td></tr>
            <tr><th>Gemstones</th><td><?= implode(', ', array_map(fn($g) => $g['gemstone_name'] . ' ' . $g['carat'] . 'ct', $result['gemstones'])) ?: '-' ?></td></tr>
            <tr><th>Issued On</th><td><?= date('d M Y', strtotime($result['issue_date'])) ?></td></tr>
            <tr><th>Issuing Branch</th><td><?= clean($result['branch_name']) ?></td></tr>
          </table>
          <?php if ($result['file_path']): ?>
            <a href="<?= UPLOAD_URL ?>/certificates/<?= clean($result['file_path']) ?>" class="btn btn-outline-dark btn-sm" target="_blank" download>
              <i class="bi bi-download"></i> Download Certificate
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="alert alert-danger">
          <i class="bi bi-x-circle"></i> No certificate found matching "<?= clean($query) ?>". Please check the number and try again.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
