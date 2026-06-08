<?php
$_SERVER['REQUEST_URI'] = '/syncro/admin/dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = '80'; 

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';

// DO NOT use an outer ob_start(). Let index.php run normally and flush on exit.
require_once __DIR__ . '/index.php';
