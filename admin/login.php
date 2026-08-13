<?php
require_once __DIR__ . '/../config/config.php';

if (is_admin_logged_in()) redirect('/admin/dashboard.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        if ($admin['status'] === 'disabled') {
            $error = 'This admin account has been disabled.';
        } else {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            log_activity($pdo, 'Logged in');
            redirect('/admin/dashboard.php');
        }
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Mahavir Ornaments</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
<style>
  body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: radial-gradient(circle at 20% 20%, var(--a-emerald), var(--a-emerald-deep) 60%);
  }
  .login-card {
    background: #fff;
    border-radius: 16px;
    padding: 2.5rem;
    box-shadow: 0 25px 60px rgba(0,0,0,.35);
  }
  .login-brand {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-family: var(--font-display); font-size: 1.5rem; margin-bottom: .25rem;
  }
  .login-brand i { color: var(--a-sand); }
</style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-11 col-sm-7 col-md-5 col-lg-4">
      <div class="login-card">
        <div class="login-brand"><i class="bi bi-gem"></i> Mahavir Admin</div>
        <p class="text-center text-muted small mb-4">Sign in to manage the store</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= clean($error) ?></div><?php endif; ?>
        <form method="post">
          <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required autofocus></div>
          <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
          <button class="btn btn-dark w-100">Login</button>
        </form>
      </div>
      <p class="text-center small mt-3" style="color:rgba(255,255,255,.6);">&larr; <a href="<?= BASE_URL ?>/index.php" style="color:rgba(255,255,255,.8);">Back to store</a></p>
    </div>
  </div>
</div>
</body>
</html>
