<?php
require_once __DIR__ . '/../config/config.php';
$pageTitle = 'Login';

if (is_logged_in()) redirect('/index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] === 'disabled') {
            $error = 'Your account has been disabled. Please contact support.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            redirect('/index.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card p-4">
      <h4 class="mb-3">Login</h4>
      <?php if ($error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <button class="btn btn-dark w-100">Login</button>
      </form>
      <p class="text-center small mt-3"><a href="<?= BASE_URL ?>/auth/forgot-password.php">Forgot password?</a></p>
      <p class="text-center small mb-0">Don't have an account? <a href="<?= BASE_URL ?>/auth/register.php">Register</a></p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
