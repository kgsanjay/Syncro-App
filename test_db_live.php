<?php
$host = 'sdb-r.hosting.stackcp.net';
$db   = 'syncro-323036dabe';
$user = 'syncro-323036dabe';
$pass = 'syncro@2026';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("DESCRIBE audit_logs");
    echo "<pre>";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    echo "</pre>";
} catch (PDOException $e) {
    echo $e->getMessage();
}
