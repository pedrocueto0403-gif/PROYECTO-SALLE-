<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

$stmt = $pdo->query("SELECT id, grado, grupo, jornada, sede, ano_lectivo FROM cursos WHERE jornada = 'Tarde'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Tarde rows count: " . count($rows) . PHP_EOL;
print_r($rows[0] ?? null);
print_r($rows[1] ?? null);
