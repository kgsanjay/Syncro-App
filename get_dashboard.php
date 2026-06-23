<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/syncro/admin/dashboard';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Mock session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';

ob_start();
require_once 'index.php';
$html = ob_get_clean();

file_put_contents('dashboard_output.html', $html);
echo "Done";
