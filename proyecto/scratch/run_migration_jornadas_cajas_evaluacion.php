<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        die("No se pudo conectar a la base de datos.");
    }

    echo "--- INICIANDO MIGRACIÓN AUTOMÁTICA ---\n";

    // 1. Columnas en tabla `cursos` (jornada, sede)
    $cursosCols = $pdo->query("SHOW COLUMNS FROM cursos")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('jornada', $cursosCols)) {
        $pdo->exec("ALTER TABLE cursos ADD COLUMN `jornada` VARCHAR(30) NOT NULL DEFAULT 'Mañana' AFTER `grupo`");
        echo "✓ Columna 'jornada' agregada a tabla 'cursos'.\n";
    }
    if (!in_array('sede', $cursosCols)) {
        $pdo->exec("ALTER TABLE cursos ADD COLUMN `sede` VARCHAR(60) NOT NULL DEFAULT 'Sede Principal' AFTER `jornada`");
        echo "✓ Columna 'sede' agregada a tabla 'cursos'.\n";
    }

    // Modificar índice único si existe para incluir jornada
    try {
        $pdo->exec("ALTER TABLE cursos DROP INDEX `uk_curso_ano`");
        $pdo->exec("ALTER TABLE cursos ADD UNIQUE KEY `uk_curso_ano_jornada` (`grado`, `grupo`, `ano_lectivo`, `jornada`)");
        echo "✓ Índice único de cursos actualizado para incluir jornada.\n";
    } catch (Exception $e) {
        // Puede que ya tenga el índice nuevo o diferente nombre
    }

    // Asegurar cursos para Jornada Mañana y Jornada Tarde
    $grados = ['Transición', '1°', '2°', '3°', '4°', '5°', '6°', '7°', '8°', '9°', '10°', '11°'];
    $grupos = ['A', 'B'];
    $jornadas = ['Mañana', 'Tarde'];

    foreach ($jornadas as $jor) {
        foreach ($grados as $gr) {
            foreach ($grupos as $gp) {
                $chk = $pdo->prepare("SELECT id FROM cursos WHERE grado = :gr AND grupo = :gp AND ano_lectivo = 2026 AND jornada = :jor LIMIT 1");
                $chk->execute([':gr' => $gr, ':gp' => $gp, ':jor' => $jor]);
                if (!$chk->fetchColumn()) {
                    $ins = $pdo->prepare("INSERT INTO cursos (grado, grupo, jornada, sede, ano_lectivo) VALUES (:gr, :gp, :jor, 'Sede Principal', 2026)");
                    $ins->execute([':gr' => $gr, ':gp' => $gp, ':jor' => $jor]);
                }
            }
        }
    }
    echo "✓ Cursos para Jornada Mañana y Jornada Tarde asegurados (2026).\n";

    // 2. Columna `sede_caja` en `perfiles_detalles`
    $perfilesCols = $pdo->query("SHOW COLUMNS FROM perfiles_detalles")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('sede_caja', $perfilesCols)) {
        $pdo->exec("ALTER TABLE perfiles_detalles ADD COLUMN `sede_caja` VARCHAR(50) NOT NULL DEFAULT 'ambas' AFTER `direccion`");
        echo "✓ Columna 'sede_caja' agregada a 'perfiles_detalles'.\n";
    }

    // 3. Columna `sede_caja` en `contabilidad_caja`
    $cajaCols = $pdo->query("SHOW COLUMNS FROM contabilidad_caja")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('sede_caja', $cajaCols)) {
        $pdo->exec("ALTER TABLE contabilidad_caja ADD COLUMN `sede_caja` VARCHAR(50) NOT NULL DEFAULT 'principal' AFTER `recibo_numero`");
        echo "✓ Columna 'sede_caja' agregada a 'contabilidad_caja'.\n";
    }

    // 4. Columna `sede_caja` en `contabilidad_egresos`
    $egresosCols = $pdo->query("SHOW COLUMNS FROM contabilidad_egresos")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('sede_caja', $egresosCols)) {
        $pdo->exec("ALTER TABLE contabilidad_egresos ADD COLUMN `sede_caja` VARCHAR(50) NOT NULL DEFAULT 'principal' AFTER `soporte_factura`");
        echo "✓ Columna 'sede_caja' agregada a 'contabilidad_egresos'.\n";
    }

    // 5. Tabla `evaluacion_docente_periodos`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `evaluacion_docente_periodos` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `titulo` VARCHAR(150) NOT NULL DEFAULT 'Evaluación Docente 2026 - Período 1',
            `fecha_inicio` DATETIME NOT NULL,
            `fecha_fin` DATETIME NOT NULL,
            `estado` ENUM('activo', 'inactivo', 'cerrado') NOT NULL DEFAULT 'activo',
            `ano_lectivo` INT NOT NULL DEFAULT 2026,
            `periodo_academico` INT NOT NULL DEFAULT 1,
            `indicaciones` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabla 'evaluacion_docente_periodos' verificada/creada.\n";

    // 6. Tabla `evaluaciones_docentes`
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `evaluaciones_docentes` (
            `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
            `periodo_id` INT NOT NULL,
            `estudiante_id` BIGINT NOT NULL,
            `docente_id` BIGINT NOT NULL,
            `curso_id` INT NOT NULL,
            `asignatura_id` INT NULL,
            `criterio_dominio` TINYINT NOT NULL DEFAULT 5,
            `criterio_metodologia` TINYINT NOT NULL DEFAULT 5,
            `criterio_respeto` TINYINT NOT NULL DEFAULT 5,
            `criterio_evaluacion` TINYINT NOT NULL DEFAULT 5,
            `criterio_motivacion` TINYINT NOT NULL DEFAULT 5,
            `puntaje_promedio` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
            `comentarios` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_eval_est_doc_per` (`periodo_id`, `estudiante_id`, `docente_id`),
            INDEX `idx_eval_doc` (`docente_id`),
            INDEX `idx_eval_per` (`periodo_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Tabla 'evaluaciones_docentes' verificada/creada.\n";

    // 7. Asegurar que exista un período activo por defecto si no hay ninguno
    $hasPeriod = $pdo->query("SELECT id FROM evaluacion_docente_periodos LIMIT 1")->fetchColumn();
    if (!$hasPeriod) {
        $stmtPer = $pdo->prepare("
            INSERT INTO evaluacion_docente_periodos 
            (titulo, fecha_inicio, fecha_fin, estado, ano_lectivo, periodo_academico, indicaciones)
            VALUES 
            ('Evaluación Institucional Docente 2026 - I Semestre', 
             '2026-02-01 08:00:00', 
             '2026-12-15 23:59:59', 
             'activo', 
             2026, 
             1, 
             'Apreciado estudiante: califica a tus docentes con sinceridad y respeto para continuar mejorando la calidad pedagógica salle.')
        ");
        $stmtPer->execute();
        $perId = $pdo->lastInsertId();
        echo "✓ Período de evaluación inicial creado (ID: {$perId}).\n";

        // Asegurar algunas asignaciones y evaluaciones demo para gráficos iniciales
        // Encontrar docentes
        $docs = $pdo->query("SELECT u.id, pd.primer_nombre, pd.primer_apellido FROM usuarios u JOIN perfiles_detalles pd ON u.id = pd.usuario_id WHERE u.rol_id = 3 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $ests = $pdo->query("SELECT u.id, pd.primer_nombre, pd.primer_apellido FROM usuarios u JOIN perfiles_detalles pd ON u.id = pd.usuario_id WHERE u.rol_id = 5 LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($docs) && !empty($ests)) {
            $sampleComments = [
                'Excelente profesor, explica con claridad y paciencia en cada clase.',
                'Utiliza muy buenas dinámicas y herramientas virtuales. Clases muy entretenidas.',
                'Siempre llega puntual y atiende todas las dudas con mucho respeto.',
                'Sus evaluaciones son muy coherentes con lo enseñado en el período.',
                'Gran compromiso pedagógico y motivación constante a los estudiantes.'
            ];

            foreach ($docs as $doc) {
                $docId = $doc['id'];
                // Crear 2-3 evaluaciones por docente
                foreach (array_slice($ests, 0, 4) as $idx => $est) {
                    $estId = $est['id'];
                    $c1 = rand(4, 5);
                    $c2 = rand(4, 5);
                    $c3 = rand(4, 5);
                    $c4 = rand(4, 5);
                    $c5 = rand(4, 5);
                    $prom = round(($c1 + $c2 + $c3 + $c4 + $c5) / 5, 2);
                    $comm = $sampleComments[$idx % count($sampleComments)];

                    $insEval = $pdo->prepare("
                        INSERT IGNORE INTO evaluaciones_docentes
                        (periodo_id, estudiante_id, docente_id, curso_id, criterio_dominio, criterio_metodologia, criterio_respeto, criterio_evaluacion, criterio_motivacion, puntaje_promedio, comentarios)
                        VALUES
                        (:pid, :eid, :did, 1, :c1, :c2, :c3, :c4, :c5, :prom, :comm)
                    ");
                    $insEval->execute([
                        ':pid' => $perId,
                        ':eid' => $estId,
                        ':did' => $docId,
                        ':c1' => $c1,
                        ':c2' => $c2,
                        ':c3' => $c3,
                        ':c4' => $c4,
                        ':c5' => $c5,
                        ':prom' => $prom,
                        ':comm' => $comm
                    ]);
                }
            }
            echo "✓ Evaluaciones iniciales de muestra registradas para gráficos dinámicos.\n";
        }
    }

    // 8. Distribuir recibos de muestra en las dos sedes para que se aprecie la separación de cajas
    $pdo->exec("UPDATE contabilidad_caja SET sede_caja = 'principal' WHERE id % 2 = 1 AND (sede_caja IS NULL OR sede_caja = '')");
    $pdo->exec("UPDATE contabilidad_caja SET sede_caja = 'segunda' WHERE id % 2 = 0 AND (sede_caja IS NULL OR sede_caja = '')");
    $pdo->exec("UPDATE contabilidad_egresos SET sede_caja = 'principal' WHERE id % 2 = 1 AND (sede_caja IS NULL OR sede_caja = '')");
    $pdo->exec("UPDATE contabilidad_egresos SET sede_caja = 'segunda' WHERE id % 2 = 0 AND (sede_caja IS NULL OR sede_caja = '')");
    echo "✓ Registros contables clasificados en Caja Principal y Caja Segunda.\n";

    echo "--- MIGRACIÓN COMPLETADA CON ÉXITO ---\n";

} catch (Exception $e) {
    echo "ERROR EN MIGRACIÓN: " . $e->getMessage() . "\n";
    exit(1);
}
