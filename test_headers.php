<?php
$ch = curl_init('http://localhost/syncro/admin/dashboard');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
// send cookies
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
echo $header;
