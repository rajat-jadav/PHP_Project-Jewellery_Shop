<?php
require_once __DIR__ . '/../config/config.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$wishlistCount = 0;
if (is_logged_in()) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([current_user_id()]);
    $wishlistCount = $stmt->fetchColumn();
}
$isHome = $currentPage === 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' — Mahavir Ornaments' : 'Mahavir Ornaments — Timeless Jewellery' ?></title>
<meta name="description" content="Mahavir Ornaments — hand-finished jewellery, certified diamonds and ethically-sourced gold. Try on virtually, book a private showroom visit.">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<script type="module" src="https://unpkg.com/@google/model-viewer@3.5.0/dist/model-viewer.min.js"></script>
</head>
<body>

<header class="site-header sticky-top">
  <nav class="navbar navbar-expand-lg container py-2">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php"><i class="bi bi-gem"></i> Mahavir Ornaments</a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav mx-lg-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link <?= $isHome ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/products.php">Collections</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'tryon.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/products.php">Try-on</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/about.php">Maison</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'certificate-verify.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/certificate-verify.php">Certificate</a></li>
        <li class="nav-item"><a class="nav-link <?= $currentPage === 'contact.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/contact.php">Visit</a></li>
      </ul>
      <form class="d-flex me-lg-3 position-relative" role="search" action="<?= BASE_URL ?>/products.php" method="get">
        <i class="bi bi-search position-absolute top-50 translate-middle-y text-muted" style="left:14px; font-size:.8rem;"></i>
        <input class="form-control form-control-sm ps-4" type="search" name="q" placeholder="Search jewellery..." value="<?= clean($_GET['q'] ?? '') ?>">
      </form>
      <ul class="navbar-nav align-items-lg-center gap-lg-1">
        <li class="nav-item">
          <a class="nav-link position-relative" href="<?= BASE_URL ?>/wishlist.php">
            <i class="bi bi-heart"></i>
            <?php if ($wishlistCount > 0): ?>
              <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size:.55rem;"><?= $wishlistCount ?></span>
            <?php endif; ?>
          </a>
        </li>
        <?php if (is_logged_in()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/profile.php"><i class="bi bi-person"></i> <?= clean($_SESSION['user_name']) ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/logout.php">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/auth/login.php">Login</a></li>
          <li class="nav-item"><a class="btn btn-dark btn-sm ms-lg-2" href="<?= BASE_URL ?>/auth/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
</header>

<?php
$successMsg = flash('success');
$errorMsg = flash('error');
if ($successMsg || $errorMsg): ?>
<div class="container my-3">
  <?php if ($successMsg): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= clean($successMsg) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= clean($errorMsg) ?><button class="btn-close" data-bs-dismiss="alert"></button></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$isHome): ?>
<main class="container my-5">
<?php else: ?>
<main>
<?php endif; ?>
