<?php
require_once 'C:/AppServ/www/practicas antigrabiti/config/database.php';
$pdo = Database::getInstance()->getConnection();

// 1. Verificar lista de docentes
$docentes = $pdo->query("
    SELECT u.id, u.correo,
           p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
           p.tipo_documento, p.documento_identidad, p.telefono, p.direccion
    FROM usuarios u
    INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
    WHERE u.rol_id = 3
    ORDER BY p.primer_apellido, p.primer_nombre
")->fetchAll(PDO::FETCH_ASSOC);

echo "Docentes encontrados: " . count($docentes) . "\n";
foreach (array_slice($docentes, 0, 5) as $d) {
    echo " - {$d['primer_apellido']} {$d['primer_nombre']} ({$d['tipo_documento']} {$d['documento_identidad']})\n";
}

// 2. Verificar calificaciones subidas
$stmtCalif = $pdo->query("
    SELECT cl.id, cl.matricula_id, m.estudiante_id,
           cl.asignatura_id, a.nombre_asignatura, a.codigo AS asignatura_codigo,
           CASE 
               WHEN a.nombre_asignatura LIKE '%MATEMÁTICA%' OR a.nombre_asignatura LIKE '%TRIGONOMETR%' THEN 5
               WHEN a.nombre_asignatura LIKE '%LENGUA%' OR a.nombre_asignatura LIKE '%CASTELLANA%' THEN 5
               WHEN a.nombre_asignatura LIKE '%CIENCIAS NATURALES%' OR a.nombre_asignatura LIKE '%QUÍMICA%' OR a.nombre_asignatura LIKE '%FÍSICA%' THEN 4
               WHEN a.nombre_asignatura LIKE '%CIENCIAS SOCIALES%' THEN 4
               WHEN a.nombre_asignatura LIKE '%INGLÉS%' OR a.nombre_asignatura LIKE '%EXTRANJERO%' THEN 3
               WHEN a.nombre_asignatura LIKE '%EDUCACIÓN FÍSICA%' THEN 2
               WHEN a.nombre_asignatura LIKE '%INFORMÁTICA%' OR a.nombre_asignatura LIKE '%TECNOLOGÍA%' THEN 2
               ELSE 2
           END AS ih,
           cl.periodo, cl.nota_final, cl.logros_observaciones
    FROM calificaciones_logros cl
    INNER JOIN matriculas m ON cl.matricula_id = m.id
    INNER JOIN asignaturas a ON cl.asignatura_id = a.id
    WHERE cl.nota_final IS NOT NULL AND cl.nota_final > 0
    ORDER BY m.estudiante_id, cl.periodo, a.nombre_asignatura
");
$allCalif = $stmtCalif->fetchAll(PDO::FETCH_ASSOC);
echo "\nTotal calificaciones subidas en el sistema: " . count($allCalif) . "\n";

$estudiantesConNotas = [];
foreach ($allCalif as $c) {
    $estudiantesConNotas[$c['estudiante_id']][] = $c;
}

echo "Estudiantes con calificaciones subidas: " . count($estudiantesConNotas) . "\n";
foreach ($estudiantesConNotas as $estId => $notas) {
    echo " Estudiante ID {$estId}: " . count($notas) . " materias subidas.\n";
    foreach ($notas as $n) {
        $n10 = $n['nota_final'] <= 5 ? $n['nota_final'] * 2 : $n['nota_final'];
        echo "   * Periodo {$n['periodo']} | {$n['nombre_asignatura']} | IH: {$n['ih']} | Nota: {$n10}\n";
    }
}
