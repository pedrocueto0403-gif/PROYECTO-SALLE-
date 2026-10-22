<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

echo "Starting migration of Sedes and Cursos...\n";

// 1. Ensure column sede_solicitada in solicitudes_admision
$colsSol = $pdo->query("SHOW COLUMNS FROM solicitudes_admision")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('sede_solicitada', $colsSol)) {
    $pdo->exec("ALTER TABLE solicitudes_admision ADD COLUMN `sede_solicitada` VARCHAR(60) NOT NULL DEFAULT 'Sede Principal' AFTER `jornada_solicitada`");
    echo "✓ Added sede_solicitada to solicitudes_admision\n";
} else {
    echo "• sede_solicitada already exists in solicitudes_admision\n";
}

// 2. Ensure unique key uk_curso_ano_jornada_sede in cursos
$keys = $pdo->query("SHOW INDEX FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
$keyNames = array_unique(array_column($keys, 'Key_name'));
if (in_array('uk_curso_ano_jornada', $keyNames)) {
    $pdo->exec("ALTER TABLE cursos DROP INDEX `uk_curso_ano_jornada`");
    echo "✓ Dropped old uk_curso_ano_jornada\n";
}
if (!in_array('uk_curso_ano_jornada_sede', $keyNames)) {
    $pdo->exec("ALTER TABLE cursos ADD UNIQUE KEY `uk_curso_ano_jornada_sede` (`grado`, `grupo`, `ano_lectivo`, `jornada`, `sede`)");
    echo "✓ Added uk_curso_ano_jornada_sede\n";
}

// 3. Setup the 60 official courses
$grados = [
    'Párvulo', 'Prejardín', 'Jardín', 'Transición',
    '1°', '2°', '3°', '4°', '5°',
    '6°', '7°', '8°', '9°', '10°', '11°'
];
$sedes = ['Sede Principal', 'Sede Victoria'];

// Check existing courses
$existing = $pdo->query("SELECT id, grado, grupo, jornada, sede, ano_lectivo FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
echo "Existing courses in DB: " . count($existing) . "\n";

// Insert courses for both Sedes:
// Jornada Mañana -> Grupo A (Ej: 1A)
// Jornada Tarde -> Grupo B (Ej: 1B)
$stmtFind = $pdo->prepare("SELECT id FROM cursos WHERE grado = :gr AND grupo = :gp AND jornada = :jor AND sede = :sede AND ano_lectivo = 2026 LIMIT 1");
$stmtIns = $pdo->prepare("INSERT INTO cursos (grado, grupo, jornada, sede, ano_lectivo) VALUES (:gr, :gp, :jor, :sede, 2026)");

$inserted = 0;
foreach ($sedes as $sede) {
    foreach ($grados as $gr) {
        // Mañana -> A
        $stmtFind->execute([':gr' => $gr, ':gp' => 'A', ':jor' => 'Mañana', ':sede' => $sede]);
        if (!$stmtFind->fetchColumn()) {
            $stmtIns->execute([':gr' => $gr, ':gp' => 'A', ':jor' => 'Mañana', ':sede' => $sede]);
            $inserted++;
        }
        // Tarde -> B
        $stmtFind->execute([':gr' => $gr, ':gp' => 'B', ':jor' => 'Tarde', ':sede' => $sede]);
        if (!$stmtFind->fetchColumn()) {
            $stmtIns->execute([':gr' => $gr, ':gp' => 'B', ':jor' => 'Tarde', ':sede' => $sede]);
            $inserted++;
        }
    }
}
echo "New courses inserted: $inserted\n";

// Also clean up any courses that have group B in Mañana or group A in Tarde if they have NO matriculas
$obsolete = $pdo->query("
    SELECT c.id, c.grado, c.grupo, c.jornada, c.sede 
    FROM cursos c
    LEFT JOIN matriculas m ON c.id = m.curso_id
    WHERE m.id IS NULL 
      AND ((c.jornada = 'Mañana' AND c.grupo != 'A') OR (c.jornada = 'Tarde' AND c.grupo != 'B'))
")->fetchAll(PDO::FETCH_ASSOC);

echo "Obsolete courses with no students (e.g. 1B Mañana or 1A Tarde): " . count($obsolete) . "\n";
// Let's delete obsolete courses that have no students or CAD dependencies, or reassign CAD if needed
$stmtDel = $pdo->prepare("DELETE FROM cursos WHERE id = :id");
$stmtCADCheck = $pdo->prepare("SELECT COUNT(*) FROM curso_asignatura_docente WHERE curso_id = :cid");
$stmtDelCAD = $pdo->prepare("DELETE FROM curso_asignatura_docente WHERE curso_id = :cid");

foreach ($obsolete as $obs) {
    $stmtCADCheck->execute([':cid' => $obs['id']]);
    $cadCount = intval($stmtCADCheck->fetchColumn());
    if ($cadCount > 0) {
        $stmtDelCAD->execute([':cid' => $obs['id']]);
    }
    $stmtDel->execute([':id' => $obs['id']]);
    echo "Deleted obsolete course ID {$obs['id']}: {$obs['grado']} {$obs['grupo']} {$obs['jornada']} ({$obs['sede']})\n";
}

$finalCursos = $pdo->query("SELECT id, grado, grupo, jornada, sede, ano_lectivo FROM cursos ORDER BY FIELD(sede, 'Sede Principal', 'Sede Victoria'), FIELD(jornada, 'Mañana', 'Tarde'), id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "\nTotal final courses: " . count($finalCursos) . "\n";
foreach ($finalCursos as $fc) {
    echo "ID {$fc['id']}: {$fc['grado']} {$fc['grupo']} ({$fc['jornada']} - {$fc['sede']})\n";
}
