<?php
$databases = ['aulavirtual_salle', 'aulavir7_aulavirtualsalle'];

$grados = [
    'Transición',
    '1°', '2°', '3°', '4°', '5°',
    '6°', '7°', '8°', '9°',
    '10°', '11°'
];
$grupos = ['A', 'B'];

foreach ($databases as $dbName) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$dbName", 'root', '123456789', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "=====================================\n";
        echo "Processing database: $dbName\n";
        echo "=====================================\n";

        // 1. Ensure all courses exist
        foreach ($grados as $gr) {
            foreach ($grupos as $gp) {
                $stmtCheck = $pdo->prepare("SELECT id FROM cursos WHERE grado = :grado AND grupo = :grupo AND ano_lectivo = 2026");
                $stmtCheck->execute([':grado' => $gr, ':grupo' => $gp]);
                $existing = $stmtCheck->fetchColumn();
                if (!$existing) {
                    $stmtIns = $pdo->prepare("INSERT INTO cursos (grado, grupo, ano_lectivo) VALUES (:grado, :grupo, 2026)");
                    $stmtIns->execute([':grado' => $gr, ':grupo' => $gp]);
                    echo "Added course: Grado {$gr} Grupo {$gp} (ID: {$pdo->lastInsertId()})\n";
                } else {
                    echo "Course already exists: Grado {$gr} Grupo {$gp} (ID: {$existing})\n";
                }
            }
        }

        // Get ID of 11° A
        $stmt11A = $pdo->prepare("SELECT id FROM cursos WHERE grado = '11°' AND grupo = 'A' AND ano_lectivo = 2026 LIMIT 1");
        $stmt11A->execute();
        $curso11AId = $stmt11A->fetchColumn();
        echo "Curso 11° A ID is: $curso11AId\n";

        if (!$curso11AId) {
            throw new Exception("Curso 11° A could not be found or created!");
        }

        // 2. Fetch all students (rol_id = 5)
        $students = $pdo->query("SELECT u.id, u.correo, p.primer_nombre, p.primer_apellido 
                                 FROM usuarios u 
                                 LEFT JOIN perfiles_detalles p ON u.id = p.usuario_id 
                                 WHERE u.rol_id = 5")->fetchAll(PDO::FETCH_ASSOC);

        echo "Found " . count($students) . " students to register in 11° A.\n";

        foreach ($students as $st) {
            $estId = $st['id'];
            $stmtMat = $pdo->prepare("SELECT id, curso_id FROM matriculas WHERE estudiante_id = :estId AND ano_lectivo = 2026");
            $stmtMat->execute([':estId' => $estId]);
            $mat = $stmtMat->fetch(PDO::FETCH_ASSOC);

            if ($mat) {
                if ($mat['curso_id'] != $curso11AId) {
                    $stmtUp = $pdo->prepare("UPDATE matriculas SET curso_id = :cid WHERE id = :mid");
                    $stmtUp->execute([':cid' => $curso11AId, ':mid' => $mat['id']]);
                    echo "Updated Estudiante ID {$estId} ({$st['primer_apellido']} {$st['primer_nombre']}) to Curso 11° A (MatID: {$mat['id']})\n";
                } else {
                    echo "Estudiante ID {$estId} ({$st['primer_apellido']} {$st['primer_nombre']}) already in 11° A\n";
                }
            } else {
                $codigo = "EST-2026-" . str_pad($estId, 5, '0', STR_PAD_LEFT);
                $stmtInsMat = $pdo->prepare("
                    INSERT INTO matriculas (estudiante_id, curso_id, ano_lectivo, estado_matricula, codigo_matricula)
                    VALUES (:estId, :cid, 2026, 'activa', :cod)
                ");
                $stmtInsMat->execute([
                    ':estId' => $estId,
                    ':cid'   => $curso11AId,
                    ':cod'   => $codigo
                ]);
                echo "Enrolled Estudiante ID {$estId} ({$st['primer_apellido']} {$st['primer_nombre']}) into 11° A (New MatID: {$pdo->lastInsertId()})\n";
            }
        }

        echo "Database $dbName updated successfully!\n\n";

    } catch (Exception $e) {
        echo "Error in $dbName: " . $e->getMessage() . "\n\n";
    }
}
