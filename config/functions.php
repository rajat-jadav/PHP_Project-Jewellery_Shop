<?php
/* =========================================================
   GENERAL HELPERS
   ========================================================= */

function clean($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

/* =========================================================
   AUTH - CUSTOMER
   ========================================================= */

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        flash('error', 'Please login to continue.');
        redirect('/auth/login.php');
    }
}

/* =========================================================
   AUTH - ADMIN
   ========================================================= */

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        redirect('/admin/login.php');
    }
}

function admin_has_role($roles = []) {
    if (!is_admin_logged_in()) return false;
    if (empty($roles)) return true;
    return in_array($_SESSION['admin_role'], $roles);
}

function log_activity($pdo, $action) {
    if (!is_admin_logged_in()) return;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, action) VALUES (?, ?)");
    $stmt->execute([$_SESSION['admin_id'], $action]);
}

/* =========================================================
   BUSINESS LOGIC
   ========================================================= */

// Calculate final price: base + making charges + gst - discount
function calculate_final_price($basePrice, $makingCharges, $gstPercent, $discount) {
    $subtotal = $basePrice + $makingCharges;
    $gstAmount = $subtotal * ($gstPercent / 100);
    $final = $subtotal + $gstAmount - $discount;
    return round(max($final, 0), 2);
}

// Auto-generate a unique SKU e.g. RG-2026-0001
function generate_sku($pdo, $categoryId) {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $cat = $stmt->fetchColumn();
    $prefix = $cat ? strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $cat), 0, 2)) : 'JW';
    $year = date('Y');
    $countStmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $countStmt->fetchColumn() + 1;
    return sprintf('%s-%s-%04d', $prefix, $year, $count);
}

// Auto-generate a unique certificate number e.g. CERT-2026-0001
function generate_certificate_number($pdo) {
    $year = date('Y');
    $countStmt = $pdo->query("SELECT COUNT(*) FROM certificates");
    $count = $countStmt->fetchColumn() + 1;
    return sprintf('CERT-%s-%05d', $year, $count);
}

// Product status badge color for Bootstrap
function status_badge_class($status) {
    return match ($status) {
        'available' => 'success',
        'reserved'  => 'warning',
        'sold'      => 'secondary',
        'hidden'    => 'dark',
        'pending'   => 'warning',
        'approved'  => 'success',
        'rejected'  => 'danger',
        'rescheduled' => 'info',
        'cancelled' => 'secondary',
        'completed' => 'primary',
        default     => 'light',
    };
}

// Star rating average for a product
function get_avg_rating($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as total FROM reviews WHERE product_id = ? AND status = 'visible'");
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    return [
        'avg'   => $row['avg_r'] ? round($row['avg_r'], 1) : 0,
        'total' => (int) $row['total'],
    ];
}

/* =========================================================
   FILE UPLOAD HELPER
   ========================================================= */

// Returns the new filename on success, or null if no file / on failure
function handle_file_upload($fieldName, $subfolder, $allowedExt = ['jpg','jpeg','png','webp']) {
    if (empty($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $original = $_FILES[$fieldName]['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        return null;
    }
    $newName = uniqid('img_', true) . '.' . $ext;
    $destDir = UPLOAD_PATH . '/' . $subfolder;
    if (!is_dir($destDir)) mkdir($destDir, 0777, true);
    $destPath = $destDir . '/' . $newName;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destPath)) {
        return $newName;
    }
    return null;
}

// Handles a <input type="file" multiple> field, returns array of saved filenames
function handle_multi_file_upload($fieldName, $subfolder, $allowedExt = ['jpg','jpeg','png','webp']) {
    $saved = [];
    if (empty($_FILES[$fieldName]['name'][0])) return $saved;

    $destDir = UPLOAD_PATH . '/' . $subfolder;
    if (!is_dir($destDir)) mkdir($destDir, 0777, true);

    foreach ($_FILES[$fieldName]['name'] as $i => $name) {
        if ($_FILES[$fieldName]['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) continue;
        $newName = uniqid('img_', true) . '.' . $ext;
        if (move_uploaded_file($_FILES[$fieldName]['tmp_name'][$i], $destDir . '/' . $newName)) {
            $saved[] = $newName;
        }
    }
    return $saved;
}

/* =========================================================
   PRODUCT STATUS WORKFLOW
   (Available -> Reserved -> Sold, or back to Available)
   ========================================================= */

function set_product_status($pdo, $productId, $status) {
    $allowed = ['available', 'reserved', 'sold', 'hidden'];
    if (!in_array($status, $allowed)) return false;
    $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $productId]);
}
