<?php
$pageTitle = 'Products';
require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    log_activity($pdo, "Deleted product #$id");
    flash('success', 'Product deleted.');
    redirect('/admin/products.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $id = (int) $_POST['product_id'];
    $status = $_POST['status'];
    set_product_status($pdo, $id, $status);
    log_activity($pdo, "Changed product #$id status to $status");
    flash('success', 'Status updated.');
    redirect('/admin/products.php');
}

$products = $pdo->query("SELECT p.*, c.name AS category_name, b.name AS branch_name
                          FROM products p
                          LEFT JOIN categories c ON p.category_id = c.id
                          LEFT JOIN branches b ON p.branch_id = b.id
                          ORDER BY p.created_at DESC")->fetchAll();
?>

<a href="<?= BASE_URL ?>/admin/product-add.php" class="btn btn-dark mb-3"><i class="bi bi-plus"></i> Add Product</a>

<div class="card p-3">
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead><tr><th>Image</th><th>Name</th><th>SKU</th><th>Category</th><th>Branch</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td><img src="<?= $p['thumbnail'] ? UPLOAD_URL . '/products/' . clean($p['thumbnail']) : 'https://via.placeholder.com/50' ?>" width="50" height="50" style="object-fit:cover;" class="rounded"></td>
            <td><?= clean($p['name']) ?></td>
            <td><?= clean($p['sku']) ?></td>
            <td><?= clean($p['category_name']) ?></td>
            <td><?= clean($p['branch_name']) ?></td>
            <td>₹<?= number_format($p['final_price'], 2) ?></td>
            <td>
              <form method="post" class="d-flex gap-1">
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                  <?php foreach (['available','reserved','sold','hidden'] as $st): ?>
                    <option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                  <?php endforeach; ?>
                </select>
                <input type="hidden" name="change_status" value="1">
              </form>
            </td>
            <td class="text-nowrap">
              <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
              <form method="post" class="d-inline" onsubmit="return confirm('Delete this product permanently?')">
                <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($products)): ?><p class="text-muted">No products yet.</p><?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
