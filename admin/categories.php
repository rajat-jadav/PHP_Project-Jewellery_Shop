<?php
$pageTitle = 'Categories';
require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $image = handle_file_upload('image', 'categories', ['jpg','jpeg','png','webp']);
        $pdo->prepare("INSERT INTO categories (name, description, image) VALUES (?, ?, ?)")
            ->execute([clean($_POST['name']), clean($_POST['description']), $image]);
        log_activity($pdo, "Added category: " . $_POST['name']);
        flash('success', 'Category added.');
    } elseif (isset($_POST['update_category'])) {
        $id = (int) $_POST['id'];
        $image = handle_file_upload('image', 'categories', ['jpg','jpeg','png','webp']);
        if ($image) {
            $pdo->prepare("UPDATE categories SET name=?, description=?, image=?, status=? WHERE id=?")
                ->execute([clean($_POST['name']), clean($_POST['description']), $image, $_POST['status'], $id]);
        } else {
            $pdo->prepare("UPDATE categories SET name=?, description=?, status=? WHERE id=?")
                ->execute([clean($_POST['name']), clean($_POST['description']), $_POST['status'], $id]);
        }
        flash('success', 'Category updated.');
    } elseif (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([(int) $_POST['delete_id']]);
        flash('success', 'Category deleted.');
    }
    redirect('/admin/categories.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Add Category</h6>
      <form method="post" enctype="multipart/form-data">
        <input class="form-control mb-2" name="name" placeholder="Category name" required>
        <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
        <input type="file" class="form-control mb-2" name="image" accept="image/*">
        <button class="btn btn-dark btn-sm" name="add_category">Add</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-3">
      <table class="table table-sm align-middle">
        <thead><tr><th>Image</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($categories as $c): ?>
            <tr>
              <td><?php if ($c['image']): ?><img src="<?= UPLOAD_URL ?>/categories/<?= clean($c['image']) ?>" width="40" height="40" style="object-fit:cover" class="rounded"><?php endif; ?></td>
              <td><?= clean($c['name']) ?></td>
              <td><span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($c['status']) ?></span></td>
              <td class="text-nowrap">
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCat<?= $c['id'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?')">
                  <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>

            <div class="modal fade" id="editCat<?= $c['id'] ?>">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post" enctype="multipart/form-data">
                    <div class="modal-header"><h6 class="modal-title">Edit Category</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <input class="form-control mb-2" name="name" value="<?= clean($c['name']) ?>" required>
                      <textarea class="form-control mb-2" name="description"><?= clean($c['description']) ?></textarea>
                      <input type="file" class="form-control mb-2" name="image" accept="image/*">
                      <select class="form-select" name="status">
                        <option value="active" <?= $c['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $c['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                      </select>
                    </div>
                    <div class="modal-footer"><button class="btn btn-dark btn-sm" name="update_category">Save</button></div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
