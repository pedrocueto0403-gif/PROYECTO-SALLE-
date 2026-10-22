<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();
$stmt = $pdo->query("
    SELECT m.estudiante_id, m.curso_id, c.grado, c.grupo, c.jornada,
           cad.docente_id, a.nombre_asignatura, p.primer_nombre, p.primer_apellido
    FROM matriculas m
    INNER JOIN cursos c ON m.curso_id = c.id
    LEFT JOIN curso_asignatura_docente cad ON c.id = cad.curso_id
    LEFT JOIN asignaturas a ON cad.asignatura_id = a.id
    LEFT JOIN usuarios u ON cad.docente_id = u.id
    LEFT JOIN perfiles_detalles p ON u.id = p.usuario_id
    WHERE m.ano_lectivo = 2026
    LIMIT 20
");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
