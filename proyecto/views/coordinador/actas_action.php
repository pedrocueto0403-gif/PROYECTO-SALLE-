<?php
/**
 * ACCIONES Y ENDPOINTS AJAX/DESCARGA PARA ACTAS DE CONVIVENCIA ESCOLAR
 * Instituto La Salle Ciénaga Magdalena
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../app/Services/ActaService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = strtolower($_SESSION['user_role'] ?? '');
$userId   = intval($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['user_name'] ?? 'Usuario';

// Roles autorizados: administrador, coordinador, docente
if (!in_array($userRole, ['administrador', 'coordinador', 'docente']) || !$userId) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Acceso no autorizado. Inicie sesión con un rol competente.']));
}

$action = $_REQUEST['action'] ?? '';

// =========================================================================
// 1. OBTENER CATÁLOGO DE FALTAS POR TIPO (AJAX)
// =========================================================================
if ($action === 'get_catalog') {
    header('Content-Type: application/json; charset=utf-8');
    $tipo = $_GET['tipo'] ?? 'Tipo I';
    
    // Regla de negocio: Docente solo puede diligenciar Tipo I
    if ($userRole === 'docente' && $tipo !== 'Tipo I') {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Los docentes solo están autorizados para diligenciar Actas de Situaciones Tipo I (Faltas Leves). Las Situaciones Tipo II y Tipo III corresponden exclusivamente a Coordinación y Rectoría.'
        ]);
        exit;
    }

    $catalogo = ActaService::getCatalogoFaltas();
    echo json_encode([
        'status' => 'success',
        'tipo'   => $tipo,
        'definicion' => ActaService::getDefinicionSituacion($tipo),
        'faltas' => $catalogo[$tipo] ?? []
    ]);
    exit;
}

// =========================================================================
// 2. OBTENER INFORMACIÓN COMPLETA DEL ESTUDIANTE (AJAX)
// =========================================================================
if ($action === 'get_student') {
    header('Content-Type: application/json; charset=utf-8');
    $studentId = intval($_GET['id'] ?? 0);
    if (!$studentId) {
        echo json_encode(['status' => 'error', 'message' => 'ID de estudiante no proporcionado']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Sin conexión a base de datos']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.correo,
                   p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   p.tipo_documento, p.documento_identidad, p.telefono, p.direccion,
                   p.acudiente_nombre, p.acudiente_telefono, p.acudiente_correo,
                   COALESCE(c.grado, '11°') AS grado,
                   COALESCE(c.grupo, 'A') AS grupo,
                   COALESCE(c.id, 0) AS curso_id
            FROM usuarios u
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            WHERE u.id = :id AND u.rol_id = 5
            LIMIT 1
        ");
        $stmt->execute([':id' => $studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'Estudiante no encontrado']);
            exit;
        }

        $row['nombre_completo'] = trim("{$row['primer_apellido']} {$row['segundo_apellido']} {$row['primer_nombre']} {$row['segundo_nombre']}");
        $row['curso_nombre'] = "Grado {$row['grado']} {$row['grupo']}";
        echo json_encode(['status' => 'success', 'data' => $row]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// 3. GUARDAR ACTA EN BASE DE DATOS (POST / AJAX)
// =========================================================================
if ($action === 'save_acta') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
        exit;
    }

    $tipo = trim($_POST['tipo_acta'] ?? 'Tipo I');
    
    // Regla de permisos estricta
    if ($userRole === 'docente' && $tipo !== 'Tipo I') {
        echo json_encode([
            'status' => 'error', 
            'message' => 'No autorizado: Como docente únicamente puede generar y registrar Actas de Situaciones Tipo I. Las Actas Tipo II y Tipo III deben ser tramitadas por Coordinación de Convivencia o Rectoría.'
        ]);
        exit;
    }

    $studentId   = intval($_POST['estudiante_id'] ?? 0);
    $cursoId     = intval($_POST['curso_id'] ?? 0);
    $fechaActa   = $_POST['fecha_acta'] ?? date('Y-m-d');
    $faltasRaw   = $_POST['faltas_seleccionadas'] ?? [];
    $descargos   = trim($_POST['descargos'] ?? '');
    $testigos    = trim($_POST['testigos'] ?? '');
    $afectados   = trim($_POST['afectados_o_bienes'] ?? '');
    $compromisos = trim($_POST['compromisos'] ?? '');
    $remision    = trim($_POST['remision_entidad'] ?? '');
    $otros       = trim($_POST['otros'] ?? '');

    $firmas = [
        'alumno'    => trim($_POST['firma_alumno'] ?? ''),
        'afectado'  => trim($_POST['firma_afectado'] ?? ''),
        'testigo1'  => trim($_POST['firma_testigo1'] ?? ''),
        'testigo2'  => trim($_POST['firma_testigo2'] ?? ''),
        'padre'     => trim($_POST['firma_padre'] ?? ''),
        'docente'   => trim($_POST['firma_docente'] ?? ''),
        'dir_grupo' => trim($_POST['firma_dir_grupo'] ?? ''),
        'coord'     => trim($_POST['firma_coord'] ?? '')
    ];

    if (!$studentId) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar un estudiante matriculado.']);
        exit;
    }

    $faltasJson = is_array($faltasRaw) ? json_encode($faltasRaw, JSON_UNESCAPED_UNICODE) : $faltasRaw;

    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Sin conexión a base de datos']);
        exit;
    }

    try {
        // Consecutivo institucional
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM actas_convivencia");
        $nextNum = intval($stmtCount->fetchColumn()) + 1;
        $numeroActa = "ACTA-2026-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $stmtIns = $pdo->prepare("
            INSERT INTO actas_convivencia 
                (numero_acta, tipo_acta, estudiante_id, curso_id, creador_id, creador_rol, creador_nombre, 
                 fecha_acta, faltas_seleccionadas, descargos, testigos, afectados_o_bienes, compromisos, 
                 remision_entidad, otros, firmas_nombres)
            VALUES 
                (:num, :tipo, :eid, :cid, :crid, :rol, :crnom, 
                 :fec, :faltas, :desc, :test, :afec, :comp, 
                 :rem, :otros, :firmas)
        ");
        $stmtIns->execute([
            ':num'    => $numeroActa,
            ':tipo'   => $tipo,
            ':eid'    => $studentId,
            ':cid'    => $cursoId ?: null,
            ':crid'   => $userId,
            ':rol'    => $userRole,
            ':crnom'  => $userName,
            ':fec'    => $fechaActa,
            ':faltas' => $faltasJson,
            ':desc'   => $descargos,
            ':test'   => $testigos,
            ':afec'   => $afectados,
            ':comp'   => $compromisos,
            ':rem'    => ($tipo === 'Tipo III') ? $remision : null,
            ':otros'  => $otros,
            ':firmas' => json_encode($firmas, JSON_UNESCAPED_UNICODE)
        ]);

        $newId = $pdo->lastInsertId();

        echo json_encode([
            'status'      => 'success',
            'message'     => "✅ {$numeroActa} registrada exitosamente en el sistema de convivencia escolar.",
            'acta_id'     => $newId,
            'numero_acta' => $numeroActa
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar acta: ' . $e->getMessage()]);
    }
    exit;
}

// =========================================================================
// 4. GENERAR Y DESCARGAR ARCHIVO WORD (.DOCX)
// =========================================================================
if ($action === 'download_docx') {
    $tipo = trim($_REQUEST['tipo_acta'] ?? 'Tipo I');

    // Validación de rol
    if ($userRole === 'docente' && $tipo !== 'Tipo I') {
        die("Error 403: Los docentes solo pueden generar actas Tipo I.");
    }

    $data = $_REQUEST;
    if (empty($data['numero_acta'])) {
        $data['numero_acta'] = 'ACTA-' . date('Ymd-His');
    }

    // Normalizar faltas
    if (isset($data['faltas']) && is_array($data['faltas'])) {
        $data['faltas_seleccionadas'] = $data['faltas'];
    } elseif (isset($data['faltas_seleccionadas']) && is_string($data['faltas_seleccionadas'])) {
        $dec = json_decode($data['faltas_seleccionadas'], true);
        if (is_array($dec)) $data['faltas_seleccionadas'] = $dec;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'salle_acta_') . '.docx';
    $ok = ActaService::generateDocx($data, $tempFile);

    if ($ok && file_exists($tempFile)) {
        $cleanEst = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['estudiante_nombre'] ?? 'Estudiante');
        $fileName = "{$data['numero_acta']}_{$tipo}_{$cleanEst}.docx";

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tempFile));
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    } else {
        die("Error al compilar el documento Word de la situación disciplinaria.");
    }
}

// =========================================================================
// 5. ENVIAR NOTIFICACIÓN Y COPIA DEL ACTA AL CORREO DEL ACUDIENTE (AJAX)
// =========================================================================
if ($action === 'send_acta_email') {
    header('Content-Type: application/json; charset=utf-8');
    require_once __DIR__ . '/../../app/Services/EmailGateway.php';

    $studentId   = intval($_POST['estudiante_id'] ?? 0);
    $tipoActa    = trim($_POST['tipo_acta'] ?? 'Tipo I');
    $numeroActa  = trim($_POST['numero_acta'] ?? ('ACTA-' . date('Ymd-His')));
    $fechaActa   = $_POST['fecha_acta'] ?? date('Y-m-d');
    $faltasRaw   = $_POST['faltas_seleccionadas'] ?? [];
    $descargos   = trim($_POST['descargos'] ?? '');
    $compromisos = trim($_POST['compromisos'] ?? '');
    $convocante  = trim($_POST['convocante'] ?? $userName);

    if (!$studentId) {
        echo json_encode(['status' => 'error', 'message' => 'Debe seleccionar un estudiante matriculado.']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Sin conexión a base de datos.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.correo,
                   p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   p.documento_identidad, p.acudiente_nombre, p.acudiente_correo, p.acudiente_telefono,
                   COALESCE(c.grado, '11°') AS grado, COALESCE(c.grupo, 'A') AS grupo
            FROM usuarios u
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $studentId]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alumno) {
            echo json_encode(['status' => 'error', 'message' => 'Estudiante no encontrado.']);
            exit;
        }

        $acudienteMail = trim($alumno['acudiente_correo'] ?? '');
        if (empty($acudienteMail) || !filter_var($acudienteMail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => "El estudiante {$alumno['primer_nombre']} {$alumno['primer_apellido']} no tiene un correo de acudiente válido registrado en el sistema escolar."
            ]);
            exit;
        }

        $faltasArr = is_array($faltasRaw) ? $faltasRaw : (json_decode($faltasRaw, true) ?: [$faltasRaw]);

        $res = EmailGateway::notificarActaConvivencia(
            [
                'primer_nombre'    => $alumno['primer_nombre'],
                'primer_apellido'  => $alumno['primer_apellido'],
                'acudiente_nombre' => $alumno['acudiente_nombre'],
                'acudiente_correo' => $acudienteMail,
                'grado'            => $alumno['grado'],
                'grupo'            => $alumno['grupo'],
            ],
            [
                'numero_acta' => $numeroActa,
                'tipo_acta'   => $tipoActa,
                'fecha_acta'  => $fechaActa,
                'faltas'      => $faltasArr,
                'descargos'   => $descargos,
                'compromisos' => $compromisos,
                'convocante'  => $convocante
            ]
        );

        if (!empty($res['success']) || in_array($res['estado'] ?? '', ['enviado', 'simulado'])) {
            echo json_encode([
                'status' => 'success',
                'message' => "✅ Notificación del Acta remitida exitosamente al correo del acudiente: {$acudienteMail}"
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => $res['mensaje'] ?? "No se pudo despachar el correo a {$acudienteMail}."
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error al notificar acudiente: ' . $e->getMessage()]);
    }
    exit;
}
