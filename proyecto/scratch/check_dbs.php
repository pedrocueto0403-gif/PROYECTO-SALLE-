<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

echo "CONFIG DB_NAME: " . DB_NAME . "\n";
echo "CONFIG DB_HOST: " . DB_HOST . "\n";
echo "CONFIG DB_USER: " . DB_USER . "\n";

$pdo = Database::getInstance()->getConnection();
if ($pdo) {
    echo "Current connected DB: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
    echo "All databases:\n";
    $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    print_r($dbs);
}
