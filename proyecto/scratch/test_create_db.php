<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456789');
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `aulavirtual_salle_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Successfully created aulavirtual_salle_db!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
