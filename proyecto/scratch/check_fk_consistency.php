<?php
require_once 'practicas antigrabiti/config/config.php';
require_once 'practicas antigrabiti/config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== VERIFICANDO BASE DE DATOS LOCAL Y EXPORTANDO DUMP PERFECTO ===\n\n";

// Check all foreign key constraints in live DB
$tables = ['roles', 'usuarios', 'perfiles_detalles', 'cursos', 'asignaturas', 'matriculas', 'curso_asignatura_docente', 'asistencias', 'calificaciones_logros', 'citaciones_acudientes', 'solicitudes_admision', 'actas_convivencia', 'planes_clase', 'banco_logros'];

foreach ($tables as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo "Tabla $t: $count registros\n";
}

// Check if any matricula has an invalid estudiante_id in DB
$orphansMatriculas = $pdo->query("
    SELECT m.id, m.estudiante_id 
    FROM matriculas m 
    LEFT JOIN usuarios u ON m.estudiante_id = u.id 
    WHERE u.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
echo "Matriculas huérfanas en BD: " . count($orphansMatriculas) . "\n";

// Check if any matricula has an invalid curso_id in DB
$orphansMatCursos = $pdo->query("
    SELECT m.id, m.curso_id 
    FROM matriculas m 
    LEFT JOIN cursos c ON m.curso_id = c.id 
    WHERE c.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
echo "Matriculas con curso_id inválido en BD: " . count($orphansMatCursos) . "\n";

// Check perfiles_detalles
$orphansPerfiles = $pdo->query("
    SELECT p.id, p.usuario_id 
    FROM perfiles_detalles p 
    LEFT JOIN usuarios u ON p.usuario_id = u.id 
    WHERE u.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
echo "Perfiles huérfanos en BD: " . count($orphansPerfiles) . "\n";

// Check citaciones
$orphansCit = $pdo->query("
    SELECT c.id, c.estudiante_id 
    FROM citaciones_acudientes c 
    LEFT JOIN usuarios u ON c.estudiante_id = u.id 
    WHERE u.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
echo "Citaciones huérfanas en BD: " . count($orphansCit) . "\n";

// Check actas
$orphansAct = $pdo->query("
    SELECT a.id, a.estudiante_id 
    FROM actas_convivencia a 
    LEFT JOIN usuarios u ON a.estudiante_id = u.id 
    WHERE u.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);
echo "Actas huérfanas en BD: " . count($orphansAct) . "\n";
