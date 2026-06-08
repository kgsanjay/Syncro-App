<?php
$_SERVER['REQUEST_URI'] = '/syncro/admin/dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
$_SERVER['SERVER_PORT'] = '80'; // Fix the CLI issue

// Mock the user session so AdminController doesn't redirect
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';

// We just want to see the HTML output
ob_start();
require_once __DIR__ . '/index.php';
$output = ob_get_clean();

echo "OUTPUT LENGTH: " . strlen($output) . "\n";
echo substr($output, 0, 1500) . "\n";
