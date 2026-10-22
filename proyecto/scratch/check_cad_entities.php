<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$teachers = $pdo->query("SELECT u.id, u.correo, p.primer_nombre, p.primer_apellido FROM usuarios u JOIN perfiles_detalles p ON u.id = p.usuario_id WHERE u.rol_id = 3")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT id, nombre_asignatura FROM asignaturas LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$courses = $pdo->query("SELECT id, grado, grupo, jornada FROM cursos LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
echo "Teachers count: " . count($teachers) . PHP_EOL;
print_r($teachers);
echo "Subjects count: " . count($subjects) . PHP_EOL;
print_r($subjects);
echo "Courses count: " . count($courses) . PHP_EOL;
print_r($courses);
