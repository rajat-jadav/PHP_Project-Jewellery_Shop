<?php
require_once __DIR__ . '/config/config.php';
require_login();
$pageTitle = 'My Profile';
$userId = current_user_id();

$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$userId]);
$user = $user->fetch();

// Handle account update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = clean($_POST['name']);
    $phone = clean($_POST['phone']);
    $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $userId]);
    $_SESSION['user_name'] = $name;
    flash('success', 'Profile updated.');
    redirect('/profile.php');
}

// Handle appointment cancellation by customer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $apptId = (int) $_POST['appointment_id'];
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->execute([$apptId, $userId]);
    $appt = $stmt->fetch();

    if ($appt && in_array($appt['status'], ['pending', 'approved', 'rescheduled'])) {
        $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?")->execute([$apptId]);
        // if product was reserved for this appointment, release it back to available
        $pdo->prepare("UPDATE products SET status = 'available' WHERE id = ? AND status = 'reserved'")->execute([$appt['product_id']]);
        flash('success', 'Appointment cancelled.');
    }
    redirect('/profile.php');
}

$appointments = $pdo->prepare("SELECT a.*, p.name AS product_name, p.thumbnail, b.name AS branch_name
                                FROM appointments a
                                JOIN products p ON a.product_id = p.id
                                JOIN branches b ON a.branch_id = b.id
                                WHERE a.user_id = ? ORDER BY a.created_at DESC");
$appointments->execute([$userId]);
$appointments = $appointments->fetchAll();

$purchases = $pdo->prepare("SELECT pu.*, p.name AS product_name FROM purchases pu JOIN products p ON pu.product_id = p.id WHERE pu.user_id = ? ORDER BY pu.purchase_date DESC");
$purchases->execute([$userId]);
$purchases = $purchases->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">My Account</h3>

<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAppts">Appointments</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPurchases">Purchase History</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSettings">Account Settings</button></li>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tabAppts">
    <?php foreach ($appointments as $a): ?>
      <div class="card mb-2 p-3">
        <div class="d-flex justify-content-between flex-wrap gap-2">
          <div>
            <strong><?= clean($a['product_name']) ?></strong>
            <span class="badge bg-<?= status_badge_class($a['status']) ?> ms-2"><?= ucfirst($a['status']) ?></span>
            <p class="small text-muted mb-0"><?= clean($a['branch_name']) ?> &bull; <?= date('d M Y', strtotime($a['appointment_date'])) ?> at <?= clean($a['time_slot']) ?></p>
            <?php if ($a['admin_note']): ?><p class="small text-muted mb-0">Note: <?= clean($a['admin_note']) ?></p><?php endif; ?>
          </div>
          <?php if (in_array($a['status'], ['pending', 'approved', 'rescheduled'])): ?>
            <form method="post">
              <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
              <button class="btn btn-outline-danger btn-sm" name="cancel_appointment" onclick="return confirm('Cancel this appointment?')">Cancel</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($appointments)): ?><p class="text-muted">No appointments yet.</p><?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tabPurchases">
    <table class="table">
      <thead><tr><th>Product</th><th>Price</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($purchases as $p): ?>
          <tr><td><?= clean($p['product_name']) ?></td><td>₹<?= number_format($p['final_price'], 2) ?></td><td><?= date('d M Y', strtotime($p['purchase_date'])) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php if (empty($purchases)): ?><p class="text-muted">No purchases recorded yet.</p><?php endif; ?>
  </div>

  <div class="tab-pane fade" id="tabSettings">
    <form method="post" class="card p-3" style="max-width:500px;">
      <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="<?= clean($user['name']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" value="<?= clean($user['email']) ?>" disabled></div>
      <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="<?= clean($user['phone']) ?>"></div>
      <button class="btn btn-dark" name="update_profile">Save Changes</button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
