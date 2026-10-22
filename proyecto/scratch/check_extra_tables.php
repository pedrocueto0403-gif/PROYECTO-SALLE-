<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

echo "ROLES:\n";
print_r($pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC));

echo "\nPERFILES COLUMNS:\n";
print_r($pdo->query("SHOW FULL COLUMNS FROM perfiles_detalles")->fetchAll(PDO::FETCH_ASSOC));

echo "\nCAJA COLUMNS:\n";
print_r($pdo->query("SHOW FULL COLUMNS FROM contabilidad_caja")->fetchAll(PDO::FETCH_ASSOC));

echo "\nEVALUACION TABLES:\n";
$tabs = $pdo->query("SHOW TABLES LIKE '%evaluacion%'")->fetchAll(PDO::FETCH_COLUMN);
print_r($tabs);
foreach ($tabs as $t) {
    echo "--- $t ---\n";
    $c = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
    echo $c['Create Table'] . "\n\n";
}
