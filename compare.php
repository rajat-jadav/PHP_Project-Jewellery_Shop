<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Compare Products';

if (!isset($_SESSION['compare'])) $_SESSION['compare'] = [];

// add product
if (isset($_GET['add'])) {
    $id = (int) $_GET['add'];
    if (!in_array($id, $_SESSION['compare'])) {
        if (count($_SESSION['compare']) >= 3) {
            flash('error', 'You can compare up to 3 products at a time. Remove one first.');
        } else {
            $_SESSION['compare'][] = $id;
            flash('success', 'Added to comparison.');
        }
    }
    redirect('/compare.php');
}

// remove product
if (isset($_GET['remove'])) {
    $_SESSION['compare'] = array_diff($_SESSION['compare'], [(int) $_GET['remove']]);
    redirect('/compare.php');
}

if (isset($_GET['clear'])) {
    $_SESSION['compare'] = [];
    redirect('/compare.php');
}

$products = [];
if (!empty($_SESSION['compare'])) {
    $placeholders = implode(',', array_fill(0, count($_SESSION['compare']), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($_SESSION['compare']);
    $products = $stmt->fetchAll();

    foreach ($products as &$p) {
        $mats = $pdo->prepare("SELECT * FROM product_materials WHERE product_id = ?");
        $mats->execute([$p['id']]);
        $p['materials'] = $mats->fetchAll();

        $gems = $pdo->prepare("SELECT * FROM product_gemstones WHERE product_id = ?");
        $gems->execute([$p['id']]);
        $p['gemstones'] = $gems->fetchAll();

        $p['rating'] = get_avg_rating($pdo, $p['id']);
    }
    unset($p);
}

require_once __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Compare Products (<?= count($products) ?>/3)</h3>

<?php if (empty($products)): ?>
  <p class="text-muted">No products added for comparison. Browse products and click "Add to Compare".</p>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <tr>
        <th>Product</th>
        <?php foreach ($products as $p): ?>
          <td class="text-center">
            <img src="<?= $p['thumbnail'] ? UPLOAD_URL . '/products/' . clean($p['thumbnail']) : 'https://via.placeholder.com/150' ?>" width="120" class="mb-2 rounded"><br>
            <strong><?= clean($p['name']) ?></strong><br>
            <a href="?remove=<?= $p['id'] ?>" class="small text-danger">Remove</a>
          </td>
        <?php endforeach; ?>
      </tr>
      <tr><th>Price</th><?php foreach ($products as $p): ?><td>₹<?= number_format($p['final_price'], 2) ?></td><?php endforeach; ?></tr>
      <tr><th>Weight</th><?php foreach ($products as $p): ?><td><?= $p['weight'] ?> g</td><?php endforeach; ?></tr>
      <tr><th>Materials</th><?php foreach ($products as $p): ?><td><?= implode(', ', array_map(fn($m) => $m['material_name'] . ' (' . $m['purity'] . ')', $p['materials'])) ?: '-' ?></td><?php endforeach; ?></tr>
      <tr><th>Gemstones</th><?php foreach ($products as $p): ?><td><?= implode(', ', array_map(fn($g) => $g['gemstone_name'] . ' ' . $g['carat'] . 'ct', $p['gemstones'])) ?: '-' ?></td><?php endforeach; ?></tr>
      <tr><th>Rating</th><?php foreach ($products as $p): ?><td><?= $p['rating']['avg'] ?: 'No reviews' ?></td><?php endforeach; ?></tr>
      <tr><th>Status</th><?php foreach ($products as $p): ?><td><span class="badge bg-<?= status_badge_class($p['status']) ?>"><?= ucfirst($p['status']) ?></span></td><?php endforeach; ?></tr>
      <tr><th></th><?php foreach ($products as $p): ?><td><a href="<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>" class="btn btn-dark btn-sm">View</a></td><?php endforeach; ?></tr>
    </table>
  </div>
  <a href="?clear=1" class="btn btn-outline-secondary btn-sm">Clear All</a>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
