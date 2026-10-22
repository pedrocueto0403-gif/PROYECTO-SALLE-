<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=aulavirtual_salle;charset=utf8mb4', 'root', '123456789');

$usedInMat = $pdo->query("SELECT DISTINCT curso_id FROM matriculas")->fetchAll(PDO::FETCH_COLUMN);
echo "Used in matriculas: " . implode(', ', $usedInMat) . "\n";

$usedInCAD = $pdo->query("SELECT DISTINCT curso_id FROM curso_asignatura_docente")->fetchAll(PDO::FETCH_COLUMN);
echo "Used in CAD: " . implode(', ', $usedInCAD) . "\n";

$usedInPlanes = $pdo->query("SELECT DISTINCT curso_id FROM planes_clase")->fetchAll(PDO::FETCH_COLUMN);
echo "Used in planes_clase: " . implode(', ', $usedInPlanes) . "\n";

$usedInEval = $pdo->query("SELECT DISTINCT curso_id FROM evaluaciones_docentes")->fetchAll(PDO::FETCH_COLUMN);
echo "Used in evaluaciones_docentes: " . implode(', ', $usedInEval) . "\n";

$usedInSol = $pdo->query("SELECT DISTINCT curso_asignado_id FROM solicitudes_admision WHERE curso_asignado_id IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
echo "Used in solicitudes_admision: " . implode(', ', $usedInSol) . "\n";
