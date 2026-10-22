<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$cursos = $pdo->query("SELECT * FROM cursos ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo "CURSOS EXISTENTES (" . count($cursos) . "):\n";
foreach ($cursos as $c) {
    echo "ID: {$c['id']} | {$c['grado']} {$c['grupo']} ({$c['ano_lectivo']})\n";
}
