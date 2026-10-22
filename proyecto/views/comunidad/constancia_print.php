<?php
/**
 * FORMATO OFICIAL IMPRIMIBLE: CONSTANCIA DE ESTUDIO
 * Instituto La Salle Ciénaga Magdalena
 * Acreditado conforme a la Ley 115 de 1994 y Decreto 1075 de 2015 del MEN
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';

// Inicializar sesión segura
Router::initSession();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "/views/auth/login.php");
    exit();
}

$pdo = Database::getInstance()->getConnection();

$currentUserRole = strtolower($_SESSION['user_role'] ?? 'estudiante');
$currentUserId   = intval($_SESSION['user_id'] ?? 0);

$estudianteId = intval($_GET['estudiante_id'] ?? 0);
$reciboId     = intval($_GET['recibo_id'] ?? 0);

// Si es estudiante, solo puede consultar su propia constancia
if ($currentUserRole === 'estudiante') {
    $estudianteId = $currentUserId;
} elseif ($estudianteId === 0) {
    $estudianteId = $currentUserId;
}

// Consultar datos completos del estudiante
$stmtEst = $pdo->prepare("
    SELECT u.id, u.correo, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
           p.tipo_documento, p.documento_identidad, p.telefono, p.direccion,
           p.acudiente_nombre, c.grado, c.grupo, m.codigo_matricula, m.ano_lectivo
    FROM usuarios u
    INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
    LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
    LEFT JOIN cursos c ON m.curso_id = c.id
    WHERE u.id = :uid
    LIMIT 1
");
$stmtEst->execute([':uid' => $estudianteId]);
$student = $stmtEst->fetch();

if (!$student) {
    die("No se encontró la información del estudiante solicitado.");
}

// Consultar estado de pago de la constancia en Tesorería / Caja
$pagoConstancia = null;
if ($reciboId > 0) {
    $stmtP = $pdo->prepare("SELECT * FROM contabilidad_caja WHERE id = :rid AND usuario_id = :uid AND concepto_pago = 'Constancia' LIMIT 1");
    $stmtP->execute([':rid' => $reciboId, ':uid' => $estudianteId]);
    $pagoConstancia = $stmtP->fetch();
}

if (!$pagoConstancia) {
    $stmtP = $pdo->prepare("SELECT * FROM contabilidad_caja WHERE usuario_id = :uid AND concepto_pago = 'Constancia' ORDER BY id DESC LIMIT 1");
    $stmtP->execute([':uid' => $estudianteId]);
    $pagoConstancia = $stmtP->fetch();
}

// CONDICIÓN ESTRICTA: La constancia solo está disponible cuando en Tesorería digan que ya la pagó
$estaPagada = ($pagoConstancia && $pagoConstancia['estado_pago'] === 'Pagado');
$reciboNum  = $pagoConstancia['recibo_numero'] ?? ('CONST-' . date('Y') . '-' . str_pad($student['id'], 4, '0', STR_PAD_LEFT));
$fechaPago  = !empty($pagoConstancia['fecha_pago']) ? date('d/m/Y H:i', strtotime($pagoConstancia['fecha_pago'])) : date('d/m/Y');

$nombreCompleto = strtoupper(trim(
    $student['primer_nombre'] . ' ' . 
    ($student['segundo_nombre'] ? $student['segundo_nombre'] . ' ' : '') . 
    $student['primer_apellido'] . ' ' . 
    ($student['segundo_apellido'] ?? '')
));

$tipoDoc = $student['tipo_documento'] ?: 'T.I.';
$numDoc  = $student['documento_identidad'] ?: 'N/D';
$grado   = strtoupper($student['grado'] ?: 'DÉCIMO');
$grupo   = strtoupper($student['grupo'] ?: 'A');
$codMat  = $student['codigo_matricula'] ?: ('EST-2026-' . str_pad($student['id'], 5, '0', STR_PAD_LEFT));

$meses = [
    '01'=>'enero','02'=>'febrero','03'=>'marzo','04'=>'abril',
    '05'=>'mayo','06'=>'junio','07'=>'julio','08'=>'agosto',
    '09'=>'septiembre','10'=>'octubre','11'=>'noviembre','12'=>'diciembre'
];

$diaNum = date('d');
$mesNom = $meses[date('m')];
$anoNum = date('Y');

// URL de validación y QR
$validationUrl = APP_URL . "/views/comunidad/constancia_print.php?estudiante_id={$estudianteId}&recibo_id=" . ($pagoConstancia['id'] ?? 0);
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&margin=2&data=" . urlencode($validationUrl);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constancia de Estudio Oficial - <?= htmlspecialchars($nombreCompleto) ?> - Instituto La Salle</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --lasalle-navy: #0F172A;
            --lasalle-blue: #1E3A8A;
            --lasalle-blue-light: #2563EB;
            --lasalle-gold: #D4AF37;
            --lasalle-gold-dark: #B89628;
            --lasalle-gold-soft: #FEF3C7;
            --text-main: #1E293B;
        }

        * { 
            box-sizing: border-box; 
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Lora', Georgia, serif;
            background: #e2e8f0;
            color: var(--text-main);
            font-size: 11.5pt;
            line-height: 1.75;
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Barra de Control No Imprimible */
        .no-print-bar {
            width: 216mm;
            max-width: 100%;
            margin-bottom: 15px;
            background: #0F172A;
            color: #FFFFFF;
            padding: 12px 20px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-family: 'Inter', sans-serif;
        }

        .bar-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 9pt;
        }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 8.5pt;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.5px;
        }

        .badge-paid {
            background: #10B981;
            color: #FFFFFF;
        }

        .badge-unpaid {
            background: #EF4444;
            color: #FFFFFF;
        }

        .bar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            background: var(--lasalle-blue);
            color: #FFFFFF;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 9pt;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .btn-action:hover {
            background: #2563EB;
            transform: translateY(-1px);
        }

        .btn-action.btn-gold {
            background: linear-gradient(135deg, #D4AF37, #B89628);
            color: #0F172A;
            border: none;
            font-weight: 700;
        }

        .btn-action.btn-gold:hover {
            background: linear-gradient(135deg, #E5C158, #C5A030);
        }

        .btn-action.btn-dark {
            background: #334155;
        }

        .btn-action.btn-dark:hover {
            background: #475569;
        }

        /* Contenedor Hoja Tamaño Carta */
        .paper-sheet {
            background: #FFFFFF;
            width: 216mm;
            min-height: 279mm;
            padding: 16mm 20mm 15mm 20mm;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 2px solid #D4AF37;
            outline: 6px solid #1E3A8A;
            outline-offset: -10px;
        }

        /* Marca de agua institucional sutil */
        .watermark-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            height: 320px;
            opacity: 0.04;
            pointer-events: none;
            z-index: 1;
            background: url('<?= ASSETS_URL ?>/assets/logo.svg') no-repeat center center;
            background-size: contain;
        }

        .content-wrap {
            position: relative;
            z-index: 2;
        }

        /* Encabezado */
        .header-box {
            border-bottom: 2px solid #1E3A8A;
            padding-bottom: 12px;
            margin-bottom: 20px;
            position: relative;
        }

        .header-box::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -5px;
            width: 100%;
            height: 1.5px;
            background: #D4AF37;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-logo {
            width: 80px;
            text-align: left;
            vertical-align: middle;
        }

        .header-logo img {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }

        .header-center {
            text-align: center;
            vertical-align: middle;
            padding: 0 10px;
        }

        .country-title {
            font-family: 'Inter', sans-serif;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 2px;
            color: #64748B;
            text-transform: uppercase;
        }

        .inst-title {
            font-family: 'Cinzel', serif;
            font-size: 19pt;
            font-weight: 700;
            color: #1E3A8A;
            letter-spacing: 1.5px;
            margin: 2px 0;
        }

        .inst-motto {
            font-size: 10pt;
            font-style: italic;
            font-weight: 700;
            color: #D4AF37;
            margin-bottom: 3px;
        }

        .inst-meta {
            font-family: 'Inter', sans-serif;
            font-size: 8pt;
            color: #475569;
            line-height: 1.35;
        }

        .header-folio {
            width: 105px;
            text-align: right;
            vertical-align: middle;
        }

        .folio-card {
            border: 1.5px solid #1E3A8A;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: center;
            background: #F8FAFC;
        }

        .folio-tag {
            font-family: 'Inter', sans-serif;
            font-size: 7pt;
            font-weight: 700;
            color: #1E3A8A;
            text-transform: uppercase;
            display: block;
        }

        .folio-num {
            font-family: 'Courier New', monospace;
            font-size: 8pt;
            font-weight: 700;
            color: #0F172A;
            display: block;
            margin-top: 2px;
        }

        .folio-badge {
            display: inline-block;
            background: #DCFCE7;
            color: #166534;
            font-size: 6.5pt;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 3px;
        }

        /* Título del Documento */
        .doc-title-container {
            text-align: center;
            margin: 22px 0 18px 0;
        }

        .doc-title {
            font-family: 'Cinzel', serif;
            font-size: 16pt;
            font-weight: 700;
            color: #1E3A8A;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            display: inline-block;
            border-bottom: 2px solid #D4AF37;
            padding-bottom: 4px;
        }

        /* Cuerpo de la Certificación */
        .doc-intro {
            text-align: center;
            font-weight: 700;
            font-size: 11pt;
            color: #0F172A;
            line-height: 1.5;
            margin-bottom: 16px;
            font-family: 'Cinzel', serif;
            letter-spacing: 0.8px;
        }

        .hacen-constar {
            text-align: center;
            font-size: 12.5pt;
            font-weight: 700;
            letter-spacing: 3px;
            color: #1E3A8A;
            margin: 14px 0 16px 0;
            font-family: 'Cinzel', serif;
        }

        .doc-body {
            text-align: justify;
            text-justify: inter-word;
            font-size: 11.5pt;
            line-height: 1.8;
            color: #1E293B;
        }

        .doc-body p {
            margin-bottom: 16px;
            text-indent: 32px;
        }

        .highlight-bold {
            font-weight: 700;
            color: #0F172A;
        }

        .student-highlight {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 12pt;
            letter-spacing: 0.5px;
            color: #1E3A8A;
            text-decoration: underline;
            text-decoration-color: #D4AF37;
        }

        /* Sellos y Firmas */
        .signatures-section {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            position: relative;
        }

        .signature-col {
            width: 42%;
            text-align: center;
        }

        .signature-cursive {
            font-family: 'Brush Script MT', 'Dancing Script', cursive;
            font-size: 26pt;
            color: #1E3A8A;
            height: 48px;
            line-height: 48px;
            user-select: none;
        }

        .signature-line {
            border-top: 1.5px solid #0F172A;
            margin-top: 5px;
            padding-top: 6px;
            font-family: 'Inter', sans-serif;
        }

        .sign-name {
            font-size: 9.5pt;
            font-weight: 700;
            color: #0F172A;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .sign-cargo {
            font-size: 8.5pt;
            color: #475569;
            font-weight: 600;
        }

        .sign-doc {
            font-size: 7.5pt;
            color: #64748B;
        }

        /* Sello Institucional */
        .seal-col {
            width: 16%;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .official-seal {
            width: 78px;
            height: 78px;
            border: 2px dashed #D4AF37;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #B89628;
            font-family: 'Cinzel', serif;
            font-size: 6.5pt;
            font-weight: 700;
            text-align: center;
            transform: rotate(-10deg);
            padding: 4px;
            background: rgba(254, 243, 199, 0.2);
        }

        .official-seal i {
            font-size: 16pt;
            margin-bottom: 2px;
            color: #1E3A8A;
        }

        /* Pie de Página Institucional */
        .footer-box {
            margin-top: 25px;
            border-top: 1px solid #CBD5E1;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: 'Inter', sans-serif;
            font-size: 7.5pt;
            color: #64748B;
        }

        .footer-qr {
            width: 60px;
            height: 60px;
        }

        .footer-qr img {
            width: 60px;
            height: 60px;
            border: 1px solid #E2E8F0;
            border-radius: 4px;
            padding: 2px;
            background: #FFFFFF;
        }

        .footer-text {
            flex: 1;
            padding-left: 12px;
            line-height: 1.4;
        }

        /* Pantalla de Bloqueo por Falta de Pago en Tesorería */
        .blocked-card {
            background: #FFFFFF;
            width: 180mm;
            max-width: 95%;
            margin: 40px auto;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 6px solid #EF4444;
            font-family: 'Inter', sans-serif;
        }

        .blocked-icon {
            width: 75px;
            height: 75px;
            background: #FEE2E2;
            color: #DC2626;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32pt;
            margin: 0 auto 20px auto;
        }

        .blocked-title {
            font-size: 18pt;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 12px;
        }

        .blocked-desc {
            font-size: 10pt;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .blocked-info-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 25px;
            text-align: left;
            font-size: 9pt;
        }

        @media print {
            body {
                background: #FFFFFF !important;
                padding: 0 !important;
            }
            .no-print-bar {
                display: none !important;
            }
            .paper-sheet {
                box-shadow: none !important;
                width: 100% !important;
                min-height: 100% !important;
                margin: 0 !important;
                padding: 12mm 15mm !important;
                border: 2px solid #D4AF37 !important;
                outline: 5px solid #1E3A8A !important;
                outline-offset: -8px !important;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            @page {
                size: letter;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

<?php if (!$estaPagada): ?>
    <!-- =======================================================
         PANTALLA DE RESTRICCIÓN: PAGO PENDIENTE EN TESORERÍA
         ======================================================= -->
    <div class="no-print-bar">
        <div class="bar-info">
            <span class="badge-status badge-unpaid">
                <i class="fas fa-lock"></i> DOCUMENTO BLOQUEADO
            </span>
            <span>Requiere confirmación de pago en Tesorería Institucional</span>
        </div>
        <div class="bar-actions">
            <a href="<?= APP_URL ?>/views/comunidad/index.php" class="btn-action btn-dark">
                <i class="fas fa-arrow-left"></i> Volver al Portal
            </a>
        </div>
    </div>

    <div class="blocked-card">
        <div class="blocked-icon">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <h2 class="blocked-title">Constancia Retenida por Tesorería</h2>
        <p class="blocked-desc">
            Para descargar o imprimir la <strong>Constancia Oficial de Estudio</strong> con la firma del Rector y validez ante el MEN, es indispensable que el pago de los derechos institucionales esté registrado y confirmado como <strong>PAGADO</strong> por la ventanilla de Tesorería o Caja.
        </p>

        <div class="blocked-info-box">
            <div style="margin-bottom: 8px;">
                <strong>Estudiante:</strong> <?= htmlspecialchars($nombreCompleto) ?> (<?= htmlspecialchars($tipoDoc) ?> <?= htmlspecialchars($numDoc) ?>)
            </div>
            <div style="margin-bottom: 8px;">
                <strong>Concepto:</strong> Constancia / Certificado de Estudio 2026
            </div>
            <div style="margin-bottom: 8px;">
                <strong>Tarifa Institucional:</strong> <span style="color: #DC2626; font-weight: 700;">$15.000 COP</span>
            </div>
            <div>
                <strong>Estado Actual en Caja:</strong> 
                <span style="color: #DC2626; font-weight: 700;">
                    <i class="fas fa-clock me-1"></i> Pendiente de Pago
                </span>
            </div>
        </div>

        <p style="font-size: 9pt; color: #64748B; margin-bottom: 25px;">
            <i class="fas fa-info-circle text-primary me-1"></i> 
            Si ya realizó el pago en efectivo o consignación, acérquese a la ventanilla de Tesorería para que el cajero valide su recibo y habilite inmediatamente la descarga en esta pantalla.
        </p>

        <a href="<?= APP_URL ?>/views/comunidad/index.php#constancia" class="btn-action btn-gold" style="padding: 10px 24px; font-size: 10pt;">
            <i class="fas fa-arrow-left me-1"></i> Regresar al Módulo del Estudiante
        </a>
    </div>

<?php else: ?>
    <!-- =======================================================
         DOCUMENTO OFICIAL HABILITADO (PAGO CONFIRMADO)
         ======================================================= -->
    <div class="no-print-bar">
        <div class="bar-info">
            <span class="badge-status badge-paid">
                <i class="fas fa-check-circle"></i> PAGADO EN TESORERÍA
            </span>
            <span>Folio Oficial: <strong><?= htmlspecialchars($reciboNum) ?></strong></span>
            <span style="color: #94A3B8;">|</span>
            <span>Fecha de Validación: <strong><?= $fechaPago ?></strong></span>
        </div>
        <div class="bar-actions">
            <button onclick="window.print()" class="btn-action btn-gold">
                <i class="fas fa-print"></i> Imprimir / Guardar en PDF
            </button>
            <a href="<?= APP_URL ?>/views/comunidad/index.php#constancia" class="btn-action btn-dark">
                <i class="fas fa-arrow-left"></i> Volver al Portal
            </a>
        </div>
    </div>

    <div class="paper-sheet">
        <div class="watermark-bg"></div>

        <div class="content-wrap">
            <!-- Encabezado Institucional -->
            <div class="header-box">
                <table class="header-table">
                    <tr>
                        <td class="header-logo">
                            <img src="<?= ASSETS_URL ?>/assets/logo.svg" alt="Escudo Oficial La Salle">
                        </td>
                        <td class="header-center">
                            <div class="country-title">República de Colombia • Departamento del Magdalena</div>
                            <h1 class="inst-title">INSTITUTO LA SALLE</h1>
                            <div class="inst-motto">"Dios, Ciencia y Virtud"</div>
                            <div class="inst-meta">
                                Reconocimiento Oficial por la Secretaría de Educación Departamental del Magdalena — Resolución N° 0542<br>
                                Código DANE: <strong>147189000123</strong> &nbsp;•&nbsp; Código ICFES: <strong>025489</strong> &nbsp;•&nbsp; NIT: <strong>891.780.123-1</strong><br>
                                Calle 12 N° 10-45, Ciénaga – Magdalena &nbsp;•&nbsp; Contacto: contacto@lasallecienaga.edu.co
                            </div>
                        </td>
                        <td class="header-folio">
                            <div class="folio-card">
                                <span class="folio-tag">FOLIO OFICIAL</span>
                                <span class="folio-num"><?= htmlspecialchars($reciboNum) ?></span>
                                <span class="folio-badge"><i class="fas fa-check"></i> RECAUDADO</span>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Título del Documento -->
            <div class="doc-title-container">
                <h2 class="doc-title">CONSTANCIA OFICIAL DE ESTUDIO</h2>
            </div>

            <!-- Preámbulo Legal -->
            <div class="doc-intro">
                EL SUSCRITO RECTOR Y LA SECRETARÍA ACADÉMICA DEL INSTITUTO LA SALLE<br>
                DE CIÉNAGA, MAGDALENA
            </div>

            <div class="hacen-constar">
                HACEN CONSTAR:
            </div>

            <!-- Cuerpo Formal de la Constancia -->
            <div class="doc-body">
                <p>
                    Que el (la) estudiante <span class="student-highlight"><?= htmlspecialchars($nombreCompleto) ?></span>, 
                    identificado(a) con documento de identidad <span class="highlight-bold"><?= htmlspecialchars($tipoDoc) ?></span> 
                    número <span class="highlight-bold"><?= htmlspecialchars($numDoc) ?></span>, 
                    se encuentra legalmente matriculado(a) y cursando sus estudios en este plantel educativo bajo el código de matrícula SIMAT 
                    <span class="highlight-bold"><?= htmlspecialchars($codMat) ?></span>, 
                    en el grado <span class="highlight-bold"><?= htmlspecialchars($grado) ?></span> 
                    (Grupo <span class="highlight-bold"><?= htmlspecialchars($grupo) ?></span>) de Educación Media Académica, 
                    durante el año lectivo institucional <span class="highlight-bold"><?= $anoNum ?></span>, 
                    en la jornada ordinaria diurna presencial.
                </p>

                <p>
                    Se certifica igualmente que durante el período cursado ha demostrado un adecuado rendimiento académico y una conducta intachable, acatando con fidelidad las disposiciones contempladas en el Manual de Convivencia Institucional y los lineamientos del Ministerio de Educación Nacional de conformidad con la Ley General de Educación (Ley 115 de 1994) y el Decreto Único Reglamentario del Sector Educación 1075 de 2015.
                </p>

                <p>
                    A solicitud de la parte interesada y para ser presentada ante las entidades públicas, privadas u organismos de protección social que la requieran, se expide y sella la presente constancia formal en Ciénaga, departamento del Magdalena, a los <span class="highlight-bold"><?= $diaNum ?></span> días del mes de <span class="highlight-bold"><?= $mesNom ?></span> del año <span class="highlight-bold"><?= $anoNum ?></span>.
                </p>
            </div>

            <!-- Firmas y Sello Oficial -->
            <div class="signatures-section">
                <div class="signature-col">
                    <div class="signature-cursive">Herman G. Rodríguez P.</div>
                    <div class="signature-line">
                        <div class="sign-name">Lic. Herman Gualberto Rodríguez Pérez</div>
                        <div class="sign-cargo">Rector Institucional</div>
                        <div class="sign-doc">C.C. 1082987001 de Ciénaga</div>
                    </div>
                </div>

                <div class="seal-col">
                    <div class="official-seal">
                        <i class="fas fa-stamp"></i>
                        SELLO OFICIAL<br>LA SALLE
                    </div>
                </div>

                <div class="signature-col">
                    <div class="signature-cursive">Claudia P. Salazar M.</div>
                    <div class="signature-line">
                        <div class="sign-name">Lic. Claudia Patricia Salazar Mendoza</div>
                        <div class="sign-cargo">Secretaría Académica</div>
                        <div class="sign-doc">C.C. 1082987004 de Ciénaga</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie de Página y Verificación Digital -->
        <div class="footer-box">
            <div class="footer-qr">
                <img src="<?= $qrUrl ?>" alt="QR de Verificación">
            </div>
            <div class="footer-text">
                <strong>Verificación Oficial de Autenticidad:</strong> Folio de Recaudo <?= htmlspecialchars($reciboNum) ?> • Validez Académica Nacional (Art. 96 Ley 115/1994).<br>
                Documento respaldado por el Sistema de Gestión Institucional y Caja de Tesorería del Instituto La Salle Ciénaga Magdalena.<br>
                Para comprobar la vigencia de este documento escanee el código QR o consulte en <code><?= htmlspecialchars(APP_URL) ?></code>.
            </div>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
