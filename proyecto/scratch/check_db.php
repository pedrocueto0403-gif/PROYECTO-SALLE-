<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
if (!$pdo) {
    echo "NO PDO\n";
    exit(1);
}
echo "TABLES:\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\nCURSOS COLUMNS:\n";
$cols = $pdo->query("DESCRIBE cursos")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);

echo "\nPERFILES_DETALLES COLUMNS:\n";
$cols2 = $pdo->query("DESCRIBE perfiles_detalles")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols2);

echo "\nCONTABILIDAD_CAJA COLUMNS:\n";
$cols3 = $pdo->query("DESCRIBE contabilidad_caja")->fetchAll(PDO::FETCH_ASSOC);
print_r($cols3);
