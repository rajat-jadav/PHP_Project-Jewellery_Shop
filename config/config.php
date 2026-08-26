<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Change this if your Laragon project folder name is different
define('BASE_URL', '/jewellery-shop');
define('UPLOAD_URL', BASE_URL . '/assets/uploads');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
