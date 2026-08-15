<?php
require_once __DIR__ . '/../../config/config.php';
require_admin();

$currentAdminPage = basename($_SERVER['PHP_SELF']);
function nav_active($page, $current) { return $page === $current ? 'active' : ''; }

$adminNameInitial = strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? clean($pageTitle) . ' - Admin' : 'Admin Panel' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>
<div class="d-flex admin-shell">
  <div class="admin-sidebar" id="adminSidebar">
    <div class="brand"><i class="bi bi-gem"></i> Mahavir Admin</div>

    <div class="nav-section-label">Catalog</div>
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= nav_active('dashboard.php', $currentAdminPage) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="<?= BASE_URL ?>/admin/products.php" class="<?= nav_active('products.php', $currentAdminPage) ?>"><i class="bi bi-gem"></i> Products</a>
    <a href="<?= BASE_URL ?>/admin/categories.php" class="<?= nav_active('categories.php', $currentAdminPage) ?>"><i class="bi bi-tags"></i> Categories</a>
    <a href="<?= BASE_URL ?>/admin/collections.php" class="<?= nav_active('collections.php', $currentAdminPage) ?>"><i class="bi bi-collection"></i> Collections</a>
    <a href="<?= BASE_URL ?>/admin/branches.php" class="<?= nav_active('branches.php', $currentAdminPage) ?>"><i class="bi bi-shop"></i> Branches</a>

    <div class="nav-section-label">Customers</div>
    <a href="<?= BASE_URL ?>/admin/appointments.php" class="<?= nav_active('appointments.php', $currentAdminPage) ?>"><i class="bi bi-calendar-check"></i> Appointments</a>
    <a href="<?= BASE_URL ?>/admin/purchases.php" class="<?= nav_active('purchases.php', $currentAdminPage) ?>"><i class="bi bi-bag-check"></i> Purchases</a>
    <a href="<?= BASE_URL ?>/admin/users.php" class="<?= nav_active('users.php', $currentAdminPage) ?>"><i class="bi bi-people"></i> Users</a>
    <a href="<?= BASE_URL ?>/admin/reviews.php" class="<?= nav_active('reviews.php', $currentAdminPage) ?>"><i class="bi bi-star"></i> Reviews</a>
    <a href="<?= BASE_URL ?>/admin/messages.php" class="<?= nav_active('messages.php', $currentAdminPage) ?>"><i class="bi bi-envelope"></i> Messages</a>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="<?= nav_active('reports.php', $currentAdminPage) ?>"><i class="bi bi-graph-up"></i> Reports</a>

    <?php if (admin_has_role(['super_admin'])): ?>
      <div class="nav-section-label">System</div>
      <a href="<?= BASE_URL ?>/admin/admins.php" class="<?= nav_active('admins.php', $currentAdminPage) ?>"><i class="bi bi-shield-lock"></i> Admin Management</a>
      <a href="<?= BASE_URL ?>/admin/settings.php" class="<?= nav_active('settings.php', $currentAdminPage) ?>"><i class="bi bi-gear"></i> Settings</a>
      <a href="<?= BASE_URL ?>/admin/activity-logs.php" class="<?= nav_active('activity-logs.php', $currentAdminPage) ?>"><i class="bi bi-clock-history"></i> Activity Logs</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/admin/logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>

  <div class="flex-fill">
    <div class="admin-topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
          <i class="bi bi-list"></i>
        </button>
        <h4><?= isset($pageTitle) ? clean($pageTitle) : 'Dashboard' ?></h4>
      </div>
      <div class="admin-chip">
        <span class="avatar"><?= clean($adminNameInitial) ?></span>
        <span><?= clean($_SESSION['admin_name']) ?> &middot; <span class="text-capitalize"><?= clean($_SESSION['admin_role']) ?></span></span>
      </div>
    </div>

    <div class="admin-content">
    <?php $s = flash('success'); $e = flash('error'); ?>
    <?php if ($s): ?><div class="alert alert-success alert-dismissible fade show"><?= clean($s) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if ($e): ?><div class="alert alert-danger alert-dismissible fade show"><?= clean($e) ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
