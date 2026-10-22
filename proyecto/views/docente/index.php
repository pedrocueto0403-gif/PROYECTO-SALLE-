<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../app/Services/ActaService.php';
Router::enforceAccess('docente');

$userName = $_SESSION['user_name'] ?? 'Profesor Carlos García';
$docente_id = $_SESSION['user_id'];

$dbInstance = Database::getInstance();
$pdo = $dbInstance->getConnection();

$message = null;
$messageType = 'success';

// Cargar perfil y firma digital del docente
$docenteProfile = null;
$docenteFirma = null;
if ($pdo) {
    try {
        $stmtDP = $pdo->prepare("SELECT * FROM perfiles_detalles WHERE usuario_id = :uid");
        $stmtDP->execute([':uid' => $docente_id]);
        $docenteProfile = $stmtDP->fetch();
        if ($docenteProfile) {
            $docenteFirma = $docenteProfile['firma_digital'] ?? null;
            if (!empty($docenteProfile['primer_nombre'])) {
                $userName = 'Prof. ' . trim($docenteProfile['primer_nombre'] . ' ' . $docenteProfile['primer_apellido']);
            }
        }
    } catch (Exception $e) {}
}

// Cargar cursos y asignaturas asignadas al docente
$assignedList = [];
if ($pdo) {
    try {
        $stmtAssigned = $pdo->prepare("
            SELECT cad.curso_id, cad.asignatura_id, cad.sistema_calif, c.grado, c.grupo, c.ano_lectivo, a.nombre_asignatura, a.codigo
            FROM curso_asignatura_docente cad
            INNER JOIN cursos c ON cad.curso_id = c.id
            INNER JOIN asignaturas a ON cad.asignatura_id = a.id
            WHERE cad.docente_id = :docente_id
        ");
        $stmtAssigned->execute([':docente_id' => $docente_id]);
        $assignedList = $stmtAssigned->fetchAll();
    } catch (Exception $e) { $assignedList = []; }
}

// Si no hay asignaciones en la DB, la lista queda vacía

// Parámetros de filtrado
$curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : ($assignedList[0]['curso_id'] ?? 1);
$asignatura_id = isset($_GET['asignatura_id']) ? intval($_GET['asignatura_id']) : ($assignedList[0]['asignatura_id'] ?? 1);
$periodo = isset($_GET['periodo']) ? intval($_GET['periodo']) : 1;

// La escala de calificación es determinada EXCLUSIVAMENTE por la Administración al registrar la materia
$sistema_calif = '1-5';
foreach ($assignedList as $item) {
    if (intval($item['curso_id']) === $curso_id && intval($item['asignatura_id']) === $asignatura_id) {
        $sistema_calif = !empty($item['sistema_calif']) ? $item['sistema_calif'] : '1-5';
        break;
    }
}

// Procesar formularios por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'save_grades') {
        if ($pdo) {
            try {
                // Consultar la escala fijada por el administrativo directamente de la BD para seguridad estricta
                $stmtSc = $pdo->prepare("SELECT sistema_calif FROM curso_asignatura_docente WHERE curso_id = :cid AND asignatura_id = :aid AND docente_id = :did LIMIT 1");
                $stmtSc->execute([':cid' => $curso_id, ':aid' => $asignatura_id, ':did' => $docente_id]);
                $scRow = $stmtSc->fetch();
                $sistema_calif_fijado = $scRow['sistema_calif'] ?? $sistema_calif;

                $pdo->beginTransaction();
                foreach ($_POST['grades'] as $mat_id => $data) {
                    $rawNota = $data['nota'] ?? ($data['nota_final'] ?? '1.0');
                    if ($sistema_calif_fijado === '1-10') {
                        $def = round(floatval($rawNota) / 2, 2); // Normalizar a escala 1-5 interna
                    } elseif ($sistema_calif_fijado === 'letras') {
                        $map = ['S' => 4.8, 'A' => 4.2, 'B' => 3.5, 'BJ' => 2.0];
                        $def = $map[$rawNota] ?? 3.5;
                    } else {
                        $def = round(floatval($rawNota), 2);
                    }
                    if ($def < 1.0) $def = 1.0;
                    if ($def > 5.0) $def = 5.0;

                    $logros = trim($data['logros'] ?? '');
                    // Guardar la nota única en nota_final y en nota_cognitiva, procedimental, actitudinal para total compatibilidad
                    $pdo->prepare("INSERT INTO calificaciones_logros (matricula_id, asignatura_id, periodo, nota_cognitiva, nota_procedimental, nota_actitudinal, nota_final, logros_observaciones) 
                                   VALUES (:mat_id, :asig_id, :periodo, :def, :def, :def, :def, :logros) 
                                   ON DUPLICATE KEY UPDATE nota_cognitiva=:def, nota_procedimental=:def, nota_actitudinal=:def, nota_final=:def, logros_observaciones=:logros")
                        ->execute([
                            ':mat_id'   => $mat_id,
                            ':asig_id'  => $asignatura_id,
                            ':periodo'  => $periodo,
                            ':def'      => $def,
                            ':logros'   => $logros
                        ]);
                }
                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = "Error al guardar calificaciones: " . $e->getMessage();
                $messageType = "danger";
            }
        }
        if (!$message) {
            $count = count($_POST['grades'] ?? []);
            $message = "✅ Calificación única y logros de <strong>{$count} estudiante(s)</strong> actualizados correctamente para el Período {$periodo}.";
            $messageType = "success";
        }

    } elseif ($action === 'save_banco_logros') {
        $logroSuperior = trim($_POST['logro_superior'] ?? '');
        $logroAlto     = trim($_POST['logro_alto'] ?? '');
        $logroBasico   = trim($_POST['logro_basico'] ?? '');
        $logroBajo     = trim($_POST['logro_bajo'] ?? '');

        if ($pdo) {
            try {
                $pdo->beginTransaction();
                $escalas = [
                    'Superior' => $logroSuperior,
                    'Alto'     => $logroAlto,
                    'Basico'   => $logroBasico,
                    'Bajo'     => $logroBajo
                ];
                $stmtL = $pdo->prepare("
                    INSERT INTO banco_logros (docente_id, curso_id, asignatura_id, periodo, escala, descripcion_logro)
                    VALUES (:docente, :curso, :asig, :per, :escala, :desc)
                    ON DUPLICATE KEY UPDATE descripcion_logro = :desc, updated_at = NOW()
                ");
                foreach ($escalas as $esc => $desc) {
                    if (!empty($desc)) {
                        $stmtL->execute([
                            ':docente' => $docente_id,
                            ':curso'   => $curso_id,
                            ':asig'    => $asignatura_id,
                            ':per'     => $periodo,
                            ':escala'  => $esc,
                            ':desc'    => $desc
                        ]);
                    }
                }
                $pdo->commit();
                $message = "✅ Logros institucionales por escala de calificación guardados correctamente para el Período {$periodo}.";
                $messageType = "success";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = "Error al guardar el banco de logros: " . $e->getMessage();
                $messageType = "danger";
            }
        }

    } elseif ($action === 'save_attendance') {
        if ($pdo) {
            try {
                $pdo->beginTransaction();
                $fecha_hoy = date('Y-m-d');
                foreach ($_POST['attendance'] as $mat_id => $estado) {
                    $obs = $_POST['attendance_obs'][$mat_id] ?? '';
                    $pdo->prepare("INSERT INTO asistencias (matricula_id, asignatura_id, fecha, estado, observacion) VALUES (:mat_id, :asig_id, :fecha, :estado, :obs) ON DUPLICATE KEY UPDATE estado=:estado, observacion=:obs")->execute([':mat_id'=>$mat_id,':asig_id'=>$asignatura_id,':fecha'=>$fecha_hoy,':estado'=>$estado,':obs'=>$obs]);
                }
                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = "Error al registrar asistencias: " . $e->getMessage();
                $messageType = "danger";
            }
        }
        if (!$message) {
            $fecha_hoy = date('Y-m-d');
            $count = count($_POST['attendance'] ?? []);
            $message = "✅ Asistencias de <strong>{$count} estudiante(s)</strong> registradas para el día <strong>{$fecha_hoy}</strong>.";
            $messageType = "success";
        }

    } elseif ($action === 'save_plan') {
        $planPeriodo = intval($_POST['periodo_plan'] ?? $periodo);
        $tema = trim($_POST['tema_principal'] ?? '');
        $obj = trim($_POST['objetivos_aprendizaje'] ?? '');
        $pei = trim($_POST['contenido_pei'] ?? '');
        
        $rutaPdf = null;
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $pdfTmp = $_FILES['archivo_pdf']['tmp_name'];
            $pdfName = $_FILES['archivo_pdf']['name'];
            $ext = strtolower(pathinfo($pdfName, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $uploadDir = __DIR__ . '/../../public/uploads/planes_clase/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $newFileName = 'plan_doc_' . $docente_id . '_per' . $planPeriodo . '_' . time() . '.pdf';
                if (move_uploaded_file($pdfTmp, $uploadDir . $newFileName)) {
                    $rutaPdf = 'public/uploads/planes_clase/' . $newFileName;
                }
            } else {
                $message = "Formato de archivo no válido. Solo se admiten documentos en formato PDF (.pdf).";
                $messageType = "danger";
            }
        }

        if (empty($tema)) {
            $message = "Por favor ingrese el tema principal del plan de clase.";
            $messageType = "danger";
        } elseif (!$message) {
            if ($pdo) {
                try {
                    $pdo->prepare("INSERT INTO planes_clase (docente_id, asignatura_id, curso_id, semana_numero, periodo, tema_principal, objetivos_aprendizaje, contenido_pei, archivo_pdf, estado_aprobacion) VALUES (:docente, :asig, :curso, NULL, :periodo, :tema, :obj, :pei, :pdf, 'Enviado')")->execute([
                        ':docente' => $docente_id,
                        ':asig'    => $asignatura_id,
                        ':curso'   => $curso_id,
                        ':periodo' => $planPeriodo,
                        ':tema'    => $tema,
                        ':obj'     => $obj,
                        ':pei'     => $pei,
                        ':pdf'     => $rutaPdf
                    ]);
                    $message = "✅ Plan de clase '<strong>{$tema}</strong>' para el Período {$planPeriodo} enviado exitosamente con su archivo PDF al Administrador para revisión y aprobación.";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Error al guardar el plan de clase: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
        }
    } elseif ($action === 'guardar_firma') {
        $tipoFirma = $_POST['tipo_firma'] ?? 'archivo';
        $rutaGuardada = null;

        if ($tipoFirma === 'archivo' && isset($_FILES['firma_archivo']) && $_FILES['firma_archivo']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['firma_archivo']['tmp_name'];
            $fileName = $_FILES['firma_archivo']['name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExts = ['png', 'jpg', 'jpeg', 'pdf'];
            if (in_array($ext, $allowedExts)) {
                $uploadDir = __DIR__ . '/../../public/uploads/firmas/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                $newFileName = 'firma_doc_' . $docente_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                    $rutaGuardada = 'public/uploads/firmas/' . $newFileName;
                }
            } else {
                $message = "Formato de archivo no válido. Solo se admiten archivos .png, .jpg, .jpeg o .pdf.";
                $messageType = "danger";
            }
        } elseif ($tipoFirma === 'canvas' && !empty($_POST['firma_canvas_data'])) {
            $dataUri = $_POST['firma_canvas_data'];
            if (strpos($dataUri, 'data:image') === 0) {
                $dataParts = explode(',', $dataUri);
                $decoded = base64_decode($dataParts[1] ?? '');
                if ($decoded) {
                    $uploadDir = __DIR__ . '/../../public/uploads/firmas/';
                    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);
                    $newFileName = 'firma_canvas_' . $docente_id . '_' . time() . '.png';
                    file_put_contents($uploadDir . $newFileName, $decoded);
                    $rutaGuardada = 'public/uploads/firmas/' . $newFileName;
                }
            }
        } elseif ($tipoFirma === 'texto' && !empty($_POST['firma_texto'])) {
            $rutaGuardada = 'TEXTO:' . trim($_POST['firma_texto']);
        }

        if ($rutaGuardada && $pdo) {
            try {
                $stmtUF = $pdo->prepare("UPDATE perfiles_detalles SET firma_digital = :firma WHERE usuario_id = :uid");
                $stmtUF->execute([':firma' => $rutaGuardada, ':uid' => $docente_id]);
                $docenteFirma = $rutaGuardada;
                $message = "✅ Firma oficial del docente guardada con éxito. Se aplicará automáticamente en Planes de Aula y Boletines Institucionales.";
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error al registrar firma: " . $e->getMessage();
                $messageType = "danger";
            }
        } elseif (!$message && empty($rutaGuardada)) {
            $message = "No se suministró una firma válida para registrar.";
            $messageType = "warning";
        }
    } elseif ($action === 'eliminar_firma') {
        if ($pdo) {
            try {
                $pdo->prepare("UPDATE perfiles_detalles SET firma_digital = NULL WHERE usuario_id = :uid")->execute([':uid' => $docente_id]);
                $docenteFirma = null;
                $message = "Firma digital removida. Se utilizará firma mecanografiada por defecto.";
                $messageType = "info";
            } catch (Exception $e) {
                $message = "Error al remover firma: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    } elseif ($action === 'save_citation_docente') {
        $student_id = intval($_POST['student_id_cit'] ?? 0);
        $fecha_cita = $_POST['fecha_cita'] ?? '';
        $modalidad  = $_POST['modalidad'] ?? 'Presencial';
        $motivo     = trim($_POST['motivo_cita'] ?? '');
        $categoria  = trim($_POST['categoria_cit'] ?? 'Académico');

        if (empty($student_id) || empty($fecha_cita) || empty($motivo)) {
            $message = "Por favor complete el estudiante, la fecha y el motivo de la citación.";
            $messageType = "danger";
        } else {
            $emailResult = null;
            $motivoCompleto = "[$categoria] " . $motivo;

            if ($pdo) {
                try {
                    $stmtIns = $pdo->prepare("
                        INSERT INTO citaciones_acudientes 
                            (estudiante_id, psicologo_id, fecha_citacion, modalidad, motivo, estado) 
                        VALUES 
                            (:eid, :pid, :fecha, :mod, :mot, 'Pendiente')
                    ");
                    $stmtIns->execute([
                        ':eid'   => $student_id,
                        ':pid'   => $docente_id,
                        ':fecha' => $fecha_cita,
                        ':mod'   => $modalidad,
                        ':mot'   => $motivoCompleto,
                    ]);

                    // Consultar datos de acudiente para despacho de correo
                    $stmtEst = $pdo->prepare("
                        SELECT u.id, u.correo AS estudiante_correo,
                               p.primer_nombre, p.primer_apellido,
                               p.acudiente_nombre, p.acudiente_correo,
                               c.grado, c.grupo
                        FROM usuarios u
                        INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                        LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
                        LEFT JOIN cursos c ON m.curso_id = c.id
                        WHERE u.id = :eid
                        LIMIT 1
                    ");
                    $stmtEst->execute([':eid' => $student_id]);
                    $alumnoData = $stmtEst->fetch();

                    if ($alumnoData && !empty($alumnoData['acudiente_correo'])) {
                        require_once __DIR__ . '/../../app/Services/EmailGateway.php';
                        $emailResult = EmailGateway::notificarCitacionAcudiente(
                            [
                                'primer_nombre'    => $alumnoData['primer_nombre'],
                                'primer_apellido'  => $alumnoData['primer_apellido'],
                                'acudiente_nombre' => $alumnoData['acudiente_nombre'],
                                'acudiente_correo' => $alumnoData['acudiente_correo'],
                                'grado'            => $alumnoData['grado'],
                                'grupo'            => $alumnoData['grupo'],
                            ],
                            [
                                'fecha_citacion' => $fecha_cita,
                                'modalidad'      => $modalidad,
                                'motivo'         => $motivoCompleto,
                                'profesional'    => $userName . ' (Docente)'
                            ]
                        );
                    }
                } catch (Exception $e) {
                    $message = "Error al registrar la citación: " . $e->getMessage();
                    $messageType = "danger";
                }
            }

            if (!$message) {
                $statusMail = ($emailResult && $emailResult['success'])
                    ? " Notificación oficial enviada al correo del acudiente (<strong>{$alumnoData['acudiente_correo']}</strong>)."
                    : " Citación registrada en el sistema escolar.";
                $message = "✅ Citación agendada exitosamente para el <strong>" . date('d/m/Y H:i', strtotime($fecha_cita)) . "</strong>. " . $statusMail;
                $messageType = "success";
            }
        }
    }
}

// Cargar citaciones emitidas por este docente
$citacionesDocente = [];
if ($pdo) {
    try {
        $stmtCit = $pdo->prepare("
            SELECT ca.*, p.primer_nombre, p.primer_apellido, p.documento_identidad, p.acudiente_nombre, p.acudiente_correo, c.grado, c.grupo
            FROM citaciones_acudientes ca
            INNER JOIN usuarios u ON ca.estudiante_id = u.id
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            WHERE ca.psicologo_id = :doc_id
            ORDER BY ca.fecha_citacion DESC
        ");
        $stmtCit->execute([':doc_id' => $docente_id]);
        $citacionesDocente = $stmtCit->fetchAll();
    } catch (Exception $e) { $citacionesDocente = []; }
}

// Cargar todos los estudiantes con documento para el selector rápido de citaciones
$studentsAll = [];
if ($pdo) {
    try {
        $stmtStAll = $pdo->query("
            SELECT u.id, p.primer_nombre, p.primer_apellido, p.documento_identidad, c.grado, c.grupo, p.acudiente_nombre, p.acudiente_correo
            FROM usuarios u
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            WHERE u.rol_id = 5
            ORDER BY c.grado, c.grupo, p.primer_apellido, p.primer_nombre
        ");
        $studentsAll = $stmtStAll->fetchAll();
    } catch (Exception $e) { $studentsAll = []; }
}

// Cargar actas disciplinarias registradas por este docente
$actasDocente = [];
if ($pdo) {
    try {
        $stmtActDoc = $pdo->prepare("
            SELECT a.*, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   p.tipo_documento, p.documento_identidad, c.grado, c.grupo
            FROM actas_convivencia a
            INNER JOIN perfiles_detalles p ON a.estudiante_id = p.usuario_id
            LEFT JOIN cursos c ON a.curso_id = c.id
            WHERE a.creador_id = :uid OR a.creador_rol = 'docente'
            ORDER BY a.id DESC
        ");
        $stmtActDoc->execute([':uid' => $docente_id]);
        $actasDocente = $stmtActDoc->fetchAll();
    } catch (Exception $e) { $actasDocente = []; }
}
// Modulo de convivencia removido

// Cargar alumnos inscritos en el curso
$studentsList = [];
if ($pdo) {
    try {
        $stmtStudents = $pdo->prepare("
            SELECT m.id AS matricula_id, u.id AS estudiante_id, p.primer_nombre, p.primer_apellido, p.segundo_nombre, p.segundo_apellido, p.documento_identidad
            FROM matriculas m
            INNER JOIN usuarios u ON m.estudiante_id = u.id
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            WHERE m.curso_id = :curso_id AND m.ano_lectivo = 2026 AND m.estado_matricula = 'activa'
            ORDER BY p.primer_apellido, p.primer_nombre
        ");
        $stmtStudents->execute([':curso_id' => $curso_id]);
        $studentsList = $stmtStudents->fetchAll();
    } catch (Exception $e) { $studentsList = []; }
}

// Si no hay estudiantes matriculados, la lista queda vacía (no mostrar datos demo)

// Cargar calificaciones existentes
$gradesList = [];
if ($pdo && !empty($studentsList)) {
    try {
        $stmtGrades = $pdo->prepare("
            SELECT * FROM calificaciones_logros 
            WHERE asignatura_id = :asig_id AND periodo = :periodo
        ");
        $stmtGrades->execute([
            ':asig_id' => $asignatura_id,
            ':periodo' => $periodo
        ]);
        $rawGrades = $stmtGrades->fetchAll();
        
        foreach ($rawGrades as $rg) {
            $gradesList[$rg['matricula_id']] = $rg;
        }
    } catch (Exception $e) {}
}
// Sin datos demo de calificaciones

// Cargar asistencias del día de hoy
$attendanceList = [];
if ($pdo && !empty($studentsList)) {
    try {
        $stmtAttendance = $pdo->prepare("
            SELECT * FROM asistencias 
            WHERE asignatura_id = :asig_id AND fecha = CURRENT_DATE()
        ");
        $stmtAttendance->execute([':asig_id' => $asignatura_id]);
        $rawAttendance = $stmtAttendance->fetchAll();
        foreach ($rawAttendance as $ra) {
            $attendanceList[$ra['matricula_id']] = $ra;
        }
    } catch (Exception $e) {}
}

// Cargar banco de logros institucional para este docente, curso, asignatura y periodo
$bancoLogros = [
    'Superior' => '',
    'Alto'     => '',
    'Basico'   => '',
    'Bajo'     => ''
];
if ($pdo) {
    try {
        $stmtBL = $pdo->prepare("
            SELECT escala, descripcion_logro 
            FROM banco_logros 
            WHERE curso_id = :cid AND asignatura_id = :aid AND periodo = :per
        ");
        $stmtBL->execute([':cid' => $curso_id, ':aid' => $asignatura_id, ':per' => $periodo]);
        $rowsBL = $stmtBL->fetchAll();
        foreach ($rowsBL as $rbl) {
            $bancoLogros[$rbl['escala']] = $rbl['descripcion_logro'];
        }
    } catch (Exception $e) {}
}

// Cargar planes de clase enviados por este docente
$planesDocente = [];
if ($pdo) {
    try {
        $stmtPl = $pdo->prepare("
            SELECT pc.*, a.nombre_asignatura, a.codigo, c.grado, c.grupo
            FROM planes_clase pc
            INNER JOIN asignaturas a ON pc.asignatura_id = a.id
            INNER JOIN cursos c ON pc.curso_id = c.id
            WHERE pc.docente_id = :did
            ORDER BY pc.id DESC
        ");
        $stmtPl->execute([':did' => $docente_id]);
        $planesDocente = $stmtPl->fetchAll();
    } catch (Exception $e) { $planesDocente = []; }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Académico Docente - AulaVirtual Salle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/custom.css" rel="stylesheet">
    <style>
        .paper-sheet-doc {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            padding: 2rem;
            font-family: 'Calibri', Arial, sans-serif;
            font-size: 0.92rem;
            line-height: 1.45;
            color: #0F172A;
        }
        .paper-table-doc {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .paper-table-doc th, .paper-table-doc td {
            border: 1px solid #94A3B8;
            padding: 5px 8px;
            font-size: 0.84rem;
        }
        .paper-table-doc th {
            background-color: #F8FAFC;
            font-weight: 700;
        }
        .fault-check-item-doc {
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 7px 10px;
            margin-bottom: 5px;
            background: #FFFFFF;
            transition: all 0.2s ease;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .fault-check-item-doc:hover {
            background: #EFF6FF;
            border-color: #93C5FD;
        }
        .locked-pill {
            opacity: 0.65;
            cursor: not-allowed !important;
            background: #F1F5F9 !important;
            border-color: #CBD5E1 !important;
            color: #64748B !important;
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
                <small class="text-success font-heading" style="font-size: 0.7rem;">PORTAL DOCENTE</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#evaluador" class="sidebar-link active"><i class="fas fa-edit"></i> Evaluador de Notas</a>
            <a href="#boletines" class="sidebar-link"><i class="fas fa-graduation-cap"></i> Boletines del Curso</a>
            <a href="#modalFirmaDigital" data-bs-toggle="modal" class="sidebar-link"><i class="fas fa-signature text-warning"></i> Mi Firma Digital</a>
            <hr class="text-white-50 my-3">
            <a href="<?= APP_URL ?>/app/Controllers/AuthController.php?action=logout" class="sidebar-link text-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </nav>
    </aside>

    <main class="app-main">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
                <h5 class="mb-0 font-heading fw-bold d-none d-md-block">Gestión Académica de Aula</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFirmaDigital">
                    <i class="fas fa-signature me-1"></i> Mi Firma Digital
                    <?php if (!empty($docenteFirma)): ?>
                        <span class="badge bg-success ms-1"><i class="fas fa-check"></i> Activa</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark ms-1">Pendiente</span>
                    <?php endif; ?>
                </button>
                <button class="btn btn-sm btn-outline-secondary rounded-circle" id="themeToggleBtn"><i class="fas fa-moon"></i></button>
                <span class="fw-semibold small"><i class="fas fa-chalkboard-teacher text-success me-1"></i> <?= htmlspecialchars($userName) ?></span>
            </div>
        </header>

        <div class="p-4">
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Filtro Dinámico de Cursos y Asignatura -->
            <div class="custom-card mb-4 bg-light">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Curso y Grado</label>
                        <select class="form-select fw-semibold text-primary" id="filter_curso" onchange="applyFilters()">
                            <?php foreach ($assignedList as $item): ?>
                                <option value="<?= $item['curso_id'] ?>" <?= $curso_id == $item['curso_id'] ? 'selected' : '' ?>>
                                    Grado <?= htmlspecialchars($item['grado'] . ' - Grupo ' . $item['grupo']) ?> (2026)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Asignatura Asignada</label>
                        <select class="form-select fw-semibold text-primary" id="filter_asignatura" onchange="applyFilters()">
                            <?php foreach ($assignedList as $item): ?>
                                <option value="<?= $item['asignatura_id'] ?>" <?= $asignatura_id == $item['asignatura_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($item['nombre_asignatura']) ?> (<?= htmlspecialchars($item['codigo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Periodo Lectivo</label>
                        <select class="form-select fw-semibold text-primary" id="filter_periodo" onchange="applyFilters()">
                            <option value="1" <?= $periodo == 1 ? 'selected' : '' ?>>Periodo 1</option>
                            <option value="2" <?= $periodo == 2 ? 'selected' : '' ?>>Periodo 2</option>
                            <option value="3" <?= $periodo == 3 ? 'selected' : '' ?>>Periodo 3</option>
                            <option value="4" <?= $periodo == 4 ? 'selected' : '' ?>>Periodo 4</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-semibold text-secondary">
                            Escala Institucional <i class="fas fa-lock text-primary ms-1" title="Escala fijada por la administración"></i>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary border"><i class="fas fa-shield-alt"></i></span>
                            <input type="text" class="form-control bg-light fw-bold text-dark border" readonly
                                   value="<?= $sistema_calif === '1-10' ? 'Escala 1.0 - 10.0 (Fija)' : ($sistema_calif === 'letras' ? 'Letras (S, A, B, BJ) (Fija)' : 'Escala 1.0 - 5.0 (Fija)') ?>"
                                   title="Definida exclusivamente por el Personal Administrativo">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matriz de Calificaciones Únicas por Periodo con Logros por Escala -->
            <div class="custom-card mb-4" id="evaluador">
                <form method="POST">
                    <input type="hidden" name="action" value="save_grades">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-0"><i class="fas fa-calculator text-primary me-2"></i> Evaluador Masivo de Calificaciones (Periodo <?= $periodo ?>)</h5>
                            <small class="text-muted">Ingrese la calificación única del período. El logro institucional se asigna automáticamente según la escala de desempeño alcanzada.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalBancoLogros">
                                <i class="fas fa-award me-1"></i> Configurar Logros por Escala
                            </button>
                            <button type="submit" class="btn btn-lasalle-primary btn-sm">
                                <i class="fas fa-save me-1"></i> Guardar Calificaciones
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th style="width: 170px;" class="text-center">Nota (Periodo <?= $periodo ?>)</th>
                                    <th style="width: 140px;" class="text-center">Desempeño</th>
                                    <th>Logro Institucional Asignado / Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($studentsList)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No hay estudiantes matriculados en este curso.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($studentsList as $st): ?>
                                        <?php 
                                        $fullName = $st['primer_apellido'] . ' ' . $st['primer_nombre'] . ' ' . ($st['segundo_nombre'] ?? '');
                                        
                                        // Recuperar notas existentes
                                        $dbGrade = $gradesList[$st['matricula_id']] ?? null;
                                        
                                        $defVal = "";
                                        $escalaLabel = "Pendiente";
                                        $badgeClass = "secondary";
                                        $logVal = "";
                                        
                                        if ($dbGrade) {
                                            $d = floatval($dbGrade['nota_final']);
                                            $logVal = $dbGrade['logros_observaciones'];
                                            
                                            if ($sistema_calif === '1-5') {
                                                $defVal = number_format($d, 1);
                                            } elseif ($sistema_calif === '1-10') {
                                                $defVal = number_format($d * 2, 1);
                                            } elseif ($sistema_calif === 'letras') {
                                                if ($d >= 4.6) $defVal = 'S';
                                                elseif ($d >= 4.0) $defVal = 'A';
                                                elseif ($d >= 3.0) $defVal = 'B';
                                                else $defVal = 'BJ';
                                            }

                                            if ($d >= 4.6) {
                                                $escalaLabel = 'Superior'; $badgeClass = 'success';
                                            } elseif ($d >= 4.0) {
                                                $escalaLabel = 'Alto'; $badgeClass = 'primary';
                                            } elseif ($d >= 3.0) {
                                                $escalaLabel = 'Básico'; $badgeClass = 'warning text-dark';
                                            } else {
                                                $escalaLabel = 'Bajo'; $badgeClass = 'danger';
                                            }

                                            // Si no hay logro personalizado, tomar del banco de logros
                                            if (empty($logVal)) {
                                                $escKey = ($escalaLabel === 'Básico') ? 'Basico' : $escalaLabel;
                                                $logVal = $bancoLogros[$escKey] ?? '';
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <strong class="text-dark"><?= htmlspecialchars($fullName) ?></strong>
                                                <br><small class="text-muted">Doc: <?= htmlspecialchars($st['documento_identidad']) ?></small>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($sistema_calif === 'letras'): ?>
                                                    <select class="form-select form-select-sm text-center fw-bold fs-6 row-grade" 
                                                            name="grades[<?= $st['matricula_id'] ?>][nota]" 
                                                            id="nota_<?= $st['matricula_id'] ?>"
                                                            data-student="<?= $st['matricula_id'] ?>" 
                                                            onchange="onNotaChange(<?= $st['matricula_id'] ?>)" required>
                                                        <option value="" <?= empty($defVal) ? 'selected' : '' ?>>--</option>
                                                        <option value="S" <?= $defVal === 'S' ? 'selected' : '' ?>>S - Superior</option>
                                                        <option value="A" <?= $defVal === 'A' ? 'selected' : '' ?>>A - Alto</option>
                                                        <option value="B" <?= $defVal === 'B' ? 'selected' : '' ?>>B - Básico</option>
                                                        <option value="BJ" <?= $defVal === 'BJ' ? 'selected' : '' ?>>BJ - Bajo</option>
                                                    </select>
                                                <?php else: ?>
                                                    <input type="number" step="0.1" 
                                                           min="<?= $sistema_calif === '1-10' ? '1.0' : '1.0' ?>" 
                                                           max="<?= $sistema_calif === '1-10' ? '10.0' : '5.0' ?>" 
                                                           class="form-control form-control-sm text-center fw-bold fs-6 row-grade" 
                                                           name="grades[<?= $st['matricula_id'] ?>][nota]" 
                                                           id="nota_<?= $st['matricula_id'] ?>"
                                                           value="<?= $defVal ?>" 
                                                           placeholder="<?= $sistema_calif === '1-10' ? '1.0 - 10.0' : '1.0 - 5.0' ?>"
                                                           data-student="<?= $st['matricula_id'] ?>" 
                                                           oninput="onNotaChange(<?= $st['matricula_id'] ?>)" required>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $badgeClass ?> fs-6" id="badge_escala_<?= $st['matricula_id'] ?>"><?= $escalaLabel ?></span>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="grades[<?= $st['matricula_id'] ?>][logros]" 
                                                           id="logros_<?= $st['matricula_id'] ?>" 
                                                           value="<?= htmlspecialchars($logVal) ?>" 
                                                           placeholder="El logro institucional se asignará al digitar la nota..." required>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <!-- Sección: Consulta y Generación de Boletines Oficiales del Curso -->
            <div class="custom-card mb-4" id="boletines">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-graduation-cap me-2"></i> Boletines de Calificaciones del Curso</h5>
                        <small class="text-muted">Consulte e imprima el boletín oficial de cada estudiante matriculado en este curso.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <?php 
                        $todosIds = array_filter(array_column($studentsList, 'estudiante_id'));
                        $idsParam = implode(',', $todosIds);
                        ?>
                        <?php if (!empty($idsParam)): ?>
                            <a href="<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiantes=<?= $idsParam ?>&periodo=<?= $periodo ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-bold">
                                <i class="fas fa-print me-1"></i> Imprimir Todos (Periodo <?= $periodo ?>)
                            </a>
                            <a href="<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiantes=<?= $idsParam ?>&periodo=todos" target="_blank" class="btn btn-primary btn-sm fw-bold">
                                <i class="fas fa-file-pdf me-1"></i> Imprimir Todos (Consolidado)
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 50px;">N°</th>
                                <th>Estudiante</th>
                                <th>Documento</th>
                                <th class="text-center" style="width: 170px;">Boletín Periodo <?= $periodo ?></th>
                                <th class="text-center" style="width: 170px;">Boletín Anual (Consolidado)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($studentsList)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No hay estudiantes en este curso.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($studentsList as $idx => $st): ?>
                                    <?php $nombreCompleto = $st['primer_apellido'] . ' ' . $st['primer_nombre'] . ' ' . ($st['segundo_nombre'] ?? ''); ?>
                                    <tr>
                                        <td class="text-muted small"><?= $idx + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($nombreCompleto) ?></strong></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($st['documento_identidad']) ?></span></td>
                                        <td class="text-center">
                                            <a href="<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiante_id=<?= $st['estudiante_id'] ?>&periodo=<?= $periodo ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-3">
                                                <i class="fas fa-file-alt me-1"></i> Periodo <?= $periodo ?>
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiante_id=<?= $st['estudiante_id'] ?>&periodo=todos" target="_blank" class="btn btn-sm btn-outline-success py-1 px-3">
                                                <i class="fas fa-file-pdf me-1"></i> Consolidado
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /p-4 -->
    </main>
</div>

<!-- Modal Gestión de Firma Digital del Docente -->
<div class="modal fade" id="modalFirmaDigital" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-card border-0 p-0 overflow-hidden">
            <div class="modal-header bg-primary text-white p-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%) !important;">
                <div>
                    <h5 class="modal-title font-heading fw-bold mb-0 text-white">
                        <i class="fas fa-signature me-2 text-warning"></i> Gestión de Firma Digital del Docente
                    </h5>
                    <small class="text-white-50">Configuración de firma gráfica y mecanografiada para Planes PEI y Boletines Académicos.</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                
                <!-- Estado Actual de la Firma -->
                <div class="p-3 mb-4 rounded border <?= !empty($docenteFirma) ? 'bg-success-subtle border-success' : 'bg-warning-subtle border-warning' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-<?= !empty($docenteFirma) ? 'success' : 'warning text-dark' ?> mb-1">
                                <i class="fas fa-<?= !empty($docenteFirma) ? 'check-circle' : 'exclamation-circle' ?> me-1"></i>
                                <?= !empty($docenteFirma) ? 'Firma Oficial Activa' : 'Sin Firma Digital Registrada' ?>
                            </span>
                            <h6 class="fw-bold mb-0 text-dark">
                                <?= !empty($docenteFirma) ? 'Su firma está vinculada y validada para documentos oficiales.' : 'Debe cargar su firma para validación de Planes de Aula y Boletines.' ?>
                            </h6>
                        </div>
                        <?php if (!empty($docenteFirma)): ?>
                            <form method="POST" onsubmit="return confirm('¿Desea remover su firma actual?');">
                                <input type="hidden" name="action" value="eliminar_firma">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash me-1"></i> Remover Firma
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($docenteFirma)): ?>
                        <div class="mt-3 p-2 bg-white rounded border text-center">
                            <span class="text-muted extra-small d-block mb-1">Vista Previa de la Firma Registrada:</span>
                            <?php if ($firmaEsImg): ?>
                                <img src="<?= htmlspecialchars($firmaImgSrc) ?>" alt="Firma Actual" style="max-height: 60px; max-width: 220px; object-fit: contain;">
                            <?php else: ?>
                                <div style="font-family:'Brush Script MT', 'Dancing Script', cursive; font-size: 1.8rem; color: #1E3A8A;">
                                    <?= htmlspecialchars($firmaTexto) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Opciones para Cargar / Actualizar Firma -->
                <ul class="nav nav-tabs border-bottom mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-subir-archivo">
                            <i class="fas fa-upload me-1 text-primary"></i> 1. Subir Archivo (.PNG / .JPG / .PDF)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-dibujar-firma" onclick="setTimeout(initCanvas, 200)">
                            <i class="fas fa-pen-nib me-1 text-success"></i> 2. Dibujar en Pantalla (Táctil / Mouse)
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-mecanografiada">
                            <i class="fas fa-font me-1 text-warning"></i> 3. Firma Mecanografiada
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Opción 1: Subir Archivo -->
                    <div class="tab-pane fade show active" id="tab-subir-archivo">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="guardar_firma">
                            <input type="hidden" name="tipo_firma" value="archivo">
                            <div class="mb-3 p-3 bg-light rounded border">
                                <label class="form-label small fw-semibold">Seleccionar archivo de firma:</label>
                                <input type="file" class="form-control mb-2" name="firma_archivo" accept=".png, .jpg, .jpeg, .pdf" required>
                                <small class="text-muted d-block">
                                    <i class="fas fa-info-circle me-1"></i> Formatos aceptados: <strong>.PNG</strong> (recomendado con fondo transparente / canal alfa), <strong>.JPG / .JPEG</strong>, o <strong>.PDF</strong> firmado.
                                </small>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-lasalle-primary btn-sm">
                                    <i class="fas fa-save me-1"></i> Guardar Archivo de Firma
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Opción 2: Dibujar en Pantalla -->
                    <div class="tab-pane fade" id="tab-dibujar-firma">
                        <form method="POST" id="formCanvasFirma">
                            <input type="hidden" name="action" value="guardar_firma">
                            <input type="hidden" name="tipo_firma" value="canvas">
                            <input type="hidden" name="firma_canvas_data" id="firmaCanvasData">

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Dibuje su firma en el recuadro usando mouse o pantalla táctil:</label>
                                <div class="border rounded bg-white shadow-sm p-1 text-center position-relative">
                                    <canvas id="signatureCanvas" width="600" height="180" style="width: 100%; height: 180px; touch-action: none; cursor: crosshair;"></canvas>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearCanvas()">
                                    <i class="fas fa-eraser me-1"></i> Limpiar Lienzo
                                </button>
                                <button type="button" class="btn btn-success btn-sm fw-semibold" onclick="submitCanvasFirma()">
                                    <i class="fas fa-check me-1"></i> Guardar Firma Trazada
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Opción 3: Mecanografiada Cursiva -->
                    <div class="tab-pane fade" id="tab-mecanografiada">
                        <form method="POST">
                            <input type="hidden" name="action" value="guardar_firma">
                            <input type="hidden" name="tipo_firma" value="texto">
                            <div class="mb-3 p-3 bg-light rounded border">
                                <label class="form-label small fw-semibold">Texto o Nombre de la Firma Oficial:</label>
                                <input type="text" class="form-control mb-3" name="firma_texto" id="inputFirmaTexto" value="<?= htmlspecialchars($userName) ?>" oninput="updateLiveTextSignature(this.value)" required>
                                
                                <label class="form-label extra-small text-muted fw-semibold">Previsualización Cursiva Oficial:</label>
                                <div class="p-3 bg-white rounded border text-center">
                                    <div id="previewFirmaTexto" style="font-family:'Brush Script MT', 'Dancing Script', cursive; font-size: 2rem; color: #1E3A8A;">
                                        <?= htmlspecialchars($userName) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-lasalle-primary btn-sm">
                                    <i class="fas fa-check-circle me-1"></i> Establecer Firma Mecanografiada
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
            <div class="modal-footer bg-light border-0 px-4 py-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Configuración de Banco de Logros por Escala -->
<div class="modal fade" id="modalBancoLogros" tabindex="-1" aria-labelledby="modalBancoLogrosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalBancoLogrosLabel">
                    <i class="fas fa-award me-2"></i> Banco de Logros Institucionales por Escala (Periodo <?= $periodo ?>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="save_banco_logros">
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 px-3 small border-0 mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        Defina el logro para cada escala de desempeño en esta asignatura y periodo. Al calificar a sus estudiantes, el sistema asociará automáticamente el logro correspondiente según la nota asignada.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-success">
                            <i class="fas fa-star me-1"></i> 1. Desempeño Superior (4.6 – 5.0 / Letra 'S')
                        </label>
                        <textarea class="form-control" name="logro_superior" rows="2" placeholder="Ej: Demuestra excelencia y dominio sobresaliente de los aprendizajes y competencias de la asignatura..." required><?= htmlspecialchars($bancoLogros['Superior'] ?: 'Desempeño Superior: Excelente asimilación de logros, demuestra gran capacidad de análisis crítico y resolución de problemas.') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-primary">
                            <i class="fas fa-check-circle me-1"></i> 2. Desempeño Alto (4.0 – 4.5 / Letra 'A')
                        </label>
                        <textarea class="form-control" name="logro_alto" rows="2" placeholder="Ej: Alcanza satisfactoriamente los objetivos y competencias propuestas con buen nivel de compromiso..." required><?= htmlspecialchars($bancoLogros['Alto'] ?: 'Desempeño Alto: Buen rendimiento académico, comprende satisfactoriamente los conceptos y cumple con las actividades del curso.') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-warning">
                            <i class="fas fa-minus-circle me-1"></i> 3. Desempeño Básico (3.0 – 3.9 / Letra 'B')
                        </label>
                        <textarea class="form-control" name="logro_basico" rows="2" placeholder="Ej: Cumple con las competencias y estándares mínimos exigidos. Requiere fortalecer hábitos de estudio..." required><?= htmlspecialchars($bancoLogros['Basico'] ?: 'Desempeño Básico: Cumple con el mínimo de competencias exigidas. Se recomienda fortalecer hábitos de estudio y repasar contenidos.') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-danger">
                            <i class="fas fa-exclamation-triangle me-1"></i> 4. Desempeño Bajo (1.0 – 2.9 / Letra 'BJ')
                        </label>
                        <textarea class="form-control" name="logro_bajo" rows="2" placeholder="Ej: Presenta dificultades en la asimilación de competencias básicas. Requiere plan de nivelación urgente..." required><?= htmlspecialchars($bancoLogros['Bajo'] ?: 'Desempeño Bajo: Dificultad para asimilar conceptos básicos de la asignatura. Requiere participar de forma urgente en actividades de nivelación.') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-save me-1"></i> Guardar Logros de la Escala
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script>
function applyFilters() {
    const curso = document.getElementById('filter_curso').value;
    const asignatura = document.getElementById('filter_asignatura').value;
    const periodo = document.getElementById('filter_periodo').value;
    window.location.href = `?curso_id=${curso}&asignatura_id=${asignatura}&periodo=${periodo}`;
}

const sistemaCalif = "<?= $sistema_calif ?>";
const bancoLogrosPeriodo = <?= json_encode($bancoLogros) ?>;

function onNotaChange(studentId) {
    const input = document.getElementById(`nota_${studentId}`);
    if (!input) return;
    const val = input.value.trim();
    if (val === "") {
        const badge = document.getElementById(`badge_escala_${studentId}`);
        if (badge) {
            badge.className = "badge bg-secondary fs-6";
            badge.innerText = "Pendiente";
        }
        return;
    }

    let numVal = 0;
    if (sistemaCalif === 'letras') {
        const letterMap = { 'S': 4.8, 'A': 4.2, 'B': 3.5, 'BJ': 2.0 };
        numVal = letterMap[val] || 3.5;
    } else {
        numVal = parseFloat(val) || 0;
        if (sistemaCalif === '1-10') {
            numVal = numVal / 2; // Normalizar a escala 1-5 interna
        }
    }

    let escalaKey = 'Bajo';
    let escalaLabel = 'Bajo';
    let badgeClass = 'bg-danger';

    if (numVal >= 4.6) {
        escalaKey = 'Superior';
        escalaLabel = 'Superior';
        badgeClass = 'bg-success';
    } else if (numVal >= 4.0) {
        escalaKey = 'Alto';
        escalaLabel = 'Alto';
        badgeClass = 'bg-primary';
    } else if (numVal >= 3.0) {
        escalaKey = 'Basico';
        escalaLabel = 'Básico';
        badgeClass = 'bg-warning text-dark';
    } else {
        escalaKey = 'Bajo';
        escalaLabel = 'Bajo';
        badgeClass = 'bg-danger';
    }

    // Actualizar badge de desempeño en tiempo real
    const badge = document.getElementById(`badge_escala_${studentId}`);
    if (badge) {
        badge.className = `badge ${badgeClass} fs-6`;
        badge.innerText = escalaLabel;
    }

    // Asignar automáticamente el logro guardado en el banco de logros por escala
    const logroInput = document.getElementById(`logros_${studentId}`);
    if (logroInput) {
        if (bancoLogrosPeriodo && bancoLogrosPeriodo[escalaKey] && bancoLogrosPeriodo[escalaKey].trim() !== "") {
            logroInput.value = bancoLogrosPeriodo[escalaKey];
        } else {
            const defaults = {
                'Superior': 'Desempeño Superior: Excelente asimilación de logros, demuestra gran capacidad de análisis crítico y resolución de problemas.',
                'Alto': 'Desempeño Alto: Buen rendimiento académico, comprende satisfactoriamente los conceptos y cumple con las actividades del curso.',
                'Basico': 'Desempeño Básico: Cumple con el mínimo de competencias exigidas. Se recomienda fortalecer hábitos de estudio y repasar contenidos.',
                'Bajo': 'Desempeño Bajo: Dificultad para asimilar conceptos básicos de la asignatura. Requiere participar de forma urgente en actividades de nivelación.'
            };
            logroInput.value = defaults[escalaKey] || '';
        }
    }
}

// Control del Lienzo Interactivo de Firma (Canvas)
let canvas, ctx, isDrawing = false, hasDrawn = false;

function initCanvas() {
    canvas = document.getElementById('signatureCanvas');
    if (!canvas) return;
    ctx = canvas.getContext('2d');
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0F172A';

    function getCoords(e) {
        const rect = canvas.getBoundingClientRect();
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        let clientX = e.clientX, clientY = e.clientY;
        if (e.touches && e.touches.length > 0) {
            clientX = e.touches[0].clientX;
            clientY = e.touches[0].clientY;
        }
        return {
            x: (clientX - rect.left) * scaleX,
            y: (clientY - rect.top) * scaleY
        };
    }

    function startDraw(e) {
        isDrawing = true;
        hasDrawn = true;
        const pos = getCoords(e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        e.preventDefault();
    }

    function moveDraw(e) {
        if (!isDrawing) return;
        const pos = getCoords(e);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        e.preventDefault();
    }

    function endDraw() {
        isDrawing = false;
    }

    canvas.onmousedown = startDraw;
    canvas.onmousemove = moveDraw;
    window.onmouseup = endDraw;

    canvas.ontouchstart = startDraw;
    canvas.ontouchmove = moveDraw;
    window.ontouchend = endDraw;
}

function clearCanvas() {
    if (!canvas || !ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasDrawn = false;
}

function submitCanvasFirma() {
    if (!hasDrawn) {
        alert('Por favor trace su firma en el lienzo antes de guardar.');
        return;
    }
    const dataURL = canvas.toDataURL('image/png');
    document.getElementById('firmaCanvasData').value = dataURL;
    document.getElementById('formCanvasFirma').submit();
}

function updateLiveTextSignature(val) {
    document.getElementById('previewFirmaTexto').innerText = val || 'Firma Oficial';
}

function buscarEstudianteDocente(input, selectId, feedbackId) {
    const term = input.value.toLowerCase().trim();
    const select = document.getElementById(selectId);
    const feedback = document.getElementById(feedbackId);
    const options = select.options;
    let matchCount = 0;
    let firstMatch = null;

    for (let i = 0; i < options.length; i++) {
        const opt = options[i];
        if (!opt.value) continue;
        
        const doc = (opt.getAttribute('data-doc') || '').toLowerCase();
        const search = (opt.getAttribute('data-search') || opt.text).toLowerCase();
        
        if (term === '' || doc.includes(term) || search.includes(term)) {
            opt.hidden = false;
            opt.style.display = '';
            matchCount++;
            if (!firstMatch) {
                firstMatch = opt;
            }
        } else {
            opt.hidden = true;
            opt.style.display = 'none';
        }
    }

    if (term.length > 0) {
        if (firstMatch) {
            select.value = firstMatch.value;
            actualizarFeedbackEstudianteDocente(select, feedbackId);
        } else {
            select.value = "";
            feedback.innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> No se encontró ningún estudiante con el documento o nombre: "<strong>${input.value}</strong>"</span>`;
        }
    } else {
        feedback.innerHTML = '';
    }
}

function limpiarBusquedaEstudianteDocente(inputId, selectId, feedbackId) {
    const input = document.getElementById(inputId);
    input.value = '';
    buscarEstudianteDocente(input, selectId, feedbackId);
    document.getElementById(selectId).value = '';
    document.getElementById(feedbackId).innerHTML = '';
}

function actualizarFeedbackEstudianteDocente(select, feedbackId) {
    const feedback = document.getElementById(feedbackId);
    const selOpt = select.options[select.selectedIndex];
    if (selOpt && selOpt.value) {
        const nom = selOpt.getAttribute('data-name');
        const doc = selOpt.getAttribute('data-doc');
        const acud = selOpt.getAttribute('data-acudiente');
        const mail = selOpt.getAttribute('data-correo');
        feedback.innerHTML = `<div class="p-2 bg-light rounded border mt-1"><span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i> Seleccionado: <strong>${nom}</strong> (Doc: ${doc})</span><br><small class="text-muted"><i class="fas fa-user-shield me-1"></i> Acudiente: <strong>${acud}</strong> &nbsp;|&nbsp; <i class="fas fa-envelope me-1"></i> Correo: <code>${mail}</code></small></div>`;
    } else {
        feedback.innerHTML = '';
    }
}

// =========================================================================
// FUNCIONES JS: ACTA DE CONVIVENCIA TIPO I (DOCENTE)
// =========================================================================
let currentDocStudent = null;
let selectedDocFaltas = [];

function buscarEstudianteActaDocente(input) {
    const term = input.value.toLowerCase().trim();
    const select = document.getElementById('select_doc_acta_student');
    const feedback = document.getElementById('feedback_doc_acta_student');
    const options = select.options;
    let matchCount = 0;
    let firstMatch = null;

    for (let i = 0; i < options.length; i++) {
        const opt = options[i];
        if (!opt.value) continue;
        const doc = (opt.getAttribute('data-doc') || '').toLowerCase();
        const search = (opt.getAttribute('data-search') || opt.text).toLowerCase();
        if (term === '' || doc.includes(term) || search.includes(term)) {
            opt.hidden = false;
            opt.style.display = '';
            matchCount++;
            if (!firstMatch) firstMatch = opt;
        } else {
            opt.hidden = true;
            opt.style.display = 'none';
        }
    }

    if (term.length > 0) {
        if (firstMatch) {
            select.value = firstMatch.value;
            cargarEstudianteParaActaDocente(firstMatch.value);
        } else {
            select.value = "";
            feedback.innerHTML = `<span class="text-danger small"><i class="fas fa-exclamation-triangle me-1"></i> No se encontró ningún estudiante con "<strong>${input.value}</strong>"</span>`;
        }
    } else {
        feedback.innerHTML = '';
    }
}

function limpiarBusquedaActaDocente() {
    const input = document.getElementById('search_doc_acta_student');
    input.value = '';
    buscarEstudianteActaDocente(input);
    document.getElementById('select_doc_acta_student').value = '';
    document.getElementById('feedback_doc_acta_student').innerHTML = '';
    currentDocStudent = null;
    actualizarVistaPreviaDocente();
}

function cargarEstudianteParaActaDocente(studentId) {
    const feedback = document.getElementById('feedback_doc_acta_student');
    if (!studentId) {
        currentDocStudent = null;
        feedback.innerHTML = '';
        actualizarVistaPreviaDocente();
        return;
    }

    feedback.innerHTML = '<span class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i> Cargando información del estudiante...</span>';

    fetch('<?= APP_URL ?>/views/coordinador/actas_action.php?action=get_student&id=' + studentId)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                const s = res.data;
                currentDocStudent = {
                    id: s.id,
                    nombre: s.nombre_completo,
                    doc: `${s.tipo_documento || 'TI'} ${s.documento_identidad}`,
                    grado: s.curso_nombre || `Grado ${s.grado} ${s.grupo}`,
                    curso_id: s.curso_id || 0,
                    acudiente: s.acudiente_nombre || 'Acudiente Registrado',
                    acudiente_tel: s.acudiente_telefono || 'N/D',
                    acudiente_mail: s.acudiente_correo || 'N/D'
                };

                document.getElementById('doc_acta_estudiante_id').value = currentDocStudent.id;
                document.getElementById('doc_acta_curso_id').value = currentDocStudent.curso_id;
                document.getElementById('doc_acta_estudiante_nombre').value = currentDocStudent.nombre;
                document.getElementById('doc_acta_estudiante_doc').value = currentDocStudent.doc;
                document.getElementById('doc_acta_estudiante_grado').value = currentDocStudent.grado;
                document.getElementById('doc_inp_firma_alumno').value = currentDocStudent.nombre;
                document.getElementById('doc_inp_firma_padre').value = currentDocStudent.acudiente;

                feedback.innerHTML = `
                    <div class="p-2 bg-light rounded border">
                        <span class="text-success fw-bold small"><i class="fas fa-check-circle me-1"></i> ${currentDocStudent.nombre}</span>
                        <div class="extra-small text-muted">
                            Documento: <strong>${currentDocStudent.doc}</strong> | Curso: <strong>${currentDocStudent.grado}</strong><br>
                            Acudiente: <strong>${currentDocStudent.acudiente}</strong> (Tel: ${currentDocStudent.acudiente_tel})
                        </div>
                    </div>
                `;
                actualizarVistaPreviaDocente();
            } else {
                feedback.innerHTML = `<span class="text-danger small"><i class="fas fa-exclamation-circle me-1"></i> ${res.message}</span>`;
            }
        })
        .catch(err => {
            feedback.innerHTML = `<span class="text-danger small">Error de conexión: ${err.message}</span>`;
        });
}

function filtrarFaltasDocente(term) {
    const q = term.toLowerCase().trim();
    const items = document.querySelectorAll('#container_doc_faltas .item-falta-doc');
    items.forEach(el => {
        const text = el.getAttribute('data-text') || '';
        el.style.display = (q === '' || text.includes(q)) ? 'flex' : 'none';
    });
}

function toggleFaltaDocente(cb) {
    selectedDocFaltas = [];
    document.querySelectorAll('#container_doc_faltas .check-falta-doc:checked').forEach(c => {
        selectedDocFaltas.push(c.value);
    });
    document.getElementById('badge_doc_faltas_count').textContent = `${selectedDocFaltas.length} seleccionada(s)`;
    actualizarVistaPreviaDocente();
}

function actualizarVistaPreviaDocente() {
    // Estudiante
    if (currentDocStudent) {
        document.getElementById('doc_pv_estudiante').textContent = currentDocStudent.nombre;
        document.getElementById('doc_pv_doc').textContent = currentDocStudent.doc;
        document.getElementById('doc_pv_grado').textContent = currentDocStudent.grado;
        document.getElementById('doc_pv_acudiente').textContent = `${currentDocStudent.acudiente} (Tel: ${currentDocStudent.acudiente_tel})`;
    } else {
        document.getElementById('doc_pv_estudiante').textContent = '-- Seleccione Estudiante --';
        document.getElementById('doc_pv_doc').textContent = '--';
        document.getElementById('doc_pv_grado').textContent = '--';
        document.getElementById('doc_pv_acudiente').textContent = '--';
    }

    // Fecha
    const fInp = document.getElementById('doc_inp_fecha_acta').value;
    if (fInp) {
        const parts = fInp.split('-');
        document.getElementById('doc_pv_fecha').textContent = `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    // Faltas
    const listaFaltas = document.getElementById('doc_pv_faltas_lista');
    if (selectedDocFaltas.length === 0) {
        listaFaltas.className = 'small text-muted fst-italic';
        listaFaltas.innerHTML = 'No se ha seleccionado ninguna falta aún.';
    } else {
        listaFaltas.className = 'small text-dark';
        let html = '<ol class="mb-0 ps-3">';
        selectedDocFaltas.forEach(f => {
            html += `<li class="mb-1">${f}</li>`;
        });
        html += '</ol>';
        listaFaltas.innerHTML = html;
    }

    // Textareas
    const descargos = document.getElementById('doc_inp_descargos').value.trim();
    document.getElementById('doc_pv_descargos').textContent = descargos || '(Espacio para la versión expresada por el estudiante)';

    const afectados = document.getElementById('doc_inp_afectados').value.trim();
    document.getElementById('doc_pv_afectados').textContent = afectados || '(Ninguna afectación física ni daño material registrado)';

    const compromisos = document.getElementById('doc_inp_compromisos').value.trim();
    document.getElementById('doc_pv_compromisos').textContent = compromisos || '(Compromisos adquiridos para subsanar y no reincidir en la conducta)';

    // Firmas
    document.getElementById('doc_pv_firma_alumno').textContent = document.getElementById('doc_inp_firma_alumno').value || 'Firma del Estudiante';
    document.getElementById('doc_pv_firma_docente').textContent = document.getElementById('doc_inp_firma_docente').value || 'Docente Convocante';
    document.getElementById('doc_pv_firma_padre').textContent = document.getElementById('doc_inp_firma_padre').value || 'Firma Padre / Acudiente';
    document.getElementById('doc_pv_firma_dir').textContent = document.getElementById('doc_inp_firma_dir').value || 'Director de Grupo';
}

function guardarActaDocente() {
    if (!currentDocStudent) {
        alert('Por favor seleccione un estudiante implicado.');
        return;
    }
    if (selectedDocFaltas.length === 0) {
        alert('Debe seleccionar al menos una falta Tipo I del listado.');
        return;
    }

    const btn = document.getElementById('btn_doc_guardar_acta');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

    const formData = new FormData(document.getElementById('formActaDocente'));
    formData.append('action', 'save_acta');

    fetch('<?= APP_URL ?>/views/coordinador/actas_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Guardar Acta en Sistema';
        if (data.status === 'success') {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Guardar Acta en Sistema';
        alert('Error al comunicarse con el servidor: ' + err.message);
    });
}

function descargarActaWordDocente() {
    if (!currentDocStudent) {
        alert('Por favor seleccione un estudiante antes de descargar el documento.');
        return;
    }
    if (selectedDocFaltas.length === 0) {
        alert('Seleccione al menos una falta Tipo I para generar el acta formal.');
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= APP_URL ?>/views/coordinador/actas_action.php';
    form.target = '_blank';

    const addField = (name, val) => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = name;
        inp.value = val;
        form.appendChild(inp);
    };

    addField('action', 'download_docx');
    addField('tipo_acta', 'Tipo I');
    addField('estudiante_nombre', currentDocStudent.nombre);
    addField('estudiante_doc', currentDocStudent.doc);
    addField('estudiante_grado', currentDocStudent.grado);
    addField('fecha_acta', document.getElementById('doc_inp_fecha_acta').value);
    addField('descargos', document.getElementById('doc_inp_descargos').value);
    addField('testigos', document.getElementById('doc_inp_afectados').value);
    addField('afectados_o_bienes', document.getElementById('doc_inp_afectados').value);
    addField('compromisos', document.getElementById('doc_inp_compromisos').value);

    addField('firma_alumno', document.getElementById('doc_inp_firma_alumno').value);
    addField('firma_docente', document.getElementById('doc_inp_firma_docente').value);
    addField('firma_padre', document.getElementById('doc_inp_firma_padre').value);
    addField('firma_dir_grupo', document.getElementById('doc_inp_firma_dir').value);

    selectedDocFaltas.forEach(f => {
        addField('faltas[]', f);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function enviarActaDocenteAlAcudiente() {
    if (!currentDocStudent) {
        alert('Por favor seleccione un estudiante implicado.');
        return;
    }
    if (selectedDocFaltas.length === 0) {
        alert('Debe seleccionar al menos una falta Tipo I para notificar al acudiente.');
        return;
    }

    const btn = document.getElementById('btn_doc_enviar_acta_correo');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Remitiendo correo...';

    const formData = new FormData(document.getElementById('formActaDocente'));
    formData.append('action', 'send_acta_email');
    selectedDocFaltas.forEach(f => {
        formData.append('faltas_seleccionadas[]', f);
    });

    fetch('<?= APP_URL ?>/views/coordinador/actas_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Enviar al Acudiente por Correo';
        alert(data.message);
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane me-1"></i> Enviar al Acudiente por Correo';
        alert('Error al remitir la notificación por correo: ' + err.message);
    });
}

function imprimirActaDocente() {
    const printContent = document.getElementById('printableActaDocente').innerHTML;
    const origContent = document.body.innerHTML;
    document.body.innerHTML = '<div style="max-width: 800px; margin: 20px auto; padding: 20px;">' + printContent + '</div>';
    window.print();
    document.body.innerHTML = origContent;
    location.reload();
}
</script>
</body>
</html>
