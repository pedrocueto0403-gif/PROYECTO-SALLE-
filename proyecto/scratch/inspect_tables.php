<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

echo "=== ROLES ===\n";
print_r($pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== CONTABILIDAD_CAJA COLUMNS ===\n";
print_r($pdo->query("SHOW COLUMNS FROM contabilidad_caja")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== PERFILES_DETALLES COLUMNS ===\n";
print_r($pdo->query("SHOW COLUMNS FROM perfiles_detalles")->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== SOLICITUDES_ADMISION COLUMNS ===\n";
print_r($pdo->query("SHOW COLUMNS FROM solicitudes_admision")->fetchAll(PDO::FETCH_ASSOC));
