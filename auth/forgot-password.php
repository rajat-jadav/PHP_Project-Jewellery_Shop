<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Forgot Password';

$resetLink = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $upd = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
        $upd->execute([$token, $expires, $user['id']]);

        // NOTE: In production, email this link via PHPMailer instead of displaying it.
        $resetLink = BASE_URL . '/auth/reset-password.php?token=' . $token;
    } else {
        $error = 'No account found with that email.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4">
      <h4 class="mb-3">Forgot Password</h4>
      <?php if ($error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endif; ?>
      <?php if ($resetLink): ?>
        <div class="alert alert-success">
          Reset link generated (in production this would be emailed):<br>
          <a href="<?= $resetLink ?>"><?= $resetLink ?></a>
        </div>
      <?php else: ?>
        <form method="post">
          <div class="mb-3"><label class="form-label">Enter your registered email</label><input type="email" name="email" class="form-control" required></div>
          <button class="btn btn-dark w-100">Send Reset Link</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
