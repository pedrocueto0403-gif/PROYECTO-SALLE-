<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userRole = $_SESSION['user_role'] ?? '';
$userRolId = $_SESSION['rol_id'] ?? 0;
if ($userRole !== 'administrador' && $userRolId != 1) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Acceso no autorizado']));
}

$action = $_REQUEST['action'] ?? '';

// =========================================================================
// ENDPOINT: Obtener lista completa de docentes activos (AJAX)
// =========================================================================
if ($action === 'get_all_teachers') {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Sin conexión a base de datos', 'data' => []]);
        exit;
    }
    try {
        $docs = $pdo->query("
            SELECT u.id, u.correo,
                   p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   p.tipo_documento, p.documento_identidad, p.telefono, p.direccion
            FROM usuarios u
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            WHERE u.rol_id = 3
            ORDER BY p.primer_apellido, p.primer_nombre
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $docs]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'data' => []]);
    }
    exit;
}

// =========================================================================
// ENDPOINT: Obtener datos completos de estudiante o docente (AJAX)
// =========================================================================
if ($action === 'get_person') {
    header('Content-Type: application/json; charset=utf-8');
    $type = $_GET['type'] ?? 'student';
    $id = intval($_GET['id'] ?? 0);

    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }

    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        echo json_encode(['status' => 'error', 'message' => 'Sin conexión a base de datos']);
        exit;
    }

    try {
        if ($type === 'student') {
            $stmt = $pdo->prepare("
                SELECT u.id, u.correo,
                       p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                       p.tipo_documento, p.documento_identidad, p.telefono, p.direccion,
                       p.acudiente_nombre, p.acudiente_telefono, p.acudiente_correo,
                       COALESCE(c.grado, '11°') AS grado,
                       COALESCE(c.grupo, 'A') AS grupo,
                       COALESCE(m.ano_lectivo, 2026) AS ano_lectivo,
                       COALESCE(m.codigo_matricula, CONCAT('EST-2026-', LPAD(u.id, 5, '0'))) AS codigo_matricula
                FROM usuarios u
                INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
                LEFT JOIN cursos c ON m.curso_id = c.id
                WHERE u.id = :id AND u.rol_id = 5
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['status' => 'error', 'message' => 'Estudiante no encontrado']);
                exit;
            }

            $nomComp = trim("{$row['primer_nombre']} {$row['segundo_nombre']} {$row['primer_apellido']} {$row['segundo_apellido']}");
            $row['nombre_completo'] = $nomComp;
            $row['lugar_exp'] = 'Ciénaga, Magdalena';
            $numGrado = intval($row['grado']);
            $row['nivel'] = ($numGrado >= 10) ? 'EDUCACIÓN MEDIA' : (($numGrado >= 6) ? 'EDUCACIÓN BÁSICA SECUNDARIA' : (stripos($row['grado'], 'trans') !== false ? 'EDUCACIÓN PREESCOLAR' : 'EDUCACIÓN BÁSICA PRIMARIA'));
            $row['jornada'] = 'Mañana';
            $row['ih'] = ($numGrado >= 10) ? 35 : 30;

            echo json_encode(['status' => 'success', 'data' => $row]);
            exit;

        } elseif ($type === 'teacher') {
            $stmt = $pdo->prepare("
                SELECT u.id, u.correo,
                       p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                       p.tipo_documento, p.documento_identidad, p.telefono, p.direccion
                FROM usuarios u
                INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                WHERE u.id = :id AND u.rol_id = 3
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['status' => 'error', 'message' => 'Docente no encontrado']);
                exit;
            }

            $nomComp = trim("{$row['primer_nombre']} {$row['segundo_nombre']} {$row['primer_apellido']} {$row['segundo_apellido']}");
            $row['nombre_completo'] = $nomComp;
            $row['lugar_exp'] = 'Ciénaga, Magdalena';
            $row['cargo'] = 'DOCENTE DE BÁSICA Y MEDIA';
            $row['tipo_contrato'] = 'PRESTACIÓN DE SERVICIOS';
            $row['salario'] = '$3.500.000 MENSUALES';
            $row['tiempo_laborado'] = '01 de Febrero del 2024 a la fecha actual';
            $row['titulo_profesional'] = 'LICENCIADO(A) EN EDUCACIÓN';

            echo json_encode(['status' => 'success', 'data' => $row]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// =========================================================================
// ENDPOINT: Generar y Descargar Documento Word (.docx)
// =========================================================================
if ($action === 'download_docx') {
    $docType = $_REQUEST['doc_type'] ?? 'constancia';
    $data = $_REQUEST;

    // Normalizar mes en español
    $mesesEs = [
        '1' => 'Enero', '2' => 'Febrero', '3' => 'Marzo', '4' => 'Abril',
        '5' => 'Mayo', '6' => 'Junio', '7' => 'Julio', '8' => 'Agosto',
        '9' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];
    if (empty($data['dia'])) $data['dia'] = date('d');
    if (empty($data['mes'])) $data['mes'] = $mesesEs[date('n')];
    if (empty($data['ano'])) $data['ano'] = date('Y');

    $tempFile = tempnam(sys_get_temp_dir(), 'salle_cert_') . '.docx';

    $ok = generateCertificateDocxFile($docType, $data, $tempFile);

    if ($ok && file_exists($tempFile)) {
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['nombre_completo'] ?? $data['docente_nombre'] ?? 'Documento');
        $fileName = "Certificado_{$docType}_{$cleanName}.docx";

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
        die("Error al generar el archivo Word.");
    }
}

// =========================================================================
// GENERADOR OPENXML COMPLETO Y ROBUSTO
// =========================================================================
function generateCertificateDocxFile($docType, $data, $outputPath) {
    $zip = new ZipArchive();
    if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return false;
    }

    // Content Types
    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>';
    $zip->addFromString('[Content_Types].xml', $contentTypes);

    // Relationships
    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
    $zip->addFromString('_rels/.rels', $rels);

    // Styles
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri"/>
        <w:sz w:val="22"/>
        <w:szCs w:val="22"/>
        <w:lang w:val="es-CO"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
</w:styles>';
    $zip->addFromString('word/styles.xml', $styles);

    $esc = function($v) {
        return htmlspecialchars($v ?? '', ENT_XML1, 'UTF-8');
    };

    $p = function($text, $align = 'left', $bold = false, $size = 22, $spaceAfter = 140) use ($esc) {
        $jc = in_array($align, ['center', 'right', 'both']) ? "<w:jc w:val=\"$align\"/>" : "";
        $b = $bold ? "<w:b/>" : "";
        return "<w:p><w:pPr>$jc<w:spacing w:after=\"$spaceAfter\" w:line=\"280\" w:lineRule=\"auto\"/></w:pPr><w:r><w:rPr>$b<w:sz w:val=\"$size\"/></w:rPr><w:t>" . $esc($text) . "</w:t></w:r></w:p>";
    };

    // Membrete Oficial La Salle
    $headerBlock = 
        $p("INSTITUTO LA SALLE", "center", true, 30, 40) .
        $p("CIÉNAGA – MAGDALENA", "center", true, 24, 40) .
        $p("“Dios, Ciencia y Virtud”", "center", false, 20, 40) .
        $p("Aprobación Oficial Secretaría de Educación del Magdalena | DANE: 147189000123 | NIT: 891.780.098-1", "center", false, 16, 260);

    // Firma Rectora Cherlys Domínguez
    $signatureBlock = 
        "<w:p><w:pPr><w:jc w:val=\"center\"/><w:spacing w:before=\"380\" w:after=\"40\"/></w:pPr><w:r><w:t>_____________________________________________</w:t></w:r></w:p>" .
        $p("CHERLYS DOMÍNGUEZ R.", "center", true, 22, 40) .
        $p("C.C. # 39.033.967 de Ciénaga", "center", false, 20, 40) .
        $p("Rectora", "center", true, 20, 80);

    $bodyXml = "";

    switch ($docType) {
        case 'estudio':
            $bodyXml .= $p("CERTIFICADO DE ESTUDIO", "center", true, 26, 180);
            $bodyXml .= $p("LA RECTORA Y SECRETARIO(A) DEL INSTITUTO LA SALLE CERTIFICAN:", "center", true, 22, 180);
            
            $txt = "Que el alumno (a) " . ($data['nombre_completo'] ?? 'ESTUDIANTE') . ", identificado (a) con " . ($data['tipo_doc'] ?? 'T.I') . " No. " . ($data['numero_doc'] ?? '00000000') . " expedida en " . ($data['lugar_exp'] ?? 'Ciénaga, Magdalena') . ", cursó y aprobó en este Plantel el grado " . ($data['grado'] ?? '11°') . " (" . ($data['grupo'] ?? 'A') . ") de " . ($data['nivel'] ?? 'EDUCACIÓN MEDIA') . " asistiendo puntualmente durante el año lectivo " . ($data['ano_lectivo'] ?? '2026') . " y obtuvo en las diferentes áreas del conocimiento las siguientes valoraciones conceptuales, según lo establecido en la Ley 115 de 1994, Decreto 230 de 2002 y Decreto 1290 de 2009:";
            $bodyXml .= $p($txt, "both", false, 22, 180);

            $materias = [];
            if (!empty($data['materias_json'])) {
                $decoded = json_decode($data['materias_json'], true);
                if (is_array($decoded)) $materias = $decoded;
            }

            if (!empty($materias)) {
                $bodyXml .= '<w:tbl><w:tblPr><w:tblW w:w="9500" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:left w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:right w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="E5E5E5"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="E5E5E5"/></w:tblBorders></w:tblPr>';
                $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="4500" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="left"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>Área / Asignatura</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1200" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>I.H</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>Calificación</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="2000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>Desempeño</w:t></w:r></w:p></w:tc></w:tr>';

                $aprobadas = 0;
                $reprobadas = 0;
                foreach ($materias as $m) {
                    $notaNum = floatval($m['nota'] ?? 0);
                    if ($notaNum >= 3.0 || $notaNum >= 6.0) $aprobadas++; else $reprobadas++;
                    $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="4500" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>' . $esc($m['nombre'] ?? '') . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1200" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>' . $esc($m['ih'] ?? '2') . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="1800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr><w:t>' . $esc($m['nota'] ?? '') . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="2000" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr><w:t>' . $esc($m['desempeno'] ?? '') . '</w:t></w:r></w:p></w:tc></w:tr>';
                }
                $bodyXml .= '</w:tbl>';

                $concepto = ($reprobadas === 0) ? 'APROBÓ' : 'PENDIENTE';
                $bodyXml .= $p("Convivencia: Demostró óptimas habilidades sociales de convivencia al trabajar en equipo con sus compañeros, acatando el manual de convivencia institucional.", "both", false, 20, 80);
                $bodyXml .= $p("Aprobadas: {$aprobadas}, No aprobadas: {$reprobadas} | CONCEPTO COMISIÓN EVALUACIÓN: {$concepto}", "center", true, 20, 80);
            } else {
                $bodyXml .= $p("⚠️ NOTA OFICIAL: El estudiante no registra calificaciones cargadas en el sistema para certificar en este periodo académico.", "center", true, 22, 160);
                $bodyXml .= $p("Aprobadas: 0, No aprobadas: 0 | CONCEPTO: SIN CALIFICACIONES REGISTRADAS", "center", false, 20, 80);
            }
            $bodyXml .= $p("Escala: 1.0 - 5.9 BAJO | 6.0 - 7.9 BÁSICO | 8.0 - 9.5 ALTO | 9.6 - 10.0 SUPERIOR", "center", false, 16, 140);
            break;

        case 'constancia':
            $bodyXml .= $p("CONSTANCIA DE ESTUDIOS", "center", true, 26, 180);
            $bodyXml .= $p("LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA", "center", true, 22, 100);
            $bodyXml .= $p("HACE CONSTAR", "center", true, 22, 180);

            $acudienteTxt = !empty($data['acudiente_nombre']) 
                ? "Que el Señor (a) " . ($data['acudiente_nombre']) . " identificado con C.C. No. " . ($data['acudiente_doc'] ?? '________________') . " de " . ($data['acudiente_exp'] ?? 'Ciénaga, Magdalena') . " se encuentra registrado(a) ante esta Institución Educativa como ACUDIENTE del (a) alumno (a) " . ($data['nombre_completo'] ?? 'ESTUDIANTE') . ", identificado (a) con " . ($data['tipo_doc'] ?? 'T.I') . " N.º " . ($data['numero_doc'] ?? '') . " de " . ($data['lugar_exp'] ?? 'Ciénaga, Magdalena') . ". "
                : "Que el (a) alumno (a) " . ($data['nombre_completo'] ?? 'ESTUDIANTE') . ", identificado (a) con " . ($data['tipo_doc'] ?? 'T.I') . " N.º " . ($data['numero_doc'] ?? '') . " de " . ($data['lugar_exp'] ?? 'Ciénaga, Magdalena') . ". ";

            $constTxt = $acudienteTxt . "Quien se encuentra debidamente MATRICULADO(A) en esta Institución cursando el grado " . ($data['grado'] ?? '11°') . " (" . ($data['grupo'] ?? 'A') . ") de " . ($data['nivel'] ?? 'EDUCACIÓN FORMAL') . ", Jornada " . ($data['jornada'] ?? 'Mañana') . ", para el presente año escolar " . ($data['ano_lectivo'] ?? '2026') . ", con una intensidad horaria de (" . ($data['ih'] ?? '30') . ") horas semanales y asiste puntualmente a clases hasta la fecha.";
            $bodyXml .= $p($constTxt, "both", false, 24, 220);

            $bodyXml .= $p("Dado en Ciénaga, Magdalena a los (" . ($data['dia'] ?? date('d')) . ") días del mes de " . ($data['mes'] ?? 'Septiembre') . " del año " . ($data['ano'] ?? '2026') . ".", "left", false, 22, 180);
            break;

        case 'conducta':
            $bodyXml .= $p("CERTIFICADO DE BUENA CONDUCTA", "center", true, 26, 180);
            $bodyXml .= $p("LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA", "center", true, 22, 100);
            $bodyXml .= $p("HACE CONSTAR", "center", true, 22, 180);

            $condTxt = "Que el (a) alumno (a) " . ($data['nombre_completo'] ?? 'ESTUDIANTE') . " identificado (a) con " . ($data['tipo_doc'] ?? 'T.I') . " N.º " . ($data['numero_doc'] ?? '') . " de " . ($data['lugar_exp'] ?? 'Ciénaga, Magdalena') . ", cursó / cursa el grado " . ($data['grado'] ?? '11°') . " (" . ($data['grupo'] ?? 'A') . ") de " . ($data['nivel'] ?? 'EDUCACIÓN FORMAL') . " en el presente año escolar " . ($data['ano_lectivo'] ?? '2026') . ", presentando una BUENA CONDUCTA, excelente disciplina y cumpliendo a cabalidad con las normas, valores y deberes requeridos por el Manual de Convivencia Institucional.";
            $bodyXml .= $p($condTxt, "both", false, 24, 220);

            $bodyXml .= $p("Dado en Ciénaga, Magdalena a los (" . ($data['dia'] ?? date('d')) . ") días del mes de " . ($data['mes'] ?? 'Septiembre') . " del año " . ($data['ano'] ?? '2026') . ".", "left", false, 22, 180);
            break;

        case 'costos':
            $bodyXml .= $p("CERTIFICACIÓN DE COSTOS EDUCATIVOS", "center", true, 26, 180);
            $bodyXml .= $p("LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA", "center", true, 22, 100);
            $bodyXml .= $p("HACE CONSTAR", "center", true, 22, 180);

            $costTxt = "Los costos educativos correspondientes a la vigencia del año escolar " . ($data['ano_lectivo'] ?? '2026') . " del (a) Estudiante " . ($data['nombre_completo'] ?? 'ESTUDIANTE') . " identificado (a) con " . ($data['tipo_doc'] ?? 'T.I') . " N.º " . ($data['numero_doc'] ?? '') . " de " . ($data['lugar_exp'] ?? 'Ciénaga, Magdalena') . ", matriculado (a) en el grado " . ($data['grado'] ?? '11°') . " (" . ($data['grupo'] ?? 'A') . ") de " . ($data['nivel'] ?? 'EDUCACIÓN FORMAL') . ", Jornada " . ($data['jornada'] ?? 'Mañana') . ":";
            $bodyXml .= $p($costTxt, "both", false, 22, 180);

            $bodyXml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:left w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:right w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="E5E5E5"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="E5E5E5"/></w:tblBorders></w:tblPr>';
            $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="5500" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="20"/></w:rPr><w:t>Concepto</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="3500" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="20"/></w:rPr><w:t>Valor Unitario</w:t></w:r></w:p></w:tc></w:tr>';
            
            $valMat = $data['costo_matricula'] ?? '$170.000 COP';
            $valPen = $data['costo_pension'] ?? '$170.000 COP';
            $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="5500" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>MATRÍCULA ANUAL 2026</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="3500" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>' . $esc($valMat) . '</w:t></w:r></w:p></w:tc></w:tr>';
            
            $meses = ['FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE'];
            foreach ($meses as $m) {
                $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="5500" w:type="dxa"/></w:tcPr><w:p><w:r><w:t>PENSIÓN ' . $m . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="3500" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:t>' . $esc($valPen) . '</w:t></w:r></w:p></w:tc></w:tr>';
            }
            
            $valTot = $data['costo_total'] ?? '$1.870.000 COP';
            $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="5500" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="F1F5F9"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>VALOR TOTAL ANUAL</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="3500" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="F1F5F9"/></w:tcPr><w:p><w:pPr><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>' . $esc($valTot) . '</w:t></w:r></w:p></w:tc></w:tr>';
            $bodyXml .= '</w:tbl>';

            $bodyXml .= $p("Dado en Ciénaga, Magdalena a los (" . ($data['dia'] ?? date('d')) . ") días del mes de " . ($data['mes'] ?? 'Septiembre') . " del año " . ($data['ano'] ?? '2026') . ".", "left", false, 22, 180);
            break;

        case 'paz_salvo':
            $bodyXml .= $p("CERTIFICADO DE PAZ Y SALVO", "center", true, 26, 180);
            $bodyXml .= $p("LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA", "center", true, 22, 100);
            $bodyXml .= $p("HACE CONSTAR", "center", true, 22, 180);

            $pazTxt = "Que el (a) alumno (a) " . ($data['nombre_completo'] ?? 'ESTUDIANTE') . " Identificado (a) con " . ($data['tipo_doc'] ?? 'T.I') . " N.º " . ($data['numero_doc'] ?? '') . " expedida en " . ($data['lugar_exp'] ?? 'Ciénaga, Magdalena') . ", que cursó y aprobó el grado " . ($data['grado'] ?? '11°') . " (" . ($data['grupo'] ?? 'A') . ") de " . ($data['nivel'] ?? 'EDUCACIÓN FORMAL') . " en el año escolar " . ($data['ano_lectivo'] ?? '2026') . ", SE ENCUENTRA A PAZ Y SALVO CON LA INSTITUCIÓN POR TODO CONCEPTO ACADÉMICO, DISCIPLINARIO Y FINANCIERO.";
            $bodyXml .= $p($pazTxt, "both", false, 24, 220);

            $bodyXml .= $p("Dado en Ciénaga, Magdalena a los (" . ($data['dia'] ?? date('d')) . ") días del mes de " . ($data['mes'] ?? 'Septiembre') . " del año " . ($data['ano'] ?? '2026') . ".", "left", false, 22, 180);
            break;

        case 'laboral':
            $bodyXml .= $p("CERTIFICADO LABORAL DOCENTE", "center", true, 26, 180);
            $bodyXml .= $p("LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA", "center", true, 22, 100);
            $bodyXml .= $p("CERTIFICA", "center", true, 22, 180);

            $labTxt = "Que el (a) señor (a) " . ($data['docente_nombre'] ?? 'DOCENTE') . ", identificado (a) con Cédula de Ciudadanía No. " . ($data['docente_doc'] ?? '') . " expedida en " . ($data['docente_exp'] ?? 'Ciénaga, Magdalena') . ", presta sus servicios profesionales en esta institución bajo las siguientes especificaciones laborales:";
            $bodyXml .= $p($labTxt, "both", false, 22, 160);

            $bodyXml .= $p("• TÍTULO PROFESIONAL: " . ($data['docente_titulo'] ?? 'LICENCIADO EN EDUCACIÓN'), "left", true, 22, 60);
            $bodyXml .= $p("• CARGO: " . ($data['docente_cargo'] ?? 'DOCENTE DE BÁSICA Y MEDIA'), "left", true, 22, 60);
            $bodyXml .= $p("• TIPO DE CONTRATO: " . ($data['docente_contrato'] ?? 'PRESTACIÓN DE SERVICIOS'), "left", true, 22, 60);
            $bodyXml .= $p("• ASIGNACIÓN SALARIAL: " . ($data['docente_salario'] ?? '$3.500.000 MENSUALES'), "left", true, 22, 60);
            $bodyXml .= $p("• TIEMPO LABORADO: " . ($data['docente_tiempo'] ?? 'Desde 01 de Febrero del 2024 a la fecha actual'), "left", true, 22, 160);

            $bodyXml .= $p("La persona antes referenciada se desempeña en forma eficiente, responsable, con alto sentido de pertenencia y mantiene magníficas relaciones interpersonales con toda la comunidad educativa salle.", "both", false, 22, 180);

            $bodyXml .= $p("Se expide la presente certificación a los (" . ($data['dia'] ?? date('d')) . ") días del mes de " . ($data['mes'] ?? 'Septiembre') . " del año " . ($data['ano'] ?? '2026') . ".", "left", false, 22, 180);
            break;

        case 'observador':
            $bodyXml .= $p("OBSERVADOR INTEGRAL DEL ESTUDIANTE - RECTORÍA", "center", true, 24, 140);
            
            $obsHeader = "Estudiante: " . ($data['nombre_completo'] ?? 'ESTUDIANTE') . " | Grado: " . ($data['grado'] ?? '11°') . " " . ($data['grupo'] ?? 'A') . " | Teléfono: " . ($data['telefono'] ?? 'N/A') . " | Jornada: " . ($data['jornada'] ?? 'Mañana');
            $bodyXml .= $p($obsHeader, "center", true, 20, 80);

            $obsFicha = "Enfermedades: " . ($data['enfermedades'] ?? 'NINGUNA') . " | Medicamento: " . ($data['medicamentos'] ?? 'NINGUNO') . " | Diagnóstico Clínico: " . ($data['diagnostico'] ?? 'NINGUNO');
            $bodyXml .= $p($obsFicha, "center", false, 18, 140);

            $bodyXml .= '<w:tbl><w:tblPr><w:tblW w:w="9200" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:left w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:right w:val="single" w:sz="4" w:space="0" w:color="CCCCCC"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="E5E5E5"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="E5E5E5"/></w:tblBorders></w:tblPr>';
            $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="800" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>No</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>Aspectos Evaluados (Convivencia y Académico)</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>P1</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>P2</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>P3</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/><w:shd w:val="clear" w:color="auto" w:fill="0B3C68"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="FFFFFF"/><w:sz w:val="18"/></w:rPr><w:t>P4</w:t></w:r></w:p></w:tc></w:tr>';

            $aspectos = [
                '1. Participa con frecuencia en las actividades programadas en la institución.',
                '2. Es respetuoso con sus compañeros y superiores.',
                '3. Sabe valorarse y valora a los demás.',
                '4. Muestra buena presentación personal y porte del uniforme.',
                '5. Evita involucrarse en brotes de indisciplina.',
                '6. Asiste con puntualidad a todas sus clases.',
                '7. Asiste con muy buena regularidad a la institución.',
                '8. Ha logrado cambios importantes en su actuar y responsabilidad.',
                '9. Sus padres asisten cumplidamente a los llamados institucionales.',
                '10. Solicita orientación adecuada a sus docentes o coordinadores.',
                '11. Cuida sus materiales y los enseres puestos al servicio del colegio.',
                '12. Su comportamiento se ajusta estrictamente al Manual de Convivencia.',
                '13. Su convivencia social y grupal es plenamente satisfactoria.',
                '14. Responde por sus tareas, talleres y compromisos académicos.',
                '15. Se interesa en el estudio y dedica tiempo de calidad a sus labores.',
                '16. Se integra constructivamente y colabora de forma solidaria.',
                '17. Realiza trabajos y proyectos de investigación con dedicación.',
                '18. Mantiene en óptimo orden y aseo su puesto de estudio.',
                '19. Cumple los compromisos pedagógicos adquiridos con el plantel.',
                '20. Asume una actitud reflexiva y propositiva ante las observaciones.'
            ];

            foreach ($aspectos as $idx => $asp) {
                $num = $idx + 1;
                $bodyXml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>' . $num . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="6000" w:type="dxa"/></w:tcPr><w:p><w:r><w:rPr><w:sz w:val="18"/></w:rPr><w:t>' . $esc($asp) . '</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr><w:t>S</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr><w:t>S</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr><w:t>S</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="600" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr><w:t>S</w:t></w:r></w:p></w:tc></w:tr>';
            }
            $bodyXml .= '</w:tbl>';
            $bodyXml .= $p("Convenciones: S = Siempre | N = No | O = Ocasionalmente", "center", false, 16, 120);
            break;
    }

    $bodyXml .= $signatureBlock;

    $fullDocumentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>' . 
    $headerBlock . 
    $bodyXml . 
    '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="900" w:right="1100" w:bottom="900" w:left="1100"/></w:sectPr>
  </w:body>
</w:document>';

    $zip->addFromString('word/document.xml', $fullDocumentXml);
    $zip->close();
    return true;
}
