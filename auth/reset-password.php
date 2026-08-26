<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Reset Password';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'This reset link is invalid or has expired.');
    redirect('/auth/forgot-password.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $upd = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $upd->execute([$hash, $user['id']]);
        flash('success', 'Password updated. Please login.');
        redirect('/auth/login.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4">
      <h4 class="mb-3">Reset Password</h4>
      <?php if ($error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="token" value="<?= clean($token) ?>">
        <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
        <button class="btn btn-dark w-100">Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
