<?php
require 'core/init.php';
try {
    $db = Database::getConnection();
    echo "Connected\n";
    $stmt = $db->query("SELECT * FROM users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
