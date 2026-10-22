<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

// Check if CAD has rows
$count = $pdo->query("SELECT COUNT(*) FROM curso_asignatura_docente")->fetchColumn();
echo "Existing CAD count: $count" . PHP_EOL;

if ($count == 0) {
    // Get teachers
    $teachers = $pdo->query("SELECT id FROM usuarios WHERE rol_id = 3 ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    // Get courses
    $courses = $pdo->query("SELECT id FROM cursos ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    // Get subjects
    $subjects = $pdo->query("SELECT id FROM asignaturas ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($teachers) && !empty($courses) && !empty($subjects)) {
        $stmtIns = $pdo->prepare("INSERT IGNORE INTO curso_asignatura_docente (curso_id, asignatura_id, docente_id) VALUES (:cid, :aid, :did)");
        $tCount = count($teachers);
        $sCount = min(8, count($subjects));

        $inserted = 0;
        foreach ($courses as $cIdx => $cid) {
            for ($s = 0; $s < $sCount; $s++) {
                $aid = $subjects[$s];
                // Deterministic teacher assignment
                $did = $teachers[($cIdx + $s) % $tCount];
                $stmtIns->execute([':cid' => $cid, ':aid' => $aid, ':did' => $did]);
                $inserted++;
            }
        }
        echo "Successfully seeded $inserted CAD rows across " . count($courses) . " courses." . PHP_EOL;
    }
}
