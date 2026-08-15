<?php
$pageTitle = 'Branches';
require_once __DIR__ . '/includes/admin_header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_branch'])) {
        $image = handle_file_upload('image', 'branches', ['jpg','jpeg','png','webp']);
        $pdo->prepare("INSERT INTO branches (name, address, phone, email, map_url, business_hours, image) VALUES (?,?,?,?,?,?,?)")
            ->execute([clean($_POST['name']), clean($_POST['address']), clean($_POST['phone']), clean($_POST['email']), clean($_POST['map_url']), clean($_POST['business_hours']), $image]);
        flash('success', 'Branch added.');
    } elseif (isset($_POST['update_branch'])) {
        $id = (int) $_POST['id'];
        $pdo->prepare("UPDATE branches SET name=?, address=?, phone=?, email=?, map_url=?, business_hours=?, status=? WHERE id=?")
            ->execute([clean($_POST['name']), clean($_POST['address']), clean($_POST['phone']), clean($_POST['email']), clean($_POST['map_url']), clean($_POST['business_hours']), $_POST['status'], $id]);
        flash('success', 'Branch updated.');
    } elseif (isset($_POST['delete_id'])) {
        $pdo->prepare("DELETE FROM branches WHERE id = ?")->execute([(int) $_POST['delete_id']]);
        flash('success', 'Branch deleted.');
    }
    redirect('/admin/branches.php');
}

$branches = $pdo->query("SELECT * FROM branches ORDER BY id DESC")->fetchAll();
?>

<div class="row g-4">
  <div class="col-md-4">
    <div class="card p-3">
      <h6>Add Branch</h6>
      <form method="post" enctype="multipart/form-data">
        <input class="form-control mb-2" name="name" placeholder="Branch name" required>
        <input class="form-control mb-2" name="address" placeholder="Address">
        <input class="form-control mb-2" name="phone" placeholder="Phone">
        <input class="form-control mb-2" name="email" placeholder="Email">
        <input class="form-control mb-2" name="map_url" placeholder="Google Maps URL">
        <input class="form-control mb-2" name="business_hours" placeholder="e.g. 10:00 AM - 8:00 PM">
        <input type="file" class="form-control mb-2" name="image" accept="image/*">
        <button class="btn btn-dark btn-sm" name="add_branch">Add</button>
      </form>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-3">
      <table class="table table-sm align-middle">
        <thead><tr><th>Name</th><th>Address</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($branches as $b): ?>
            <tr>
              <td><?= clean($b['name']) ?></td>
              <td class="small"><?= clean($b['address']) ?></td>
              <td><span class="badge bg-<?= $b['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($b['status']) ?></span></td>
              <td class="text-nowrap">
                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editBr<?= $b['id'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this branch? Products assigned to it will also be removed.')">
                  <input type="hidden" name="delete_id" value="<?= $b['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <div class="modal fade" id="editBr<?= $b['id'] ?>">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post">
                    <div class="modal-header"><h6 class="modal-title">Edit Branch</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $b['id'] ?>">
                      <input class="form-control mb-2" name="name" value="<?= clean($b['name']) ?>" required>
                      <input class="form-control mb-2" name="address" value="<?= clean($b['address']) ?>">
                      <input class="form-control mb-2" name="phone" value="<?= clean($b['phone']) ?>">
                      <input class="form-control mb-2" name="email" value="<?= clean($b['email']) ?>">
                      <input class="form-control mb-2" name="map_url" value="<?= clean($b['map_url']) ?>">
                      <input class="form-control mb-2" name="business_hours" value="<?= clean($b['business_hours']) ?>">
                      <select class="form-select" name="status">
                        <option value="active" <?= $b['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $b['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                      </select>
                    </div>
                    <div class="modal-footer"><button class="btn btn-dark btn-sm" name="update_branch">Save</button></div>
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
