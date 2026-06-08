<?php
require 'core/init.php';

$_SERVER['REQUEST_URI'] = '/syncro/admin/dashboard';
$basePath = '/syncro';
$pageTitle = 'SaaS Command Center';
$csrfToken = 'test';
$successMsg = '';
$errorMsg = '';
$mrr = 0;
$platformGmv = 0;
$activeHotels = 0;
$totalHotels = 0;
$announcements = [];
$recentHotels = [];

ob_start();
require 'core/Views/admin/dashboard.php';
$dashboardContent = ob_get_clean();

file_put_contents('test_dummy_view.php', $dashboardContent);
$viewPath = 'test_dummy_view.php';

ob_start(function($buffer) use ($basePath) {
    $buffer = preg_replace('/(href|action|src)=["\']\/(?!\/)(.*?)["\']/i', '$1="' . $basePath . '/$2"', $buffer);
    return $buffer;
});
require 'core/Views/layouts/admin_layout.php';
ob_end_flush();
