<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
require_once __DIR__ . '/../../config/Database.php';
Router::enforceAccess('comunidad');

$userName = $_SESSION['user_name'] ?? 'Mateo Martínez';
$userId   = $_SESSION['user_id'];

$dbInstance = Database::getInstance();
$pdo = $dbInstance->getConnection();

// Parsear parámetro de estudiantes: puede ser un único ID (?estudiante_id=X) o múltiple (?estudiantes=X,Y,Z)
$studentIds = [];
if (!empty($_GET['estudiantes'])) {
    $rawIds = explode(',', $_GET['estudiantes']);
    foreach ($rawIds as $rid) {
        $idVal = intval(trim($rid));
        if ($idVal > 0) $studentIds[] = $idVal;
    }
}
if (empty($studentIds)) {
    $studentIds = [isset($_GET['estudiante_id']) ? intval($_GET['estudiante_id']) : $userId];
}

$periodoRaw   = $_GET['periodo'] ?? 'todos';
$isSeparated  = ($periodoRaw === 'separados' || $periodoRaw === 'individuales');
$isAllPeriodsRequested = ($periodoRaw === 'todos' || $periodoRaw === 'all' || $periodoRaw === '0' || $periodoRaw === 'juntos');

if ($isSeparated) {
    $periodList = [1, 2, 3, 4];
} elseif ($isAllPeriodsRequested) {
    $periodList = ['todos'];
} else {
    $periodList = [intval($periodoRaw) ?: 1];
}

/* =====================================================================
   ESTRUCTURA OFICIAL DE ÁREAS Y SUB-ASIGNATURAS - INSTITUTO LA SALLE
   Con íconos tipográficos vectoriales Font Awesome idénticos y limpios
   ===================================================================== */
$estructura = [
    'CIENCIAS NATURALES' => [
        'bg'   => '#2d7d32',
        'icon' => '<i class="fas fa-flask"></i>',
        'subs' => [
            ['sub' => 'Química',      'ih' => 1],
            ['sub' => 'Física',       'ih' => 1],
            ['sub' => 'Laboratorio',  'ih' => 2],
        ]
    ],
    'CIENCIAS ECONOMICAS Y POLITICA' => [
        'bg'   => '#117a65',
        'icon' => '<i class="fas fa-chart-line"></i>',
        'subs' => [
            ['sub' => 'Economía Política', 'ih' => 2],
        ]
    ],
    'EDUCACION RELIGIOSA' => [
        'bg'   => '#784212',
        'icon' => '<i class="fas fa-cross"></i>',
        'subs' => [
            ['sub' => 'Religión', 'ih' => 1],
        ]
    ],
    'HUMANIDADES LENGUA CASTELLANA' => [
        'bg'   => '#1E3A8A',
        'icon' => '<i class="fas fa-book"></i>',
        'subs' => [
            ['sub' => 'Lengua Castellana',    'ih' => 3],
            ['sub' => 'Comprensión Lectora',  'ih' => 1],
            ['sub' => 'Producción Textual',   'ih' => 1],
        ]
    ],
    'IDIOMAS EXTRANJEROS' => [
        'bg'   => '#1f618d',
        'icon' => '<i class="fas fa-globe"></i>',
        'subs' => [
            ['sub' => 'Ingles', 'ih' => 3],
        ]
    ],
    'MATEMATICAS' => [
        'bg'   => '#1E3A8A',
        'icon' => '<i class="fas fa-calculator"></i>',
        'subs' => [
            ['sub' => 'Geometría',     'ih' => 1],
            ['sub' => 'Estadística',   'ih' => 1],
            ['sub' => 'Trigonometría', 'ih' => 3],
        ]
    ],
    'EDUCACION FISICA, RECREACION Y DEPORTE' => [
        'bg'   => '#196f3d',
        'icon' => '<i class="fas fa-running"></i>',
        'subs' => [
            ['sub' => 'Expresión Corporal', 'ih' => 1],
            ['sub' => 'Danza',              'ih' => 1],
        ]
    ],
    'TECNOLOGIA Y INFORMATICA' => [
        'bg'   => '#0d47a1',
        'icon' => '<i class="fas fa-laptop-code"></i>',
        'subs' => [
            ['sub' => 'Informática',  'ih' => 1],
            ['sub' => 'Robótica',     'ih' => 2],
            ['sub' => 'Programación', 'ih' => 1],
        ]
    ],
    'FILOSOFIA' => [
        'bg'   => '#4a148c',
        'icon' => '<i class="fas fa-brain"></i>',
        'subs' => [
            ['sub' => 'Filosofía', 'ih' => 2],
        ]
    ],
    'CATEDRA LA SALLISTA' => [
        'bg'   => '#b7950b',
        'icon' => '<i class="fas fa-star"></i>',
        'subs' => [
            ['sub' => 'Cátedra La Sallista', 'ih' => 2],
        ]
    ],
    'METODOLOGIA DE LA INVESTIGACION' => [
        'bg'   => '#78281f',
        'icon' => '<i class="fas fa-search"></i>',
        'subs' => [
            ['sub' => 'Metodología de la Investigación', 'ih' => 1],
        ]
    ],
    'EMPRENDIMIENTO' => [
        'bg'   => '#117a65',
        'icon' => '<i class="fas fa-lightbulb"></i>',
        'subs' => [
            ['sub' => 'Emprendimiento', 'ih' => 1],
        ]
    ],
    'EDUCACION EMOCIONAL' => [
        'bg'   => '#c0392b',
        'icon' => '<i class="fas fa-heart"></i>',
        'subs' => [
            ['sub' => 'Educación Emocional', 'ih' => 1],
        ]
    ],
];

if (!function_exists('normalizeKey')) {
    function normalizeKey(string $s): string {
        $from = ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'];
        $to   = ['a','e','i','o','u','u','n','A','E','I','O','U','U','N'];
        return mb_strtolower(trim(str_replace($from, $to, $s)));
    }
}

if (!function_exists('desempeno')) {
    function desempeno(float $nota): string {
        if ($nota >= 4.6) return 'Superior';
        if ($nota >= 4.0) return 'Alto';
        if ($nota >= 3.0) return 'Básico';
        if ($nota > 0)    return 'Bajo';
        return 'Pendiente';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Boletines Oficiales PDF – Instituto La Salle Ciénaga</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: Arial, Helvetica, sans-serif;
    background: #c5c5c5;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px 0 60px;
    color: #000;
}

/* ─── HOJA A4 CON SALTO DE PÁGINA AUTOMÁTICO EN IMPRESIÓN ─── */
.pagina {
    width: 210mm;
    height: 297mm;
    background: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 40px rgba(0,0,0,.4);
    padding: 0;
    margin-bottom: 30px;
    page-break-after: always;
    page-break-inside: avoid;
}

/* ─── FRANJAS Y BORDES CON COLORES INSTITUCIONALES ─── */
.franja-top-1 {
    position: absolute; top: 0; left: 0; right: 0; height: 12px;
    background: linear-gradient(to right, #1E3A8A 0%, #1E3A8A 40%, #2d7d32 40%, #2d7d32 70%, #f5c518 70%, #f5c518 100%);
    z-index: 10;
}
.franja-top-2 {
    position: absolute; top: 12px; left: 0; right: 0; height: 6px;
    background: linear-gradient(to right, #f5c518 0%, #f5c518 35%, #1E3A8A 35%, #1E3A8A 75%, #2d7d32 75%, #2d7d32 100%);
    z-index: 10;
}

.franja-bot-1 {
    position: absolute; bottom: 12px; left: 0; right: 0; height: 6px;
    background: linear-gradient(to right, #2d7d32 0%, #2d7d32 35%, #f5c518 35%, #f5c518 70%, #1E3A8A 70%, #1E3A8A 100%);
    z-index: 10;
}
.franja-bot-2 {
    position: absolute; bottom: 0; left: 0; right: 0; height: 12px;
    background: linear-gradient(to right, #f5c518 0%, #f5c518 30%, #2d7d32 30%, #2d7d32 65%, #1E3A8A 65%, #1E3A8A 100%);
    z-index: 10;
}

/* Arcos esquinas (Azul, Verde, Dorado) */
.arco { position: absolute; z-index: 9; }
.arco-tl {
    top: -20px; left: -20px;
    width: 110px; height: 110px;
    border-radius: 50%;
    border: 32px solid #f5c518;
    clip-path: polygon(50% 50%, 100% 0, 100% 100%, 0 100%, 0 0);
}
.arco-tr {
    top: -20px; right: -20px;
    width: 110px; height: 110px;
    border-radius: 50%;
    border: 32px solid #2d7d32;
    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 50% 50%);
}
.arco-bl {
    bottom: -20px; left: -20px;
    width: 110px; height: 110px;
    border-radius: 50%;
    border: 32px solid #2d7d32;
    clip-path: polygon(0 0, 100% 0, 100% 50%, 0 100%);
}
.arco-br {
    bottom: -20px; right: -20px;
    width: 110px; height: 110px;
    border-radius: 50%;
    border: 32px solid #1E3A8A;
    clip-path: polygon(0 0, 100% 0, 100% 100%, 0 50%);
}

/* Barras laterales tricolor */
.barra-izq {
    position: absolute; top:0; left: 8px;
    width: 6px; height: 100%;
    background: linear-gradient(to bottom, #1E3A8A 0%, #f5c518 50%, #2d7d32 100%);
    z-index: 8;
}
.barra-der {
    position: absolute; top:0; right: 8px;
    width: 6px; height: 100%;
    background: linear-gradient(to bottom, #2d7d32 0%, #f5c518 50%, #1E3A8A 100%);
    z-index: 8;
}

/* ─── MARCA DE AGUA CON EL LOGO OFICIAL DE FONDO ─── */
.watermark-logo {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 440px; height: 440px;
    border-radius: 50%;
    overflow: hidden;
    opacity: 0.26;
    z-index: 0;
    pointer-events: none;
    object-fit: cover;
    display: block;
}

/* ─── CONTENIDO GENERAL ─── */
.contenido {
    position: relative;
    z-index: 5;
    padding: 18px 24px 14px 24px;
}

/* ENCABEZADO */
.encabezado {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-bottom: 6px;
}
.logo-wrap {
    width: 82px; height: 82px;
    border-radius: 50%;
    overflow: hidden;
    border: 2.5px solid #1E3A8A;
    box-shadow: 0 2px 6px rgba(30,58,138,0.25);
    flex-shrink: 0;
    background: #fff;
}
.logo-wrap img { width:100%; height:100%; object-fit:cover; }

.info-colegio { text-align: center; }
.nombre-grande {
    font-size: 22pt;
    font-weight: 900;
    color: #1E3A8A;
    text-transform: uppercase;
    letter-spacing: 1px;
    line-height: 1.05;
}
.sub-verde {
    font-size: 10pt;
    font-weight: 900;
    color: #2d7d32;
    text-transform: uppercase;
    margin-top: 1px;
}
.ciudad-verde {
    font-size: 10pt;
    font-weight: 900;
    color: #2d7d32;
    text-transform: uppercase;
    margin-bottom: 3px;
}
.meta {
    font-size: 7.5pt;
    font-weight: 700;
    color: #111827;
    line-height: 1.35;
}

/* TÍTULO BOLETÍN CON COLORES INSTITUCIONALES */
.titulo-row {
    display: flex; align-items: center; gap: 10px;
    margin: 6px 0 8px;
}
.linea-gold { flex:1; height: 2.5px; background: linear-gradient(to right, #1E3A8A, #f5c518, #2d7d32); }
.titulo-texto {
    font-size: 14pt;
    font-weight: 900;
    color: #1E3A8A;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    white-space: nowrap;
    background: #fef9e7;
    padding: 2px 14px;
    border: 1.5px solid #f5c518;
    border-radius: 5px;
}

/* DATOS ESTUDIANTE EN NEGRILLA Y COLORES INSTITUCIONALES */
.bloque-est {
    display: flex; gap: 10px; margin-bottom: 8px; align-items: stretch;
}
.foto-box {
    width: 72px; min-height: 80px;
    border: 1.5px solid #1E3A8A;
    display: flex; align-items: center; justify-content: center;
    font-size: 7.5pt; color: #1E3A8A; text-align: center;
    flex-shrink: 0; background: #f0f4ff;
    font-weight: bold;
    border-radius: 4px;
}
.tabla-est { flex:1; border-collapse: collapse; font-size: 8pt; }
.tabla-est th {
    background: #1E3A8A !important; color: #ffffff !important;
    padding: 4px 5px; border: 1px solid #1E3A8A;
    text-align: center; font-size: 7.5pt; text-transform: uppercase;
    font-weight: 900;
}
.tabla-est td {
    padding: 4px 5px; border: 1px solid #1E3A8A;
    text-align: center; font-weight: 900; font-size: 8.5pt;
    color: #000;
    background: #ffffff;
}
.td-acud { text-align:left!important; font-weight:700!important; font-size:8pt!important; background:#fef9e7!important; color:#000!important; }

/* TABLA NOTAS CON NEGRILLA Y ACABADOS VIVOS */
.tabla-notas {
    width:100%; border-collapse:collapse; font-size:7.5pt; margin-bottom:6px;
}
.tabla-notas thead th {
    background: #1E3A8A !important; color:#ffffff !important;
    padding: 4px 5px; border: 1px solid #1E3A8A;
    text-align:center; font-size:7.5pt; font-weight:900;
}
.tabla-notas thead th:first-child { text-align:left; width:22%; }
.tabla-notas thead th:nth-child(2){ text-align:left; width:22%; }
.tabla-notas tbody td {
    padding: 2px 4px; border: 1px solid #b0bec5; vertical-align:middle;
    font-weight: 700; color: #000;
}
.td-area {
    font-weight:900; color:#1E3A8A;
    font-size:7pt; text-transform:uppercase; background:#e8f0fe !important;
    vertical-align:middle; line-height:1.15;
}
.td-sub  { font-size:7.5pt; color:#111; font-weight:700; }
.td-num  { text-align:center; font-weight:900; font-size:8pt; color:#000; }
.td-desc { font-size:7pt; color:#111827; font-weight:700; }

.icono-area {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px; height: 18px; border-radius: 3px;
    font-size: 8.5pt;
    margin-right: 4px; vertical-align: middle; color: #fff;
    font-weight: bold;
    flex-shrink: 0;
}

.fila-prom td {
    background:#d6eaf8 !important; font-weight:900; color:#1E3A8A;
    font-size:8.5pt; padding:4px 6px; border-top: 1.5px solid #1E3A8A; border-bottom: 1.5px solid #1E3A8A;
}

/* CONVIVENCIA CON MARCO VERDE / AZUL */
.bloque-conv { border:1.5px solid #2d7d32; margin-bottom:6px; border-radius:4px; overflow:hidden; }
.encab-conv  { background:#2d7d32 !important; color:#ffffff !important; padding:4px 10px; font-weight:900; font-size:8.5pt; text-transform:uppercase; letter-spacing:0.5px; }
.cuerpo-conv { padding:5px 10px; min-height:24px; font-size:8.5pt; font-weight:800; color:#000; background:#f1f8e9 !important; }

/* OBSERVACIONES + ACCESO */
.fila-obs { display:flex; gap:10px; margin-bottom:8px; }
.caja-obs   { flex:1; border:1.5px solid #1E3A8A; border-radius:4px; overflow:hidden; }
.caja-acc   { width:210px; border:1.5px solid #1E3A8A; border-radius:4px; overflow:hidden; }
.encab-caja { background:#1E3A8A !important; color:#ffffff !important; padding:4px 8px; font-weight:900; font-size:7.5pt; text-transform:uppercase; }
.cuerpo-caja{ padding:5px 8px; min-height:38px; font-size:8pt; line-height:1.5; font-weight:800; color:#000; background:#ffffff !important; }
.cuerpo-caja strong { color:#1E3A8A; font-weight:900; }

/* FIRMAS EN NEGRILLA CON ESPACIO OPTIMIZADO */
.fila-firmas {
    display:flex; justify-content:space-around; gap:16px;
    margin-top: 4px;
    padding-top: 2px;
}
.firma-item  { text-align:center; flex:1; }
.firma-icono { font-size:22pt; margin-bottom:2px; color:#1E3A8A; }
.firma-nombre{
    font-weight:900; font-size:8pt; text-transform:uppercase;
    line-height:1.25; min-height:26px; color:#000;
}
.firma-linea {
    border-top:2px solid #1E3A8A;
    padding-top:4px; margin-top:20px;
    font-size:8pt; color:#1E3A8A;
    font-weight: 900; text-transform: uppercase;
}

/* PIE DE PÁGINA */
.pie {
    text-align:center; font-size:7pt; color:#374151; font-weight:800;
    border-top:1px dashed #1E3A8A; padding-top:4px; margin-top:6px;
}

/* ─── BOTÓN FLOTANTE IMPRIMIR/PDF ─── */
.btn-pdf {
    position:fixed; bottom:24px; right:24px;
    background:#1E3A8A; color:#fff; border:none;
    padding:14px 32px; font-size:13pt; font-weight:900;
    border-radius:50px; cursor:pointer;
    box-shadow:0 6px 24px rgba(30,58,138,.5);
    z-index:9999; display:flex; align-items:center; gap:10px;
    transition: all 0.2s ease;
}
.btn-pdf:hover { background:#163072; transform: scale(1.05); }

/* ─── ESTILOS IMPRESIÓN Y CONSERVACIÓN DE COLORES ─── */
* {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    color-adjust: exact !important;
}

@media print {
    @page { size:A4 portrait; margin:0; }
    body  { background:#fff; padding:0; color:#000; }
    .pagina {
        box-shadow:none;
        page-break-after: always !important;
        page-break-inside: avoid !important;
        margin: 0 !important;
    }
    .btn-pdf{ display:none!important; }
}
</style>
</head>
<body>

<?php foreach ($studentIds as $estId): ?>
    <?php
    // Obtener datos del estudiante actual (soporta usuario_id o matricula_id)
    $profile = null;
    if ($pdo) {
        try {
            // 1. Probar por matricula_id primero si coincide con registro en matrículas
            $stmt = $pdo->prepare("
                SELECT p.*, u.id AS usuario_id, u.correo AS email, c.id AS curso_id, c.grado, c.grupo, c.ano_lectivo,
                       m.id AS matricula_id, m.codigo_matricula
                FROM matriculas m
                INNER JOIN usuarios u ON m.estudiante_id = u.id
                INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                LEFT JOIN cursos c ON m.curso_id = c.id
                WHERE m.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $estId]);
            $profile = $stmt->fetch();

            // 2. Si no coincide con un ID de matrícula, buscar directamente por usuario_id
            if (!$profile) {
                $stmt = $pdo->prepare("
                    SELECT p.*, u.id AS usuario_id, u.correo AS email, c.id AS curso_id, c.grado, c.grupo, c.ano_lectivo,
                           m.id AS matricula_id, m.codigo_matricula
                    FROM usuarios u
                    INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                    LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
                    LEFT JOIN cursos c ON m.curso_id = c.id
                    WHERE u.id = :id
                    LIMIT 1
                ");
                $stmt->execute([':id' => $estId]);
                $profile = $stmt->fetch();
            }
        } catch (Exception $e) { $profile = null; }
    }

    if (!$profile) {
        $profile = [
            'usuario_id'          => $estId,
            'primer_nombre'       => 'Estudiante',
            'segundo_nombre'      => '',
            'primer_apellido'     => 'La Salle',
            'segundo_apellido'    => '',
            'tipo_documento'      => 'TI',
            'documento_identidad' => '108298' . sprintf('%04d', $estId),
            'email'               => 'estudiante' . $estId . '@lasallecienaga.edu.co',
            'grado'               => '10°',
            'grupo'               => 'A',
            'ano_lectivo'         => 2026,
            'curso_id'            => 1,
            'matricula_id'        => $estId,
            'codigo_matricula'    => 'EST-2026-' . $estId,
            'acudiente_nombre'    => 'Acudiente La Salle',
            'acudiente_telefono'  => '3201122334',
        ];
    }

    $studentUserId = $profile['usuario_id'] ?? $estId;
    $matricula_id  = $profile['matricula_id'] ?? $estId;
    $curso_id      = $profile['curso_id'] ?? 1;

    /* ── CREDENCIALES REALES DEL ESTUDIANTE ── */
    $credEmail    = !empty($profile['email']) ? $profile['email'] : ('estudiante' . $studentUserId . '@lasallecienaga.edu.co');
    $credPassword = 'Salle2026*';
    ?>

    <?php foreach ($periodList as $periodo): ?>
        <?php
        $isAllPeriods = ($periodo === 'todos');

        /* Consulta de Calificaciones */
        $gradeLookup = [];
        $promP1 = 0; $promP2 = 0; $promP3 = 0; $promP4 = 0;
        if ($pdo && $matricula_id) {
            try {
                if ($isAllPeriods) {
                $stmt = $pdo->prepare("
                    SELECT 
                        a.nombre_asignatura,
                        a.codigo,
                        cl.id AS calificacion_id,
                        cl.periodo,
                        cl.nota_cognitiva,
                        cl.nota_procedimental,
                        cl.nota_actitudinal,
                        cl.nota_final,
                        cl.logros_observaciones
                    FROM calificaciones_logros cl
                    INNER JOIN asignaturas a ON cl.asignatura_id = a.id
                    WHERE cl.matricula_id = :mat_id AND cl.nota_final > 0
                    ORDER BY a.nombre_asignatura, cl.periodo
                ");
                $stmt->execute([':mat_id' => $matricula_id]);
                foreach ($stmt->fetchAll() as $row) {
                    $key = normalizeKey($row['nombre_asignatura']);
                    if (!isset($gradeLookup[$key])) {
                        $gradeLookup[$key] = [
                            'nombre_asignatura'    => $row['nombre_asignatura'],
                            'codigo'               => $row['codigo'],
                            'calificacion_id'      => $row['calificacion_id'],
                            'p1'                   => null,
                            'p2'                   => null,
                            'p3'                   => null,
                            'p4'                   => null,
                            'nota_actitudinal'     => null,
                            'nota_final'           => 0,
                            'logros_observaciones' => $row['logros_observaciones']
                        ];
                    }
                    $p = intval($row['periodo']);
                    if ($p >= 1 && $p <= 4) {
                        $gradeLookup[$key]['p' . $p] = floatval($row['nota_final']);
                    }
                    if (!empty($row['logros_observaciones'])) {
                        $gradeLookup[$key]['logros_observaciones'] = $row['logros_observaciones'];
                    }
                }
                foreach ($gradeLookup as $k => &$gItem) {
                    $validGrades = [];
                    for ($i = 1; $i <= 4; $i++) {
                        if ($gItem['p' . $i] !== null && $gItem['p' . $i] > 0) {
                            $validGrades[] = $gItem['p' . $i];
                        }
                    }
                    $gItem['nota_final'] = !empty($validGrades) ? round(array_sum($validGrades) / count($validGrades), 2) : 0;
                }
                unset($gItem);

                $p1Arr = array_filter(array_column($gradeLookup, 'p1'));
                $promP1 = !empty($p1Arr) ? round(array_sum($p1Arr) / count($p1Arr), 2) : 0;
                $p2Arr = array_filter(array_column($gradeLookup, 'p2'));
                $promP2 = !empty($p2Arr) ? round(array_sum($p2Arr) / count($p2Arr), 2) : 0;
                $p3Arr = array_filter(array_column($gradeLookup, 'p3'));
                $promP3 = !empty($p3Arr) ? round(array_sum($p3Arr) / count($p3Arr), 2) : 0;
                $p4Arr = array_filter(array_column($gradeLookup, 'p4'));
                $promP4 = !empty($p4Arr) ? round(array_sum($p4Arr) / count($p4Arr), 2) : 0;
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        a.nombre_asignatura,
                        a.codigo,
                        cl.id AS calificacion_id,
                        cl.nota_cognitiva,
                        cl.nota_procedimental,
                        cl.nota_actitudinal,
                        cl.nota_final,
                        cl.logros_observaciones
                    FROM calificaciones_logros cl
                    INNER JOIN asignaturas a ON cl.asignatura_id = a.id
                    WHERE cl.matricula_id = :mat_id AND cl.periodo = :periodo
                ");
                $stmt->execute([':mat_id' => $matricula_id, ':periodo' => $periodo]);
                foreach ($stmt->fetchAll() as $row) {
                    $key = normalizeKey($row['nombre_asignatura']);
                    $gradeLookup[$key] = $row;
                }
            }
        } catch (Exception $e) { $gradeLookup = []; }
    }

    /* Calcular promedio general sobre notas registradas */
    $notasValidas = array_filter(
        array_column($gradeLookup, 'nota_final'),
        function($v) { return floatval($v) > 0; }
    );
    $promedio = !empty($notasValidas)
        ? round(array_sum($notasValidas) / count($notasValidas), 2)
        : 0;

    /* Consulta Director de Grupo y Firma Digital */
    $directorGrupo = 'Carlos Alberto García López';
    $directorFirma = null;
    if ($pdo && $curso_id) {
        try {
            $stmtDir = $pdo->prepare("
                SELECT pd.primer_nombre, pd.primer_apellido, pd.segundo_nombre, pd.segundo_apellido, pd.firma_digital
                FROM curso_asignatura_docente cad
                INNER JOIN usuarios du ON cad.docente_id = du.id
                INNER JOIN perfiles_detalles pd ON du.id = pd.usuario_id
                WHERE cad.curso_id = :cid
                ORDER BY cad.id ASC LIMIT 1
            ");
            $stmtDir->execute([':cid' => $curso_id]);
            $dirRow = $stmtDir->fetch();
            if ($dirRow && !empty($dirRow['primer_nombre'])) {
                $directorGrupo = trim(implode(' ', array_filter([
                    $dirRow['primer_nombre'],
                    $dirRow['segundo_nombre'] ?? '',
                    $dirRow['primer_apellido'],
                    $dirRow['segundo_apellido'] ?? ''
                ])));
                $directorFirma = $dirRow['firma_digital'] ?? null;
            }
        } catch (Exception $e) {}
    }

    /* Consulta Asistencias */
    $asistencias = ['total'=>0,'asistio'=>0,'falto'=>0,'excusa'=>0];
    if ($pdo && $matricula_id) {
        try {
            $stmt = $pdo->prepare("SELECT estado, COUNT(*) as cnt FROM asistencias WHERE matricula_id = :mat_id GROUP BY estado");
            $stmt->execute([':mat_id' => $matricula_id]);
            foreach ($stmt->fetchAll() as $row) {
                $asistencias['total'] += $row['cnt'];
                if ($row['estado']==='Asistió')   $asistencias['asistio'] = $row['cnt'];
                elseif ($row['estado']==='Faltó') $asistencias['falto']   = $row['cnt'];
                elseif ($row['estado']==='Excusa') $asistencias['excusa'] = $row['cnt'];
            }
        } catch (Exception $e) {}
    }
    $pctAsistencia = $asistencias['total'] > 0 ? round(($asistencias['asistio'] / $asistencias['total']) * 100) : 100;

    $fullName = trim(implode(' ', array_filter([
        $profile['primer_apellido']  ?? '',
        $profile['segundo_apellido'] ?? '',
        $profile['primer_nombre']    ?? '',
        $profile['segundo_nombre']   ?? '',
    ])));

    /* ── CÁLCULO DE PUESTO EN EL CURSO ── */
    $puesto = '—';
    $totalEstCurso = 0;
    if ($pdo && $curso_id) {
        try {
            if ($isAllPeriods) {
                $stmtRank = $pdo->prepare("
                    SELECT
                        m.estudiante_id,
                        ROUND(AVG(cl.nota_final), 2) AS prom,
                        RANK() OVER (ORDER BY AVG(cl.nota_final) DESC) AS puesto
                    FROM matriculas m
                    INNER JOIN calificaciones_logros cl ON cl.matricula_id = m.id
                    WHERE m.curso_id = :cid AND cl.nota_final > 0
                    GROUP BY m.estudiante_id
                ");
                $stmtRank->execute([':cid' => $curso_id]);
            } else {
                $stmtRank = $pdo->prepare("
                    SELECT
                        m.estudiante_id,
                        ROUND(AVG(cl.nota_final), 2) AS prom,
                        RANK() OVER (ORDER BY AVG(cl.nota_final) DESC) AS puesto
                    FROM matriculas m
                    INNER JOIN calificaciones_logros cl ON cl.matricula_id = m.id
                    WHERE m.curso_id = :cid AND cl.periodo = :per AND cl.nota_final > 0
                    GROUP BY m.estudiante_id
                ");
                $stmtRank->execute([':cid' => $curso_id, ':per' => $periodo]);
            }
            $rankRows = $stmtRank->fetchAll(PDO::FETCH_ASSOC);
            $totalEstCurso = count($rankRows);
            foreach ($rankRows as $rr) {
                if (intval($rr['estudiante_id']) === intval($studentUserId)) {
                    $puesto = intval($rr['puesto']);
                    break;
                }
            }
        } catch (Exception $e) { $puesto = '—'; }
    }

    ?>

    <!-- Página A4 del Boletín del Estudiante -->
    <div class="pagina">
        <!-- Marca de agua con el Logo Oficial en el Trasfondo -->
        <img src="<?= ASSETS_URL ?>/assets/logo_lasalle.jpg" class="watermark-logo" alt="Marca de agua La Salle">

        <!-- Franjas decorativas en colores institucionales -->
        <div class="franja-top-1"></div>
        <div class="franja-top-2"></div>
        <div class="franja-bot-1"></div>
        <div class="franja-bot-2"></div>

        <!-- Arcos esquinas -->
        <div class="arco arco-tl"></div>
        <div class="arco arco-tr"></div>
        <div class="arco arco-bl"></div>
        <div class="arco arco-br"></div>

        <!-- Barras laterales -->
        <div class="barra-izq"></div>
        <div class="barra-der"></div>

        <div class="contenido">

            <!-- ══ ENCABEZADO INSTITUCIONAL ══ -->
            <div class="encabezado">
                <div class="logo-wrap">
                    <img src="<?= ASSETS_URL ?>/assets/logo_lasalle.jpg" alt="Logo Instituto La Salle">
                </div>
                <div class="info-colegio">
                    <div class="nombre-grande">Instituto La Salle</div>
                    <div class="sub-verde">Instituto La Salle</div>
                    <div class="ciudad-verde">Ciénaga – Magdalena</div>
                    <div class="meta">
                        Aprobado por Resolución No. 1806 de Octubre 08 de 2019 y Decreto 009 de Junio de 2015<br>
                        NIT. 8190049841-0 &nbsp;|&nbsp; DANE 347189041870<br>
                        Dirección: Carrera 31 N° 18-70 &nbsp;|&nbsp; Teléfono: 5 4101084
                    </div>
                </div>
            </div>

            <!-- ══ TÍTULO BOLETÍN ══ -->
            <div class="titulo-row">
                <div class="linea-gold"></div>
                <div class="titulo-texto"><?= $isAllPeriods ? 'Boletín Consolidado (Periodos 1 al 4)' : ('Boletín Informativo — Período ' . $periodo) ?></div>
                <div class="linea-gold"></div>
            </div>

            <!-- ══ DATOS DEL ESTUDIANTE ══ -->
            <div class="bloque-est">
                <div class="foto-box">📷<br><span style="font-size:7pt">Foto<br>Estudiante</span></div>
                <table class="tabla-est">
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
                            <td><?= $isAllPeriods ? '1° - 4°' : ($periodo . '°') ?></td>
                        </tr>
                        <tr>
                            <td colspan="5" class="td-acud">
                                <strong>ACUDIENTE:</strong>&nbsp;<?= htmlspecialchars(strtoupper($profile['acudiente_nombre'] ?? '')) ?>
                                &nbsp;|&nbsp;<strong>TEL:</strong>&nbsp;<?= htmlspecialchars($profile['acudiente_telefono'] ?? '') ?>
                            </td>
                            <td colspan="2" style="text-align:center;font-weight:900;background:#fff8dc;color:#1E3A8A;font-size:10pt;border:1px solid #1E3A8A;">
                                <?php if (is_numeric($puesto)): ?>
                                    🏆 PUESTO<br><span style="font-size:16pt;font-weight:900;color:#1E3A8A;"><?= $puesto ?></span><span style="font-size:7pt;"> / <?= $totalEstCurso ?></span>
                                <?php else: ?>
                                    Puesto: —
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php
            $areasConNota = [];
            $matchedKeys = [];

            foreach ($estructura as $areaNombre => $areaDef) {
                $subsConNota = [];
                foreach ($areaDef['subs'] as $sub) {
                    $key = normalizeKey($sub['sub']);
                    $gRow = $gradeLookup[$key] ?? null;
                    $tieneNota = ($gRow && !empty($gRow['calificacion_id']) && floatval($gRow['nota_final']) > 0);
                    if ($tieneNota) {
                        $matchedKeys[$key] = true;
                        $subsConNota[] = [
                            'sub'   => $sub['sub'],
                            'ih'    => $sub['ih'],
                            'gRow'  => $gRow
                        ];
                    }
                }
                if (!empty($subsConNota)) {
                    $areasConNota[$areaNombre] = [
                        'bg'   => $areaDef['bg'],
                        'icon' => $areaDef['icon'],
                        'subs' => $subsConNota
                    ];
                }
            }

            foreach ($gradeLookup as $key => $gRow) {
                if (!isset($matchedKeys[$key]) && !empty($gRow['calificacion_id']) && floatval($gRow['nota_final']) > 0) {
                    $areaExtra = 'OTRAS ASIGNATURAS';
                    if (!isset($areasConNota[$areaExtra])) {
                        $areasConNota[$areaExtra] = [
                            'bg'   => '#1E3A8A',
                            'icon' => '<i class="fas fa-book-open"></i>',
                            'subs' => []
                        ];
                    }
                    $areasConNota[$areaExtra]['subs'][] = [
                        'sub'  => $gRow['nombre_asignatura'],
                        'ih'   => 1,
                        'gRow' => $gRow
                    ];
                }
            }
            ?>

            <?php if ($isAllPeriods): ?>
            <!-- ══ TABLA DE ÁREAS Y CALIFICACIONES CONSOLIDADAS (TODOS LOS PERIODOS) ══ -->
            <table class="tabla-notas">
                <thead>
                    <tr>
                        <th>Áreas/Asignaturas</th>
                        <th>Asignatura</th>
                        <th style="width:26px;text-align:center;">IH</th>
                        <th style="width:34px;text-align:center;">P1</th>
                        <th style="width:34px;text-align:center;">P2</th>
                        <th style="width:34px;text-align:center;">P3</th>
                        <th style="width:34px;text-align:center;">P4</th>
                        <th style="width:38px;text-align:center;">DEF</th>
                        <th style="width:70px;text-align:center;">Desempeño</th>
                        <th>Descriptor de desempeño</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($areasConNota)): ?>
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted" style="text-align:center; padding: 25px; font-style:italic;">
                            <i class="fas fa-info-circle me-1"></i> No se han subido calificaciones para este estudiante en ningún período aún.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($areasConNota as $areaNombre => $areaDef):
                        $subsCount  = count($areaDef['subs']);
                        $primeraFila = true;
                        foreach ($areaDef['subs'] as $item):
                            $gRow  = $item['gRow'];
                            $def   = floatval($gRow['nota_final']);
                            $logro = !empty($gRow['logros_observaciones']) ? $gRow['logros_observaciones'] : 'Sin observación registrada.';
                    ?>
                    <tr>
                        <?php if ($primeraFila): ?>
                        <td class="td-area" rowspan="<?= $subsCount ?>">
                            <span class="icono-area" style="background:<?= $areaDef['bg'] ?>"><?= $areaDef['icon'] ?></span>
                            <?= htmlspecialchars($areaNombre) ?>
                        </td>
                        <?php $primeraFila = false; endif; ?>
                        <td class="td-sub">– <?= htmlspecialchars($item['sub']) ?></td>
                        <td class="td-num"><?= $item['ih'] ?></td>
                        <td class="td-num" style="<?= ($gRow['p1'] !== null && $gRow['p1'] < 3.0) ? 'color:#dc2626;' : '' ?>"><?= ($gRow['p1'] !== null && $gRow['p1'] > 0) ? number_format($gRow['p1'], 1) : '—' ?></td>
                        <td class="td-num" style="<?= ($gRow['p2'] !== null && $gRow['p2'] < 3.0) ? 'color:#dc2626;' : '' ?>"><?= ($gRow['p2'] !== null && $gRow['p2'] > 0) ? number_format($gRow['p2'], 1) : '—' ?></td>
                        <td class="td-num" style="<?= ($gRow['p3'] !== null && $gRow['p3'] < 3.0) ? 'color:#dc2626;' : '' ?>"><?= ($gRow['p3'] !== null && $gRow['p3'] > 0) ? number_format($gRow['p3'], 1) : '—' ?></td>
                        <td class="td-num" style="<?= ($gRow['p4'] !== null && $gRow['p4'] < 3.0) ? 'color:#dc2626;' : '' ?>"><?= ($gRow['p4'] !== null && $gRow['p4'] > 0) ? number_format($gRow['p4'], 1) : '—' ?></td>
                        <td class="td-num" style="background:#e0f2fe; color:#0369a1; font-weight:900;"><strong><?= $def > 0 ? number_format($def, 1) : '—' ?></strong></td>
                        <td style="text-align:center; font-size:7pt; font-weight:900; color:<?= $def >= 4.6 ? '#15803d' : ($def >= 4.0 ? '#1d4ed8' : ($def >= 3.0 ? '#b45309' : '#b91c1c')) ?>;">
                            <?= $def > 0 ? desempeno($def) : '—' ?>
                        </td>
                        <td class="td-desc"><?= htmlspecialchars(mb_strimwidth($logro, 0, 60, '…')) ?></td>
                    </tr>
                    <?php endforeach; endforeach; ?>
                    <?php endif; ?>
                    <tr class="fila-prom">
                        <td colspan="3" style="text-align:right; font-weight:900;">PROMEDIOS POR PERÍODO:</td>
                        <td class="td-num" style="font-weight:900;"><?= $promP1 > 0 ? number_format($promP1, 1) : '—' ?></td>
                        <td class="td-num" style="font-weight:900;"><?= $promP2 > 0 ? number_format($promP2, 1) : '—' ?></td>
                        <td class="td-num" style="font-weight:900;"><?= $promP3 > 0 ? number_format($promP3, 1) : '—' ?></td>
                        <td class="td-num" style="font-weight:900;"><?= $promP4 > 0 ? number_format($promP4, 1) : '—' ?></td>
                        <td class="td-num" style="font-weight:900; background:#dbeafe; color:#1e40af;"><?= $promedio > 0 ? number_format($promedio, 2) : '—' ?></td>
                        <td colspan="2" style="font-weight:900;"><?= $promedio > 0 ? 'Desempeño Anual ' . desempeno($promedio) : 'Sin notas registradas' ?></td>
                    </tr>
                </tbody>
            </table>
            <?php else: ?>
            <!-- ══ TABLA DE ÁREAS Y CALIFICACIONES INDIVIDUAL (PERIODO ESPECÍFICO) ══ -->
            <table class="tabla-notas">
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
                    <?php if (empty($areasConNota)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted" style="text-align:center; padding: 25px; font-style:italic;">
                            <i class="fas fa-info-circle me-1"></i> No se han subido calificaciones para este estudiante en el periodo <?= $periodo ?> aún.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($areasConNota as $areaNombre => $areaDef):
                        $subsCount  = count($areaDef['subs']);
                        $primeraFila = true;
                        foreach ($areaDef['subs'] as $item):
                            $gRow  = $item['gRow'];
                            $def   = floatval($gRow['nota_final']);
                            $act   = $gRow['nota_actitudinal'] !== null ? floatval($gRow['nota_actitudinal']) : null;
                            $logro = !empty($gRow['logros_observaciones']) ? $gRow['logros_observaciones'] : 'Sin observación registrada.';
                    ?>
                    <tr>
                        <?php if ($primeraFila): ?>
                        <td class="td-area" rowspan="<?= $subsCount ?>">
                            <span class="icono-area" style="background:<?= $areaDef['bg'] ?>"><?= $areaDef['icon'] ?></span>
                            <?= htmlspecialchars($areaNombre) ?>
                        </td>
                        <?php $primeraFila = false; endif; ?>
                        <td class="td-sub">– <?= htmlspecialchars($item['sub']) ?></td>
                        <td class="td-num"><?= $item['ih'] ?></td>
                        <td class="td-num"><?= ($act !== null && $act > 0) ? number_format($act,1) : '—' ?></td>
                        <td class="td-num"><strong><?= number_format($def,1) ?></strong></td>
                        <td class="td-desc"><?= htmlspecialchars(mb_strimwidth($logro, 0, 75, '…')) ?></td>
                    </tr>
                    <?php endforeach; endforeach; ?>
                    <?php endif; ?>
                    <tr class="fila-prom">
                        <td colspan="4" style="text-align:right;">PROMEDIO GENERAL — PERIODO <?= $periodo ?>:</td>
                        <td class="td-num"><?= $promedio > 0 ? number_format($promedio,2) : '—' ?></td>
                        <td><?= $promedio > 0 ? 'Desempeño ' . desempeno($promedio) : 'Sin notas registradas' ?></td>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- ══ SECCIÓN DE CONVIVENCIA ══ -->
            <div class="bloque-conv">
                <div class="encab-conv">Convivencia</div>
                <div class="cuerpo-conv">
                    Asistencias: <strong><?= $asistencias['asistio'] ?></strong> días &nbsp;|&nbsp;
                    Inasistencias: <strong><?= $asistencias['falto'] ?></strong> &nbsp;|&nbsp;
                    Excusas: <strong><?= $asistencias['excusa'] ?></strong> &nbsp;|&nbsp;
                    % Asistencia: <strong><?= $asistencias['total'] > 0 ? $pctAsistencia . '%' : '100%' ?></strong>
                </div>
            </div>

            <div class="fila-obs">
                <div class="caja-obs">
                    <div class="encab-caja">Observación del Director(a) de Grupo</div>
                    <div class="cuerpo-caja">&nbsp;</div>
                </div>
                <div class="caja-acc">
                    <div class="encab-caja"><i class="fas fa-lock"></i> Acceso Plataforma AulaVirtual Salle</div>
                    <div class="cuerpo-caja" style="font-size:7.5pt;">
                        🌐 &nbsp;<strong>WEB:</strong> http://aulavirtualsalle.com/<br>
                        👤 &nbsp;<strong>USUARIO:</strong> <?= htmlspecialchars($credEmail) ?><br>
                        🔑 &nbsp;<strong>CLAVE INICIAL:</strong> Salle2026*
                    </div>
                </div>
            </div>

            <!-- ══ SECCIÓN DE FIRMAS AUTORIZADAS ══ -->
            <div class="fila-firmas">
                <div class="firma-item">
                    <?php 
                    $dirFirmaEsImg = !empty($directorFirma) && strpos($directorFirma, 'TEXTO:') !== 0;
                    $dirFirmaTexto = !empty($directorFirma) && strpos($directorFirma, 'TEXTO:') === 0 ? substr($directorFirma, 6) : $directorGrupo;
                    $dirFirmaImgSrc = $dirFirmaEsImg ? (strpos($directorFirma, 'http') === 0 ? $directorFirma : (ASSETS_URL . '/' . ltrim($directorFirma, '/'))) : null;
                    ?>
                    <?php if ($dirFirmaEsImg): ?>
                        <div style="height:36px; display:flex; align-items:center; justify-content:center; margin-bottom:2px;">
                            <img src="<?= htmlspecialchars($dirFirmaImgSrc) ?>" alt="Firma Director" style="max-height:34px; max-width:130px; object-fit:contain;">
                        </div>
                    <?php else: ?>
                        <div style="font-family:'Brush Script MT', 'Dancing Script', cursive, sans-serif; font-size:13pt; color:#1E3A8A; height:36px; line-height:36px; margin-bottom:2px; font-weight:normal;">
                            <?= htmlspecialchars($dirFirmaTexto) ?>
                        </div>
                    <?php endif; ?>
                    <div class="firma-nombre">
                        <?= htmlspecialchars($directorGrupo) ?><br>
                        <span style="font-weight:700;font-size:7.5pt;color:#374151;">Director(a) de Grupo</span>
                    </div>
                    <div class="firma-linea">Director(a) de Grupo</div>
                </div>
                <div class="firma-item">
                    <div class="firma-icono">🧑‍💼</div>
                    <div class="firma-nombre">
                        Cherlys Judith Domínguez Ramírez<br>
                        <span style="font-weight:700;font-size:7.5pt;color:#374151;">Rectora</span>
                    </div>
                    <div class="firma-linea">Rector</div>
                </div>
                <div class="firma-item">
                    <div class="firma-icono">✒️</div>
                    <div class="firma-nombre">
                        Héctor Sandoval Ojeda<br>
                        <span style="font-weight:700;font-size:7.5pt;color:#374151;">Secretario</span>
                    </div>
                    <div class="firma-linea">Secretario</div>
                </div>
            </div>

            <!-- PIE DE PÁGINA -->
            <div class="pie">
                Documento Oficial generado por AulaVirtual Salle &nbsp;•&nbsp; Instituto La Salle Ciénaga &nbsp;•&nbsp;
                Fecha de Emisión: <?= date('d/m/Y') ?> &nbsp;•&nbsp; <?= $isAllPeriods ? 'Consolidado General (Periodos 1 al 4)' : ('Periodo ' . $periodo) ?>
            </div>

        </div><!-- /contenido -->
    </div><!-- /pagina -->
    <?php endforeach; // Fin foreach $periodList ?>
<?php endforeach; // Fin foreach $studentIds ?>

<!-- Botón flotante único para imprimir todo en un solo PDF -->
<button class="btn-pdf" onclick="window.print()">
    <i class="fas fa-print"></i> Guardar Todo en Un Solo PDF
</button>

<script>
if (new URLSearchParams(window.location.search).get('auto') === '1') {
    window.onload = () => setTimeout(() => window.print(), 500);
}
</script>
</body>
</html>
