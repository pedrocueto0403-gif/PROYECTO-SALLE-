<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== ESTUDIANTES / MATRICULAS SAMPLE ===\n";
$stmt = $pdo->query("SELECT u.id, u.nombre, u.apellido, u.documento, u.correo, m.codigo_matricula, c.grado, c.grupo 
                     FROM usuarios u 
                     LEFT JOIN matriculas m ON m.estudiante_id = u.id 
                     LEFT JOIN cursos c ON m.curso_id = c.id 
                     WHERE u.rol_id = (SELECT id FROM roles WHERE nombre = 'Estudiante' LIMIT 1) 
                     LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== DOCENTES SAMPLE ===\n";
$stmt2 = $pdo->query("SELECT u.id, u.nombre, u.apellido, u.documento, u.correo, pd.titulo_profesional, pd.cargo, pd.salario_mensual, pd.fecha_ingreso, pd.tipo_contrato
                      FROM usuarios u 
                      LEFT JOIN perfiles_detalles pd ON pd.usuario_id = u.id
                      WHERE u.rol_id = (SELECT id FROM roles WHERE nombre = 'Docente' LIMIT 1)
                      LIMIT 5");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== SOLICITUDES ADMISION SAMPLE ===\n";
$stmt3 = $pdo->query("SELECT id, codigo_solicitud, primer_nombre, primer_apellido, tipo_documento, documento_identidad, grado_solicitado, acudiente_nombre, acudiente_documento FROM solicitudes_admision LIMIT 3");
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
