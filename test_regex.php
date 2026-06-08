<?php
$buffer = '<link rel="stylesheet" href="/assets/css/app.css?v=1.0">
<script src="/assets/js/tailwindcss.js?v=1.0"></script>';
$basePath = '/syncro';
echo preg_replace('/(href|action|src)=["\']\/(?!\/)(.*?)["\']/i', '$1="' . $basePath . '/$2"', $buffer);
