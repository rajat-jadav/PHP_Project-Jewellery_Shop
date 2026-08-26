<?php
require_once __DIR__ . '/config/config.php';

// Handle toggle action (add/remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_wishlist'])) {
    require_login();
    $productId = (int) $_POST['product_id'];
    $userId = current_user_id();

    $check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->execute([$userId, $productId]);
    $existing = $check->fetch();

    if ($existing) {
        $pdo->prepare("DELETE FROM wishlist WHERE id = ?")->execute([$existing['id']]);
        flash('success', 'Removed from wishlist.');
    } else {
        $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)")->execute([$userId, $productId]);
        flash('success', 'Added to wishlist.');
    }

    $redirectTo = $_POST['redirect_to'] ?? '/wishlist.php';
    redirect($redirectTo);
}

require_login();
$pageTitle = 'My Wishlist';

$stmt = $pdo->prepare("SELECT p.* FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
$stmt->execute([current_user_id()]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">My Wishlist</h3>
<div class="row g-4">
  <?php foreach ($items as $p): ?>
    <div class="col-6 col-md-3">
      <div class="card product-card h-100">
        <a href="<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>">
          <img src="<?= $p['thumbnail'] ? UPLOAD_URL . '/products/' . clean($p['thumbnail']) : 'https://via.placeholder.com/300x220?text=Jewellery' ?>" class="card-img-top">
        </a>
        <div class="card-body">
          <h6><?= clean($p['name']) ?></h6>
          <p class="fw-bold">₹<?= number_format($p['final_price'], 2) ?></p>
          <form method="post">
            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="redirect_to" value="/wishlist.php">
            <button class="btn btn-outline-danger btn-sm w-100" name="toggle_wishlist">Remove</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (empty($items)): ?><p class="text-muted">Your wishlist is empty.</p><?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
