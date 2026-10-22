<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$res = $pdo->query('SELECT id, grado, grupo, jornada, sede, ano_lectivo FROM cursos ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/cursos_catalog.json', json_encode($res, JSON_UNESCAPED_UNICODE));
echo "SAVED cursos_catalog.json with " . count($res) . " courses\n";
