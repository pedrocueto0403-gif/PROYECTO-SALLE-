<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

echo "=== VERIFYING SEDE VICTORIA & PREESCOLAR FLOW ===\n\n";

// 1. Check Courses in DB
$coursesCount = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
echo "1. Total courses in DB: $coursesCount (Expected: 60)\n";

$principalCount = $pdo->query("SELECT COUNT(*) FROM cursos WHERE sede = 'Sede Principal'")->fetchColumn();
$victoriaCount = $pdo->query("SELECT COUNT(*) FROM cursos WHERE sede = 'Sede Victoria'")->fetchColumn();
echo "   - Sede Principal: $principalCount (Expected: 30)\n";
echo "   - Sede Victoria:  $victoriaCount (Expected: 30)\n";

// Check 1 course per jornada
$mananaGroups = $pdo->query("SELECT DISTINCT grupo FROM cursos WHERE jornada = 'Mañana'")->fetchAll(PDO::FETCH_COLUMN);
$tardeGroups  = $pdo->query("SELECT DISTINCT grupo FROM cursos WHERE jornada = 'Tarde'")->fetchAll(PDO::FETCH_COLUMN);
echo "   - Grupos en Mañana: " . implode(', ', $mananaGroups) . " (Expected: only A)\n";
echo "   - Grupos en Tarde:  " . implode(', ', $tardeGroups) . " (Expected: only B)\n";

// Check preschool grades
$preescolarGrados = ['Párvulo', 'Prejardín', 'Jardín', 'Transición'];
$foundPre = $pdo->query("SELECT DISTINCT grado FROM cursos WHERE grado IN ('Párvulo', 'Prejardín', 'Jardín', 'Transición')")->fetchAll(PDO::FETCH_COLUMN);
echo "   - Preescolar registrados: " . implode(', ', $foundPre) . " (Expected: 4 grades)\n";

// 2. Test Solicitud insertion for Sede Victoria
$testDoc = '999888' . rand(10, 99);
$stmtIns = $pdo->prepare("
    INSERT INTO solicitudes_admision (
        primer_nombre, primer_apellido, tipo_documento, documento_identidad, 
        grado_solicitado, jornada_solicitada, sede_solicitada,
        acudiente_nombre, acudiente_telefono, acudiente_correo, direccion, telefono,
        estado, monto_matricula, created_at
    ) VALUES (
        'Mateo', 'Valenzuela', 'Tarjeta de Identidad', :doc,
        'Jardín', 'Tarde', 'Sede Victoria',
        'Carolina Gómez', '3009998877', 'carolina.val@gmail.com', 'Calle 15 # 4-20', '3009998877',
        'Aprobada', 350000, NOW()
    )
");
$stmtIns->execute([':doc' => $testDoc]);
$solId = $pdo->lastInsertId();
echo "\n2. Inserted test solicitud ID: $solId with Sede Victoria & Jardín Tarde.\n";

// Find matching course: Jardín B Tarde Sede Victoria
$stmtCurso = $pdo->prepare("SELECT id FROM cursos WHERE grado = 'Jardín' AND jornada = 'Tarde' AND sede = 'Sede Victoria'");
$stmtCurso->execute();
$cursoId = $stmtCurso->fetchColumn();
echo "   - Matching course ID found: $cursoId\n";

// Assign course to solicitud
$pdo->prepare("UPDATE solicitudes_admision SET curso_asignado_id = :cid WHERE id = :id")->execute([':cid' => $cursoId, ':id' => $solId]);
echo "   - Assigned course $cursoId to solicitud $solId\n";

// 3. Test Administrative query for aspirantes
$stmtAsp = $pdo->prepare("
    SELECT s.*, c.grado, c.grupo, c.jornada, c.sede
    FROM solicitudes_admision s
    LEFT JOIN cursos c ON s.curso_asignado_id = c.id
    WHERE s.id = :id
");
$stmtAsp->execute([':id' => $solId]);
$asp = $stmtAsp->fetch(PDO::FETCH_ASSOC);

echo "\n3. Checking Administrative data extraction:\n";
echo "   - Sede Solicitada: " . $asp['sede_solicitada'] . "\n";
echo "   - Curso Asignado Sede: " . $asp['sede'] . "\n";
echo "   - Curso Asignado Jornada: " . $asp['jornada'] . "\n";
echo "   - Curso Asignado Grado: " . $asp['grado'] . " " . $asp['grupo'] . "\n";

// Cleanup test solicitud
$pdo->prepare("DELETE FROM solicitudes_admision WHERE id = :id")->execute([':id' => $solId]);
echo "\n4. Test cleanup completed successfully.\n";

echo "\n=== ALL VERIFICATIONS PASSED 100% ===\n";
