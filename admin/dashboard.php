<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/admin_header.php';

$stats = [
    'users'        => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'products'     => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'available'    => $pdo->query("SELECT COUNT(*) FROM products WHERE status='available'")->fetchColumn(),
    'reserved'     => $pdo->query("SELECT COUNT(*) FROM products WHERE status='reserved'")->fetchColumn(),
    'sold'         => $pdo->query("SELECT COUNT(*) FROM products WHERE status='sold'")->fetchColumn(),
    'branches'     => $pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn(),
    'appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='pending'")->fetchColumn(),
    'reviews'      => $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
];

$recentAppointments = $pdo->query("SELECT a.*, p.name AS product_name, u.name AS user_name
                                    FROM appointments a
                                    JOIN products p ON a.product_id = p.id
                                    JOIN users u ON a.user_id = u.id
                                    ORDER BY a.created_at DESC LIMIT 8")->fetchAll();

$recentActivity = $pdo->query("SELECT al.*, ad.name AS admin_name FROM activity_logs al LEFT JOIN admins ad ON al.admin_id = ad.id ORDER BY al.created_at DESC LIMIT 8")->fetchAll();
?>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Total Users</div><h3><?= $stats['users'] ?></h3></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Total Products</div><h3><?= $stats['products'] ?></h3></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Pending Appointments</div><h3><?= $stats['appointments'] ?></h3></div></div>
  <div class="col-md-3"><div class="card p-3"><div class="text-muted small">Branches</div><h3><?= $stats['branches'] ?></h3></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card p-3 border-success"><div class="text-muted small">Available Products</div><h4 class="text-success"><?= $stats['available'] ?></h4></div></div>
  <div class="col-md-4"><div class="card p-3 border-warning"><div class="text-muted small">Reserved Products</div><h4 class="text-warning"><?= $stats['reserved'] ?></h4></div></div>
  <div class="col-md-4"><div class="card p-3 border-secondary"><div class="text-muted small">Sold Products</div><h4 class="text-secondary"><?= $stats['sold'] ?></h4></div></div>
</div>

<div class="row g-3">
  <div class="col-md-7">
    <div class="card p-3">
      <h6>Recent Appointments</h6>
      <table class="table table-sm">
        <thead><tr><th>Customer</th><th>Product</th><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($recentAppointments as $a): ?>
            <tr>
              <td><?= clean($a['user_name']) ?></td>
              <td><?= clean($a['product_name']) ?></td>
              <td><?= date('d M', strtotime($a['appointment_date'])) ?> - <?= clean($a['time_slot']) ?></td>
              <td><span class="badge bg-<?= status_badge_class($a['status']) ?>"><?= ucfirst($a['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="<?= BASE_URL ?>/admin/appointments.php" class="small">View all appointments &rarr;</a>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card p-3">
      <h6>Recent Activity</h6>
      <ul class="list-unstyled small">
        <?php foreach ($recentActivity as $log): ?>
          <li class="border-bottom py-1"><?= clean($log['admin_name'] ?? 'System') ?> — <?= clean($log['action']) ?> <span class="text-muted">(<?= date('d M, H:i', strtotime($log['created_at'])) ?>)</span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
