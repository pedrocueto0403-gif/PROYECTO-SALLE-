<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

$students = $pdo->query("
    SELECT u.id, u.correo, 
           p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
           p.tipo_documento, p.documento_identidad,
           m.id AS matricula_id, m.curso_id, m.ano_lectivo,
           c.grado, c.grupo
    FROM usuarios u
    JOIN perfiles_detalles p ON u.id = p.usuario_id
    LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
    LEFT JOIN cursos c ON m.curso_id = c.id
    WHERE u.rol_id = 5
    ORDER BY p.primer_apellido, p.primer_nombre
")->fetchAll(PDO::FETCH_ASSOC);

echo "Total students (rol_id = 5): " . count($students) . "\n\n";
foreach ($students as $s) {
    $fullName = trim("{$s['primer_apellido']} {$s['segundo_apellido']} {$s['primer_nombre']} {$s['segundo_nombre']}");
    $curso = $s['grado'] ? "{$s['grado']} {$s['grupo']}" : "SIN CURSO";
    echo "ID: {$s['id']} | {$fullName} | Doc: {$s['tipo_documento']} {$s['documento_identidad']} | Curso: {$curso} (Mat: " . ($s['matricula_id'] ?? 'NULL') . ", CursoID: " . ($s['curso_id'] ?? 'NULL') . ")\n";
}
