<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

echo "=== COLUMNS OF cursos ===\n";
print_r($pdo->query("DESCRIBE cursos")->fetchAll(PDO::FETCH_ASSOC));

echo "=== COLUMNS OF matriculas ===\n";
print_r($pdo->query("DESCRIBE matriculas")->fetchAll(PDO::FETCH_ASSOC));

echo "=== ALL CURSOS ===\n";
$cursos = $pdo->query("SELECT * FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
print_r($cursos);

echo "\n=== ALL MATRICULAS ===\n";
$mats = $pdo->query("SELECT m.*, 
                            p.primer_nombre, p.primer_apellido,
                            c.grado, c.grupo
                     FROM matriculas m
                     JOIN perfiles_detalles p ON m.estudiante_id = p.usuario_id
                     LEFT JOIN cursos c ON m.curso_id = c.id
                     ORDER BY m.estudiante_id, m.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($mats as $m) {
    echo "MatID: {$m['id']} | EstID: {$m['estudiante_id']} ({$m['primer_apellido']} {$m['primer_nombre']}) | CursoID: {$m['curso_id']} ({$m['grado']} {$m['grupo']}) | Año: " . ($m['ano_lectivo'] ?? 'N/A') . "\n";
}

echo "\n=== QUERY IN index.php LINE 821 ===\n";
$estCert = $pdo->query("
    SELECT u.id, u.correo,
           p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
           p.tipo_documento, p.documento_identidad,
           c.id as curso_id,
           COALESCE(c.grado, '10°') AS grado,
           COALESCE(c.grupo, 'A') AS grupo,
           m.id as mat_id,
           m.ano_lectivo
    FROM usuarios u
    INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
    LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
    LEFT JOIN cursos c ON m.curso_id = c.id
    WHERE u.rol_id = 5
    ORDER BY p.primer_apellido, p.primer_nombre
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($estCert as $ec) {
    echo "Est {$ec['id']}: {$ec['primer_apellido']} {$ec['primer_nombre']} => Grado: {$ec['grado']} {$ec['grupo']} (Mat: {$ec['mat_id']}, Curso: {$ec['curso_id']}, Año: {$ec['ano_lectivo']})\n";
}
