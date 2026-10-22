<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

echo "=== TABLAS EN BD ACTUAL ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

echo "\n=== COLUMNAS DE CURSOS ===\n";
$cols = $pdo->query("SHOW FULL COLUMNS FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} - {$c['Type']} - Null:{$c['Null']} - Default:{$c['Default']}\n";
}

echo "\n=== KEYS DE CURSOS ===\n";
$keys = $pdo->query("SHOW INDEX FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
foreach ($keys as $k) {
    echo "{$k['Key_name']} - Column: {$k['Column_name']} - Non_unique: {$k['Non_unique']}\n";
}

echo "\n=== TOTAL CURSOS Y MUESTRA ===\n";
$cursos = $pdo->query("SELECT id, grado, grupo, jornada, sede, ano_lectivo FROM cursos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Total cursos: " . count($cursos) . "\n";
foreach ($cursos as $c) {
    echo "ID {$c['id']}: {$c['grado']} {$c['grupo']} ({$c['jornada']} - {$c['sede']} - {$c['ano_lectivo']})\n";
}
