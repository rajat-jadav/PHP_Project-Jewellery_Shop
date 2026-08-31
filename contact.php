<?php
require_once __DIR__ . '/config/config.php';
$pageTitle = 'Contact Us';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        clean($_POST['name']),
        clean($_POST['email']),
        clean($_POST['phone']),
        clean($_POST['subject']),
        clean($_POST['message']),
    ]);
    flash('success', 'Your message has been sent. We will get back to you shortly.');
    redirect('/contact.php');
}

$branches = $pdo->query("SELECT * FROM branches WHERE status = 'active'")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4">
  <div class="col-md-6">
    <h4 class="mb-3">Get in Touch</h4>
    <form method="post">
      <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control"></div>
      <div class="mb-3"><label class="form-label">Message</label><textarea name="message" rows="4" class="form-control" required></textarea></div>
      <button type="submit" name="send_message" class="btn btn-dark">Send Message</button>
    </form>
  </div>
  <div class="col-md-6">
    <h4 class="mb-3">Our Showrooms</h4>
    <?php foreach ($branches as $b): ?>
      <div class="card mb-2 p-3">
        <h6><?= clean($b['name']) ?></h6>
        <p class="small mb-1"><i class="bi bi-geo-alt"></i> <?= clean($b['address']) ?></p>
        <p class="small mb-1"><i class="bi bi-telephone"></i> <?= clean($b['phone']) ?></p>
        <p class="small mb-1"><i class="bi bi-envelope"></i> <?= clean($b['email']) ?></p>
        <p class="small mb-0"><i class="bi bi-clock"></i> <?= clean($b['business_hours']) ?></p>
        <?php if ($b['map_url']): ?><a href="<?= clean($b['map_url']) ?>" target="_blank" class="small">View on Google Maps</a><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
