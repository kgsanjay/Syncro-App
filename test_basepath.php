<?php
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = str_replace('/index.php', '', $scriptName);
echo "SCRIPT_NAME: $scriptName\n";
echo "BASE_PATH: $basePath\n";
