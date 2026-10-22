<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
require_once __DIR__ . '/../../config/Database.php';
Router::enforceAccess('comunidad');

$userName    = $_SESSION['user_name'] ?? 'Mateo Martínez';
$userRole    = $_SESSION['user_role'] ?? 'estudiante';
$userId      = $_SESSION['user_id'];

$dbInstance = Database::getInstance();
$pdo = $dbInstance->getConnection();

// Si es acudiente, necesitamos buscar el estudiante vinculado a él.
// Por ahora asumimos que el usuario logueado ES el estudiante (rol 5 = estudiante, rol 6 = acudiente)
$estudiante_id = $userId;

// Cargar perfil del estudiante
$profile = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.correo AS email, c.id AS curso_id, c.grado, c.grupo, c.jornada, c.ano_lectivo, m.id AS matricula_id, m.codigo_matricula
            FROM perfiles_detalles p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            WHERE p.usuario_id = :uid
        ");
        $stmt->execute([':uid' => $estudiante_id]);
        $profile = $stmt->fetch();
    } catch (Exception $e) { $profile = null; }
}

if (!$profile) {
    $profile = [
        'primer_nombre' => 'Mateo',
        'segundo_nombre' => 'Andrés',
        'primer_apellido' => 'Martínez',
        'segundo_apellido' => 'Suárez',
        'tipo_documento' => 'TI',
        'documento_identidad' => '1082987005',
        'email' => 'estudiante.martinez@lasallecienaga.edu.co',
        'curso_id' => 1,
        'grado' => '10°',
        'grupo' => 'A',
        'jornada' => 'Mañana',
        'ano_lectivo' => 2026,
        'matricula_id' => 1,
        'codigo_matricula' => 'EST-2026-10829',
        'acudiente_nombre' => 'Jorge Luis Martínez Blanco',
        'acudiente_telefono' => '3201122334'
    ];
}

$matricula_id = $profile['matricula_id'] ?? 1;
$estudianteCursoId = $profile['curso_id'] ?? 1;

$flashMsg = '';
$flashType = 'info';

// Consultar Periodo de Evaluación Docente (Vigente / Activo)
$evalPeriodoActivo = null;
$evalPeriodoUltimo = null;
if ($pdo) {
    try {
        $stmtPer = $pdo->query("
            SELECT * FROM evaluacion_docente_periodos 
            WHERE estado = 'activo' AND (NOW() BETWEEN fecha_inicio AND fecha_fin) 
            ORDER BY id DESC LIMIT 1
        ");
        $evalPeriodoActivo = $stmtPer->fetch(PDO::FETCH_ASSOC);

        $stmtLastPer = $pdo->query("SELECT * FROM evaluacion_docente_periodos ORDER BY id DESC LIMIT 1");
        $evalPeriodoUltimo = $stmtLastPer->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Procesar Calificación de Evaluación Docente por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'guardar_evaluacion_docente') {
    if (!$pdo || !$estudiante_id) {
        $flashMsg = "Sesión no válida para enviar evaluación docente.";
        $flashType = "danger";
    } elseif (empty($evalPeriodoActivo)) {
        $flashMsg = "⚠️ El módulo de Evaluación Docente no se encuentra disponible en este momento o el periodo ha concluido.";
        $flashType = "warning";
    } else {
        $targetDocenteId = intval($_POST['docente_id'] ?? 0);
        $targetCursoId   = intval($_POST['curso_id'] ?? $estudianteCursoId);
        $c1 = min(5, max(1, floatval($_POST['criterio_dominio'] ?? 5)));
        $c2 = min(5, max(1, floatval($_POST['criterio_metodologia'] ?? 5)));
        $c3 = min(5, max(1, floatval($_POST['criterio_respeto'] ?? 5)));
        $c4 = min(5, max(1, floatval($_POST['criterio_evaluacion'] ?? 5)));
        $c5 = min(5, max(1, floatval($_POST['criterio_motivacion'] ?? 5)));
        $comentario = trim($_POST['comentarios'] ?? '');
        $promedioEval = round(($c1 + $c2 + $c3 + $c4 + $c5) / 5.0, 2);

        if ($targetDocenteId <= 0) {
            $flashMsg = "Docente no válido para evaluar.";
            $flashType = "danger";
        } else {
            try {
                // Compatibilidad segura para timestamp (creado_en y/o created_at)
                $colNames = $pdo->query("SHOW COLUMNS FROM evaluaciones_docentes")->fetchAll(PDO::FETCH_COLUMN);
                $extraCols = [];
                $extraVals = [];
                $extraUpd  = [];
                if (in_array('creado_en', $colNames)) {
                    $extraCols[] = 'creado_en';
                    $extraVals[] = 'NOW()';
                    $extraUpd[]  = 'creado_en = NOW()';
                }
                if (in_array('created_at', $colNames)) {
                    $extraCols[] = 'created_at';
                    $extraVals[] = 'NOW()';
                    $extraUpd[]  = 'created_at = NOW()';
                }
                $strExtraCols = !empty($extraCols) ? ', ' . implode(', ', $extraCols) : '';
                $strExtraVals = !empty($extraVals) ? ', ' . implode(', ', $extraVals) : '';
                $strExtraUpd  = !empty($extraUpd)  ? ', ' . implode(', ', $extraUpd)  : '';

                $stmtInsEval = $pdo->prepare("
                    INSERT INTO evaluaciones_docentes 
                    (periodo_id, estudiante_id, docente_id, curso_id, criterio_dominio, criterio_metodologia, criterio_respeto, criterio_evaluacion, criterio_motivacion, puntaje_promedio, comentarios{$strExtraCols})
                    VALUES (:pid, :eid, :did, :cid, :c1, :c2, :c3, :c4, :c5, :prom, :com{$strExtraVals})
                    ON DUPLICATE KEY UPDATE
                    criterio_dominio = :c1, criterio_metodologia = :c2, criterio_respeto = :c3,
                    criterio_evaluacion = :c4, criterio_motivacion = :c5, puntaje_promedio = :prom,
                    comentarios = :com{$strExtraUpd}
                ");
                $stmtInsEval->execute([
                    ':pid' => $evalPeriodoActivo['id'],
                    ':eid' => $estudiante_id,
                    ':did' => $targetDocenteId,
                    ':cid' => $targetCursoId,
                    ':c1'  => $c1,
                    ':c2'  => $c2,
                    ':c3'  => $c3,
                    ':c4'  => $c4,
                    ':c5'  => $c5,
                    ':prom'=> $promedioEval,
                    ':com' => $comentario
                ]);
                $flashMsg = "✅ ¡Evaluación registrada exitosamente! Tu calificación de <strong>" . number_format($promedioEval, 1) . " / 5.0</strong> ha sido guardada. Gracias por contribuir a la excelencia académica La Salle.";
                $flashType = "success";
            } catch (Exception $e) {
                $flashMsg = "Error al registrar la evaluación: " . $e->getMessage();
                $flashType = "danger";
            }
        }
    }
}

// Cargar Docentes Asignados al Curso del Estudiante y Evaluaciones ya realizadas
$docentesAsignados = [];
$evaluacionesRealizadas = [];
if ($pdo) {
    try {
        $stmtDoc = $pdo->prepare("
            SELECT DISTINCT cad.docente_id, cad.asignatura_id, a.nombre_asignatura,
                   p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   u.correo, c.grado, c.grupo, c.jornada
            FROM curso_asignatura_docente cad
            INNER JOIN asignaturas a ON cad.asignatura_id = a.id
            INNER JOIN usuarios u ON cad.docente_id = u.id
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            INNER JOIN cursos c ON cad.curso_id = c.id
            WHERE cad.curso_id = :cid
            ORDER BY a.nombre_asignatura ASC
        ");
        $stmtDoc->execute([':cid' => $estudianteCursoId]);
        $docentesAsignados = $stmtDoc->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($evalPeriodoActivo) && $estudiante_id) {
            $stmtEvalDone = $pdo->prepare("
                SELECT docente_id, puntaje_promedio, criterio_dominio, criterio_metodologia,
                       criterio_respeto, criterio_evaluacion, criterio_motivacion, comentarios
                FROM evaluaciones_docentes
                WHERE estudiante_id = :uid AND periodo_id = :pid
            ");
            $stmtEvalDone->execute([':uid' => $estudiante_id, ':pid' => $evalPeriodoActivo['id']]);
            $rows = $stmtEvalDone->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $rw) {
                $evaluacionesRealizadas[$rw['docente_id']] = $rw;
            }
        }
    } catch (Exception $e) {}
}

// Procesar solicitud de constancia de estudio desde el portal del estudiante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'solicitar_constancia') {
    if ($pdo && $estudiante_id) {
        try {
            $stmtCheck = $pdo->prepare("SELECT id, estado_pago, recibo_numero FROM contabilidad_caja WHERE usuario_id = :uid AND concepto_pago = 'Constancia' ORDER BY id DESC LIMIT 1");
            $stmtCheck->execute([':uid' => $estudiante_id]);
            $ultConst = $stmtCheck->fetch();

            if ($ultConst && $ultConst['estado_pago'] === 'Pendiente') {
                $flashMsg = "Ya tienes una solicitud de constancia activa en estado <strong>Pendiente</strong> (Folio: <code>" . htmlspecialchars($ultConst['recibo_numero']) . "</code>). Debe cancelar los $15.000 COP en Tesorería.";
                $flashType = "warning";
            } else {
                $countCaja = $pdo->query("SELECT COUNT(*) FROM contabilidad_caja")->fetchColumn();
                $solNum = 'SOL-CONST-' . date('Y') . '-' . str_pad($countCaja + 1, 4, '0', STR_PAD_LEFT);
                $stmtIns = $pdo->prepare("
                    INSERT INTO contabilidad_caja 
                    (usuario_id, concepto_pago, monto, metodo_pago, estado_pago, fecha_pago, fecha_vencimiento, recibo_numero)
                    VALUES (:uid, 'Constancia', 15000.00, 'Efectivo', 'Pendiente', NULL, DATE_ADD(NOW(), INTERVAL 15 DAY), :recibo)
                ");
                $stmtIns->execute([':uid' => $estudiante_id, ':recibo' => $solNum]);
                $flashMsg = "✅ Solicitud de Constancia de Estudio radicada con éxito con el Folio <strong>{$solNum}</strong>. Cancele los $15.000 COP en Tesorería para habilitar la descarga oficial de inmediato.";
                $flashType = "success";
            }
        } catch (Exception $e) {
            $flashMsg = "Error al radicar solicitud: " . $e->getMessage();
            $flashType = "danger";
        }
    }
}

// Cargar estado de pago de constancia de estudio en contabilidad de caja
$constanciaPago = null;
if ($pdo && $estudiante_id) {
    try {
        $stmtCP = $pdo->prepare("SELECT * FROM contabilidad_caja WHERE usuario_id = :uid AND concepto_pago = 'Constancia' ORDER BY id DESC LIMIT 1");
        $stmtCP->execute([':uid' => $estudiante_id]);
        $constanciaPago = $stmtCP->fetch();
    } catch (Exception $e) {}
}

// Selección de periodo para el boletín
$periodoParam  = $_GET['periodo'] ?? '1';
$isConsolidado = ($periodoParam === 'todos' || $periodoParam === 'all');
$periodo       = $isConsolidado ? 'todos' : (intval($periodoParam) ?: 1);

// Cargar calificaciones del periodo seleccionado o consolidado
$gradeRows           = [];
$consolidadoSubjects = [];
$promedio            = 0;

if ($pdo && $matricula_id) {
    try {
        if ($isConsolidado) {
            $stmt = $pdo->prepare("
                SELECT cl.*, a.nombre_asignatura, a.codigo,
                       pd.primer_nombre AS doc_nombre, pd.primer_apellido AS doc_apellido
                FROM calificaciones_logros cl
                INNER JOIN asignaturas a ON cl.asignatura_id = a.id
                LEFT JOIN curso_asignatura_docente cad ON cad.asignatura_id = cl.asignatura_id
                LEFT JOIN usuarios du ON cad.docente_id = du.id
                LEFT JOIN perfiles_detalles pd ON du.id = pd.usuario_id
                WHERE cl.matricula_id = :mat_id AND cl.nota_final > 0
                ORDER BY a.nombre_asignatura, cl.periodo
            ");
            $stmt->execute([':mat_id' => $matricula_id]);
            $allRows = $stmt->fetchAll();
            foreach ($allRows as $r) {
                $aid = $r['asignatura_id'];
                if (!isset($consolidadoSubjects[$aid])) {
                    $consolidadoSubjects[$aid] = [
                        'nombre_asignatura' => $r['nombre_asignatura'],
                        'codigo'            => $r['codigo'],
                        'doc_nombre'        => $r['doc_nombre'],
                        'doc_apellido'      => $r['doc_apellido'],
                        'p1'                => null,
                        'p2'                => null,
                        'p3'                => null,
                        'p4'                => null,
                        'logros'            => $r['logros_observaciones']
                    ];
                }
                $p = intval($r['periodo']);
                if ($p >= 1 && $p <= 4) {
                    $consolidadoSubjects[$aid]['p' . $p] = floatval($r['nota_final']);
                }
                if (!empty($r['logros_observaciones'])) {
                    $consolidadoSubjects[$aid]['logros'] = $r['logros_observaciones'];
                }
            }
            foreach ($consolidadoSubjects as &$cs) {
                $valid = [];
                for ($i = 1; $i <= 4; $i++) {
                    if ($cs['p' . $i] !== null && $cs['p' . $i] > 0) $valid[] = $cs['p' . $i];
                }
                $cs['definitiva'] = !empty($valid) ? round(array_sum($valid) / count($valid), 2) : 0;
            }
            unset($cs);

            if (!empty($consolidadoSubjects)) {
                $sumDef = 0; $cntDef = 0;
                foreach ($consolidadoSubjects as $cs) {
                    if ($cs['definitiva'] > 0) {
                        $sumDef += $cs['definitiva'];
                        $cntDef++;
                    }
                }
                $promedio = $cntDef > 0 ? round($sumDef / $cntDef, 2) : 0;
            }
        } else {
            $stmt = $pdo->prepare("
                SELECT cl.*, a.nombre_asignatura, a.codigo,
                       pd.primer_nombre AS doc_nombre, pd.primer_apellido AS doc_apellido
                FROM calificaciones_logros cl
                INNER JOIN asignaturas a ON cl.asignatura_id = a.id
                LEFT JOIN curso_asignatura_docente cad ON cad.asignatura_id = cl.asignatura_id
                LEFT JOIN usuarios du ON cad.docente_id = du.id
                LEFT JOIN perfiles_detalles pd ON du.id = pd.usuario_id
                WHERE cl.matricula_id = :mat_id AND cl.periodo = :periodo AND cl.nota_final > 0
                GROUP BY a.id
                ORDER BY a.nombre_asignatura
            ");
            $stmt->execute([
                ':mat_id'   => $matricula_id,
                ':periodo'  => $periodo,
            ]);
            $gradeRows = $stmt->fetchAll();
            if (!empty($gradeRows)) {
                $suma = array_sum(array_column($gradeRows, 'nota_final'));
                $promedio = round($suma / count($gradeRows), 2);
            }
        }
    } catch (Exception $e) { $gradeRows = []; $consolidadoSubjects = []; }
}

// Cargar resumen de asistencias
$asistencias = ['total' => 0, 'asistio' => 0, 'falto' => 0, 'excusa' => 0];
if ($pdo && $matricula_id) {
    try {
        $stmt = $pdo->prepare("SELECT estado, COUNT(*) as cnt FROM asistencias WHERE matricula_id = :mat_id GROUP BY estado");
        $stmt->execute([':mat_id' => $matricula_id]);
        foreach ($stmt->fetchAll() as $row) {
            $asistencias['total'] += $row['cnt'];
            if ($row['estado'] === 'Asistió')    $asistencias['asistio'] = $row['cnt'];
            elseif ($row['estado'] === 'Faltó')  $asistencias['falto']   = $row['cnt'];
            elseif ($row['estado'] === 'Excusa') $asistencias['excusa']  = $row['cnt'];
        }
    } catch (Exception $e) { $asistencias = ['total' => 0, 'asistio' => 0, 'falto' => 0, 'excusa' => 0]; }
}

$pctAsistencia = $asistencias['total'] > 0
    ? round(($asistencias['asistio'] / $asistencias['total']) * 100)
    : 100;

// Función: determinar estado del desempeño
function desempeno(float $nota): string {
    if ($nota >= 4.6) return 'Superior';
    if ($nota >= 4.0) return 'Alto';
    if ($nota >= 3.0) return 'Básico';
    return 'Bajo';
}
function desempenoBadge(float $nota): string {
    if ($nota >= 4.6) return 'success';
    if ($nota >= 4.0) return 'primary';
    if ($nota >= 3.0) return 'warning text-dark';
    return 'danger';
}

$fullName = '';
if ($profile) {
    $fullName = trim(implode(' ', array_filter([
        $profile['primer_nombre'],
        $profile['segundo_nombre'] ?? '',
        $profile['primer_apellido'],
        $profile['segundo_apellido'] ?? '',
    ])));
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Estudiantil y de Acudientes - AulaVirtual Salle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/custom.css" rel="stylesheet">
    <style>
        /* ===== ESTILOS DE IMPRESIÓN DEL BOLETÍN OFICIAL LA SALLE ===== */
        #boletin-print { display: none; }

        @media print {
            @page { size: A4 portrait; margin: 0; }

            body.printing-boletin > *:not(#boletin-print) { display: none !important; }

            body.printing-boletin #boletin-print {
                display: block !important;
                width: 210mm;
                min-height: 297mm;
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
                font-size: 9pt;
                color: #000;
                background: #fff;
                position: relative;
                overflow: hidden;
            }

            /* Marcos decorativos esquinas */
            .blt-corner-tl { position: absolute; top: 0; left: 0; width: 28mm; height: 28mm; background: linear-gradient(135deg, #f5c400 50%, transparent 50%); z-index: 1; }
            .blt-corner-tr { position: absolute; top: 0; right: 0; width: 28mm; height: 28mm; background: linear-gradient(225deg, #2d7d32 50%, transparent 50%); z-index: 1; }
            .blt-corner-bl { position: absolute; bottom: 0; left: 0; width: 28mm; height: 28mm; background: linear-gradient(45deg, #2d7d32 50%, transparent 50%); z-index: 1; }
            .blt-corner-br { position: absolute; bottom: 0; right: 0; width: 28mm; height: 28mm; background: linear-gradient(315deg, #f5c400 50%, transparent 50%); z-index: 1; }

            /* Barras laterales */
            .blt-bar-left  { position: absolute; top: 0; left: 0; width: 6mm; height: 100%; background: linear-gradient(to bottom, #f5c400, #2d7d32); z-index: 2; }
            .blt-bar-right { position: absolute; top: 0; right: 0; width: 6mm; height: 100%; background: linear-gradient(to bottom, #2d7d32, #f5c400); z-index: 2; }

            /* Marca de agua */
            .blt-watermark {
                position: absolute;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%) rotate(-30deg);
                font-size: 72pt;
                font-weight: 900;
                color: rgba(30,58,138,0.05);
                text-transform: uppercase;
                white-space: nowrap;
                z-index: 0;
                letter-spacing: 4px;
                pointer-events: none;
            }

            .blt-content { position: relative; z-index: 3; padding: 8mm 14mm 8mm 14mm; }

            /* Encabezado */
            .blt-header { display: flex; align-items: center; justify-content: center; gap: 8mm; margin-bottom: 3mm; }
            .blt-logo { width: 22mm; height: 22mm; border-radius: 50%; border: 2px solid #1E3A8A; object-fit: contain; }
            .blt-school-info { text-align: center; }
            .blt-school-name { font-size: 22pt; font-weight: 900; color: #1E3A8A; text-transform: uppercase; line-height: 1; margin: 0; letter-spacing: 1px; }
            .blt-school-subtitle { font-size: 10pt; font-weight: bold; color: #2d7d32; text-transform: uppercase; margin: 0; }
            .blt-school-city { font-size: 10pt; font-weight: bold; color: #2d7d32; text-transform: uppercase; margin: 0 0 1mm; }
            .blt-school-meta { font-size: 7.5pt; color: #333; line-height: 1.4; margin: 0; }

            /* Título boletín */
            .blt-title-bar {
                display: flex; align-items: center; justify-content: center; gap: 4mm;
                margin: 3mm 0;
            }
            .blt-title-line { flex: 1; height: 2px; background: #f5c400; }
            .blt-title-text { font-size: 14pt; font-weight: 900; color: #1E3A8A; text-transform: uppercase; letter-spacing: 2px; }

            /* Datos del estudiante */
            .blt-student-wrap { display: flex; gap: 3mm; margin-bottom: 3mm; align-items: stretch; }
            .blt-photo-box { width: 22mm; min-height: 28mm; border: 1px solid #888; display: flex; align-items: center; justify-content: center; color: #aaa; font-size: 7pt; text-align: center; flex-shrink: 0; }
            .blt-student-table { flex: 1; border-collapse: collapse; font-size: 8pt; }
            .blt-student-table th { background: #1E3A8A; color: #fff; padding: 2.5mm 2mm; border: 1px solid #1E3A8A; text-align: center; font-size: 7.5pt; text-transform: uppercase; }
            .blt-student-table td { padding: 2.5mm 2mm; border: 1px solid #bbb; text-align: center; font-weight: bold; }
            .blt-acudiente-row td { text-align: left; font-weight: normal; }

            /* Tabla de notas */
            .blt-notas-table { width: 100%; border-collapse: collapse; font-size: 8pt; margin-bottom: 2mm; }
            .blt-notas-table thead tr th { background: #1E3A8A; color: #fff; padding: 2mm 1.5mm; border: 1px solid #1E3A8A; text-align: center; font-size: 7.5pt; }
            .blt-notas-table thead tr th:first-child { text-align: left; width: 22%; }
            .blt-notas-table thead tr th:nth-child(2) { text-align: left; width: 18%; }
            .blt-notas-table tbody tr td { padding: 1.5mm 1.5mm; border: 1px solid #ccc; vertical-align: middle; }
            .blt-notas-table tbody tr td:first-child { background: #f0f4ff; font-weight: bold; color: #1E3A8A; font-size: 7.5pt; text-transform: uppercase; }
            .blt-notas-table tbody tr td:nth-child(2) { font-size: 7.5pt; }
            .blt-area-icon { display: inline-block; width: 6mm; height: 6mm; border-radius: 2px; margin-right: 1mm; vertical-align: middle; text-align: center; line-height: 6mm; font-size: 8pt; color: #fff; }
            .blt-nota-cell { text-align: center; font-weight: bold; }
            .blt-desc-cell { font-size: 7pt; color: #333; }

            /* Convivencia */
            .blt-convivencia { border: 1px solid #1E3A8A; margin-bottom: 2mm; }
            .blt-convivencia-header { background: #1E3A8A; color: #fff; padding: 2mm 3mm; font-weight: bold; font-size: 8.5pt; text-transform: uppercase; }
            .blt-convivencia-body { padding: 2mm 3mm; min-height: 10mm; }

            /* Observaciones e info acceso */
            .blt-obs-row { display: flex; gap: 3mm; margin-bottom: 3mm; }
            .blt-obs-box { flex: 1; border: 1px solid #1E3A8A; }
            .blt-obs-header { background: #1E3A8A; color: #fff; padding: 2mm 3mm; font-weight: bold; font-size: 8pt; }
            .blt-obs-body { padding: 2mm 3mm; min-height: 12mm; font-size: 7.5pt; }
            .blt-access-box { width: 70mm; border: 1px solid #1E3A8A; }
            .blt-access-body { padding: 2mm 3mm; font-size: 7.5pt; }
            .blt-access-body strong { color: #1E3A8A; }

            /* Firmas */
            .blt-firmas { display: flex; justify-content: space-around; margin-top: 4mm; gap: 5mm; }
            .blt-firma-item { text-align: center; flex: 1; }
            .blt-firma-name { font-weight: bold; font-size: 8pt; text-transform: uppercase; }
            .blt-firma-cargo { font-size: 7.5pt; color: #555; border-top: 1px solid #333; padding-top: 1.5mm; margin-top: 7mm; }
            .blt-firma-icon { font-size: 18pt; margin-bottom: 1mm; }
        }
    </style>
</head>
<body>

<div class="app-wrapper">
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <img src="<?= ASSETS_URL ?>/assets/logo.svg" alt="La Salle Logo" style="width: 42px; height: 42px;">
            <div>
                <h6 class="font-heading mb-0 text-white fw-bold">AulaVirtual</h6>
                <small class="text-warning font-heading" style="font-size: 0.7rem;">COMUNIDAD EDUCATIVA</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#boletin" class="sidebar-link active"><i class="fas fa-graduation-cap"></i> Boletín de Calificaciones</a>
            <hr class="text-white-50 my-3">
            <a href="<?= APP_URL ?>/app/Controllers/AuthController.php?action=logout" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
                <h5 class="mb-0 font-heading fw-bold d-none d-md-block">Portal Estudiantil y de Familia</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary rounded-circle" id="themeToggleBtn"><i class="fas fa-moon"></i></button>
                <span class="fw-semibold small"><i class="fas fa-user-graduate text-warning me-1"></i> <?= htmlspecialchars($userName) ?></span>
            </div>
        </header>

        <div class="p-4">

            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['flash_success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($flashMsg)): ?>
                <div class="alert alert-<?= $flashType ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : ($flashType === 'warning' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                    <?= $flashMsg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Resumen Informativo del Estudiante -->
            <div class="custom-card mb-4" style="background: linear-gradient(135deg, #1E3A8A 0%, #2563EB 100%); color: #fff;">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        <span class="badge bg-warning text-dark mb-2">AÑO LECTIVO <?= $profile['ano_lectivo'] ?? 2026 ?></span>
                        <h4 class="fw-bold mb-1 text-white">
                            <?= htmlspecialchars($fullName ?: $userName) ?>
                        </h4>
                        <p class="mb-0 text-white-50">
                            Grado: <?= htmlspecialchars(($profile['grado'] ?? '?') . '° ' . ($profile['grupo'] ?? '')) ?>
                            &nbsp;•&nbsp; Matrícula: <?= htmlspecialchars($profile['codigo_matricula'] ?? 'EST-2026') ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="d-block small text-white-50"><?= $isConsolidado ? 'Promedio Acumulado Anual' : ('Promedio Periodo ' . $periodo) ?></span>
                        <h2 class="fw-bold text-warning mb-0"><?= $promedio > 0 ? number_format($promedio, 2) : '—' ?></h2>
                    </div>
                </div>
            </div>

            <!-- Boletín de Notas -->
            <div class="custom-card mb-4" id="boletin">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="fas fa-book-reader text-primary me-2"></i> Boletín de Notas</h5>
                        <small class="text-muted"><?= $isConsolidado ? 'Visualizando resumen consolidado de todos los periodos (1° al 4°).' : 'Seleccione el periodo para ver sus calificaciones.' ?></small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Selector de periodo -->
                        <select class="form-select form-select-sm fw-bold border-primary" style="width: auto;" onchange="window.location.href='?periodo='+this.value+'#boletin'">
                            <?php for ($p = 1; $p <= 4; $p++): ?>
                                <option value="<?= $p ?>" <?= ($periodo == $p && !$isConsolidado && $periodo !== 'separados') ? 'selected' : '' ?>>Periodo <?= $p ?></option>
                            <?php endfor; ?>
                            <option value="todos" <?= ($periodo === 'todos' || $isConsolidado) ? 'selected' : '' ?>>Todos los Periodos (Juntos / Consolidado)</option>
                            <option value="separados" <?= ($periodo === 'separados') ? 'selected' : '' ?>>Todos los Periodos (Separados / Individuales)</option>
                        </select>
                        <button class="btn btn-outline-primary btn-sm fw-bold" onclick="abrirBoletin()">
                            <i class="fas fa-file-pdf me-1"></i> Descargar / Imprimir Boletín
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <?php if ($isConsolidado): ?>
                    <!-- TABLA CONSOLIDADA (TODOS LOS PERIODOS) -->
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Asignatura</th>
                                <th class="text-center" style="width: 75px;">P1</th>
                                <th class="text-center" style="width: 75px;">P2</th>
                                <th class="text-center" style="width: 75px;">P3</th>
                                <th class="text-center" style="width: 75px;">P4</th>
                                <th class="text-center" style="width: 90px;">Definitiva</th>
                                <th>Estado / Desempeño</th>
                                <th>Logros y Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($consolidadoSubjects)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-book-open fs-2 d-block mb-2 opacity-25"></i>
                                        No hay calificaciones registradas en el sistema aún.<br>
                                        <small>Las notas aparecerán consolidadas a medida que los docentes las registren.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($consolidadoSubjects as $cs): ?>
                                    <?php $def = $cs['definitiva']; ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($cs['nombre_asignatura']) ?></strong>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($cs['codigo'] ?? '') ?></span>
                                        </td>
                                        <td class="text-center fw-bold <?= ($cs['p1'] !== null && $cs['p1'] < 3.0) ? 'text-danger' : 'text-dark' ?>"><?= $cs['p1'] !== null ? number_format($cs['p1'], 1) : '—' ?></td>
                                        <td class="text-center fw-bold <?= ($cs['p2'] !== null && $cs['p2'] < 3.0) ? 'text-danger' : 'text-dark' ?>"><?= $cs['p2'] !== null ? number_format($cs['p2'], 1) : '—' ?></td>
                                        <td class="text-center fw-bold <?= ($cs['p3'] !== null && $cs['p3'] < 3.0) ? 'text-danger' : 'text-dark' ?>"><?= $cs['p3'] !== null ? number_format($cs['p3'], 1) : '—' ?></td>
                                        <td class="text-center fw-bold <?= ($cs['p4'] !== null && $cs['p4'] < 3.0) ? 'text-danger' : 'text-dark' ?>"><?= $cs['p4'] !== null ? number_format($cs['p4'], 1) : '—' ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= desempenoBadge($def) ?> fs-6 px-3 py-2"><?= number_format($def, 1) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-<?= desempenoBadge($def) ?> fw-bold">
                                                <i class="fas fa-<?= $def >= 3.0 ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                                <?= desempeno($def) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted" style="max-width: 220px;">
                                            <span title="<?= htmlspecialchars($cs['logros'] ?? '') ?>">
                                                <?= htmlspecialchars(mb_strimwidth($cs['logros'] ?? 'Sin observación.', 0, 80, '...')) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Fila de promedio consolidado -->
                                <tr class="table-active fw-bold">
                                    <td colspan="5" class="text-end pe-3">Promedio General Consolidado (Todos los Periodos):</td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= desempenoBadge($promedio) ?> fs-5 px-3 py-2"><?= number_format($promedio, 2) ?></span>
                                    </td>
                                    <td colspan="2">
                                        <strong class="text-<?= desempenoBadge($promedio) ?>">Desempeño <?= desempeno($promedio) ?></strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <!-- TABLA DE PERIODO INDIVIDUAL -->
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Asignatura</th>
                                <th class="text-center">Cognitivo<br><small class="fw-normal text-muted">40%</small></th>
                                <th class="text-center">Proced.<br><small class="fw-normal text-muted">40%</small></th>
                                <th class="text-center">Actitud.<br><small class="fw-normal text-muted">20%</small></th>
                                <th class="text-center">Definitiva</th>
                                <th>Estado / Desempeño</th>
                                <th>Logros y Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($gradeRows)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-book-open fs-2 d-block mb-2 opacity-25"></i>
                                        No hay calificaciones registradas para el Periodo <?= $periodo ?> aún.<br>
                                        <small>Las notas serán visibles una vez el docente las registre.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($gradeRows as $row): ?>
                                    <?php $def = floatval($row['nota_final']); ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($row['nombre_asignatura']) ?></strong>
                                            <span class="text-muted small d-block"><?= htmlspecialchars($row['codigo'] ?? '') ?></span>
                                        </td>
                                        <td class="text-center"><?= number_format(floatval($row['nota_cognitiva']), 1) ?></td>
                                        <td class="text-center"><?= number_format(floatval($row['nota_procedimental']), 1) ?></td>
                                        <td class="text-center"><?= number_format(floatval($row['nota_actitudinal']), 1) ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= desempenoBadge($def) ?> fs-6 px-3 py-2"><?= number_format($def, 1) ?></span>
                                        </td>
                                        <td>
                                            <span class="text-<?= desempenoBadge($def) ?> fw-bold">
                                                <i class="fas fa-<?= $def >= 3.0 ? 'check-circle' : 'times-circle' ?> me-1"></i>
                                                <?= desempeno($def) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted" style="max-width: 200px;">
                                            <span title="<?= htmlspecialchars($row['logros_observaciones'] ?? '') ?>">
                                                <?= htmlspecialchars(mb_strimwidth($row['logros_observaciones'] ?? 'Sin observación.', 0, 80, '...')) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Fila de promedio -->
                                <tr class="table-active fw-bold">
                                    <td colspan="4" class="text-end pe-3">Promedio General Periodo <?= $periodo ?>:</td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= desempenoBadge($promedio) ?> fs-5 px-3 py-2"><?= number_format($promedio, 2) ?></span>
                                    </td>
                                    <td colspan="2">
                                        <strong class="text-<?= desempenoBadge($promedio) ?>">Desempeño <?= desempeno($promedio) ?></strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /p-4 -->
    </main>
</div>

<!-- ===== BOLETÍN OFICIAL INSTITUTO LA SALLE (DISEÑO NUEVO) ===== -->
<div id="boletin-print">
    <!-- Marcos decorativos de esquinas -->
    <div class="blt-corner-tl"></div>
    <div class="blt-corner-tr"></div>
    <div class="blt-corner-bl"></div>
    <div class="blt-corner-br"></div>
    <!-- Barras laterales -->
    <div class="blt-bar-left"></div>
    <div class="blt-bar-right"></div>
    <!-- Marca de agua -->
    <div class="blt-watermark">La Salle<br>Ciénaga<br>Magdalena</div>

    <div class="blt-content">

        <!-- ENCABEZADO -->
        <div class="blt-header">
            <img src="<?= ASSETS_URL ?>/assets/logo.svg" alt="Logo La Salle" class="blt-logo">
            <div class="blt-school-info">
                <p class="blt-school-name">Instituto La Salle</p>
                <p class="blt-school-subtitle">Instituto La Salle</p>
                <p class="blt-school-city">Ciénaga – Magdalena</p>
                <p class="blt-school-meta">
                    Aprobado por Resolución No. 1806 de Octubre 08 de 2019 y Decreto 009 de Junio de 2015<br>
                    NIT. 8190049841-0 &nbsp;|&nbsp; DANE 347189041870<br>
                    Dirección: Carrera 31 N° 18-70 &nbsp;|&nbsp; Teléfono: 5 4101084
                </p>
            </div>
        </div>

        <!-- TÍTULO -->
        <div class="blt-title-bar">
            <div class="blt-title-line"></div>
            <div class="blt-title-text">Boletín Informativo</div>
            <div class="blt-title-line"></div>
        </div>

        <!-- DATOS DEL ESTUDIANTE -->
        <div class="blt-student-wrap">
            <div class="blt-photo-box">Foto<br>Estudiante</div>
            <table class="blt-student-table">
                <thead>
                    <tr>
                        <th>Documento</th>
                        <th>Apellidos y Nombres</th>
                        <th>Año</th>
                        <th>Jornada</th>
                        <th>Grado</th>
                        <th>Grupo</th>
                        <th>Periodo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($profile['documento_identidad'] ?? '—') ?></td>
                        <td><?= htmlspecialchars(strtoupper($fullName)) ?></td>
                        <td><?= $profile['ano_lectivo'] ?? 2026 ?></td>
                        <td>MAÑANA</td>
                        <td><?= htmlspecialchars(strtoupper($profile['grado'] ?? '10°')) ?></td>
                        <td><?= htmlspecialchars(strtoupper($profile['grupo'] ?? 'A')) ?></td>
                        <td><?= $periodo ?></td>
                    </tr>
                    <tr class="blt-acudiente-row">
                        <td colspan="7"><strong>Acudiente:</strong> <?= htmlspecialchars($profile['acudiente_nombre'] ?? '') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- TABLA DE ÁREAS / ASIGNATURAS -->
        <table class="blt-notas-table">
            <thead>
                <tr>
                    <th>Áreas/Asignaturas</th>
                    <th>Asignatura</th>
                    <th>IH</th>
                    <th>A.U</th>
                    <th>Nota</th>
                    <th>Descriptor de desempeño</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gradeRows)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted" style="text-align:center; padding: 25px; font-style:italic;">
                        <i class="fas fa-info-circle me-1"></i> No se han registrado calificaciones para este período aún.
                    </td>
                </tr>
                <?php else: ?>
                <?php
                $iconColors = ['#1565c0','#388e3c','#f57f17','#6a1b9a','#00838f','#c62828','#e65100','#2e7d32','#0277bd','#4a148c','#558b2f','#37474f'];
                $iconSymbols = ['🔬','📈','✝','📚','🌐','➕','🏃','🖥','🔵','⭐','🔎','💡','❤'];
                $idx = 0;
                foreach ($gradeRows as $row):
                    $def = floatval($row['nota_final']);
                    $iconColor = $iconColors[$idx % count($iconColors)];
                    $iconSym   = $iconSymbols[$idx % count($iconSymbols)];
                    $idx++;
                ?>
                <tr>
                    <td>
                        <span class="blt-area-icon" style="background:<?= $iconColor ?>"><?= $iconSym ?></span>
                        <?= htmlspecialchars(strtoupper($row['nombre_asignatura'])) ?>
                    </td>
                    <td class="blt-desc-cell">- <?= htmlspecialchars($row['nombre_asignatura']) ?></td>
                    <td class="blt-nota-cell"><?= number_format(floatval($row['nota_cognitiva']), 0) ?></td>
                    <td class="blt-nota-cell"><?= number_format(floatval($row['nota_actitudinal']), 0) ?></td>
                    <td class="blt-nota-cell"><strong><?= number_format($def, 1) ?></strong></td>
                    <td class="blt-desc-cell"><?= htmlspecialchars($row['logros_observaciones'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                <!-- Fila promedio general -->
                <tr style="background:#e8f0fe;">
                    <td colspan="4" style="text-align:right; font-weight:bold; color:#1E3A8A;">PROMEDIO GENERAL PERIODO <?= $periodo ?>:</td>
                    <td class="blt-nota-cell" style="color:#1E3A8A; font-size:10pt;"><strong><?= number_format($promedio, 2) ?></strong></td>
                    <td class="blt-desc-cell" style="color:#1E3A8A; font-weight:bold;">Desempeño <?= desempeno($promedio) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- CONVIVENCIA -->
        <div class="blt-convivencia">
            <div class="blt-convivencia-header">Convivencia</div>
            <div class="blt-convivencia-body">
                Asistencias: <?= $asistencias['asistio'] ?> días &nbsp;|&nbsp;
                Inasistencias: <?= $asistencias['falto'] ?> &nbsp;|&nbsp;
                Excusas: <?= $asistencias['excusa'] ?> &nbsp;|&nbsp;
                % Asistencia: <strong><?= $pctAsistencia ?>%</strong>
            </div>
        </div>

        <!-- OBSERVACIONES + ACCESO -->
        <div class="blt-obs-row">
            <div class="blt-obs-box">
                <div class="blt-obs-header">Observación</div>
                <div class="blt-obs-body">&nbsp;</div>
            </div>
            <div class="blt-access-box">
                <div class="blt-obs-header">Para acceder a la Escuela en Línea, utilice estos datos:</div>
                <div class="blt-access-body">
                    🌐 &nbsp;<strong>WEB:</strong> www.laescuelaenlinea.com<br>
                    👤 &nbsp;<strong>USUARIO:</strong> ils.<?= strtolower(explode(' ', $fullName)[0] ?? 'estudiante') ?>.<?= strtolower($profile['primer_apellido'] ?? 'alumno') ?><br>
                    🔑 &nbsp;<strong>CONTRASEÑA:</strong> <?= htmlspecialchars($profile['documento_identidad'] ?? '—') ?>
                </div>
            </div>
        </div>

        <!-- FIRMAS -->
        <div class="blt-firmas">
            <div class="blt-firma-item">
                <div class="blt-firma-icon">👥</div>
                <div class="blt-firma-name">Carlos Alberto García López</div>
                <div class="blt-firma-cargo">Director(a) de Grupo</div>
            </div>
            <div class="blt-firma-item">
                <div class="blt-firma-icon">👤</div>
                <div class="blt-firma-name">Lic. Rodrigo Andrés Pérez García</div>
                <div class="blt-firma-cargo">Rector</div>
            </div>
            <div class="blt-firma-item">
                <div class="blt-firma-icon">✒</div>
                <div class="blt-firma-name">Secretario(a) Académico(a)</div>
                <div class="blt-firma-cargo">Secretario</div>
            </div>
        </div>

    </div><!-- /blt-content -->
</div><!-- /boletin-print -->

<!-- =========================================================================
     MODAL INSTITUCIONAL DE EVALUACIÓN DOCENTE (100% ESTABLE, CENTRADO Y FLUIDO)
     ========================================================================= -->
<div class="modal fade" id="modalCalificarDocente" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg" style="max-width: 700px;">
        <div class="modal-content border-0 rounded-4 shadow-2xl overflow-hidden" style="max-height: 88vh;">
            <div class="modal-header text-white p-3 px-4 position-relative" style="background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%) !important;">
                <div>
                    <span class="badge bg-warning text-dark px-2 py-0.5 small fw-bold mb-1 text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.5px;">Evaluación Institucional La Salle</span>
                    <h5 class="modal-title font-heading fw-bold mb-0 text-white" id="modalEvalDocNombre">
                        Calificar Docente
                    </h5>
                    <small class="text-white-50" id="modalEvalDocSubtitulo">Asignatura — Curso</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            
            <div class="modal-body p-4" style="overflow-y: auto; overscroll-behavior: contain;">
                <!-- Banner Sticky con Promedio en Tiempo Real -->
                <div class="p-3 rounded-3 mb-3 d-flex justify-content-between align-items-center shadow-sm" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                    <div>
                        <span class="text-muted small d-block">Promedio General Asignado:</span>
                        <span class="fw-bold fs-4" id="modalEvalPromedioNum" style="color: #059669;">⭐ 5.0 / 5.0</span>
                    </div>
                    <div class="text-end">
                        <span class="badge fs-6 px-3 py-1 text-uppercase text-white" id="modalEvalNivelBadge" style="background: #10B981;">Excelente</span>
                    </div>
                </div>

                <div class="alert alert-info border-0 rounded-3 small py-2 mb-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Califica cada criterio arrastrando el control deslizante o pulsando directamente en las opciones del 1 al 5.
                </div>

                <form method="POST" id="formEvaluacionDocente">
                    <input type="hidden" name="action" value="guardar_evaluacion_docente">
                    <input type="hidden" name="docente_id" id="modalEvalDocenteId" value="">
                    <input type="hidden" name="curso_id" id="modalEvalCursoId" value="">

                    <!-- Criterio 1: Dominio -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark mb-0">
                                1. 📘 Dominio y Claridad de la Asignatura
                            </label>
                            <span class="badge bg-primary fs-6 px-3 py-1 score-badge" id="badge_criterio_1">5.0</span>
                        </div>
                        <small class="text-muted d-block mb-2">Demuestra conocimiento amplio de los temas, explica con claridad y responde acertadamente las dudas de clase.</small>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range flex-grow-1 eval-range" id="range_criterio_1" name="criterio_dominio" min="1" max="5" step="0.5" value="5" oninput="actualizarCriterio(1, this.value)">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(1, 1)">1</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(1, 2)">2</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(1, 3)">3</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(1, 4)">4</button>
                                <button type="button" class="btn btn-primary text-white fw-bold py-0 px-2 btn-val-pill active" onclick="fijarCriterio(1, 5)">5</button>
                            </div>
                        </div>
                    </div>

                    <!-- Criterio 2: Metodología -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark mb-0">
                                2. 🎨 Metodología, Didáctica y Recursos
                            </label>
                            <span class="badge bg-primary fs-6 px-3 py-1 score-badge" id="badge_criterio_2">5.0</span>
                        </div>
                        <small class="text-muted d-block mb-2">Utiliza dinámicas participativas, guías, talleres y herramientas tecnológicas adecuadas para el aprendizaje.</small>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range flex-grow-1 eval-range" id="range_criterio_2" name="criterio_metodologia" min="1" max="5" step="0.5" value="5" oninput="actualizarCriterio(2, this.value)">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(2, 1)">1</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(2, 2)">2</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(2, 3)">3</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(2, 4)">4</button>
                                <button type="button" class="btn btn-primary text-white fw-bold py-0 px-2 btn-val-pill active" onclick="fijarCriterio(2, 5)">5</button>
                            </div>
                        </div>
                    </div>

                    <!-- Criterio 3: Respeto y Puntualidad -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark mb-0">
                                3. 🤝 Puntualidad, Respeto y Clima de Aula
                            </label>
                            <span class="badge bg-primary fs-6 px-3 py-1 score-badge" id="badge_criterio_3">5.0</span>
                        </div>
                        <small class="text-muted d-block mb-2">Inicia las clases puntualmente, trata con respeto a todos los estudiantes y fomenta una convivencia armónica.</small>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range flex-grow-1 eval-range" id="range_criterio_3" name="criterio_respeto" min="1" max="5" step="0.5" value="5" oninput="actualizarCriterio(3, this.value)">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(3, 1)">1</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(3, 2)">2</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(3, 3)">3</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(3, 4)">4</button>
                                <button type="button" class="btn btn-primary text-white fw-bold py-0 px-2 btn-val-pill active" onclick="fijarCriterio(3, 5)">5</button>
                            </div>
                        </div>
                    </div>

                    <!-- Criterio 4: Criterios de Evaluación -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark mb-0">
                                4. 📝 Criterios de Evaluación y Retroalimentación
                            </label>
                            <span class="badge bg-primary fs-6 px-3 py-1 score-badge" id="badge_criterio_4">5.0</span>
                        </div>
                        <small class="text-muted d-block mb-2">Comunica previamente los criterios para calificar y retroalimenta oportunamente los exámenes y tareas.</small>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range flex-grow-1 eval-range" id="range_criterio_4" name="criterio_evaluacion" min="1" max="5" step="0.5" value="5" oninput="actualizarCriterio(4, this.value)">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(4, 1)">1</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(4, 2)">2</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(4, 3)">3</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(4, 4)">4</button>
                                <button type="button" class="btn btn-primary text-white fw-bold py-0 px-2 btn-val-pill active" onclick="fijarCriterio(4, 5)">5</button>
                            </div>
                        </div>
                    </div>

                    <!-- Criterio 5: Motivación y Apoyo -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold text-dark mb-0">
                                5. 🚀 Motivación, Acompañamiento y Empatía
                            </label>
                            <span class="badge bg-primary fs-6 px-3 py-1 score-badge" id="badge_criterio_5">5.0</span>
                        </div>
                        <small class="text-muted d-block mb-2">Motiva a superarse, demuestra interés por el bienestar del alumno y brinda acompañamiento sincero.</small>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range flex-grow-1 eval-range" id="range_criterio_5" name="criterio_motivacion" min="1" max="5" step="0.5" value="5" oninput="actualizarCriterio(5, this.value)">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(5, 1)">1</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(5, 2)">2</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(5, 3)">3</button>
                                <button type="button" class="btn btn-outline-secondary py-0 px-2 btn-val-pill" onclick="fijarCriterio(5, 4)">4</button>
                                <button type="button" class="btn btn-primary text-white fw-bold py-0 px-2 btn-val-pill active" onclick="fijarCriterio(5, 5)">5</button>
                            </div>
                        </div>
                    </div>

                    <!-- Comentarios constructivos -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-dark">
                            💬 Comentarios y Sugerencias de Mejora (Opcional)
                        </label>
                        <textarea class="form-control" name="comentarios" id="modalEvalComentarios" rows="3" placeholder="Escribe felicitaciones o recomendaciones pedagógicas respetuosas para que el docente siga mejorando..."></textarea>
                        <small class="text-muted">Tus comentarios serán confidenciales y leídos por los directivos de la institución de forma formativa.</small>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light p-3 px-4 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formEvaluacionDocente" class="btn btn-lasalle-primary px-4 fw-bold shadow-sm" id="btnSubmitEvalDoc">
                    <i class="fas fa-paper-plane me-1"></i> Guardar y Enviar Evaluación
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.eval-range {
    touch-action: pan-y !important;
    cursor: pointer;
}
#modalCalificarDocente .modal-dialog {
    margin: 1.75rem auto;
}
#modalCalificarDocente .btn-val-pill {
    min-width: 28px;
    font-size: 0.8rem;
    transition: all 0.15s ease-in-out;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script>
var modalEvalInstance = null;

function abrirModalCalificarDocente(data) {
    if (!data) return;
    document.getElementById('modalEvalDocenteId').value = data.docente_id;
    document.getElementById('modalEvalCursoId').value = data.curso_id;
    document.getElementById('modalEvalDocNombre').textContent = 'Calificar a: ' + data.docente_nombre;
    document.getElementById('modalEvalDocSubtitulo').textContent = 'Asignatura: ' + data.asignatura + ' — Curso: ' + data.curso;
    document.getElementById('modalEvalComentarios').value = data.comentarios || '';
    
    var btnSubmit = document.getElementById('btnSubmitEvalDoc');
    if (btnSubmit) {
        btnSubmit.innerHTML = data.is_evaluated ? '<i class="fas fa-edit me-1"></i> Actualizar mi Calificación' : '<i class="fas fa-paper-plane me-1"></i> Guardar y Enviar Evaluación';
    }

    // Set criteria 1 to 5
    fijarCriterio(1, parseFloat(data.criterio_dominio || 5));
    fijarCriterio(2, parseFloat(data.criterio_metodologia || 5));
    fijarCriterio(3, parseFloat(data.criterio_respeto || 5));
    fijarCriterio(4, parseFloat(data.criterio_evaluacion || 5));
    fijarCriterio(5, parseFloat(data.criterio_motivacion || 5));

    recalcularPromedioModal();

    var modalEl = document.getElementById('modalCalificarDocente');
    if (!modalEvalInstance) {
        modalEvalInstance = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
        });
    }
    modalEvalInstance.show();
}

function fijarCriterio(critNum, valor) {
    var range = document.getElementById('range_criterio_' + critNum);
    if (range) range.value = valor;
    actualizarCriterio(critNum, valor);
}

function actualizarCriterio(critNum, valor) {
    var valFloat = parseFloat(valor);
    var badge = document.getElementById('badge_criterio_' + critNum);
    if (badge) {
        badge.textContent = valFloat.toFixed(1);
        badge.className = 'badge fs-6 px-3 py-1 score-badge ' + (
            valFloat >= 4.5 ? 'bg-success' :
            valFloat >= 3.8 ? 'bg-primary' :
            valFloat >= 3.0 ? 'bg-warning text-dark' : 'bg-danger'
        );
    }

    // Highlight pill button
    var container = document.getElementById('range_criterio_' + critNum)?.closest('.p-3');
    if (container) {
        var pills = container.querySelectorAll('.btn-val-pill');
        pills.forEach(function(p) {
            var pVal = parseFloat(p.textContent.trim());
            if (pVal === Math.round(valFloat)) {
                p.classList.add('active', 'btn-primary', 'text-white', 'fw-bold');
                p.classList.remove('btn-outline-secondary', 'btn-outline-primary');
            } else {
                p.classList.remove('active', 'btn-primary', 'text-white', 'fw-bold');
                p.classList.add('btn-outline-secondary');
            }
        });
    }

    recalcularPromedioModal();
}

function recalcularPromedioModal() {
    var c1 = parseFloat(document.getElementById('range_criterio_1')?.value || 5);
    var c2 = parseFloat(document.getElementById('range_criterio_2')?.value || 5);
    var c3 = parseFloat(document.getElementById('range_criterio_3')?.value || 5);
    var c4 = parseFloat(document.getElementById('range_criterio_4')?.value || 5);
    var c5 = parseFloat(document.getElementById('range_criterio_5')?.value || 5);

    var prom = (c1 + c2 + c3 + c4 + c5) / 5.0;
    var promSpan = document.getElementById('modalEvalPromedioNum');
    var badgeNivel = document.getElementById('modalEvalNivelBadge');

    if (promSpan) {
        promSpan.textContent = '⭐ ' + prom.toFixed(1) + ' / 5.0';
        promSpan.style.color = prom >= 4.5 ? '#059669' : (prom >= 3.8 ? '#2563EB' : (prom >= 3.0 ? '#D97706' : '#DC2626'));
    }

    if (badgeNivel) {
        if (prom >= 4.5) {
            badgeNivel.textContent = 'Excelente';
            badgeNivel.style.background = '#10B981';
        } else if (prom >= 3.8) {
            badgeNivel.textContent = 'Sobresaliente';
            badgeNivel.style.background = '#3B82F6';
        } else if (prom >= 3.0) {
            badgeNivel.textContent = 'Aceptable';
            badgeNivel.style.background = '#F59E0B';
        } else {
            badgeNivel.textContent = 'Insuficiente';
            badgeNivel.style.background = '#EF4444';
        }
    }
}

function abrirBoletin() {
    // Leer el periodo seleccionado
    var periodo = document.querySelector('select[onchange*="periodo"]') ?
        document.querySelector('select[onchange*="periodo"]').value : 1;
    // Abrir ventana nueva con el boletín listo para imprimir
    var url = '<?= APP_URL ?>/views/comunidad/boletin_print.php?periodo=' + periodo;
    window.open(url, '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
}
// Alias
function imprimirBoletin() { abrirBoletin(); }
function printBoletin()    { abrirBoletin(); }
function descargarBoletin(){ abrirBoletin(); }
</script>
</body>
</html>
