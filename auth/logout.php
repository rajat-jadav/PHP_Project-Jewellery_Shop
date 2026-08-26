<?php
require_once __DIR__ . '/../config/config.php';
unset($_SESSION['user_id'], $_SESSION['user_name']);
redirect('/index.php');
