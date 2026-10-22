<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();

$estudiantesCertificados = $pdo->query("
    SELECT u.id, u.correo,
           p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
           p.tipo_documento, p.documento_identidad, p.telefono, p.direccion,
           p.acudiente_nombre, p.acudiente_telefono, p.acudiente_correo,
           COALESCE(c.grado, '11°') AS grado,
           COALESCE(c.grupo, 'A') AS grupo,
           COALESCE(m.ano_lectivo, 2026) AS ano_lectivo,
           COALESCE(m.id, 0) AS matricula_id,
           COALESCE(m.codigo_matricula, CONCAT('EST-2026-', LPAD(u.id, 5, '0'))) AS codigo_matricula
    FROM usuarios u
    INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
    LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
    LEFT JOIN cursos c ON m.curso_id = c.id
    WHERE u.rol_id = 5
    ORDER BY p.primer_apellido, p.primer_nombre
")->fetchAll(PDO::FETCH_ASSOC);

echo "Total estudiantes en dropdown: " . count($estudiantesCertificados) . "\n\n";

$all11A = true;
foreach ($estudiantesCertificados as $est) {
    $nomEst = trim("{$est['primer_apellido']} {$est['segundo_apellido']} {$est['primer_nombre']} {$est['segundo_nombre']}");
    $optionText = "{$nomEst} ({$est['grado']} {$est['grupo']} - {$est['tipo_documento']} {$est['documento_identidad']})";
    echo $optionText . "\n";
    if ($est['grado'] !== '11°' || $est['grupo'] !== 'A') {
        $all11A = false;
    }
}

echo "\n============================================\n";
if ($all11A) {
    echo "VERIFICACIÓN EXITOSA: ¡Todos los estudiantes están en 11° A!\n";
} else {
    echo "ERROR: Hay estudiantes que no están en 11° A.\n";
}
echo "============================================\n";

echo "\nCursos registrados en el sistema (Transición a 11°):\n";
$cursos = $pdo->query("SELECT * FROM cursos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cursos as $c) {
    echo "ID: {$c['id']} | Grado: {$c['grado']} | Grupo: {$c['grupo']} | Año: {$c['ano_lectivo']}\n";
}
echo "Total cursos: " . count($cursos) . "\n";
