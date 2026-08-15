<?php
$pageTitle = 'Collections';
require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_collection'])) {
        $image = handle_file_upload('image', 'collections', ['jpg','jpeg','png','webp']);
        $pdo->prepare("INSERT INTO collections (name, description, image) VALUES (?, ?, ?)")
            ->execute([clean($_POST['name']), clean($_POST['description']), $image]);
        flash('success', 'Collection added.');
    } elseif (isset($_POST['update_collection'])) {
        $id = (int) $_POST['id'];
        $image = handle_file_upload('image', 'collections', ['jpg','jpeg','png','webp']);
        if ($image) {
            $pdo->prepare("UPDATE collections SET name=?, description=?, image=?, status=? WHERE id=?")
                ->execute([clean($_POST['name']), clean($_POST['description']), $image, $_POST['status'], $id]);
        } else {
            $pdo->prepare("UPDATE collections SET name=?, description=?, status=? WHERE id=?")
                ->execute([clean($_POST['name']), clean($_POST['description']), $_POST['status'], $id]);
        }
        flash('success', 'Collection updated.');
    } elseif (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM collections WHERE id = ?")->execute([(int) $_POST['delete_id']]);
        flash('success', 'Collection deleted.');
    }
    redirect('/admin/collections.php');
}

$collections = $pdo->query("SELECT * FROM collections ORDER BY id DESC")->fetchAll();
?>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Add Collection</h6>
      <form method="post" enctype="multipart/form-data">
        <input class="form-control mb-2" name="name" placeholder="Collection name" required>
        <textarea class="form-control mb-2" name="description" placeholder="Description"></textarea>
        <input type="file" class="form-control mb-2" name="image" accept="image/*">
        <button class="btn btn-dark btn-sm" name="add_collection">Add</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-3">
      <table class="table table-sm align-middle">
        <thead><tr><th>Image</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($collections as $c): ?>
            <tr>
              <td><?php if ($c['image']): ?><img src="<?= UPLOAD_URL ?>/collections/<?= clean($c['image']) ?>" width="40" height="40" style="object-fit:cover" class="rounded"><?php endif; ?></td>
              <td><?= clean($c['name']) ?></td>
              <td><span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($c['status']) ?></span></td>
              <td class="text-nowrap">
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editCol<?= $c['id'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this collection?')">
                  <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <div class="modal fade" id="editCol<?= $c['id'] ?>">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post" enctype="multipart/form-data">
                    <div class="modal-header"><h6 class="modal-title">Edit Collection</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
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
                    <div class="modal-footer"><button class="btn btn-dark btn-sm" name="update_collection">Save</button></div>
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
