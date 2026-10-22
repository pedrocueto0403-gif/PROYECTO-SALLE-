<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getInstance()->getConnection();

$stmt = $pdo->query("
    SELECT cl.id, cl.matricula_id, m.estudiante_id,
           cl.asignatura_id, a.nombre_asignatura, a.codigo AS asignatura_codigo,
           cl.periodo, cl.nota_final, cl.logros_observaciones
    FROM calificaciones_logros cl
    INNER JOIN matriculas m ON cl.matricula_id = m.id
    INNER JOIN asignaturas a ON cl.asignatura_id = a.id
    WHERE cl.nota_final IS NOT NULL AND cl.nota_final > 0
    ORDER BY m.estudiante_id, cl.periodo, a.nombre_asignatura
");
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total grades found: " . count($grades) . "\n";
print_r($grades);
