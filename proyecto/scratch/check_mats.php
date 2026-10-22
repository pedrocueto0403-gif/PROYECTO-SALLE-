<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
$mats = $pdo->query("SELECT m.id, m.estudiante_id, m.curso_id, p.primer_nombre, p.primer_apellido FROM matriculas m INNER JOIN perfiles_detalles p ON m.estudiante_id = p.usuario_id LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($mats);

$asigs = $pdo->query("SELECT id, nombre_asignatura FROM asignaturas LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($asigs);
