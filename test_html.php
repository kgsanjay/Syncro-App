<?php
$basePath = '/syncro';
$buffer = file_get_contents('core/Views/layouts/admin_layout.php');
$buffer = preg_replace('/(href|action|src)=["\']\/(?!\/)(.*?)["\']/i', '$1="' . $basePath . '/$2"', $buffer);
echo $buffer;
