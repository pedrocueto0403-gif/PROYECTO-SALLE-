<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
echo "--- CURSOS ---\n";
foreach ($pdo->query("SELECT * FROM cursos ORDER BY ano_lectivo DESC, grado, grupo") as $r) {
    echo "ID: {$r['id']} | Grado: {$r['grado']} | Grupo: {$r['grupo']} | Año: {$r['ano_lectivo']}\n";
}
echo "\n--- ROLES ---\n";
foreach ($pdo->query("SELECT * FROM roles") as $r) {
    echo "ID: {$r['id']} | Rol: {$r['nombre_rol']}\n";
}
echo "\n--- SAMPLE MATRICULAS COUNT ---\n";
$c = $pdo->query("SELECT COUNT(*) FROM matriculas")->fetchColumn();
echo "Total matriculas: $c\n";
