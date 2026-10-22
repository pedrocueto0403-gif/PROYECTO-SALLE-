<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
require_once __DIR__ . '/../../app/Controllers/MigrationController.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../app/Services/UserBulkImporter.php';
require_once __DIR__ . '/../../app/Services/EmailGateway.php';
Router::enforceAccess('admin');

$userName = $_SESSION['user_name'] ?? 'Administrador';
$userRole = $_SESSION['user_role'] ?? 'administrador';

$dbInstance = Database::getInstance();
$pdo = $dbInstance->getConnection();

$message = null;
$messageType = 'success';


// Roles Map
$rolesMap = [
    1 => 'administrador',
    2 => 'administrativo',
    3 => 'docente',
    4 => 'psicologo',
    5 => 'estudiante',
    6 => 'acudiente',
    7 => 'coordinador'
];

// Inicializar lista demo en sesión si no existe
if (!isset($_SESSION['demo_users']) || empty($_SESSION['demo_users'])) {
    $_SESSION['demo_users'] = [
        ['id' => 1, 'correo' => 'admin@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 1, 'nombre_rol' => 'administrador', 'primer_nombre' => 'Herman', 'segundo_nombre' => 'Gualberto', 'primer_apellido' => 'Rodríguez', 'segundo_apellido' => 'Pérez', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987001', 'telefono' => '3001234567', 'direccion' => 'Calle 12 # 8-45 Ciénaga', 'ultimo_acceso' => 'Hoy, 09:30 AM'],
        ['id' => 2, 'correo' => 'tesoreria@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 2, 'nombre_rol' => 'administrativo', 'primer_nombre' => 'María', 'segundo_nombre' => 'Fernanda', 'primer_apellido' => 'Gómez', 'segundo_apellido' => 'Ríos', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987002', 'telefono' => '3019876543', 'direccion' => 'Cra 15 # 10-20 Ciénaga', 'ultimo_acceso' => 'Ayer, 04:15 PM'],
        ['id' => 3, 'correo' => 'docente.garcia@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 3, 'nombre_rol' => 'docente', 'primer_nombre' => 'Carlos', 'segundo_nombre' => 'Alberto', 'primer_apellido' => 'García', 'segundo_apellido' => 'López', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987003', 'telefono' => '3104567890', 'direccion' => 'Calle 7 # 12-30 Ciénaga', 'ultimo_acceso' => 'Hoy, 08:45 AM'],
        ['id' => 4, 'correo' => 'psicologia@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 4, 'nombre_rol' => 'psicologo', 'primer_nombre' => 'Claudia', 'segundo_nombre' => 'Patricia', 'primer_apellido' => 'Salazar', 'segundo_apellido' => 'Mendoza', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987004', 'telefono' => '3157890123', 'direccion' => 'Cra 8 # 5-60 Ciénaga', 'ultimo_acceso' => 'Hace 2 días'],
        ['id' => 5, 'correo' => 'estudiante.martinez@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 5, 'nombre_rol' => 'estudiante', 'primer_nombre' => 'Mateo', 'segundo_nombre' => 'Andrés', 'primer_apellido' => 'Martínez', 'segundo_apellido' => 'Suárez', 'tipo_documento' => 'TI', 'documento_identidad' => '1082987005', 'telefono' => '3201122334', 'direccion' => 'Calle 18 # 14-50 Ciénaga', 'ultimo_acceso' => 'Hoy, 10:12 AM', 'grado' => '10°', 'curso' => 'DÉCIMO A'],
        ['id' => 6, 'correo' => 'acudiente.martinez@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 6, 'nombre_rol' => 'acudiente', 'primer_nombre' => 'Jorge', 'segundo_nombre' => 'Luis', 'primer_apellido' => 'Martínez', 'segundo_apellido' => 'Blanco', 'tipo_documento' => 'CC', 'documento_identidad' => '1082987006', 'telefono' => '3201122334', 'direccion' => 'Calle 18 # 14-50 Ciénaga', 'ultimo_acceso' => 'Hace 3 días'],
        ['id' => 7, 'correo' => 'ahumada.anahy@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 5, 'nombre_rol' => 'estudiante', 'primer_nombre' => 'Anahy', 'segundo_nombre' => 'Sofía', 'primer_apellido' => 'Ahumada', 'segundo_apellido' => 'Mariño', 'tipo_documento' => 'TI', 'documento_identidad' => '1176965995', 'telefono' => '3005551122', 'direccion' => 'Ciénaga', 'ultimo_acceso' => 'Ayer', 'grado' => '8°', 'curso' => 'OCTAVO A'],
        ['id' => 8, 'correo' => 'alvarado.santiago@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 5, 'nombre_rol' => 'estudiante', 'primer_nombre' => 'Santiago', 'segundo_nombre' => 'Alejandro', 'primer_apellido' => 'Alvarado', 'segundo_apellido' => 'Toro', 'tipo_documento' => 'TI', 'documento_identidad' => '5905856', 'telefono' => '3005553344', 'direccion' => 'Ciénaga', 'ultimo_acceso' => 'Hoy', 'grado' => '8°', 'curso' => 'OCTAVO A'],
        ['id' => 9, 'correo' => 'aponte.isel@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 5, 'nombre_rol' => 'estudiante', 'primer_nombre' => 'Isel', 'segundo_nombre' => 'Nayleth', 'primer_apellido' => 'Aponte', 'segundo_apellido' => 'Ferreira', 'tipo_documento' => 'TI', 'documento_identidad' => '1080437034', 'telefono' => '3005555566', 'direccion' => 'Ciénaga', 'ultimo_acceso' => 'Hoy', 'grado' => '8°', 'curso' => 'OCTAVO A'],
        ['id' => 10, 'correo' => 'barraza.jasiell@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 5, 'nombre_rol' => 'estudiante', 'primer_nombre' => 'Jasiell', 'segundo_nombre' => 'Nicolle', 'primer_apellido' => 'Barraza', 'segundo_apellido' => 'Polo', 'tipo_documento' => 'TI', 'documento_identidad' => '1080437253', 'telefono' => '3005557788', 'direccion' => 'Ciénaga', 'ultimo_acceso' => 'Ayer', 'grado' => '8°', 'curso' => 'OCTAVO A'],
        ['id' => 11, 'correo' => 'bermudez.oriana@lasallecienaga.edu.co', 'password' => 'Salle2026*', 'estado_cuenta' => 'activo', 'rol_id' => 5, 'nombre_rol' => 'estudiante', 'primer_nombre' => 'Oriana', 'segundo_nombre' => 'Nicole', 'primer_apellido' => 'Bermúdez', 'segundo_apellido' => 'Forero', 'tipo_documento' => 'TI', 'documento_identidad' => '1080436551', 'telefono' => '3005559900', 'direccion' => 'Ciénaga', 'ultimo_acceso' => 'Hoy', 'grado' => '8°', 'curso' => 'OCTAVO A'],
    ];
}

// Inicializar lista demo de asignaciones de materias a cursos (CAD)
if (!isset($_SESSION['demo_cad']) || empty($_SESSION['demo_cad'])) {
    $_SESSION['demo_cad'] = [
        ['id' => 1, 'curso_nombre' => 'DÉCIMO A (10°A)', 'asignatura_nombre' => 'Matemáticas (Trigonometría)', 'docente_nombre' => 'Carlos Alberto García López', 'ih' => 3],
        ['id' => 2, 'curso_nombre' => 'DÉCIMO A (10°A)', 'asignatura_nombre' => 'Lengua Castellana', 'docente_nombre' => 'Carlos Alberto García López', 'ih' => 3],
        ['id' => 3, 'curso_nombre' => 'DÉCIMO A (10°A)', 'asignatura_nombre' => 'Química', 'docente_nombre' => 'Carlos Alberto García López', 'ih' => 2],
        ['id' => 4, 'curso_nombre' => 'OCTAVO A (8°A)', 'asignatura_nombre' => 'Matemáticas (Álgebra)', 'docente_nombre' => 'Carlos Alberto García López', 'ih' => 3],
        ['id' => 5, 'curso_nombre' => 'OCTAVO A (8°A)', 'asignatura_nombre' => 'Informática & Robótica', 'docente_nombre' => 'Carlos Alberto García López', 'ih' => 2],
    ];
}

// Lista Oficial de Asignaturas La Salle
$mallaAsignaturas = [
    ['id' => 1,  'nombre' => 'Química', 'ih' => 1, 'area' => 'Ciencias Naturales'],
    ['id' => 2,  'nombre' => 'Física', 'ih' => 1, 'area' => 'Ciencias Naturales'],
    ['id' => 3,  'nombre' => 'Laboratorio', 'ih' => 2, 'area' => 'Ciencias Naturales'],
    ['id' => 4,  'nombre' => 'Economía Política', 'ih' => 2, 'area' => 'Ciencias Económicas y Política'],
    ['id' => 5,  'nombre' => 'Religión', 'ih' => 1, 'area' => 'Educación Religiosa'],
    ['id' => 6,  'nombre' => 'Lengua Castellana', 'ih' => 3, 'area' => 'Humanidades Lengua Castellana'],
    ['id' => 7,  'nombre' => 'Comprensión Lectora', 'ih' => 1, 'area' => 'Humanidades Lengua Castellana'],
    ['id' => 8,  'nombre' => 'Producción Textual', 'ih' => 1, 'area' => 'Humanidades Lengua Castellana'],
    ['id' => 9,  'nombre' => 'Inglés', 'ih' => 3, 'area' => 'Idiomas Extranjeros'],
    ['id' => 10, 'nombre' => 'Geometría', 'ih' => 1, 'area' => 'Matemáticas'],
    ['id' => 11, 'nombre' => 'Estadística', 'ih' => 1, 'area' => 'Matemáticas'],
    ['id' => 12, 'nombre' => 'Trigonometría', 'ih' => 3, 'area' => 'Matemáticas'],
    ['id' => 13, 'nombre' => 'Expresión Corporal', 'ih' => 1, 'area' => 'Educación Física'],
    ['id' => 14, 'nombre' => 'Danza', 'ih' => 1, 'area' => 'Educación Física'],
    ['id' => 15, 'nombre' => 'Informática', 'ih' => 1, 'area' => 'Tecnología e Informática'],
    ['id' => 16, 'nombre' => 'Robótica', 'ih' => 2, 'area' => 'Tecnología e Informática'],
    ['id' => 17, 'nombre' => 'Programación', 'ih' => 1, 'area' => 'Tecnología e Informática'],
    ['id' => 18, 'nombre' => 'Filosofía', 'ih' => 2, 'area' => 'Filosofía'],
    ['id' => 19, 'nombre' => 'Cátedra La Sallista', 'ih' => 2, 'area' => 'Cátedra La Sallista'],
    ['id' => 20, 'nombre' => 'Metodología de la Investigación', 'ih' => 1, 'area' => 'Investigación'],
    ['id' => 21, 'nombre' => 'Emprendimiento', 'ih' => 1, 'area' => 'Emprendimiento'],
    ['id' => 22, 'nombre' => 'Educación Emocional', 'ih' => 1, 'area' => 'Educación Emocional'],
];

// Cargar Cursos reales desde la BD con Jornada y Sede
$cursosDB = [];
if ($pdo) {
    try {
        $cursosDB = $pdo->query("
            SELECT id, grado, grupo, jornada, sede, ano_lectivo 
            FROM cursos 
            ORDER BY FIELD(sede, 'Sede Principal', 'Sede Victoria'),
                     FIELD(jornada, 'Mañana', 'Tarde', 'Única'), 
                     FIELD(grado, 'Párvulo', 'Prejardín', 'Jardín', 'Transición', '1°', '2°', '3°', '4°', '5°', '6°', '7°', '8°', '9°', '10°', '11°'), 
                     grupo ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $cursosDB = []; }
}

$cursosPorJornada = [
    'Mañana' => [],
    'Tarde'  => []
];
foreach ($cursosDB as $cItem) {
    $jor = (!empty($cItem['jornada']) && strtolower($cItem['jornada']) === 'tarde') ? 'Tarde' : 'Mañana';
    $cursosPorJornada[$jor][] = $cItem;
}

if (!function_exists('renderCursosOptionsByJornada')) {
    function renderCursosOptionsByJornada($cursosInput, $selectedId = null) {
        $html = '';
        $items = isset($cursosInput['Mañana']) ? array_merge($cursosInput['Mañana'], $cursosInput['Tarde'] ?? []) : $cursosInput;
        
        $sedes = [
            'Sede Principal' => ['icono' => '🏢', 'jornadas' => ['Mañana' => [], 'Tarde' => []]],
            'Sede Victoria'  => ['icono' => '🏫', 'jornadas' => ['Mañana' => [], 'Tarde' => []]]
        ];

        foreach ($items as $c) {
            $nomSede = (!empty($c['sede']) && stripos($c['sede'], 'victoria') !== false) ? 'Sede Victoria' : 'Sede Principal';
            $nomJor = (!empty($c['jornada']) && strtolower($c['jornada']) === 'tarde') ? 'Tarde' : 'Mañana';
            $sedes[$nomSede]['jornadas'][$nomJor][] = $c;
        }

        foreach ($sedes as $nomSede => $info) {
            foreach ($info['jornadas'] as $jor => $lista) {
                if (empty($lista)) continue;
                $jorIcon = ($jor === 'Tarde') ? '⛅' : '☀️';
                $html .= '<optgroup label="' . $info['icono'] . ' ' . htmlspecialchars($nomSede) . ' — ' . $jorIcon . ' ' . strtoupper($jor) . ' (' . count($lista) . ' Cursos)">';
                foreach ($lista as $c) {
                    $sel = ($selectedId && intval($c['id']) === intval($selectedId)) ? ' selected' : '';
                    $html .= '<option value="' . $c['id'] . '" data-jornada="' . $jor . '" data-sede="' . htmlspecialchars($c['sede']) . '"' . $sel . '>' . $jorIcon . ' ' . $jor . ' — ' . htmlspecialchars($c['grado'] . ' ' . $c['grupo']) . ' (' . htmlspecialchars($c['sede']) . ' - ' . $c['ano_lectivo'] . ')</option>';
                }
                $html .= '</optgroup>';
            }
        }
        return $html;
    }
}

// Cargar Asignaturas reales desde la BD
$asignaturasDB = [];
if ($pdo) {
    try {
        $asignaturasDB = $pdo->query("SELECT id, nombre_asignatura, codigo FROM asignaturas ORDER BY nombre_asignatura")->fetchAll();
    } catch (Exception $e) { $asignaturasDB = []; }
}

// Lista Oficial de Cursos (fallback si BD no disponible)
$cursosDisponibles = [
    'TRANSICIÓN A', 'TRANSICIÓN B',
    'PRIMERO A (1°A)', 'PRIMERO B (1°B)',
    'SEGUNDO A (2°A)', 'SEGUNDO B (2°B)',
    'TERCERO A (3°A)', 'TERCERO B (3°B)',
    'CUARTO A (4°A)', 'CUARTO B (4°B)',
    'QUINTO A (5°A)', 'QUINTO B (5°B)',
    'SEXTO A (6°A)', 'SEXTO B (6°B)',
    'SÉPTIMO A (7°A)', 'SÉPTIMO B (7°B)',
    'OCTAVO A (8°A)', 'OCTAVO B (8°B)',
    'NOVENO A (9°A)', 'NOVENO B (9°B)',
    'DÉCIMO A (10°A)', 'DÉCIMO B (10°B)',
    'ONCE A (11°A)', 'ONCE B (11°B)'
];

// ============================================================
// DATOS PARA MÓDULO DE REPORTES
// ============================================================

// 1. Reporte de Estudiantes Matriculados
$reporteEstudiantes = [];
if ($pdo) {
    try {
        $reporteEstudiantes = $pdo->query("
            SELECT p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   p.tipo_documento, p.documento_identidad, p.telefono,
                   p.acudiente_nombre, p.acudiente_correo, p.acudiente_telefono,
                   u.correo, u.estado_cuenta,
                   c.grado, c.grupo, c.ano_lectivo,
                   m.codigo_matricula, m.estado_matricula,
                   m.id AS matricula_id, u.id AS usuario_id
            FROM matriculas m
            INNER JOIN usuarios u ON m.estudiante_id = u.id
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            INNER JOIN cursos c ON m.curso_id = c.id
            WHERE m.ano_lectivo = 2026
            ORDER BY c.grado, c.grupo, p.primer_apellido, p.primer_nombre
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $reporteEstudiantes = []; }
}

// 2. Rendimiento Académico por Curso
$reporteRendimiento = [];
if ($pdo) {
    try {
        $reporteRendimiento = $pdo->query("
            SELECT c.grado, c.grupo, c.ano_lectivo,
                   COUNT(DISTINCT m.estudiante_id) AS total_estudiantes,
                   COUNT(DISTINCT cl.matricula_id) AS con_notas,
                   ROUND(AVG(cl.nota_final), 2) AS promedio_general,
                   SUM(CASE WHEN cl.nota_final >= 3.0 THEN 1 ELSE 0 END) AS aprobados,
                   SUM(CASE WHEN cl.nota_final > 0 AND cl.nota_final < 3.0 THEN 1 ELSE 0 END) AS reprobados,
                   SUM(CASE WHEN cl.nota_final >= 4.6 THEN 1 ELSE 0 END) AS superiores
            FROM cursos c
            INNER JOIN matriculas m ON m.curso_id = c.id AND m.ano_lectivo = 2026
            LEFT JOIN calificaciones_logros cl ON cl.matricula_id = m.id AND cl.periodo = 1
            GROUP BY c.id, c.grado, c.grupo, c.ano_lectivo
            ORDER BY c.grado, c.grupo
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $reporteRendimiento = []; }
}

// 3. Situación Financiera por Estudiante
$reporteFinanciero = [];
if ($pdo) {
    try {
        $reporteFinanciero = $pdo->query("
            SELECT p.primer_nombre, p.primer_apellido, p.documento_identidad,
                   u.correo, u.id AS usuario_id,
                   c.grado, c.grupo,
                   COALESCE(SUM(cc.monto), 0) AS total_facturado,
                   COALESCE(SUM(CASE WHEN cc.estado_pago = 'Pagado' THEN cc.monto ELSE 0 END), 0) AS total_pagado,
                   COALESCE(SUM(CASE WHEN cc.estado_pago IN ('Pendiente','Vencido') THEN cc.monto ELSE 0 END), 0) AS total_deuda,
                   COUNT(CASE WHEN cc.estado_pago = 'Pagado' THEN 1 END) AS cuotas_pagadas,
                   COUNT(CASE WHEN cc.estado_pago IN ('Pendiente','Vencido') THEN 1 END) AS cuotas_pendientes,
                   MAX(cc.fecha_vencimiento) AS proxima_fecha
            FROM usuarios u
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            INNER JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            INNER JOIN cursos c ON m.curso_id = c.id
            LEFT JOIN contabilidad_caja cc ON u.id = cc.usuario_id
            WHERE u.rol_id = 5
            GROUP BY u.id, p.primer_nombre, p.primer_apellido, p.documento_identidad, u.correo, c.grado, c.grupo
            ORDER BY total_deuda DESC, p.primer_apellido
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $reporteFinanciero = []; }
}

// 4. KPIs Financieros Globales
$kpiFinanciero = ['total_facturado'=>0, 'total_recaudado'=>0, 'total_pendiente'=>0, 'total_estudiantes_deuda'=>0];
if ($pdo) {
    try {
        $kpiRow = $pdo->query("
            SELECT
                COALESCE(SUM(monto), 0) AS total_facturado,
                COALESCE(SUM(CASE WHEN estado_pago='Pagado' THEN monto ELSE 0 END), 0) AS total_recaudado,
                COALESCE(SUM(CASE WHEN estado_pago IN ('Pendiente','Vencido') THEN monto ELSE 0 END), 0) AS total_pendiente,
                COUNT(DISTINCT CASE WHEN estado_pago IN ('Pendiente','Vencido') THEN usuario_id END) AS total_estudiantes_deuda
            FROM contabilidad_caja
        ")->fetch(PDO::FETCH_ASSOC);
        if ($kpiRow) $kpiFinanciero = $kpiRow;
    } catch (Exception $e) {}
}

// 5. Boletines: lista de estudiantes con matrícula para el generador masivo
$boletinEstudiantes = $reporteEstudiantes; // reutilizar la misma consulta

// Ejecutar migración si se presiona el botón en la consola
$migrationResult = null;
if (isset($_POST['run_etl'])) {
    $etl = new MigrationController();
    $migrationResult = $etl->runMigration();
}

// Procesar POST: Asignación de Materia a Curso, CRUD Usuarios y Aprobaciones
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'assign_course_subject') {
        $curso_id_post      = intval($_POST['curso_id']   ?? 0);
        $asignatura_id_post = intval($_POST['asignatura_id'] ?? 0);
        $docente_id_post    = intval($_POST['docente_id']  ?? 0);
        $ih                 = intval($_POST['ih'] ?? 2);
        $sistema_calif_post = $_POST['sistema_calif'] ?? '1-5';
        if (!in_array($sistema_calif_post, ['1-5', '1-10', 'letras'])) {
            $sistema_calif_post = '1-5';
        }

        if (!$curso_id_post || !$asignatura_id_post || !$docente_id_post) {
            $message = "Por favor seleccione el curso, la asignatura y el docente titular.";
            $messageType = "danger";
        } else {
            $errorMsg = null;
            if ($pdo) {
                try {
                    // Verificar si ya existe esa asignación exacta
                    $stmtCheck = $pdo->prepare("SELECT id FROM curso_asignatura_docente WHERE curso_id=:cid AND asignatura_id=:aid AND docente_id=:did LIMIT 1");
                    $stmtCheck->execute([':cid' => $curso_id_post, ':aid' => $asignatura_id_post, ':did' => $docente_id_post]);
                    if ($stmtCheck->fetch()) {
                        $message = "⚠️ Esta asignación ya existe en el sistema.";
                        $messageType = "warning";
                    } else {
                        $stmtIns = $pdo->prepare("INSERT INTO curso_asignatura_docente (curso_id, asignatura_id, docente_id, sistema_calif) VALUES (:cid, :aid, :did, :sc)");
                        $stmtIns->execute([':cid' => $curso_id_post, ':aid' => $asignatura_id_post, ':did' => $docente_id_post, ':sc' => $sistema_calif_post]);
                        $message = "✅ Materia asignada correctamente al docente con escala institucional fijada.";
                        $messageType = "success";
                    }
                } catch (Exception $e) {
                    $message = "❌ Error al guardar: " . $e->getMessage();
                    $messageType = "danger";
                }
            } else {
                $message = "❌ No hay conexión a la base de datos.";
                $messageType = "danger";
            }
        }

    } elseif ($action === 'delete_cad_item') {
        $cad_id = intval($_POST['cad_id']);
        if ($pdo && $cad_id) {
            try {
                $stmtDel = $pdo->prepare("DELETE FROM curso_asignatura_docente WHERE id = :id");
                $stmtDel->execute([':id' => $cad_id]);
                $message = "✅ Se eliminó la asignación de materia del curso.";
                $messageType = "success";
            } catch (Exception $e) {
                $message = "❌ Error al eliminar: " . $e->getMessage();
                $messageType = "danger";
            }
        }

    } elseif ($action === 'create_user') {
        $rol_id = intval($_POST['rol_id']);
        $correo = trim($_POST['correo']);
        $password = trim($_POST['password']);
        $primer_nombre = trim($_POST['primer_nombre']);
        $segundo_nombre = trim($_POST['segundo_nombre'] ?? '');
        $primer_apellido = trim($_POST['primer_apellido']);
        $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
        $tipo_documento = $_POST['tipo_documento'];
        $documento = trim($_POST['documento_identidad']);
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        // Fix #1: Leer curso seleccionado para Estudiante (rol=5)
        $curso_id_reg = intval($_POST['curso_id'] ?? 1);
        if ($curso_id_reg <= 0) $curso_id_reg = 1;
        // Fix #2: Leer ID del estudiante a vincular para Acudiente (rol=6)
        $estudiante_vinculo_id = intval($_POST['estudiante_vinculo_id'] ?? 0);
        // Datos de Vinculación Obligatoria de Acudiente para Estudiante (rol=5)
        $acudiente_nombre   = trim($_POST['acudiente_nombre'] ?? '');
        $acudiente_correo   = trim($_POST['acudiente_correo'] ?? '');
        $acudiente_telefono = trim($_POST['acudiente_telefono'] ?? '');
        // Sede / Caja asignada para Administrativo / Tesorería (rol=2)
        $sede_caja_post = in_array($_POST['sede_caja'] ?? '', ['principal', 'segunda', 'ambas']) ? $_POST['sede_caja'] : 'ambas';
        
        if (empty($correo) || empty($password) || empty($primer_nombre) || empty($primer_apellido) || empty($documento)) {
            $message = "Por favor complete todos los campos obligatorios del usuario.";
            $messageType = "danger";
        } elseif ($rol_id == 5 && (empty($acudiente_nombre) || empty($acudiente_correo))) {
            $message = "Para matricular a un estudiante es obligatorio ingresar el Nombre completo y el Correo electrónico del acudiente/tutor.";
            $messageType = "danger";
        } else {
            $newId = 1;
            foreach ($_SESSION['demo_users'] as $u) {
                if ($u['id'] >= $newId) $newId = $u['id'] + 1;
            }

            if ($pdo) {
                try {
                    $pdo->beginTransaction();
                    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo");
                    $stmtCheck->execute([':correo' => $correo]);
                    if ($stmtCheck->fetch()) throw new Exception("El correo institucional ya está registrado.");
                    
                    $stmtCheckDoc = $pdo->prepare("SELECT id FROM perfiles_detalles WHERE documento_identidad = :doc");
                    $stmtCheckDoc->execute([':doc' => $documento]);
                    if ($stmtCheckDoc->fetch()) throw new Exception("El documento de identidad ya está registrado.");
                    
                    $passHash = password_hash($password, PASSWORD_BCRYPT);
                    $debeCambiar = (in_array($rol_id, [3, 5]) || $password === 'Salle2026*') ? 1 : 0;
                    $stmtUser = $pdo->prepare("INSERT INTO usuarios (rol_id, correo, password_hash, estado_cuenta, debe_cambiar_password) VALUES (:rol_id, :correo, :pass_hash, 'activo', :debe_cambiar)");
                    $stmtUser->execute([':rol_id' => $rol_id, ':correo' => $correo, ':pass_hash' => $passHash, ':debe_cambiar' => $debeCambiar]);
                    $newUserId = $pdo->lastInsertId();
                    $newId = intval($newUserId);
                    
                    $stmtProfile = $pdo->prepare("INSERT INTO perfiles_detalles (usuario_id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, documento_identidad, telefono, direccion, sede_caja, acudiente_nombre, acudiente_correo, acudiente_telefono) VALUES (:user_id, :p_nom, :s_nom, :p_ape, :s_ape, :tipo_doc, :doc, :tel, :dir, :sc, :anom, :acorreo, :atel)");
                    $stmtProfile->execute([
                        ':user_id' => $newUserId,
                        ':p_nom'   => $primer_nombre,
                        ':s_nom'   => $segundo_nombre,
                        ':p_ape'   => $primer_apellido,
                        ':s_ape'   => $segundo_apellido,
                        ':tipo_doc'=> $tipo_documento,
                        ':doc'     => $documento,
                        ':tel'     => $telefono,
                        ':dir'     => $direccion,
                        ':sc'      => ($rol_id == 2 ? $sede_caja_post : 'ambas'),
                        ':anom'    => ($rol_id == 5 ? $acudiente_nombre : null),
                        ':acorreo' => ($rol_id == 5 ? $acudiente_correo : null),
                        ':atel'    => ($rol_id == 5 ? $acudiente_telefono : null),
                    ]);
                    
                    if ($rol_id == 5) {
                        // Estudiante: matricular en el curso seleccionado
                        $cursoReg  = $curso_id_reg ?: 1;
                        $anoLect   = intval(date('Y'));
                        $codMat    = 'EST-' . $anoLect . '-' . str_pad($newUserId, 5, '0', STR_PAD_LEFT);
                        $stmtMat   = $pdo->prepare("INSERT INTO matriculas (estudiante_id, curso_id, ano_lectivo, estado_matricula, codigo_matricula) VALUES (:est_id, :cur_id, :ano, 'activa', :cod) ON DUPLICATE KEY UPDATE curso_id=:cur_id, estado_matricula='activa'");
                        $stmtMat->execute([':est_id'=>$newUserId, ':cur_id'=>$cursoReg, ':ano'=>$anoLect, ':cod'=>$codMat]);
                    }

                    if ($rol_id == 6 && $estudiante_vinculo_id > 0) {
                        // Acudiente: vincular al estudiante registrando su nombre/tel/correo en el perfil del estudiante
                        $stmtVinc = $pdo->prepare("UPDATE perfiles_detalles SET acudiente_nombre=:anombre, acudiente_correo=:acorreo, acudiente_telefono=:atel WHERE usuario_id=:uid");
                        $stmtVinc->execute([':anombre'=>($primer_nombre.' '.$primer_apellido), ':acorreo'=>$correo, ':atel'=>$telefono, ':uid'=>$estudiante_vinculo_id]);
                    }
                    $pdo->commit();
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = "Error al crear usuario: " . $e->getMessage();
                    $messageType = "danger";
                }
            }

            if (!$message || $messageType !== 'danger') {
                $nombreRol = $rolesMap[$rol_id] ?? 'estudiante';
                $newUser = [
                    'id' => $newId,
                    'correo' => $correo,
                    'password' => $password,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                    'estado_cuenta' => 'activo',
                    'rol_id' => $rol_id,
                    'nombre_rol' => $nombreRol,
                    'primer_nombre' => $primer_nombre,
                    'segundo_nombre' => $segundo_nombre,
                    'primer_apellido' => $primer_apellido,
                    'segundo_apellido' => $segundo_apellido,
                    'tipo_documento' => $tipo_documento,
                    'documento_identidad' => $documento,
                    'telefono' => $telefono,
                    'direccion' => $direccion,
                    'debe_cambiar_password' => $debeCambiar,
                    'ultimo_acceso' => 'Recién creado',
                    'grado' => '10°',
                    'curso' => 'DÉCIMO A'
                ];
                array_unshift($_SESSION['demo_users'], $newUser);

                $message = "✅ Usuario '{$primer_nombre} {$primer_apellido}' registrado exitosamente. Credenciales creadas (Correo: <strong>{$correo}</strong> | Clave: <strong>{$password}</strong>).";
                $messageType = "success";
            }
        }
    } elseif ($action === 'edit_user') {
        $id = intval($_POST['id']);
        $rol_id = intval($_POST['rol_id']);
        $correo = trim($_POST['correo']);
        $password = trim($_POST['password'] ?? '');
        $primer_nombre = trim($_POST['primer_nombre']);
        $segundo_nombre = trim($_POST['segundo_nombre'] ?? '');
        $primer_apellido = trim($_POST['primer_apellido']);
        $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
        $tipo_documento = $_POST['tipo_documento'];
        $documento = trim($_POST['documento_identidad']);
        $telefono = trim($_POST['telefono'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $estado = $_POST['estado_cuenta'];
        // Sede / Caja para Administrativo / Tesorería (rol=2)
        $edit_sede_caja = in_array($_POST['sede_caja'] ?? '', ['principal', 'segunda', 'ambas']) ? $_POST['sede_caja'] : 'ambas';
        // Curso para Estudiante (rol=5)
        $edit_curso_id = intval($_POST['curso_id'] ?? 0);
        $edit_acudiente_nombre   = trim($_POST['acudiente_nombre'] ?? '');
        $edit_acudiente_correo   = trim($_POST['acudiente_correo'] ?? '');
        $edit_acudiente_telefono = trim($_POST['acudiente_telefono'] ?? '');
        
        if (empty($correo) || empty($primer_nombre) || empty($primer_apellido) || empty($documento)) {
            $message = "Por favor complete todos los campos obligatorios.";
            $messageType = "danger";
        } else {
            if ($pdo) {
                try {
                    $pdo->beginTransaction();
                    $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo AND id != :id");
                    $stmtCheck->execute([':correo' => $correo, ':id' => $id]);
                    if ($stmtCheck->fetch()) throw new Exception("El correo institucional ya lo usa otro usuario.");
                    
                    $stmtCheckDoc = $pdo->prepare("SELECT id FROM perfiles_detalles WHERE documento_identidad = :doc AND usuario_id != :id");
                    $stmtCheckDoc->execute([':doc' => $documento, ':id' => $id]);
                    if ($stmtCheckDoc->fetch()) throw new Exception("El documento de identidad ya lo usa otro usuario.");
                    
                    if (!empty($password)) {
                        $passHash = password_hash($password, PASSWORD_BCRYPT);
                        $pdo->prepare("UPDATE usuarios SET rol_id=:rol_id, correo=:correo, password_hash=:pass, estado_cuenta=:estado WHERE id=:id")->execute([':rol_id'=>$rol_id,':correo'=>$correo,':pass'=>$passHash,':estado'=>$estado,':id'=>$id]);
                    } else {
                        $pdo->prepare("UPDATE usuarios SET rol_id=:rol_id, correo=:correo, estado_cuenta=:estado WHERE id=:id")->execute([':rol_id'=>$rol_id,':correo'=>$correo,':estado'=>$estado,':id'=>$id]);
                    }
                    
                    if ($rol_id == 5) {
                        $pdo->prepare("UPDATE perfiles_detalles SET primer_nombre=:p_nom, segundo_nombre=:s_nom, primer_apellido=:p_ape, segundo_apellido=:s_ape, tipo_documento=:tipo_doc, documento_identidad=:doc, telefono=:tel, direccion=:dir, acudiente_nombre=:anom, acudiente_correo=:acorreo, acudiente_telefono=:atel WHERE usuario_id=:id")->execute([':p_nom'=>$primer_nombre,':s_nom'=>$segundo_nombre,':p_ape'=>$primer_apellido,':s_ape'=>$segundo_apellido,':tipo_doc'=>$tipo_documento,':doc'=>$documento,':tel'=>$telefono,':dir'=>$direccion,':anom'=>$edit_acudiente_nombre,':acorreo'=>$edit_acudiente_correo,':atel'=>$edit_acudiente_telefono,':id'=>$id]);
                    } elseif ($rol_id == 2) {
                        $pdo->prepare("UPDATE perfiles_detalles SET primer_nombre=:p_nom, segundo_nombre=:s_nom, primer_apellido=:p_ape, segundo_apellido=:s_ape, tipo_documento=:tipo_doc, documento_identidad=:doc, telefono=:tel, direccion=:dir, sede_caja=:sc WHERE usuario_id=:id")->execute([':p_nom'=>$primer_nombre,':s_nom'=>$segundo_nombre,':p_ape'=>$primer_apellido,':s_ape'=>$segundo_apellido,':tipo_doc'=>$tipo_documento,':doc'=>$documento,':tel'=>$telefono,':dir'=>$direccion,':sc'=>$edit_sede_caja,':id'=>$id]);
                    } else {
                        $pdo->prepare("UPDATE perfiles_detalles SET primer_nombre=:p_nom, segundo_nombre=:s_nom, primer_apellido=:p_ape, segundo_apellido=:s_ape, tipo_documento=:tipo_doc, documento_identidad=:doc, telefono=:tel, direccion=:dir WHERE usuario_id=:id")->execute([':p_nom'=>$primer_nombre,':s_nom'=>$segundo_nombre,':p_ape'=>$primer_apellido,':s_ape'=>$segundo_apellido,':tipo_doc'=>$tipo_documento,':doc'=>$documento,':tel'=>$telefono,':dir'=>$direccion,':id'=>$id]);
                    }
                    // Upsert matrícula si el usuario es Estudiante
                    if ($rol_id == 5 && $edit_curso_id > 0) {
                        $anoLect = intval(date('Y'));
                        $codMat  = 'EST-' . $anoLect . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
                        $pdo->prepare("INSERT INTO matriculas (estudiante_id, curso_id, ano_lectivo, estado_matricula, codigo_matricula) VALUES (:est_id, :cur_id, :ano, 'activa', :cod) ON DUPLICATE KEY UPDATE curso_id=:cur_id, estado_matricula='activa'")->execute([':est_id'=>$id, ':cur_id'=>$edit_curso_id, ':ano'=>$anoLect, ':cod'=>$codMat]);
                    }
                    $pdo->commit();
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = "Error al actualizar usuario: " . $e->getMessage();
                    $messageType = "danger";
                }
            }

            if (!$message || $messageType !== 'danger') {
                $nombreRol = $rolesMap[$rol_id] ?? 'estudiante';
                foreach ($_SESSION['demo_users'] as &$u) {
                    if ($u['id'] == $id) {
                        $u['rol_id'] = $rol_id;
                        $u['nombre_rol'] = $nombreRol;
                        $u['correo'] = $correo;
                        if (!empty($password)) {
                            $u['password'] = $password;
                            $u['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
                        }
                        $u['primer_nombre'] = $primer_nombre;
                        $u['segundo_nombre'] = $segundo_nombre;
                        $u['primer_apellido'] = $primer_apellido;
                        $u['segundo_apellido'] = $segundo_apellido;
                        $u['tipo_documento'] = $tipo_documento;
                        $u['documento_identidad'] = $documento;
                        $u['telefono'] = $telefono;
                        $u['direccion'] = $direccion;
                        $u['estado_cuenta'] = $estado;
                        $u['sede_caja'] = ($rol_id == 2 ? $edit_sede_caja : 'ambas');
                        break;
                    }
                }
                unset($u);

                $message = "✅ Datos de '{$primer_nombre} {$primer_apellido}' actualizados correctamente.";
                $messageType = "success";
            }
        }
    } elseif ($action === 'change_user_role') {
        $targetId = intval($_POST['user_id'] ?? 0);
        $newRolId = intval($_POST['new_rol_id'] ?? 0);
        if ($targetId > 0 && isset($rolesMap[$newRolId])) {
            $rolNombreNuevo = $rolesMap[$newRolId];
            if ($pdo) {
                try {
                    $pdo->prepare("UPDATE usuarios SET rol_id = :rid WHERE id = :id")->execute([':rid' => $newRolId, ':id' => $targetId]);
                    $message = "✅ Rol de usuario actualizado a <strong>" . strtoupper($rolNombreNuevo) . "</strong> con éxito.";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Error al actualizar rol: " . $e->getMessage();
                    $messageType = "danger";
                }
            }
            foreach ($_SESSION['demo_users'] as &$u) {
                if ($u['id'] == $targetId) {
                    $u['rol_id'] = $newRolId;
                    $u['nombre_rol'] = $rolNombreNuevo;
                    break;
                }
            }
            unset($u);
            if (!$message) {
                $message = "✅ Rol de usuario actualizado a <strong>" . strtoupper($rolNombreNuevo) . "</strong> con éxito.";
                $messageType = "success";
            }
        }
    } elseif ($action === 'guardar_periodo_evaluacion') {
        $titulo_eval  = trim($_POST['titulo_evaluacion'] ?? 'Evaluación Docente 2026');
        $fecha_ini    = !empty($_POST['fecha_inicio']) ? str_replace('T', ' ', $_POST['fecha_inicio']) : date('Y-m-d H:i:s');
        $fecha_fin    = !empty($_POST['fecha_fin']) ? str_replace('T', ' ', $_POST['fecha_fin']) : date('Y-m-d 23:59:59', strtotime('+30 days'));
        $estado_eval  = in_array($_POST['estado_evaluacion'] ?? '', ['activo', 'inactivo', 'cerrado']) ? $_POST['estado_evaluacion'] : 'activo';
        $indicaciones = trim($_POST['indicaciones'] ?? '');
        $periodo_id   = intval($_POST['periodo_id'] ?? 0);

        if ($pdo) {
            try {
                if ($periodo_id > 0) {
                    $stmtEv = $pdo->prepare("UPDATE evaluacion_docente_periodos SET titulo=:tit, fecha_inicio=:ini, fecha_fin=:fin, estado=:est, indicaciones=:ind WHERE id=:id");
                    $stmtEv->execute([':tit'=>$titulo_eval, ':ini'=>$fecha_ini, ':fin'=>$fecha_fin, ':est'=>$estado_eval, ':ind'=>$indicaciones, ':id'=>$periodo_id]);
                } else {
                    $stmtEv = $pdo->prepare("INSERT INTO evaluacion_docente_periodos (titulo, fecha_inicio, fecha_fin, estado, indicaciones, ano_lectivo, periodo_academico) VALUES (:tit, :ini, :fin, :est, :ind, 2026, 1)");
                    $stmtEv->execute([':tit'=>$titulo_eval, ':ini'=>$fecha_ini, ':fin'=>$fecha_fin, ':est'=>$estado_eval, ':ind'=>$indicaciones]);
                }
                $message = "✅ Período de Evaluación Docente configurado con éxito. Estado: <strong>" . strtoupper($estado_eval) . "</strong> (Disponible desde {$fecha_ini} hasta {$fecha_fin}).";
                $messageType = "success";
            } catch (Exception $e) {
                $message = "Error al programar período de evaluación: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id']);
        $estado_actual = $_POST['estado_actual'];
        $nuevo_estado = $estado_actual === 'activo' ? 'suspendido' : 'activo';
        if ($pdo) {
            try {
                $pdo->prepare("UPDATE usuarios SET estado_cuenta = :estado WHERE id = :id")->execute([':estado' => $nuevo_estado, ':id' => $id]);
            } catch (Exception $e) {}
        }
        foreach ($_SESSION['demo_users'] as &$u) {
            if ($u['id'] == $id) {
                $u['estado_cuenta'] = $nuevo_estado;
                break;
            }
        }
        unset($u);

        $message = "✅ Estado del usuario cambiado a '{$nuevo_estado}' exitosamente.";
        $messageType = "success";

    } elseif ($action === 'bulk_create_users') {
        $defaultRolId = intval($_POST['default_rol_id'] ?? 5);
        $defaultCursoId = intval($_POST['default_curso_id'] ?? 0);
        $importer = new UserBulkImporter($pdo);
        $result = null;

        if (isset($_FILES['bulk_file']) && $_FILES['bulk_file']['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($_FILES['bulk_file']['tmp_name']);
            $result = $importer->processRawText($fileContent, $defaultRolId, $defaultCursoId);
        } elseif (!empty($_POST['bulk_text'])) {
            $result = $importer->processRawText($_POST['bulk_text'], $defaultRolId, $defaultCursoId);
        }

        if ($result && ($result['created'] > 0 || ($result['updated'] ?? 0) > 0)) {
            $msgParts = [];
            if ($result['created'] > 0) $msgParts[] = "<strong>{$result['created']}</strong> nuevos usuarios registrados";
            if (!empty($result['updated'])) $msgParts[] = "<strong>{$result['updated']}</strong> usuarios existentes actualizados con sus datos de acudiente / matrícula";
            $msgAcudiente = ($result['con_acudiente'] > 0) ? " (<strong>{$result['con_acudiente']}</strong> con acudiente vinculado)." : "";
            $msgOmitidos = ($result['skipped'] > 0) ? " ({$result['skipped']} sin cambios)." : "";
            $message = "✅ <strong>Carga Masiva Exitosa:</strong> " . implode(', ', $msgParts) . "{$msgAcudiente}{$msgOmitidos}";
            $messageType = "success";
        } elseif ($result && $result['skipped'] > 0) {
            $message = "⚠️ No se realizaron modificaciones porque los <strong>{$result['skipped']}</strong> usuarios ya se encontraban registrados con información idéntica.";
            $messageType = "warning";
        } else {
            $err = $result['message'] ?? 'Verifique el formato o contenido de su archivo o lista.';
            $message = "⚠️ {$err}";
            $messageType = "warning";
        }

    
    }
}

// Cargar usuarios de BD
$usersList = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.correo, u.estado_cuenta, u.rol_id, r.nombre_rol, 
                   p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   p.tipo_documento, p.documento_identidad, p.telefono, p.direccion,
                   p.acudiente_nombre, p.acudiente_correo, p.acudiente_telefono, p.firma_digital,
                   p.sede_caja,
                   c.id AS curso_id, c.grado, c.grupo
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            LEFT JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            ORDER BY u.id DESC
        ");
        $usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $usersList = []; }
}

// Cargar período de evaluación docente y estadísticas para gráficos dinámicos
$evalPeriodoActivo = null;
$evalDocentesStats = [];
$evalCriteriosPromedio = ['dominio' => 0, 'metodologia' => 0, 'respeto' => 0, 'evaluacion' => 0, 'motivacion' => 0];
$evalDistribucion = ['excelente' => 0, 'sobresaliente' => 0, 'aceptable' => 0, 'insuficiente' => 0];
$totalEvaluaciones = 0;
$promedioInstitucional = 0;
$evalComentarios = [];

if ($pdo) {
    try {
        $stmtPer = $pdo->query("SELECT * FROM evaluacion_docente_periodos ORDER BY id DESC LIMIT 1");
        $evalPeriodoActivo = $stmtPer->fetch(PDO::FETCH_ASSOC);

        if ($evalPeriodoActivo) {
            $perId = intval($evalPeriodoActivo['id']);

            $stmtDocEval = $pdo->prepare("
                SELECT ed.docente_id,
                       p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                       u.correo,
                       GROUP_CONCAT(DISTINCT a.nombre_asignatura SEPARATOR ', ') AS asignaturas,
                       COUNT(ed.id) AS total_evaluaciones,
                       ROUND(AVG(ed.puntaje_promedio), 2) AS promedio_general,
                       ROUND(AVG(ed.criterio_dominio), 2) AS prom_dominio,
                       ROUND(AVG(ed.criterio_metodologia), 2) AS prom_metodologia,
                       ROUND(AVG(ed.criterio_respeto), 2) AS prom_respeto,
                       ROUND(AVG(ed.criterio_evaluacion), 2) AS prom_evaluacion,
                       ROUND(AVG(ed.criterio_motivacion), 2) AS prom_motivacion
                FROM evaluaciones_docentes ed
                INNER JOIN usuarios u ON ed.docente_id = u.id
                INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                LEFT JOIN curso_asignatura_docente cad ON cad.docente_id = u.id
                LEFT JOIN asignaturas a ON cad.asignatura_id = a.id
                WHERE ed.periodo_id = :pid
                GROUP BY ed.docente_id, p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido, u.correo
                ORDER BY promedio_general DESC
            ");
            $stmtDocEval->execute([':pid' => $perId]);
            $evalDocentesStats = $stmtDocEval->fetchAll(PDO::FETCH_ASSOC);

            $stmtComms = $pdo->prepare("
                SELECT ed.docente_id, ed.puntaje_promedio, ed.comentarios, ed.created_at,
                       c.grado, c.grupo, c.jornada,
                       p.primer_nombre AS doc_nombre, p.primer_apellido AS doc_apellido
                FROM evaluaciones_docentes ed
                LEFT JOIN cursos c ON ed.curso_id = c.id
                INNER JOIN usuarios u ON ed.docente_id = u.id
                INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                WHERE ed.periodo_id = :pid AND ed.comentarios IS NOT NULL AND TRIM(ed.comentarios) != ''
                ORDER BY ed.id DESC
            ");
            $stmtComms->execute([':pid' => $perId]);
            $evalComentarios = $stmtComms->fetchAll(PDO::FETCH_ASSOC);

            $stmtTot = $pdo->prepare("
                SELECT COUNT(*) AS total_evals,
                       ROUND(AVG(puntaje_promedio), 2) AS prom_global,
                       ROUND(AVG(criterio_dominio), 2) AS avg_dom,
                       ROUND(AVG(criterio_metodologia), 2) AS avg_met,
                       ROUND(AVG(criterio_respeto), 2) AS avg_resp,
                       ROUND(AVG(criterio_evaluacion), 2) AS avg_eval,
                       ROUND(AVG(criterio_motivacion), 2) AS avg_mot
                FROM evaluaciones_docentes
                WHERE periodo_id = :pid
            ");
            $stmtTot->execute([':pid' => $perId]);
            $glob = $stmtTot->fetch(PDO::FETCH_ASSOC);
            if ($glob && $glob['total_evals'] > 0) {
                $totalEvaluaciones = intval($glob['total_evals']);
                $promedioInstitucional = floatval($glob['prom_global']);
                $evalCriteriosPromedio = [
                    'dominio'     => floatval($glob['avg_dom']),
                    'metodologia' => floatval($glob['avg_met']),
                    'respeto'     => floatval($glob['avg_resp']),
                    'evaluacion'  => floatval($glob['avg_eval']),
                    'motivacion'  => floatval($glob['avg_mot'])
                ];
            }

            $stmtDist = $pdo->prepare("
                SELECT 
                    SUM(CASE WHEN puntaje_promedio >= 4.5 THEN 1 ELSE 0 END) AS excelente,
                    SUM(CASE WHEN puntaje_promedio >= 4.0 AND puntaje_promedio < 4.5 THEN 1 ELSE 0 END) AS sobresaliente,
                    SUM(CASE WHEN puntaje_promedio >= 3.0 AND puntaje_promedio < 4.0 THEN 1 ELSE 0 END) AS aceptable,
                    SUM(CASE WHEN puntaje_promedio < 3.0 THEN 1 ELSE 0 END) AS insuficiente
                FROM evaluaciones_docentes
                WHERE periodo_id = :pid
            ");
            $stmtDist->execute([':pid' => $perId]);
            $distRow = $stmtDist->fetch(PDO::FETCH_ASSOC);
            if ($distRow) {
                $evalDistribucion = [
                    'excelente'     => intval($distRow['excelente'] ?? 0),
                    'sobresaliente' => intval($distRow['sobresaliente'] ?? 0),
                    'aceptable'     => intval($distRow['aceptable'] ?? 0),
                    'insuficiente'  => intval($distRow['insuficiente'] ?? 0)
                ];
            }
        }
    } catch (Exception $e) {}
}

if (empty($usersList)) {
    $usersList = $_SESSION['demo_users'];
}

// Filtrar Estudiantes para el Gestor de Boletines
$estudiantesList = array_values(array_filter($usersList, function($u) {
    return strtolower($u['nombre_rol']) === 'estudiante';
}));

// Filtrar Docentes para Asignación de Materias
$docentesActivosList = array_values(array_filter($usersList, function($u) {
    return strtolower($u['nombre_rol']) === 'docente' || strtolower($u['nombre_rol']) === 'administrador';
}));

// Cargar planes de clase
$planesList = [];
if ($pdo) {
    try {
        $stmtPlanes = $pdo->query("
            SELECT pc.*, p.primer_nombre, p.primer_apellido, p.firma_digital, a.nombre_asignatura, c.grado, c.grupo
            FROM planes_clase pc
            INNER JOIN usuarios u ON pc.docente_id = u.id
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            INNER JOIN asignaturas a ON pc.asignatura_id = a.id
            INNER JOIN cursos c ON pc.curso_id = c.id
            ORDER BY pc.id DESC
        ");
        $planesList = $stmtPlanes->fetchAll();
    } catch (Exception $e) { $planesList = []; }
}

if (empty($planesList)) {
    $planesList = [
        ['id' => 1, 'primer_nombre' => 'Carlos', 'primer_apellido' => 'García', 'nombre_asignatura' => 'Matemáticas Avanzadas', 'grado' => '10°', 'grupo' => 'A', 'semana_numero' => 1, 'periodo' => 1, 'tema_principal' => 'Introducción al Álgebra Lineal y Matrices', 'objetivos_aprendizaje' => 'Operar matrices m×n.', 'contenido_pei' => 'Clase magistral y trabajo colaborativo.', 'estado_aprobacion' => 'Aprobado'],
        ['id' => 2, 'primer_nombre' => 'Carlos', 'primer_apellido' => 'García', 'nombre_asignatura' => 'Lengua Castellana y Literatura', 'grado' => '10°', 'grupo' => 'A', 'semana_numero' => 2, 'periodo' => 1, 'tema_principal' => 'Análisis Literario: Realismo Mágico', 'objetivos_aprendizaje' => 'Analizar elementos narrativos.', 'contenido_pei' => 'Lectura guiada y debate.', 'estado_aprobacion' => 'Enviado'],
    ];
}
// Cargar asignaciones CAD desde la BD real
$cadList = [];
if ($pdo) {
    try {
        $stmtCAD = $pdo->query("
            SELECT cad.id,
                   CONCAT(c.grado, ' ', c.grupo, ' (', c.ano_lectivo, ')') AS curso_nombre,
                   c.grado, c.grupo,
                   a.nombre_asignatura AS asignatura_nombre,
                   CONCAT(p.primer_nombre, ' ', p.primer_apellido) AS docente_nombre,
                   cad.sistema_calif,
                   2 AS ih
            FROM curso_asignatura_docente cad
            INNER JOIN cursos c ON cad.curso_id = c.id
            INNER JOIN asignaturas a ON cad.asignatura_id = a.id
            INNER JOIN perfiles_detalles p ON cad.docente_id = p.usuario_id
            ORDER BY c.grado, c.grupo, a.nombre_asignatura
        ");
        $cadList = $stmtCAD->fetchAll();
    } catch (Exception $e) { $cadList = []; }
}

// Cargar Reportes Financieros para Consulta del Administrador (Solo Lectura)
$kpiFinanciero = [
    'total_facturado' => 0,
    'total_recaudado' => 0,
    'total_egresos'   => 0,
    'saldo_neto'      => 0,
    'total_pendiente' => 0,
    'total_estudiantes_deuda' => 0
];
$reporteFinanciero = [];

if ($pdo) {
    try {
        $stmtTot = $pdo->query("
            SELECT 
                COALESCE(SUM(monto), 0) AS total_facturado,
                COALESCE(SUM(CASE WHEN estado_pago = 'Pagado' THEN monto ELSE 0 END), 0) AS total_recaudado,
                COALESCE(SUM(CASE WHEN estado_pago = 'Pendiente' THEN monto ELSE 0 END), 0) AS total_pendiente,
                COUNT(DISTINCT CASE WHEN estado_pago = 'Pendiente' THEN usuario_id ELSE NULL END) AS total_estudiantes_deuda
            FROM contabilidad_caja
        ");
        $rowTot = $stmtTot->fetch();
        if ($rowTot) {
            $kpiFinanciero['total_facturado'] = floatval($rowTot['total_facturado']);
            $kpiFinanciero['total_recaudado'] = floatval($rowTot['total_recaudado']);
            $kpiFinanciero['total_pendiente'] = floatval($rowTot['total_pendiente']);
            $kpiFinanciero['total_estudiantes_deuda'] = intval($rowTot['total_estudiantes_deuda']);
        }
        
        $totEg = $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM contabilidad_egresos")->fetchColumn();
        $kpiFinanciero['total_egresos'] = floatval($totEg ?: 0);
        $kpiFinanciero['saldo_neto'] = $kpiFinanciero['total_recaudado'] - $kpiFinanciero['total_egresos'];

        // Detalle por estudiante
        $stmtRepFin = $pdo->query("
            SELECT 
                u.id AS usuario_id,
                p.primer_nombre, p.primer_apellido, p.documento_identidad,
                c.grado, c.grupo,
                COALESCE(SUM(cc.monto), 0) AS total_facturado,
                COALESCE(SUM(CASE WHEN cc.estado_pago = 'Pagado' THEN cc.monto ELSE 0 END), 0) AS total_pagado,
                COALESCE(SUM(CASE WHEN cc.estado_pago = 'Pendiente' THEN cc.monto ELSE 0 END), 0) AS total_deuda,
                COUNT(CASE WHEN cc.estado_pago = 'Pagado' THEN cc.id ELSE NULL END) AS cuotas_pagadas,
                COUNT(CASE WHEN cc.estado_pago = 'Pendiente' THEN cc.id ELSE NULL END) AS cuotas_pendientes
            FROM usuarios u
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            LEFT JOIN contabilidad_caja cc ON u.id = cc.usuario_id
            WHERE u.rol_id = 5
            GROUP BY u.id, p.primer_nombre, p.primer_apellido, p.documento_identidad, c.grado, c.grupo
            ORDER BY total_deuda DESC, c.grado, p.primer_apellido
        ");
        $reporteFinanciero = $stmtRepFin->fetchAll();
    } catch (Exception $e) {}
}

// Cargar Solicitudes y Emisión de Constancias de Estudio Oficiales
$constanciasList = [];
if ($pdo) {
    try {
        $stmtConst = $pdo->query("
            SELECT cc.*, p.primer_nombre, p.primer_apellido, p.segundo_nombre, p.segundo_apellido,
                   p.documento_identidad, p.tipo_documento, c.grado, c.grupo
            FROM contabilidad_caja cc
            INNER JOIN usuarios u ON cc.usuario_id = u.id
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            WHERE cc.concepto_pago = 'Constancia'
            ORDER BY cc.id DESC
        ");
        $constanciasList = $stmtConst->fetchAll();
    } catch (Exception $e) { $constanciasList = []; }
}

// Cargar Solicitudes de Admisión y Aspirantes
$solicitudesAdmision = [];
$totalPendientesAdmision = 0;
$totalAprobadasAdmision = 0;
$totalMatriculadosAdmision = 0;
$totalRechazadasAdmision = 0;
$listaCursosDisponibles = [];

if ($pdo) {
    try {
        $stmtSol = $pdo->query("
            SELECT s.*, c.grado AS curso_grado, c.grupo AS curso_grupo,
                   cc.recibo_numero, cc.estado_pago AS recibo_estado,
                   m.codigo_matricula, u.correo AS usuario_correo
            FROM solicitudes_admision s
            LEFT JOIN cursos c ON s.curso_asignado_id = c.id
            LEFT JOIN contabilidad_caja cc ON s.recibo_caja_id = cc.id
            LEFT JOIN matriculas m ON s.matricula_id = m.id
            LEFT JOIN usuarios u ON s.usuario_estudiante_id = u.id
            ORDER BY s.id DESC
        ");
        $solicitudesAdmision = $stmtSol->fetchAll(PDO::FETCH_ASSOC);

        foreach ($solicitudesAdmision as $s) {
            if ($s['estado'] === 'Pendiente') $totalPendientesAdmision++;
            elseif ($s['estado'] === 'Aprobada') $totalAprobadasAdmision++;
            elseif ($s['estado'] === 'Matriculado') $totalMatriculadosAdmision++;
            elseif ($s['estado'] === 'Rechazada') $totalRechazadasAdmision++;
        }

        $listaCursosDisponibles = $pdo->query("SELECT * FROM cursos ORDER BY grado, grupo")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Cargar Estudiantes y Docentes para el Centro Digital de Certificados
$estudiantesCertificados = [];
$docentesCertificados = [];
$calificacionesPorEstudiante = [];
if ($pdo) {
    try {
        $estudiantesCertificados = $pdo->query("
            SELECT u.id, u.correo,
                   p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                   p.tipo_documento, p.documento_identidad, p.telefono, p.direccion,
                   p.acudiente_nombre, p.acudiente_telefono, p.acudiente_correo,
                   COALESCE(c.grado, '11°') AS grado,
                   COALESCE(c.grupo, 'A') AS grupo,
                   COALESCE(m.ano_lectivo, 2026) AS ano_lectivo,
                   COALESCE(m.id, 0) AS matricula_id,
                   COALESCE(m.codigo_matricula, CONCAT('EST-2026-', LPAD(u.id, 5, '0'))) AS codigo_matricula
            FROM usuarios u
            INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
            LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026
            LEFT JOIN cursos c ON m.curso_id = c.id
            WHERE u.rol_id = 5
            ORDER BY p.primer_apellido, p.primer_nombre
        ")->fetchAll(PDO::FETCH_ASSOC);

        try {
            $docentesCertificados = $pdo->query("
                SELECT u.id, u.correo,
                       p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
                       p.tipo_documento, p.documento_identidad, p.telefono, p.direccion
                FROM usuarios u
                INNER JOIN perfiles_detalles p ON u.id = p.usuario_id
                WHERE u.rol_id = 3
                ORDER BY p.primer_apellido, p.primer_nombre
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error cargando docentes para certificados: " . $e->getMessage());
        }

        if (empty($docentesCertificados) && !empty($docentesActivosList)) {
            $docentesCertificados = $docentesActivosList;
        }
        if (empty($estudiantesCertificados) && !empty($estudiantesList)) {
            $estudiantesCertificados = $estudiantesList;
        }

        // Cargar Calificaciones Oficiales Subidas por Docentes
        try {
            $stmtCalif = $pdo->query("
                SELECT cl.id, cl.matricula_id, m.estudiante_id,
                       cl.asignatura_id, a.nombre_asignatura, a.codigo AS asignatura_codigo,
                       CASE 
                           WHEN a.nombre_asignatura LIKE '%MATEMÁTICA%' OR a.nombre_asignatura LIKE '%TRIGONOMETR%' THEN 5
                           WHEN a.nombre_asignatura LIKE '%LENGUA%' OR a.nombre_asignatura LIKE '%CASTELLANA%' THEN 5
                           WHEN a.nombre_asignatura LIKE '%CIENCIAS NATURALES%' OR a.nombre_asignatura LIKE '%QUÍMICA%' OR a.nombre_asignatura LIKE '%FÍSICA%' THEN 4
                           WHEN a.nombre_asignatura LIKE '%CIENCIAS SOCIALES%' THEN 4
                           WHEN a.nombre_asignatura LIKE '%INGLÉS%' OR a.nombre_asignatura LIKE '%EXTRANJERO%' THEN 3
                           WHEN a.nombre_asignatura LIKE '%EDUCACIÓN FÍSICA%' THEN 2
                           WHEN a.nombre_asignatura LIKE '%INFORMÁTICA%' OR a.nombre_asignatura LIKE '%TECNOLOGÍA%' THEN 2
                           ELSE 2
                       END AS ih,
                       cl.periodo, cl.nota_final, cl.logros_observaciones
                FROM calificaciones_logros cl
                INNER JOIN matriculas m ON cl.matricula_id = m.id
                INNER JOIN asignaturas a ON cl.asignatura_id = a.id
                WHERE cl.nota_final IS NOT NULL AND cl.nota_final > 0
                ORDER BY m.estudiante_id, cl.periodo, a.nombre_asignatura
            ");
            $allCalif = $stmtCalif ? $stmtCalif->fetchAll(PDO::FETCH_ASSOC) : [];

            foreach ($allCalif as $c) {
                $estId = $c['estudiante_id'];
                if (!isset($calificacionesPorEstudiante[$estId])) {
                    $calificacionesPorEstudiante[$estId] = [];
                }
                $calificacionesPorEstudiante[$estId][] = $c;
            }
        } catch (Exception $e) {
            error_log("Error cargando calificaciones para certificados: " . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Error general en carga de certificados: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consola Administrativa Global - AulaVirtual Salle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/custom.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .admin-module-section {
            display: none;
            animation: fadeIn 0.25s ease-in-out;
        }
        .admin-module-section.active {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .nav-module-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            height: 100%;
        }
        .nav-module-card:hover {
            transform: translateY(-3px);
            border-color: var(--lasalle-blue);
            box-shadow: var(--card-shadow);
        }
        .nav-module-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        .sub-category-badge {
            background: rgba(30, 58, 138, 0.08);
            color: #1E3A8A;
            border: 1px solid rgba(30, 58, 138, 0.15);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s;
            cursor: pointer;
        }
        .sub-category-badge:hover {
            background: #1E3A8A;
            color: #fff;
        }
        .filter-bar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.25rem;
        }
        .module-header-box {
            background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%) !important;
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            color: white;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
        }
        .btn-filtro-adm {
            background: #F8FAFC;
            color: #475569;
            border: 1px solid #CBD5E1;
            font-size: 0.82rem;
            transition: all 0.2s ease;
        }
        .btn-filtro-adm:hover {
            background: #E2E8F0;
            color: #0F172A;
            border-color: #94A3B8;
        }
        .btn-filtro-adm.active {
            background: #1E3A8A !important;
            color: #FFFFFF !important;
            border-color: #1E3A8A !important;
            box-shadow: 0 3px 10px rgba(30, 58, 138, 0.3) !important;
        }
        .btn-filtro-adm.active .badge {
            background: #FFFFFF !important;
            color: #1E3A8A !important;
        }
        .kpi-admision-card {
            background: #FFFFFF;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            padding: 1.25rem 1.25rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .kpi-admision-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }
        .kpi-icon-adm {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 50px;
            text-transform: uppercase;
        }
        .stat-badge-active { background: #DCFCE7; color: #166534; }
        .stat-badge-suspended { background: #FEE2E2; color: #991B1B; }
        .stat-badge-pending { background: #FEF3C7; color: #92400E; }
        .stat-badge-info { background: #DBEAFE; color: #1E40AF; }

        /* Estilos del Consola de Boletines */
        .boletin-panel-header {
            background: #1E3A8A;
            color: #fff;
            padding: 10px 16px;
            font-weight: 800;
            font-size: 0.9rem;
            border-radius: 8px 8px 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .boletin-sidebar-card {
            border: 1px solid #1E3A8A;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            margin-bottom: 1rem;
        }
        .boletin-body-pad {
            padding: 14px;
        }
        .form-label-compact {
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 3px;
        }

        /* =======================================================
           CENTRO DE CERTIFICADOS DIGITALES OFICIALES LA SALLE
           ======================================================= */
        .cert-nav-pill {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 10px;
            padding: 0.65rem 1.1rem;
            font-weight: 600;
            font-size: 0.85rem;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .cert-nav-pill:hover {
            background: #f1f5f9;
            color: #0b3c68;
            border-color: #cbd5e1;
        }
        .cert-nav-pill.active {
            background: linear-gradient(135deg, #0b3c68 0%, #1e40af 100%) !important;
            color: #ffffff !important;
            border-color: #0b3c68 !important;
            box-shadow: 0 4px 12px rgba(11, 60, 104, 0.25) !important;
        }
        .cert-paper-container {
            background: #e2e8f0;
            padding: 2rem 1rem;
            border-radius: 16px;
            display: flex;
            justify-content: center;
            overflow-x: auto;
        }
        .cert-paper-sheet {
            background: #ffffff;
            width: 100%;
            max-width: 820px;
            min-height: 1080px;
            padding: 50px 58px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.04);
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            position: relative;
            font-family: 'Times New Roman', Times, serif;
            color: #0f172a;
            line-height: 1.65;
        }
        .cert-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 360px;
            height: 360px;
            opacity: 0.04;
            pointer-events: none;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            z-index: 0;
        }
        .cert-content {
            position: relative;
            z-index: 1;
        }
        .cert-header {
            text-align: center;
            border-bottom: 2px solid #0b3c68;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .cert-header .cert-school-name {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0b3c68;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .cert-header .cert-school-sub {
            font-size: 1.05rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 2px;
        }
        .cert-header .cert-motto {
            font-style: italic;
            font-size: 0.95rem;
            color: #475569;
            margin-bottom: 4px;
        }
        .cert-header .cert-resol {
            font-size: 0.76rem;
            color: #64748b;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .cert-doc-title {
            font-size: 1.25rem;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            color: #0b3c68;
            margin: 22px 0 14px 0;
            letter-spacing: 0.5px;
        }
        .cert-doc-proclamation {
            font-size: 1.05rem;
            font-weight: 700;
            text-align: center;
            color: #0f172a;
            margin-bottom: 22px;
        }
        .cert-body-p {
            font-size: 1.03rem;
            text-align: justify;
            text-justify: inter-word;
            margin-bottom: 18px;
            line-height: 1.75;
        }
        .cert-highlight {
            font-weight: 700;
            color: #0f172a;
            background: #eff6ff;
            padding: 1px 4px;
            border-radius: 3px;
            border-bottom: 1px dashed #93c5fd;
        }
        .cert-table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
            font-size: 0.88rem;
            font-family: system-ui, -apple-system, sans-serif;
        }
        .cert-table th {
            background-color: #0b3c68;
            color: #ffffff;
            padding: 8px 10px;
            font-weight: 600;
            text-align: left;
            border: 1px solid #0b3c68;
        }
        .cert-table td {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            color: #334155;
        }
        .cert-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .cert-signature-area {
            margin-top: 48px;
            text-align: center;
        }
        .cert-signature-line {
            width: 260px;
            border-top: 1px solid #1e293b;
            margin: 0 auto 8px auto;
        }
        .cert-rector-name {
            font-weight: 700;
            font-size: 1.02rem;
            color: #0f172a;
        }
        .cert-rector-cargo {
            font-size: 0.9rem;
            color: #475569;
        }

        /* =======================================================
           CONFIGURACIÓN DE IMPRESIÓN OFICIAL (1 SOLA PÁGINA)
           ======================================================= */
        @page {
            size: letter portrait;
            margin: 8mm 12mm 8mm 12mm;
        }
        @media print {
            html, body {
                height: auto !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            body * {
                visibility: hidden !important;
            }
            #cert_digital_paper, #cert_digital_paper * {
                visibility: visible !important;
            }
            #cert_digital_paper {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                height: auto !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                background: #ffffff !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: avoid !important;
                break-after: avoid !important;
            }
            .cert-header {
                border-bottom: 2px solid #0b3c68 !important;
                padding-bottom: 6px !important;
                margin-bottom: 12px !important;
            }
            .cert-header img {
                width: 58px !important;
                height: 58px !important;
            }
            .cert-header .cert-school-name {
                font-size: 1.35rem !important;
                margin-bottom: 1px !important;
                color: #0b3c68 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .cert-header .cert-school-sub {
                font-size: 0.95rem !important;
                margin-bottom: 1px !important;
            }
            .cert-header .cert-motto {
                font-size: 0.82rem !important;
                margin-bottom: 2px !important;
            }
            .cert-header .cert-resol {
                font-size: 0.68rem !important;
                line-height: 1.25 !important;
            }
            .cert-doc-title {
                font-size: 1.15rem !important;
                margin: 10px 0 6px 0 !important;
                color: #0b3c68 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .cert-doc-proclamation {
                font-size: 0.92rem !important;
                margin-bottom: 12px !important;
            }
            .cert-body-p {
                font-size: 0.92rem !important;
                line-height: 1.55 !important;
                margin-bottom: 10px !important;
                text-align: justify !important;
            }
            .cert-highlight {
                background: transparent !important;
                border-bottom: none !important;
                padding: 0 !important;
                font-weight: 700 !important;
                color: #000000 !important;
            }
            .cert-table {
                width: 100% !important;
                margin: 6px 0 10px 0 !important;
                font-size: 0.78rem !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .cert-table th {
                background-color: #0b3c68 !important;
                color: #ffffff !important;
                padding: 4px 8px !important;
                font-size: 0.76rem !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .cert-table td {
                padding: 3px 8px !important;
                font-size: 0.76rem !important;
                border: 1px solid #cbd5e1 !important;
            }
            .cert-watermark {
                width: 280px !important;
                height: 280px !important;
                opacity: 0.05 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .cert-signature-area {
                margin-top: 20px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .cert-signature-area img {
                height: 55px !important;
                margin-bottom: -10px !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .cert-signature-line {
                width: 220px !important;
                margin: 0 auto 4px auto !important;
                border-top: 1px solid #000000 !important;
            }
            .cert-rector-name {
                font-size: 0.92rem !important;
            }
            .cert-rector-cargo {
                font-size: 0.8rem !important;
            }
            .p-3.bg-light.rounded.border {
                padding: 8px 12px !important;
                margin: 8px 0 !important;
                font-size: 0.88rem !important;
                line-height: 1.5 !important;
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Fijo Nivel Pro con 9 Módulos Principales -->
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <img src="<?= ASSETS_URL ?>/assets/logo.svg" alt="La Salle Logo" style="width: 42px; height: 42px;">
            <div>
                <h6 class="font-heading mb-0 text-white fw-bold">AulaVirtual</h6>
                <small class="text-warning font-heading" style="font-size: 0.7rem;">GESTOR ADMINISTRATIVO</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="#dashboard" class="sidebar-link active" data-module="dashboard">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="#institucion" class="sidebar-link" data-module="institucion">
                <i class="fas fa-university"></i> Institución & Cursos
            </a>
            <a href="#usuarios" class="sidebar-link" data-module="usuarios">
                <i class="fas fa-users-cog"></i> Usuarios y Permisos
            </a>
            <a href="#academico" class="sidebar-link" data-module="academico">
                <i class="fas fa-graduation-cap"></i> Carga Académica (CAD)
            </a>
            <a href="#evaluacion" class="sidebar-link" data-module="evaluacion">
                <i class="fas fa-clipboard-check"></i> Calificaciones & Boletines
            </a>
            <a href="#reportes" class="sidebar-link" data-module="reportes">
                <i class="fas fa-chart-pie"></i> Reportes Académicos
            </a>
            <a href="#configuracion" class="sidebar-link" data-module="configuracion">
                <i class="fas fa-cogs"></i> Configuración
            </a>
            <hr class="text-white-50 my-3">
            <a href="<?= APP_URL ?>/app/Controllers/AuthController.php?action=logout" class="sidebar-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </nav>
    </aside>

    <!-- Área Principal -->
    <main class="app-main">
        <!-- Topbar -->
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-lg-none" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
                <h5 class="mb-0 font-heading fw-bold d-none d-md-block" id="topbarModuleTitle">Consola de Control Global</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary rounded-circle" id="themeToggleBtn" title="Cambiar Tema"><i class="fas fa-moon"></i></button>
                <div class="dropdown">
                    <button class="btn btn-light d-flex align-items-center gap-2 rounded-pill px-3 py-1" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle text-primary fs-5"></i>
                        <span class="fw-semibold small"><?= htmlspecialchars($userName) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="#usuarios" onclick="switchAdminModule('usuarios')"><i class="fas fa-user-shield me-2"></i> Perfil Administrador</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/app/Controllers/AuthController.php?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Salir</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="p-4">
            <!-- Mensajes de Estado o Servidor -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- =========================================================================
                 MÓDULO 1: DASHBOARD
                 ========================================================================= -->
            <section id="module-dashboard" class="admin-module-section active">
                <div class="module-header-box position-relative overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center position-relative z-1">
                        <div>
                            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2"><i class="fas fa-shield-alt me-1"></i> Rectoría & Administración General</span>
                            <h4 class="fw-bold mb-1 text-white">¡Bienvenido al Panel Ejecutivo de AulaVirtual!</h4>
                            <p class="mb-0 text-white-50">Instituto La Salle Ciénaga Magdalena • Resumen general e indicadores estratégicos 2026.</p>
                        </div>
                    </div>
                </div>

                <!-- KPIs Globales -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-4">
                        <div class="custom-card d-flex align-items-center gap-3 mb-0">
                            <div class="kpi-icon-box bg-primary-subtle text-primary">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <span class="text-muted small fw-semibold">Estudiantes Activos</span>
                                <h3 class="fw-bold mb-0">1,248</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-4">
                        <div class="custom-card d-flex align-items-center gap-3 mb-0">
                            <div class="kpi-icon-box bg-success-subtle text-success">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div>
                                <span class="text-muted small fw-semibold">Docentes Activos</span>
                                <h3 class="fw-bold mb-0">58 profesores</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-4">
                        <div class="custom-card d-flex align-items-center gap-3 mb-0">
                            <div class="kpi-icon-box bg-info-subtle text-info">
                                <i class="fas fa-layer-group"></i>
                            </div>
                            <div>
                                <span class="text-muted small fw-semibold">Cursos / Grupos Activos</span>
                                <h3 class="fw-bold mb-0">24 Cursos</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accesos Rápidos a Módulos Principales -->
                <div class="mb-4">
                    <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-rocket text-primary me-2"></i> Módulos Principales de Administración</h5>
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="nav-module-card" onclick="switchAdminModule('institucion')">
                                <div class="nav-module-icon bg-primary-subtle text-primary">
                                    <i class="fas fa-university"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Institución</h6>
                                    <p class="text-muted extra-small mb-0">Sedes, grados, cursos, jornadas y parámetros.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="nav-module-card" onclick="switchAdminModule('usuarios')">
                                <div class="nav-module-icon bg-success-subtle text-success">
                                    <i class="fas fa-users-cog"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Usuarios & Permisos</h6>
                                    <p class="text-muted extra-small mb-0">Comunidad, roles RBAC y control de acceso.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="nav-module-card" onclick="switchAdminModule('academico')">
                                <div class="nav-module-icon bg-info-subtle text-info">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Gestión Académica</h6>
                                    <p class="text-muted extra-small mb-0">Asignación de materias a cursos y planta docente.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="nav-module-card" onclick="switchAdminModule('evaluacion')">
                                <div class="nav-module-icon bg-warning-subtle text-warning">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Evaluación & Boletines</h6>
                                    <p class="text-muted extra-small mb-0">Gestor de boletines, notas, recuperaciones y faltas.</p>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </section>

            <!-- =========================================================================
                 MÓDULO: ADMISIONES & PRE-INSCRIPCIONES DE NUEVOS ESTUDIANTES
                 ========================================================================= -->
            <section id="module-institucion" class="admin-module-section">
                <div class="module-header-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-1 text-white"><i class="fas fa-university me-2"></i> Módulo Institución</h4>
                        <p class="mb-0 text-white-50">Estructura administrativa, plataformas gubernamentales del MEN y gestión oficial de constancias de estudio.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-light text-dark px-3 py-2 rounded-pill fw-bold border">
                            <i class="fas fa-school me-1 text-primary"></i> DANE: 147189000123
                        </span>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold border">
                            <i class="fas fa-award me-1"></i> ICFES: 025489
                        </span>
                    </div>
                </div>

                <!-- Tarjeta de Ficha Institucional -->
                <div class="custom-card mb-4 bg-light border">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <img src="<?= ASSETS_URL ?>/assets/logo.svg" alt="La Salle" style="width: 52px; height: 52px;">
                                <div>
                                    <h5 class="fw-bold text-dark mb-1">Instituto La Salle — Ciénaga, Magdalena</h5>
                                    <p class="text-muted small mb-0">
                                        Resolución SED N° 0542 • NIT: 891.780.123-1 • Calendario A • Jornada Completa • Rector: <strong>Lic. Herman Gualberto Rodríguez Pérez</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 text-md-end">
                            <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill fw-semibold">
                                <i class="fas fa-check-circle me-1"></i> Vigencia Académica 2026 Activa
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════════════════
                     CENTRO DIGITAL DE EMISIÓN Y CERTIFICACIÓN DOCUMENTAL OFICIAL
                     ══════════════════════════════════════════════════════════════════════ -->
                </section>

            <!-- =========================================================================
                 MÓDULO 3: USUARIOS Y PERMISOS
                 ========================================================================= -->
            <section id="module-usuarios" class="admin-module-section">
                <div class="module-header-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-1 text-white"><i class="fas fa-users-cog me-2"></i> Módulo Usuarios y Permisos</h4>
                        <p class="mb-0 text-white-50">Control integral de la comunidad educativa, asignación de roles institucionales y políticas RBAC.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm fw-bold px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalBulkUser">
                            <i class="fas fa-file-excel me-1"></i> 📥 Carga Masiva (Excel / CSV)
                        </button>
                        <button class="btn btn-warning btn-sm fw-bold px-3 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUser">
                            <i class="fas fa-plus-circle me-1"></i> + Nuevo Usuario
                        </button>
                    </div>
                </div>

                <!-- Barra de Filtros Profesional -->
                <div class="filter-bar">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                <input type="text" id="userSearchInput" class="form-control border-start-0 ps-0" placeholder="Buscar por nombre, documento o correo...">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select id="userRoleFilter" class="form-select">
                                <option value="">Todos los Roles</option>
                                <option value="administrador">Administrador</option>
                                <option value="coordinador">Coordinador</option>
                                <option value="docente">Docente</option>
                                <option value="estudiante">Estudiante</option>
                                <option value="administrativo">Administrativo</option>
                                <option value="psicologo">Psicólogo</option>
                                <option value="acudiente">Acudiente</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3">
                            <select id="userStatusFilter" class="form-select">
                                <option value="">Todos los Estados</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="suspendido">Suspendido</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Tabla Profesional de Usuarios -->
                <div class="custom-card p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0" id="usersMainTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Nombre Completo</th>
                                    <th>Correo Institucional</th>
                                    <th>Rol Sistema</th>
                                    <th>Documento</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th class="text-end pe-4">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersList as $usr): ?>
                                    <?php 
                                    $fullname = trim(($usr['primer_nombre'] ?? '') . ' ' . ($usr['segundo_nombre'] ?? '') . ' ' . ($usr['primer_apellido'] ?? '') . ' ' . ($usr['segundo_apellido'] ?? ''));
                                    if (empty($fullname)) $fullname = 'Usuario sin Perfil';
                                    $roleBadge = 'bg-secondary';
                                    if ($usr['nombre_rol'] === 'administrador') $roleBadge = 'bg-primary';
                                    elseif ($usr['nombre_rol'] === 'coordinador') $roleBadge = 'bg-dark border border-warning text-warning fw-bold';
                                    elseif ($usr['nombre_rol'] === 'docente') $roleBadge = 'bg-success';
                                    elseif ($usr['nombre_rol'] === 'psicologo') $roleBadge = 'bg-warning text-dark';
                                    elseif ($usr['nombre_rol'] === 'administrativo') $roleBadge = 'bg-info text-dark';
                                    elseif ($usr['nombre_rol'] === 'estudiante') $roleBadge = 'bg-dark';

                                    $statusClass = 'stat-badge-active';
                                    if ($usr['estado_cuenta'] === 'suspendido') $statusClass = 'stat-badge-suspended';
                                    elseif ($usr['estado_cuenta'] === 'inactivo') $statusClass = 'stat-badge-pending';
                                    ?>
                                    <tr data-role="<?= strtolower($usr['nombre_rol']) ?>" data-status="<?= strtolower($usr['estado_cuenta']) ?>" data-search="<?= strtolower($fullname . ' ' . $usr['correo'] . ' ' . ($usr['documento_identidad'] ?? '')) ?>">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold fs-6" style="width: 36px; height: 36px;">
                                                    <?= mb_substr($usr['primer_nombre'] ?? 'U', 0, 1) ?>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block mb-0"><?= htmlspecialchars($fullname) ?></strong>
                                                    <small class="text-muted extra-small">ID #<?= $usr['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="text-dark small fw-semibold"><?= htmlspecialchars($usr['correo']) ?></span></td>
                                        <td><span class="badge <?= $roleBadge ?> text-capitalize"><?= htmlspecialchars($usr['nombre_rol']) ?></span></td>
                                        <td><span class="small font-monospace"><?= htmlspecialchars($usr['tipo_documento'] ?? 'CC') ?> <?= htmlspecialchars($usr['documento_identidad'] ?? 'N/D') ?></span></td>
                                        <td>
                                            <span class="stat-badge <?= $statusClass ?>">
                                                <?= htmlspecialchars($usr['estado_cuenta']) ?>
                                            </span>
                                        </td>
                                        <td><span class="text-muted small"><?= htmlspecialchars($usr['ultimo_acceso'] ?? 'Reciente') ?></span></td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v text-secondary"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="openUserDetailModal(<?= htmlspecialchars(json_encode($usr)) ?>)">
                                                            <i class="fas fa-eye text-primary me-2"></i> Ver Detalle Completo
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="openEditModal(<?= htmlspecialchars(json_encode($usr)) ?>)">
                                                            <i class="fas fa-edit text-info me-2"></i> Editar Usuario
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="#" onclick="openQuickRoleModal(<?= htmlspecialchars(json_encode($usr)) ?>)">
                                                            <i class="fas fa-user-tag text-warning me-2"></i> Cambiar Rol & RBAC
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="id" value="<?= $usr['id'] ?>">
                                                            <input type="hidden" name="estado_actual" value="<?= $usr['estado_cuenta'] ?>">
                                                            <button type="submit" class="dropdown-item text-<?= $usr['estado_cuenta'] === 'activo' ? 'danger' : 'success' ?>">
                                                                <i class="fas fa-<?= $usr['estado_cuenta'] === 'activo' ? 'ban' : 'check-circle' ?> me-2"></i>
                                                                <?= $usr['estado_cuenta'] === 'activo' ? 'Suspender Usuario' : 'Activar Cuenta' ?>
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =========================================================================
                 MÓDULO 4: GESTIÓN ACADÉMICA (INCLUYE ASIGNADOR DE MATERIAS A CURSOS)
                 ========================================================================= -->
            <section id="module-academico" class="admin-module-section">
                <div class="module-header-box">
                    <h4 class="fw-bold mb-1 text-white"><i class="fas fa-graduation-cap me-2"></i> Módulo Gestión Académica & Carga Docente</h4>
                    <p class="mb-0 text-white-50">Asignación oficial de materias a cursos, distribución de escala evaluativa y planta docente responsable.</p>
                </div>

                <!-- FORMULARIO DE ASIGNACIÓN DE MATERIA A CURSO -->
                <div class="custom-card mb-4 border-start border-primary border-4">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fas fa-plus-circle me-2"></i> Asignar Materia / Asignatura a un Curso</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_course_subject">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-bold">1. Seleccionar Curso / Grado *</label>
                                <select class="form-select border-primary fw-bold" name="curso_id" required>
                                    <option value="" disabled selected>-- Seleccione Curso (Por Jornada) --</option>
                                    <?= renderCursosOptionsByJornada($cursosPorJornada) ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-bold">2. Seleccionar Materia / Asignatura *</label>
                                <select class="form-select border-primary" name="asignatura_id" required>
                                    <option value="" disabled selected>-- Seleccione Asignatura --</option>
                                    <?php if (!empty($asignaturasDB)): ?>
                                        <?php foreach ($asignaturasDB as $asig): ?>
                                            <option value="<?= $asig['id'] ?>"><?= htmlspecialchars($asig['nombre_asignatura']) ?> (<?= htmlspecialchars($asig['codigo']) ?>)</option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($mallaAsignaturas as $asig): ?>
                                            <option value="0"><?= htmlspecialchars($asig['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-bold">3. Docente Titular Responsable *</label>
                                <select class="form-select border-primary" name="docente_id" required>
                                    <option value="" disabled selected>-- Seleccione Docente --</option>
                                    <?php foreach ($docentesActivosList as $doc): ?>
                                        <?php 
                                        $dName = trim(($doc['primer_nombre'] ?? '') . ' ' . ($doc['primer_apellido'] ?? ''));
                                        $dId   = $doc['id'] ?? 0;
                                        if (empty($dName) || !$dId) continue;
                                        ?>
                                        <option value="<?= $dId ?>"><?= htmlspecialchars($dName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <label class="form-label small fw-bold text-primary"><i class="fas fa-balance-scale me-1"></i>4. Escala de Notas *</label>
                                <select class="form-select border-primary fw-bold" name="sistema_calif" required>
                                    <option value="1-5" selected>1.0 - 5.0 (Nacional)</option>
                                    <option value="1-10">1.0 - 10.0 (Extendida)</option>
                                    <option value="letras">Cualitativa (S, A, B, BJ)</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-1">
                                <input type="hidden" name="ih" value="2">
                                <button type="submit" class="btn btn-lasalle-primary w-100 fw-bold py-2" title="Asignar Materia con Escala Fijada">
                                    <i class="fas fa-save me-1"></i> Asignar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- TABLA DE MATERIAS ASIGNADAS POR CURSO -->
                <div class="custom-card p-0 overflow-hidden">
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-list-check text-success me-2"></i> Asignaturas y Carga Docente Asignada por Curso</h6>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small fw-bold text-muted mb-0">Filtrar por Curso:</label>
                            <select id="cadCourseFilter" class="form-select form-select-sm" style="width: 220px;" onchange="filtrarTablaCAD()">
                                <option value="">Todos los Cursos</option>
                                <?php foreach ($cursosDB as $cOpt): ?>
                                    <option value="<?= htmlspecialchars($cOpt['grado'] . ' ' . $cOpt['grupo']) ?>"><?= htmlspecialchars($cOpt['grado'] . ' ' . $cOpt['grupo'] . ' (' . $cOpt['ano_lectivo'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle mb-0" id="tableCAD">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Curso / Grado</th>
                                    <th>Asignatura / Materia</th>
                                    <th>Docente Titular Responsable</th>
                                    <th>Escala Fijada (Admin)</th>
                                    <th class="text-end pe-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cadList)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i>No hay asignaturas asignadas aún. Use el formulario de arriba para agregar.</td></tr>
                                <?php else: ?>
                                <?php foreach ($cadList as $cad): ?>
                                    <tr data-curso="<?= htmlspecialchars($cad['grado'] . ' ' . $cad['grupo']) ?>">
                                        <td class="ps-4"><span class="badge bg-primary px-3 py-2 fs-6"><?= htmlspecialchars($cad['curso_nombre']) ?></span></td>
                                        <td><strong class="text-dark fs-6"><?= htmlspecialchars($cad['asignatura_nombre']) ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-chalkboard-teacher text-success fs-5"></i>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($cad['docente_nombre']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $sc = $cad['sistema_calif'] ?? '1-5';
                                            if ($sc === '1-10') {
                                                echo '<span class="badge bg-warning text-dark"><i class="fas fa-lock me-1"></i>Escala 1.0 - 10.0</span>';
                                            } elseif ($sc === 'letras') {
                                                echo '<span class="badge bg-info text-dark"><i class="fas fa-lock me-1"></i>Letras (S, A, B, BJ)</span>';
                                            } else {
                                                echo '<span class="badge bg-success"><i class="fas fa-lock me-1"></i>Escala 1.0 - 5.0</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Desea remover esta asignatura del curso?');">
                                                <input type="hidden" name="action" value="delete_cad_item">
                                                <input type="hidden" name="cad_id" value="<?= $cad['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar Asignación">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- =========================================================================
                 MÓDULO 5: EVALUACIÓN Y CALIFICACIONES (INCLUYE EL GESTOR DE BOLETINES)
                 ========================================================================= -->
            <section id="module-evaluacion" class="admin-module-section">
                <div class="module-header-box">
                    <h4 class="fw-bold mb-1 text-white"><i class="fas fa-clipboard-check me-2"></i> Módulo Evaluación y Calificaciones</h4>
                    <p class="mb-0 text-white-50">Administración de notas, recuperaciones, promedios y la Consola Masiva de Generación de Boletines.</p>
                </div>

                <!-- Sub-barra de categorías -->
                <div class="custom-card mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-sliders-h text-primary me-2"></i> Funciones de Evaluación Académica</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="sub-category-badge active bg-primary text-white" onclick="switchEvalTab('boletines')" id="etab-boletines"><i class="fas fa-file-pdf"></i> Consola Masiva de Boletines</span>
                        <span class="sub-category-badge" onclick="switchEvalTab('notas')" id="etab-notas"><i class="fas fa-star"></i> Calificaciones & Notas</span>
                        <span class="sub-category-badge" onclick="switchEvalTab('promedios')" id="etab-promedios"><i class="fas fa-calculator"></i> Promedios y Rendimiento</span>
                    </div>
                </div>

                <!-- CONSOLA MASIVA DE BOLETINES (DISEÑO ORDENADO SECTORES IZQ Y DER) -->
                <div class="custom-card p-4 mb-4" id="eval-tab-boletines">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-print me-2"></i> Consola Masiva de Generación y Descarga de Boletines</h5>
                            <small class="text-muted">Filtre por Sede, Jornada, Grado, Curso o Período para consultar la lista oficial de estudiantes y generar los boletines oficiales en PDF.</small>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Panel Izquierdo: Datos Básicos u Opciones (Como la imagen de referencia) -->
                        <div class="col-12 col-lg-4">
                            <!-- Datos Básicos Card -->
                            <div class="boletin-sidebar-card">
                                <div class="boletin-panel-header">
                                    <i class="fas fa-filter me-1"></i> Datos Básicos
                                </div>
                                <div class="boletin-body-pad">
                                    <div class="mb-2">
                                        <label class="form-label-compact">Año Lectivo</label>
                                        <select class="form-select form-select-sm" id="bol_lectivo">
                                            <option value="2026" selected>2026</option>
                                            <option value="2025">2025</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label-compact">Alumnos</label>
                                        <select class="form-select form-select-sm" id="bol_alumnos" onchange="filtrarEstudiantesBoletin()">
                                            <option value="activos" selected>Activos</option>
                                            <option value="todos">Todos</option>
                                            <option value="inactivos">Inactivos</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label-compact">Sede</label>
                                        <select class="form-select form-select-sm" id="bol_sede">
                                            <option value="INSTITUTO LA SALLE" selected>INSTITUTO LA SALLE</option>
                                            <option value="SEDE PRIMARIA">SEDE PRIMARIA</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label-compact">Jornada</label>
                                        <select class="form-select form-select-sm" id="bol_jornada" onchange="filtrarEstudiantesBoletin()">
                                            <option value="" selected>* Todas las Jornadas</option>
                                            <option value="Mañana">☀️ Jornada Mañana</option>
                                            <option value="Tarde">⛅ Jornada Tarde</option>
                                            <option value="Única">Jornada Única</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label-compact">Grado</label>
                                        <select class="form-select form-select-sm" id="bol_grado" onchange="filtrarEstudiantesBoletin()">
                                            <option value="">* Todos los Grados</option>
                                            <option value="Transición">Transición</option>
                                            <option value="1°">Primero (1°)</option>
                                            <option value="2°">Segundo (2°)</option>
                                            <option value="3°">Tercero (3°)</option>
                                            <option value="4°">Cuarto (4°)</option>
                                            <option value="5°">Quinto (5°)</option>
                                            <option value="6°">Sexto (6°)</option>
                                            <option value="7°">Séptimo (7°)</option>
                                            <option value="8°">Octavo (8°)</option>
                                            <option value="9°">Noveno (9°)</option>
                                            <option value="10°">Décimo (10°)</option>
                                            <option value="11°">Once (11°)</option>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label-compact">Curso / Grupo</label>
                                        <select class="form-select form-select-sm" id="bol_curso" onchange="filtrarEstudiantesBoletin()">
                                            <option value="">* Todos los Cursos</option>
                                            <?= renderCursosOptionsByJornada($cursosPorJornada) ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label-compact">Periodo Lectivo *</label>
                                        <select class="form-select form-select-sm fw-bold border-primary" id="bol_periodo" onchange="filtrarEstudiantesBoletin()">
                                            <option value="1">Periodo 1</option>
                                            <option value="2">Periodo 2</option>
                                            <option value="3">Periodo 3</option>
                                            <option value="4">Periodo 4</option>
                                            <option value="todos" selected>Todos los Periodos (Juntos / Consolidado)</option>
                                            <option value="separados">Todos los Periodos (Separados / Individuales)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Opciones de Impresión Card -->
                            <div class="boletin-sidebar-card">
                                <div class="boletin-panel-header" style="background:#2d7d32;">
                                    <i class="fas fa-sliders-h me-1"></i> Opciones de Impresión
                                </div>
                                <div class="boletin-body-pad">
                                    <div class="row g-2 extra-small font-weight-bold">
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="chk_tperiodos" checked>
                                                <label class="form-check-label" for="chk_tperiodos">T Periodos</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="chk_puestos" checked>
                                                <label class="form-check-label" for="chk_puestos">Puestos</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="chk_docente" checked>
                                                <label class="form-check-label" for="chk_docente">Docente</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="chk_ausencias" checked>
                                                <label class="form-check-label" for="chk_ausencias">Ausencias</label>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <label class="form-label-compact d-block">Promedios</label>
                                            <select class="form-select form-select-sm" id="bol_opt_promedio">
                                                <option value="asignaturas">Asignaturas</option>
                                                <option value="areas">Áreas</option>
                                                <option value="ambos">Ambos</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel Derecho: Lista de Estudiantes (Como la imagen de referencia) -->
                        <div class="col-12 col-lg-8">
                            <div class="border rounded-3 overflow-hidden bg-white shadow-sm">
                                <div class="bg-primary text-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <input class="form-check-input" type="checkbox" id="chk_select_all" onchange="toggleSelectAllBoletines(this)">
                                        <label for="chk_select_all" class="fw-bold mb-0 text-white cursor-pointer">Lista de Estudiantes (<span id="bol_count_filtered"><?= count($estudiantesList) ?></span>)</label>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-warning btn-sm fw-bold shadow-sm" onclick="imprimirBoletinesSeleccionados()">
                                            <i class="fas fa-file-pdf me-1"></i> Imprimir Seleccionados en 1 Solo PDF
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0" id="tableEstudiantesBoletin">
                                        <thead class="table-light sticky-top">
                                            <tr class="extra-small text-uppercase">
                                                <th style="width: 40px;" class="text-center">#</th>
                                                <th style="width: 40px;">Sel</th>
                                                <th>Identificación</th>
                                                <th>Estudiante</th>
                                                <th>Curso / Grado</th>
                                                <th>Estado</th>
                                                <th class="text-end pe-3">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $i=1; foreach ($estudiantesList as $est): ?>
                                                <tr data-grado="<?= htmlspecialchars($est['grado'] ?? '10°') ?>" data-curso="<?= htmlspecialchars($est['curso'] ?? 'DÉCIMO A') ?>" data-estado="<?= htmlspecialchars($est['estado_cuenta'] ?? 'activo') ?>" data-search="<?= strtolower(($est['primer_nombre'] ?? '') . ' ' . ($est['primer_apellido'] ?? '') . ' ' . ($est['documento_identidad'] ?? '')) ?>">
                                                    <td class="text-center text-muted small fw-bold"><?= $i++ ?></td>
                                                    <td>
                                                        <input class="form-check-input chk-student-boletin" type="checkbox" value="<?= $est['id'] ?>">
                                                    </td>
                                                    <td><span class="font-monospace small font-weight-bold text-dark"><?= htmlspecialchars($est['documento_identidad'] ?? '1082987000') ?></span></td>
                                                    <td>
                                                        <strong class="text-dark d-block mb-0"><?= htmlspecialchars(strtoupper(trim(($est['primer_apellido'] ?? '') . ' ' . ($est['segundo_apellido'] ?? '') . ' ' . ($est['primer_nombre'] ?? '') . ' ' . ($est['segundo_nombre'] ?? '')))) ?></strong>
                                                        <small class="text-muted extra-small"><?= htmlspecialchars($est['correo']) ?></small>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($est['curso'] ?? '10°A') ?></span></td>
                                                    <td><span class="stat-badge stat-badge-active"><?= htmlspecialchars($est['estado_cuenta'] ?? 'activo') ?></span></td>
                                                    <td class="text-end pe-3">
                                                        <button class="btn btn-lasalle-primary btn-sm px-2 py-1 shadow-sm" onclick="abrirBoletinAdmin(<?= $est['id'] ?>)" title="Ver / Descargar Boletín PDF">
                                                            <i class="fas fa-file-pdf me-1"></i> Boletín
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══════════════════════════════════════════════════════════════════════
                     SECCIÓN: EVALUACIÓN DOCENTE INSTITUCIONAL (ESTUDIANTES ➔ DOCENTES)
                     ══════════════════════════════════════════════════════════════════════ -->
                </section>

            <!-- =========================================================================
                 MÓDULO 6: PLANES DE CLASE / PEI
                 ========================================================================= -->
            <section id="module-reportes" class="admin-module-section">
                <div class="module-header-box d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-1 text-white"><i class="fas fa-chart-pie me-2"></i> Módulo Reportes & Estadísticas</h4>
                        <p class="mb-0 text-white-50">Boletines masivos, listado oficial de matriculados, rendimiento académico y situación financiera por estudiante.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light btn-sm fw-bold px-3" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Imprimir Reporte
                        </button>
                    </div>
                </div>

                <!-- Sub-tabs del módulo Reportes -->
                <div class="custom-card mb-4">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="sub-category-badge active bg-primary text-white" onclick="switchReporteTab('boletines')" id="rtab-boletines">
                            <i class="fas fa-file-pdf"></i> Boletines Masivos
                        </span>
                        <span class="sub-category-badge" onclick="switchReporteTab('estudiantes')" id="rtab-estudiantes">
                            <i class="fas fa-user-graduate"></i> Reporte Estudiantes
                        </span>
                        <span class="sub-category-badge" onclick="switchReporteTab('rendimiento')" id="rtab-rendimiento">
                            <i class="fas fa-chart-line"></i> Rendimiento Académico
                        </span>
                        
                    </div>
                </div>

                <!-- ══ TAB 1: BOLETINES MASIVOS ══ -->
                <div id="rep-tab-boletines">
                    <div class="custom-card p-4 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                            <div>
                                <h5 class="fw-bold mb-0 text-primary"><i class="fas fa-print me-2"></i> Generador Masivo de Boletines PDF</h5>
                                <small class="text-muted">Seleccione estudiantes individualmente o por curso/período y genere todos los boletines en un solo PDF.</small>
                            </div>
                        </div>

                        <!-- Barra de Herramientas y Filtros Rápidos -->
                        <div class="bg-light p-3 rounded-3 border mb-4 shadow-sm">
                            <div class="row g-3 align-items-end">
                                <!-- Filtro por Curso -->
                                <div class="col-12 col-md-3">
                                    <label class="form-label small fw-bold text-secondary mb-1">
                                        <i class="fas fa-layer-group me-1 text-primary"></i> Filtrar por Curso
                                    </label>
                                    <select class="form-select form-select-sm" id="bolFiltCurso" onchange="filtrarListaBoletin()">
                                        <option value="">-- Todos los Cursos y Jornadas --</option>
                                        <?= renderCursosOptionsByJornada($cursosPorJornada) ?>
                                    </select>
                                </div>

                                <!-- Período / Formato de Impresión -->
                                <div class="col-12 col-md-4">
                                    <label class="form-label small fw-bold text-secondary mb-1">
                                        <i class="fas fa-calendar-alt me-1 text-primary"></i> Período / Formato
                                    </label>
                                    <select class="form-select form-select-sm fw-semibold" id="bolPeriodo" style="border-color: #3b82f6;">
                                        <option value="todos" selected>Todos los Periodos (Juntos / Consolidado)</option>
                                        <option value="separados">Todos los Periodos (Separados / Individuales)</option>
                                        <option value="1">Período 1</option>
                                        <option value="2">Período 2</option>
                                        <option value="3">Período 3</option>
                                        <option value="4">Período 4</option>
                                    </select>
                                </div>

                                <!-- Acciones de Selección y Generación -->
                                <div class="col-12 col-md-5 d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                                    <!-- Botones de Selección Rápida en Grupo Homogéneo -->
                                    <div class="btn-group btn-group-sm shadow-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary px-3 text-nowrap" onclick="seleccionarTodosBoletin(true)" title="Marcar todos los estudiantes visibles">
                                            <i class="fas fa-check-square text-success me-1"></i> Marcar Todos
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary px-3 text-nowrap" onclick="seleccionarTodosBoletin(false)" title="Desmarcar todos">
                                            <i class="fas fa-square text-muted me-1"></i> Desmarcar
                                        </button>
                                    </div>

                                    <!-- Botón Principal Generar PDF -->
                                    <button class="btn btn-primary btn-sm fw-bold px-3 shadow-sm text-nowrap d-inline-flex align-items-center gap-2" onclick="generarBoletinesMasivos()" style="background:#1E3A8A;border-color:#1E3A8A;">
                                        <i class="fas fa-file-pdf fs-6 text-warning"></i>
                                        <span>Generar PDF Seleccionados</span>
                                        <span class="badge bg-white text-dark rounded-pill px-2" id="bolCountBadge" style="font-size:0.75rem;">0</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de Estudiantes para Boletines -->
                        <?php if (empty($boletinEstudiantes)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay estudiantes matriculados en 2026 con acceso a boletines. Verifique que los estudiantes tengan matrícula asignada.
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0" id="tableBoletin">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width:40px;">
                                            <input type="checkbox" id="checkAllBoletin" onchange="seleccionarTodosBoletin(this.checked)" class="form-check-input">
                                        </th>
                                        <th>Estudiante</th>
                                        <th>Documento</th>
                                        <th>Curso</th>
                                        <th>Cód. Matrícula</th>
                                        <th>Estado</th>
                                        <th class="text-end pe-3">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($boletinEstudiantes as $be): 
                                        $beUid = $be['usuario_id'] ?? $be['matricula_id'];
                                    ?>
                                    <tr data-curso="<?= htmlspecialchars($be['grado'].' '.$be['grupo']) ?>" data-matid="<?= $be['matricula_id'] ?>" data-uid="<?= $beUid ?>">
                                        <td class="ps-3">
                                            <input type="checkbox" class="form-check-input boletin-check" value="<?= $beUid ?>"
                                                   data-uid="<?= $beUid ?>" data-matid="<?= $be['matricula_id'] ?>"
                                                   onchange="actualizarContadorBoletines()">
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">
                                                <?= htmlspecialchars(strtoupper($be['primer_apellido'].' '.$be['segundo_apellido'])) ?>,
                                                <?= htmlspecialchars($be['primer_nombre'].' '.($be['segundo_nombre']??'')) ?>
                                            </span>
                                        </td>
                                        <td><span class="text-muted small"><?= htmlspecialchars($be['tipo_documento'].' '.$be['documento_identidad']) ?></span></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($be['grado'].' '.$be['grupo']) ?></span></td>
                                        <td><code class="small"><?= htmlspecialchars($be['codigo_matricula']) ?></code></td>
                                        <td>
                                            <?php $st = $be['estado_matricula']; ?>
                                            <span class="badge <?= $st==='activa'?'bg-success':($st==='retirado'?'bg-danger':'bg-secondary') ?>">
                                                <?= strtoupper($st) ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiante_id=<?= $beUid ?>&periodo=todos"
                                               class="btn btn-sm btn-outline-primary" target="_blank"
                                               id="btnBoletin<?= $be['matricula_id'] ?>"
                                               onclick="this.href='<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiante_id=<?= $beUid ?>&periodo='+(document.getElementById('bolPeriodo')?.value || document.getElementById('bol_periodo')?.value || 'todos')">
                                                <i class="fas fa-eye me-1"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ══ TAB 2: REPORTE DE ESTUDIANTES ══ -->
                <div id="rep-tab-estudiantes" style="display:none;">
                    <div class="custom-card p-0 overflow-hidden mb-4">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold mb-0 text-success"><i class="fas fa-user-graduate me-2"></i> Listado Oficial de Estudiantes Matriculados 2026</h5>
                                <small class="text-muted"><?= count($reporteEstudiantes) ?> estudiante(s) registrado(s) con matrícula activa.</small>
                            </div>
                            <button class="btn btn-success btn-sm fw-bold" onclick="exportarTablaCSV('tablaEstRep','reporte_estudiantes_2026')">
                                <i class="fas fa-file-csv me-1"></i> Exportar CSV
                            </button>
                        </div>
                        <?php if (empty($reporteEstudiantes)): ?>
                            <div class="p-4 text-center text-muted">
                                <i class="fas fa-user-slash fs-2 mb-2 d-block"></i>
                                No hay estudiantes matriculados en 2026. Asigne cursos a los estudiantes desde el módulo de Usuarios.
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle mb-0" id="tablaEstRep">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">#</th>
                                        <th>Apellidos y Nombres</th>
                                        <th>Documento</th>
                                        <th>Correo</th>
                                        <th>Teléfono</th>
                                        <th>Curso</th>
                                        <th>Código Matrícula</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reporteEstudiantes as $i => $est): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= $i+1 ?></td>
                                        <td>
                                            <span class="fw-bold">
                                                <?= htmlspecialchars(strtoupper($est['primer_apellido'].' '.($est['segundo_apellido']??''))) ?>,
                                                <?= htmlspecialchars($est['primer_nombre'].' '.($est['segundo_nombre']??'')) ?>
                                            </span>
                                        </td>
                                        <td><span class="text-muted small"><?= htmlspecialchars($est['tipo_documento'].' '.$est['documento_identidad']) ?></span></td>
                                        <td><a href="mailto:<?= htmlspecialchars($est['correo']) ?>" class="text-primary small"><?= htmlspecialchars($est['correo']) ?></a></td>
                                        <td class="small"><?= htmlspecialchars($est['telefono'] ?? '—') ?></td>
                                        <td><span class="badge bg-primary px-3"><?= htmlspecialchars($est['grado'].' '.$est['grupo']) ?></span></td>
                                        <td><code class="small"><?= htmlspecialchars($est['codigo_matricula']) ?></code></td>
                                        <td>
                                            <span class="badge <?= $est['estado_matricula']==='activa'?'bg-success':'bg-secondary' ?>">
                                                <?= strtoupper($est['estado_matricula']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ══ TAB 3: RENDIMIENTO ACADÉMICO ══ -->
                <div id="rep-tab-rendimiento" style="display:none;">
                    <div class="row g-3 mb-4">
                        <?php foreach ($reporteRendimiento as $rend): ?>
                        <?php
                            $prom = floatval($rend['promedio_general'] ?? 0);
                            $colorProm = $prom >= 4.6 ? 'success' : ($prom >= 4.0 ? 'info' : ($prom >= 3.0 ? 'warning' : 'danger'));
                            $totalNot = max(intval($rend['aprobados']) + intval($rend['reprobados']), 1);
                            $pctAprob = round(intval($rend['aprobados']) / $totalNot * 100);
                        ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="custom-card h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-primary">
                                        <i class="fas fa-layer-group me-1"></i>
                                        <?= htmlspecialchars($rend['grado'].' '.$rend['grupo']) ?>
                                        <small class="text-muted fw-normal">(<?= $rend['ano_lectivo'] ?>)</small>
                                    </h6>
                                    <span class="badge bg-<?= $colorProm ?> fs-6"><?= $prom > 0 ? number_format($prom,2) : '—' ?></span>
                                </div>
                                <div class="row g-2 text-center mb-3">
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-light">
                                            <div class="fw-bold fs-5 text-primary"><?= $rend['total_estudiantes'] ?></div>
                                            <div class="extra-small text-muted">Matriculados</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-success-subtle">
                                            <div class="fw-bold fs-5 text-success"><?= $rend['aprobados'] ?></div>
                                            <div class="extra-small text-muted">Aprobados</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-2 rounded bg-danger-subtle">
                                            <div class="fw-bold fs-5 text-danger"><?= $rend['reprobados'] ?></div>
                                            <div class="extra-small text-muted">Reprobados</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-1 d-flex justify-content-between small">
                                    <span class="text-muted">Tasa de Aprobación</span>
                                    <span class="fw-bold"><?= $pctAprob ?>%</span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-<?= $colorProm ?>" style="width:<?= $pctAprob ?>%"></div>
                                </div>
                                <?php if ($rend['superiores'] > 0): ?>
                                <div class="mt-2 text-center">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-star me-1"></i> <?= $rend['superiores'] ?> desempeños superiores
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($reporteRendimiento)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay datos de rendimiento. Los docentes deben ingresar calificaciones desde su portal.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ══ TAB 4: SITUACIÓN FINANCIERA ══ -->
                </section>

            <!-- =========================================================================
                 MÓDULO 8: AUDITORÍA & ETL
                 ========================================================================= -->
            <section id="module-configuracion" class="admin-module-section">
                <div class="module-header-box">
                    <h4 class="fw-bold mb-1 text-white"><i class="fas fa-cogs me-2"></i> Módulo Configuración del Sistema</h4>
                    <p class="mb-0 text-white-50">Parámetros generales de la plataforma, políticas de seguridad BCRYPT e integraciones.</p>
                </div>

                <div class="custom-card mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-sliders-h text-primary me-2"></i> Parámetros de Servidor & Seguridad</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Año Lectivo Activo</label>
                            <input type="text" class="form-control" value="2026" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Algoritmo de Encriptación de Claves</label>
                            <input type="text" class="form-control" value="BCRYPT (Cost 10)" readonly>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-card border-0 p-0">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title font-heading fw-bold">Registrar Usuario Institucional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="create_user">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rol en el Sistema *</label>
                            <select class="form-select" name="rol_id" id="regRolSelect" required onchange="toggleRolFields(this.value)">
                                <option value="1">Administrador</option>
                                <option value="7">Coordinador</option>
                                <option value="2">Administrativo / Tesorería</option>
                                <option value="3" selected>Docente</option>
                                <option value="4">Psicólogo Orientador</option>
                                <option value="5">Estudiante</option>
                                <option value="6">Acudiente</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tipo Documento *</label>
                            <select class="form-select" name="tipo_documento" required>
                                <option value="CC">Cédula de Ciudadanía (CC)</option>
                                <option value="TI">Tarjeta de Identidad (TI)</option>
                                <option value="CE">Cédula de Extranjería (CE)</option>
                                <option value="RC">Registro Civil (RC)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Documento de Identidad *</label>
                            <input type="text" class="form-control" name="documento_identidad" placeholder="Ej: 1082987000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Institucional *</label>
                            <input type="email" class="form-control" name="correo" placeholder="correo@lasallecienaga.edu.co" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Nombre *</label>
                            <input type="text" class="form-control" name="primer_nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Nombre</label>
                            <input type="text" class="form-control" name="segundo_nombre">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Apellido *</label>
                            <input type="text" class="form-control" name="primer_apellido" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Apellido</label>
                            <input type="text" class="form-control" name="segundo_apellido">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contraseña Inicial *</label>
                            <input type="password" class="form-control" name="password" required value="Salle2026*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono de Contacto</label>
                            <input type="text" class="form-control" name="telefono">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Dirección de Residencia</label>
                            <input type="text" class="form-control" name="direccion" placeholder="Calle/Carrera...">
                        </div>

                        <!-- CAMPO EXCLUSIVO ADMINISTRATIVO / TESORERÍA: Asignar Caja / Sede Autorizada -->
                        <div class="col-12" id="fieldCajaSede" style="display:none;">
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-cash-register me-1"></i> <strong>Asignación de Caja / Sede de Recaudo (Tesorería)</strong>
                            </div>
                            <label class="form-label small fw-semibold">Caja / Sede Autorizada para Administrar *</label>
                            <select class="form-select border-info" name="sede_caja" id="regSedeCajaSelect">
                                <option value="ambas" selected>Ambas Cajas (Sede Principal y Sede Victoria al mismo tiempo)</option>
                                <option value="principal">Solo Caja Sede Principal</option>
                                <option value="segunda">Solo Caja Sede Victoria</option>
                            </select>
                            <small class="text-muted">El usuario podrá cobrar, ver movimientos y realizar arqueo en la(s) caja(s) seleccionada(s).</small>
                        </div>

                        <!-- CAMPO EXCLUSIVO ESTUDIANTE: Seleccionar Sede, Jornada y Curso Oficial -->
                        <div class="col-12" id="fieldCurso" style="display:none;">
                            <div class="alert alert-primary py-2 mb-2">
                                <i class="fas fa-graduation-cap me-1"></i> <strong>Asignación de Sede, Curso Oficial & Acudiente (Estudiante)</strong>
                            </div>
                            
                            <!-- 1. Filtrar por Sede -->
                            <label class="form-label small fw-semibold text-dark mb-1">
                                <i class="fas fa-building me-1 text-primary"></i> 1. Seleccione la Sede Institucional:
                            </label>
                            <div class="btn-group btn-group-sm w-100 mb-2 shadow-sm" id="btnGroupSedeReg" role="group">
                                <button type="button" class="btn btn-dark fw-bold btn-sede-filter active" data-sede="todas" onclick="filtrarCursosModalExt('regCursoSelect', 'todas', 'btnGroupSedeReg', 'sede')">🌐 Ambas Sedes</button>
                                <button type="button" class="btn btn-outline-primary fw-bold btn-sede-filter" data-sede="Sede Principal" onclick="filtrarCursosModalExt('regCursoSelect', 'Sede Principal', 'btnGroupSedeReg', 'sede')">🏢 Sede Principal</button>
                                <button type="button" class="btn btn-outline-success fw-bold btn-sede-filter" data-sede="Sede Victoria" onclick="filtrarCursosModalExt('regCursoSelect', 'Sede Victoria', 'btnGroupSedeReg', 'sede')">🏫 Sede Victoria</button>
                            </div>

                            <!-- 2. Filtrar por Jornada (1 Curso por Jornada) -->
                            <label class="form-label small fw-semibold text-dark mb-1">
                                <i class="fas fa-clock me-1 text-primary"></i> 2. Seleccione la Jornada Escolar (1 Curso por Jornada):
                            </label>
                            <div class="btn-group btn-group-sm w-100 mb-2 shadow-sm" id="btnGroupJorReg" role="group">
                                <button type="button" class="btn btn-dark fw-bold btn-jor-filter active" data-jornada="todas" onclick="filtrarCursosModalExt('regCursoSelect', 'todas', 'btnGroupJorReg', 'jornada')">🌐 Ambas Jornadas</button>
                                <button type="button" class="btn btn-outline-warning text-dark fw-bold btn-jor-filter" data-jornada="Mañana" onclick="filtrarCursosModalExt('regCursoSelect', 'Mañana', 'btnGroupJorReg', 'jornada')">☀️ Jornada Mañana (Grupo A)</button>
                                <button type="button" class="btn btn-outline-primary fw-bold btn-jor-filter" data-jornada="Tarde" onclick="filtrarCursosModalExt('regCursoSelect', 'Tarde', 'btnGroupJorReg', 'jornada')">⛅ Jornada Tarde (Grupo B)</button>
                            </div>

                            <label class="form-label small fw-semibold">3. Curso Oficial a Matricular *</label>
                            <select class="form-select border-primary mb-3" name="curso_id" id="regCursoSelect">
                                <option value="">-- Seleccione el Curso Oficial --</option>
                                <?= renderCursosOptionsByJornada($cursosDB) ?>
                            </select>

                            <!-- Sección Dedicada al Acudiente / Tutor Legal -->
                            <div class="p-3 bg-light rounded border border-primary-subtle">
                                <h6 class="fw-bold text-primary mb-2 small text-uppercase">
                                    <i class="fas fa-user-shield me-1"></i> Vinculación del Acudiente / Tutor Legal (Obligatorio)
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label extra-small fw-semibold">Nombre Completo del Acudiente *</label>
                                        <input type="text" class="form-control form-control-sm" name="acudiente_nombre" id="regAcudienteNombre" placeholder="Nombre completo del tutor">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label extra-small fw-semibold">Correo Electrónico de Contacto *</label>
                                        <input type="email" class="form-control form-control-sm" name="acudiente_correo" id="regAcudienteCorreo" placeholder="correo.acudiente@ejemplo.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label extra-small fw-semibold">Teléfono del Acudiente</label>
                                        <input type="text" class="form-control form-control-sm" name="acudiente_telefono" id="regAcudienteTelefono" placeholder="300 000 0000">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CAMPO EXCLUSIVO ACUDIENTE: Vincular Estudiante -->
                        <div class="col-12" id="fieldAcudiente" style="display:none;">
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="fas fa-link me-1"></i> <strong>Vínculo Familiar (Acudiente)</strong>
                            </div>
                            <label class="form-label small fw-semibold mt-2">Estudiante que representa</label>
                            <select class="form-select border-warning" name="estudiante_vinculo_id" id="regEstudianteSelect">
                                <option value="0">-- Ninguno / Vincular después --</option>
                                <?php foreach ($estudiantesList as $est): ?>
                                    <option value="<?= $est['id'] ?>"><?= htmlspecialchars(($est['primer_nombre'] ?? '') . ' ' . ($est['primer_apellido'] ?? '') . ' — Doc: ' . ($est['documento_identidad'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">El nombre del acudiente quedará registrado en el perfil del estudiante.</small>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 pt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-lasalle-primary">Guardar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
function toggleRolFields(rolId) {
    var isEstudiante = (String(rolId) === '5');
    var isAcudiente  = (String(rolId) === '6');
    var isTesoreria  = (String(rolId) === '2');
    
    var fCurso = document.getElementById('fieldCurso');
    var fAcud  = document.getElementById('fieldAcudiente');
    var fCaja  = document.getElementById('fieldCajaSede');
    
    if (fCurso) fCurso.style.display = isEstudiante ? 'block' : 'none';
    if (fAcud)  fAcud.style.display  = isAcudiente ? 'block' : 'none';
    if (fCaja)  fCaja.style.display  = isTesoreria ? 'block' : 'none';
    
    var cursoSel = document.getElementById('regCursoSelect');
    if (cursoSel) cursoSel.required = isEstudiante;

    var acudNom = document.getElementById('regAcudienteNombre');
    var acudMail = document.getElementById('regAcudienteCorreo');
    if (acudNom)  acudNom.required = isEstudiante;
    if (acudMail) acudMail.required = isEstudiante;
}
// Run on page load to set initial state
document.addEventListener('DOMContentLoaded', function(){ toggleRolFields(document.getElementById('regRolSelect').value); });
</script>


<!-- Modal Carga Masiva de Usuarios -->
<div class="modal fade" id="modalBulkUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-card border-0 p-0 shadow-lg">
            <div class="modal-header bg-gradient text-white p-4" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                <div>
                    <h5 class="modal-title font-heading fw-bold mb-1 text-white">
                        <i class="fas fa-magic me-2"></i> Carga Masiva Inteligente de Usuarios
                    </h5>
                    <small class="text-white-50">
                        Suba un archivo o pegue su lista: el sistema detecta automáticamente cada dato sin importar el orden de las columnas.
                    </small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Alerta de Detección Agnóstica -->
                <div class="alert alert-success d-flex align-items-center mb-4 py-2 px-3 border-0 bg-success-subtle text-success-emphasis rounded-3">
                    <i class="fas fa-brain fs-4 me-3 text-success"></i>
                    <div class="small">
                        <strong>¡No importa el orden de las columnas!</strong> El motor inteligente analiza los encabezados o el contenido y reconoce automáticamente: 
                        <em>Nombres, Apellidos, Documento, Tipo Doc, Correo, Celular, Grado/Curso y Datos del Acudiente</em>.
                    </div>
                </div>

                <!-- Pestañas de Subida -->
                <ul class="nav nav-pills mb-4 nav-fill gap-2" id="bulkTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold py-2 border" id="file-tab" data-bs-toggle="tab" data-bs-target="#tab-file" type="button" role="tab">
                            <i class="fas fa-file-excel me-1 text-success"></i> 1. Subir Archivo (Excel / CSV)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold py-2 border" id="paste-tab" data-bs-toggle="tab" data-bs-target="#tab-paste" type="button" role="tab">
                            <i class="fas fa-paste me-1 text-primary"></i> 2. Copiar y Pegar Texto
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="bulkTabsContent">
                    <!-- ══ PESTAÑA 1: SUBIR ARCHIVO ══ -->
                    <div class="tab-pane fade show active" id="tab-file" role="tabpanel">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="bulk_create_users">
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Rol por Defecto <span class="text-muted">(si el archivo no especifica rol)</span>:</label>
                                    <select class="form-select border-primary" name="default_rol_id">
                                        <option value="5" selected>Estudiante (Predeterminado)</option>
                                        <option value="7">Coordinador</option>
                                        <option value="3">Docente</option>
                                        <option value="2">Administrativo / Tesorería</option>
                                        <option value="4">Psicólogo Orientador</option>
                                        <option value="6">Acudiente</option>
                                        <option value="1">Administrador</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Curso por Defecto <span class="text-muted">(si el archivo no indica grado)</span>:</label>
                                    <select class="form-select border-success" name="default_curso_id">
                                        <option value="0">-- Auto-detectar por nombre de grado en el archivo --</option>
                                        <?= renderCursosOptionsByJornada($cursosPorJornada) ?>
                                    </select>
                                </div>
                            </div>

                            <div class="p-3 border rounded bg-light mb-3">
                                <label class="form-label small fw-semibold">Seleccionar archivo Excel CSV o de Texto (.csv, .txt):</label>
                                <input type="file" class="form-control" id="bulkFileInput" name="bulk_file" accept=".csv, .txt" onchange="previewBulkFile(this)" required>
                                <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                                    <small class="text-muted"><i class="fas fa-check-circle text-success me-1"></i> Admite cualquier archivo CSV o delimitado con columnas en cualquier orden.</small>
                                    <a href="<?= APP_URL ?>/views/admin/descargar_plantilla.php" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-download me-1"></i> Descargar Plantilla Modelo (.csv)
                                    </a>
                                </div>
                            </div>

                            <!-- Contenedor de Previsualización Inteligente de Columnas -->
                            <div id="filePreviewArea" class="mb-3 d-none">
                                <div class="card border border-success-subtle bg-white shadow-sm">
                                    <div class="card-header bg-success-subtle py-2 d-flex justify-content-between align-items-center">
                                        <span class="small fw-bold text-success"><i class="fas fa-search me-1"></i> Columnas Detectadas Automáticamente:</span>
                                        <span id="previewRowsCount" class="badge bg-success">0 filas</span>
                                    </div>
                                    <div class="card-body p-3">
                                        <div id="detectedColumnsChips" class="d-flex flex-wrap gap-2 mb-2"></div>
                                        <div class="table-responsive" style="max-height: 180px;">
                                            <table class="table table-sm table-bordered extra-small mb-0" id="previewDataTable">
                                                <thead class="table-light" id="previewTableHead"></thead>
                                                <tbody id="previewTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success fw-bold px-4">
                                    <i class="fas fa-upload me-1"></i> Procesar Archivo Masivo
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- ══ PESTAÑA 2: COPIAR Y PEGAR TEXTO ══ -->
                    <div class="tab-pane fade" id="tab-paste" role="tabpanel">
                        <form method="POST">
                            <input type="hidden" name="action" value="bulk_create_users">
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Rol por Defecto:</label>
                                    <select class="form-select border-primary" name="default_rol_id">
                                        <option value="5" selected>Estudiante (Predeterminado)</option>
                                        <option value="7">Coordinador</option>
                                        <option value="3">Docente</option>
                                        <option value="2">Administrativo / Tesorería</option>
                                        <option value="4">Psicólogo Orientador</option>
                                        <option value="6">Acudiente</option>
                                        <option value="1">Administrador</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Curso por Defecto:</label>
                                    <select class="form-select border-success" name="default_curso_id">
                                        <option value="0">-- Auto-detectar por grado en el texto --</option>
                                        <?= renderCursosOptionsByJornada($cursosPorJornada) ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Pegar filas delimitadas por comas, punto y coma o tabulaciones (con o sin encabezado):</label>
                                <textarea class="form-control font-monospace extra-small" name="bulk_text" rows="8" placeholder="Ejemplo 1 (Con encabezado completo recomendado):
Documento, Nombre Completo, Grado, Correo Estudiante, Nombre Acudiente, Telefono Acudiente, Correo Acudiente
1082987090, Carlos Andrés Mendoza Gómez, 10° A, carlos.mendoza@lasallecienaga.edu.co, Jorge Mendoza, 3012345678, jorge.mendoza@gmail.com
1082987091, Valentina Herrera Pérez, 10° A, valentina.herrera@lasallecienaga.edu.co, Laura Pérez, 3109876543, laura.perez@gmail.com

Ejemplo 2 (Sin encabezados):
Andrés, Felipe, Mendoza, Castro, TI, 1082987110, 10° A, Jorge Mendoza, jorge@gmail.com, 3015551234" required></textarea>
                                <div class="alert alert-info py-2 px-3 small mt-2 mb-1 border-0 bg-info-subtle text-info-emphasis">
                                    <i class="fas fa-info-circle me-1"></i> <strong>Regla de Correos:</strong> La columna <code>Correo</code> asigna el correo institucional al estudiante. Para que el perfil del estudiante muestre el correo del acudiente, asegúrese de incluir la columna <code>Correo Acudiente</code> (ej: <code>correo.acudiente@gmail.com</code>).
                                </div>
                                <small class="text-muted d-block mt-1">El sistema soporta nombres unidos en una sola columna (ej: "Juan Pérez") o separados en Primer Nombre, Segundo Nombre, etc., y actualiza automáticamente acudientes de estudiantes ya creados.</small>
                            </div>

                            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success fw-bold px-4">
                                    <i class="fas fa-check-circle me-1"></i> Procesar Texto Pegado
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Previsualización y Análisis Dinámico del Archivo
function previewBulkFile(input) {
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split(/\r\n|\r|\n/).filter(l => l.trim().length > 0);
        if (lines.length === 0) return;

        // Detectar delimitador
        const firstLine = lines[0];
        let delim = ',';
        if (firstLine.includes(';') && (firstLine.split(';').length >= firstLine.split(',').length)) delim = ';';
        else if (firstLine.includes('\t')) delim = '\t';

        const parseLine = (l) => {
            return l.split(delim).map(c => c.replace(/^["']|["']$/g, '').trim());
        };

        const headers = parseLine(firstLine);
        const sampleRows = lines.slice(1, 4).map(parseLine);

        // Mostrar área de previsualización
        const previewArea = document.getElementById('filePreviewArea');
        previewArea.classList.remove('d-none');
        document.getElementById('previewRowsCount').textContent = (lines.length - 1) + ' registros detectados';

        // Renderizar chips de detección
        const chipsContainer = document.getElementById('detectedColumnsChips');
        chipsContainer.innerHTML = '';

        headers.forEach((h, idx) => {
            const hLower = h.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            let tag = 'Columna ' + (idx + 1);
            let badgeClass = 'bg-secondary';
            let icon = 'fa-tag';

            if (hLower.includes('acudiente') || hLower.includes('tutor') || hLower.includes('padre')) {
                tag = 'Acudiente'; badgeClass = 'bg-info text-dark'; icon = 'fa-user-shield';
            } else if (hLower.includes('correo') || hLower.includes('email')) {
                tag = 'Correo'; badgeClass = 'bg-primary'; icon = 'fa-envelope';
            } else if (hLower.includes('documento') || hLower.includes('cedula') || hLower.includes('ti') || hLower.includes('identificacion')) {
                tag = 'Documento'; badgeClass = 'bg-dark'; icon = 'fa-id-card';
            } else if (hLower.includes('nombre') || hLower.includes('estudiante') || hLower.includes('alumno')) {
                tag = 'Nombre / Estudiante'; badgeClass = 'bg-success'; icon = 'fa-user';
            } else if (hLower.includes('grado') || hLower.includes('curso') || hLower.includes('salon')) {
                tag = 'Grado / Curso'; badgeClass = 'bg-warning text-dark'; icon = 'fa-graduation-cap';
            } else if (hLower.includes('tel') || hLower.includes('cel') || hLower.includes('phone') || hLower.includes('movil') || hLower.includes('whatsapp') || hLower.includes('contacto')) {
                tag = 'Teléfono'; badgeClass = 'bg-teal text-white'; icon = 'fa-phone';
            }

            const chip = document.createElement('span');
            chip.className = `badge ${badgeClass} p-2`;
            chip.innerHTML = `<i class="fas ${icon} me-1"></i> Col ${idx+1}: <strong>${h}</strong> &rarr; <span class="badge bg-white text-dark">${tag}</span>`;
            chipsContainer.appendChild(chip);
        });

        // Renderizar tabla previa
        const thead = document.getElementById('previewTableHead');
        const tbody = document.getElementById('previewTableBody');
        thead.innerHTML = '<tr>' + headers.map(h => `<th class="text-nowrap">${h}</th>`).join('') + '</tr>';
        tbody.innerHTML = sampleRows.map(r => '<tr>' + r.map(c => `<td class="text-truncate" style="max-width:140px;">${c}</td>`).join('') + '</tr>').join('');
    };
    reader.readAsText(file);
}

// Helpers para copiar
function copyToClipboard(elementId, btn) {
    const input = document.getElementById(elementId);
    input.select();
    document.execCommand('copy');
    const original = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> ¡Copiado!';
    setTimeout(() => btn.innerHTML = original, 2000);
}

function copyCodeSnippet(btn) {
    const code = document.getElementById('appsScriptCode').innerText;
    navigator.clipboard.writeText(code).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> ¡Copiado!';
        setTimeout(() => btn.innerHTML = original, 2000);
    });
}
</script>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-card border-0 p-0">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title font-heading fw-bold">Editar Usuario Institucional</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Rol en el Sistema *</label>
                            <select class="form-select" name="rol_id" id="edit_rol_id" required onchange="toggleEditRolFields(this.value)">
                                <option value="1">Administrador</option>
                                <option value="7">Coordinador</option>
                                <option value="2">Administrativo / Tesorería</option>
                                <option value="3">Docente</option>
                                <option value="4">Psicólogo Orientador</option>
                                <option value="5">Estudiante</option>
                                <option value="6">Acudiente</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo Documento *</label>
                            <select class="form-select" name="tipo_documento" id="edit_tipo_documento" required>
                                <option value="CC">Cédula de Ciudadanía (CC)</option>
                                <option value="TI">Tarjeta de Identidad (TI)</option>
                                <option value="CE">Cédula de Extranjería (CE)</option>
                                <option value="RC">Registro Civil (RC)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Estado de Cuenta</label>
                            <select class="form-select" name="estado_cuenta" id="edit_estado_cuenta" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo / Retirado</option>
                                <option value="suspendido">Suspendido</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Documento de Identidad *</label>
                            <input type="text" class="form-control" name="documento_identidad" id="edit_documento_identidad" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Institucional *</label>
                            <input type="email" class="form-control" name="correo" id="edit_correo" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Nombre *</label>
                            <input type="text" class="form-control" name="primer_nombre" id="edit_primer_nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Nombre</label>
                            <input type="text" class="form-control" name="segundo_nombre" id="edit_segundo_nombre">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Apellido *</label>
                            <input type="text" class="form-control" name="primer_apellido" id="edit_primer_apellido" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Apellido</label>
                            <input type="text" class="form-control" name="segundo_apellido" id="edit_segundo_apellido">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nueva Contraseña (Dejar vacío para mantener)</label>
                            <input type="password" class="form-control" name="password" placeholder="Nueva clave...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono de Contacto</label>
                            <input type="text" class="form-control" name="telefono" id="edit_telefono">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Dirección de Residencia</label>
                            <input type="text" class="form-control" name="direccion" id="edit_direccion">
                        </div>

                        <!-- CAMPO CAJA / SEDE: visible solo cuando rol = Administrativo / Tesorería (2) -->
                        <div class="col-12" id="editFieldCajaSede" style="display:none;">
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-cash-register me-1"></i> <strong>Asignación de Caja / Sede de Recaudo (Tesorería)</strong>
                            </div>
                            <label class="form-label small fw-semibold">Caja / Sede Autorizada para Administrar *</label>
                            <select class="form-select border-info" name="sede_caja" id="edit_sede_caja">
                                <option value="ambas">Ambas Cajas (Sede Principal y Sede Victoria al mismo tiempo)</option>
                                <option value="principal">Solo Caja Sede Principal</option>
                                <option value="segunda">Solo Caja Sede Victoria</option>
                            </select>
                            <small class="text-muted">Permisos de operación de caja en el módulo de Tesorería.</small>
                        </div>

                        <!-- CAMPO CURSO & ACUDIENTE: visible solo cuando rol = Estudiante (5) -->
                        <div class="col-12" id="editFieldCurso" style="display:none;">
                            <div class="alert alert-primary py-2 mb-2">
                                <i class="fas fa-graduation-cap me-1"></i> <strong>Asignación de Sede, Curso Oficial & Acudiente (Estudiante)</strong>
                            </div>
                            
                            <!-- 1. Filtrar por Sede -->
                            <label class="form-label small fw-semibold text-dark mb-1">
                                <i class="fas fa-building me-1 text-primary"></i> 1. Filtrar por Sede Institucional:
                            </label>
                            <div class="btn-group btn-group-sm w-100 mb-2 shadow-sm" id="btnGroupSedeEdit" role="group">
                                <button type="button" class="btn btn-dark fw-bold btn-sede-filter active" data-sede="todas" onclick="filtrarCursosModalExt('edit_curso_id', 'todas', 'btnGroupSedeEdit', 'sede')">🌐 Ambas Sedes</button>
                                <button type="button" class="btn btn-outline-primary fw-bold btn-sede-filter" data-sede="Sede Principal" onclick="filtrarCursosModalExt('edit_curso_id', 'Sede Principal', 'btnGroupSedeEdit', 'sede')">🏢 Sede Principal</button>
                                <button type="button" class="btn btn-outline-success fw-bold btn-sede-filter" data-sede="Sede Victoria" onclick="filtrarCursosModalExt('edit_curso_id', 'Sede Victoria', 'btnGroupSedeEdit', 'sede')">🏫 Sede Victoria</button>
                            </div>

                            <!-- 2. Filtrar por Jornada -->
                            <label class="form-label small fw-semibold text-dark mb-1">
                                <i class="fas fa-clock me-1 text-primary"></i> 2. Filtrar por Jornada Escolar (1 Curso por Jornada):
                            </label>
                            <div class="btn-group btn-group-sm w-100 mb-2 shadow-sm" id="btnGroupJorEdit" role="group">
                                <button type="button" class="btn btn-dark fw-bold btn-jor-filter active" data-jornada="todas" onclick="filtrarCursosModalExt('edit_curso_id', 'todas', 'btnGroupJorEdit', 'jornada')">🌐 Ambas Jornadas</button>
                                <button type="button" class="btn btn-outline-warning text-dark fw-bold btn-jor-filter" data-jornada="Mañana" onclick="filtrarCursosModalExt('edit_curso_id', 'Mañana', 'btnGroupJorEdit', 'jornada')">☀️ Mañana (Grupo A)</button>
                                <button type="button" class="btn btn-outline-primary fw-bold btn-jor-filter" data-jornada="Tarde" onclick="filtrarCursosModalExt('edit_curso_id', 'Tarde', 'btnGroupJorEdit', 'jornada')">⛅ Tarde (Grupo B)</button>
                            </div>

                            <label class="form-label small fw-semibold">3. Curso / Grado Oficial</label>
                            <select class="form-select border-primary mb-2" name="curso_id" id="edit_curso_id">
                                <option value="">-- Sin cambiar / Mantener curso actual --</option>
                                <?= renderCursosOptionsByJornada($cursosDB) ?>
                            </select>

                            <div class="p-3 bg-light rounded border border-primary-subtle mt-2">
                                <h6 class="fw-bold text-primary mb-2 small text-uppercase">
                                    <i class="fas fa-user-shield me-1"></i> Información del Acudiente / Tutor Legal
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label extra-small fw-semibold">Nombre Completo del Acudiente</label>
                                        <input type="text" class="form-control form-control-sm" name="acudiente_nombre" id="edit_acudiente_nombre" placeholder="Nombre del tutor">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label extra-small fw-semibold">Correo Electrónico de Contacto</label>
                                        <input type="email" class="form-control form-control-sm" name="acudiente_correo" id="edit_acudiente_correo" placeholder="correo.acudiente@ejemplo.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label extra-small fw-semibold">Teléfono del Acudiente</label>
                                        <input type="text" class="form-control form-control-sm" name="acudiente_telefono" id="edit_acudiente_telefono" placeholder="300 000 0000">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 pt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-lasalle-primary">Actualizar Datos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cambio de Rol Rápido & RBAC -->
<div class="modal fade" id="modalChangeUserRole" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-user-tag text-warning me-2"></i> Asignar / Cambiar Rol de Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_user_role">
                <input type="hidden" name="user_id" id="quick_role_user_id">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="text-muted extra-small">Usuario Seleccionado:</div>
                        <strong class="fs-6 text-dark d-block" id="quick_role_user_name">—</strong>
                        <small class="text-muted" id="quick_role_user_email">—</small>
                    </div>
                    <label class="form-label small fw-bold text-dark">Nuevo Rol en el Sistema *</label>
                    <select class="form-select form-select-lg border-primary fw-bold" name="new_rol_id" id="quick_new_rol_id" required>
                        <option value="1">👑 Administrador</option>
                        <option value="7">🎓 Coordinador (Coordinación Académica & Convivencia)</option>
                        <option value="2">💼 Administrativo / Tesorería (Gestión de Cajas)</option>
                        <option value="3">👨‍🏫 Docente (Calificaciones, Logros & PEI)</option>
                        <option value="4">🧠 Psicólogo Orientador (Observador Confidencial)</option>
                        <option value="5">🎒 Estudiante (Boletines, Asistencias & Eval. Docente)</option>
                        <option value="6">👨‍👩‍👦 Acudiente (Firma & Seguimiento)</option>
                    </select>
                    <div class="mt-3 p-2 bg-light rounded border border-warning-subtle small text-muted">
                        <i class="fas fa-info-circle text-warning me-1"></i> El cambio de rol actualizará inmediatamente los permisos, vistas y accesos RBAC de este usuario.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold px-4">
                        <i class="fas fa-check-circle me-1"></i> Guardar y Aplicar Cambio de Rol
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detalle Profesional de Usuario (3 Secciones Organizadas) -->
<div class="modal fade" id="modalUserDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-card border-0 p-0 overflow-hidden">
            <div class="bg-primary text-white p-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%) !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-warning text-dark mb-2 fw-bold" id="det_badge_rol">Rol</span>
                        <h5 class="fw-bold mb-0 text-white" id="det_nombre_completo">Nombre del Usuario</h5>
                        <small class="text-white-50" id="det_correo">correo@lasallecienaga.edu.co</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-4">
                <ul class="nav nav-tabs border-bottom mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-info-personal">
                            <i class="fas fa-id-card text-primary me-1"></i> INFORMACIÓN PERSONAL
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-info-institucional">
                            <i class="fas fa-university text-success me-1"></i> INFORMACIÓN INSTITUCIONAL
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-bold small" data-bs-toggle="tab" data-bs-target="#tab-info-permisos">
                            <i class="fas fa-key text-warning me-1"></i> PERMISOS & RBAC
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Tab 1: Personal -->
                    <div class="tab-pane fade show active" id="tab-info-personal">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Nombre Completo</label>
                                <span class="fw-bold text-dark" id="det_val_nombre">—</span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Documento Identidad</label>
                                <span class="fw-bold text-dark" id="det_val_doc">—</span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Correo Institucional</label>
                                <span class="fw-bold text-dark" id="det_val_correo">—</span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Teléfono de Contacto</label>
                                <span class="fw-bold text-dark" id="det_val_telefono">—</span>
                            </div>
                            <div class="col-12">
                                <label class="text-muted extra-small d-block">Dirección de Residencia</label>
                                <span class="fw-bold text-dark" id="det_val_direccion">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Institucional -->
                    <div class="tab-pane fade" id="tab-info-institucional">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Rol en el Sistema</label>
                                <span class="fw-bold text-primary text-uppercase" id="det_val_rol">—</span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Estado de Cuenta</label>
                                <span class="fw-bold" id="det_val_estado">—</span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Año Lectivo / Período</label>
                                <span class="fw-bold text-dark">2026 • Instituto La Salle Ciénaga</span>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted extra-small d-block">Último Acceso Registrado</label>
                                <span class="fw-bold text-dark" id="det_val_acceso">—</span>
                            </div>
                            <!-- Datos del Acudiente (Estudiante) -->
                            <div class="col-12" id="det_row_acudiente" style="display:none;">
                                <div class="p-3 bg-light rounded border border-primary-subtle mt-2">
                                    <h6 class="fw-bold text-primary mb-2 small text-uppercase">
                                        <i class="fas fa-user-shield me-1"></i> Información del Acudiente / Tutor Legal
                                    </h6>
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="text-muted extra-small d-block">Nombre del Acudiente</label>
                                            <span class="fw-bold text-dark" id="det_val_acud_nombre">—</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted extra-small d-block">Correo de Contacto</label>
                                            <span class="fw-bold text-dark" id="det_val_acud_correo">—</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted extra-small d-block">Teléfono de Contacto</label>
                                            <span class="fw-bold text-dark" id="det_val_acud_telefono">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Permisos -->
                    <div class="tab-pane fade" id="tab-info-permisos">
                        <div class="p-3 bg-light rounded border mb-3">
                            <h6 class="fw-bold mb-2 text-dark"><i class="fas fa-shield-alt text-warning me-1"></i> Permisos RBAC Concedidos:</h6>
                            <div class="d-flex flex-wrap gap-2" id="det_val_permisos_badges">
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i> Lectura de Datos</span>
                                <span class="badge bg-primary"><i class="fas fa-key me-1"></i> Autenticación BCRYPT</span>
                            </div>
                        </div>
                        <p class="extra-small text-muted mb-0">Los accesos están regulados por la matriz de control de acceso basada en roles (RBAC) de AulaVirtual Salle.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4 py-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aprobar Solicitud de Admisión -->
<div class="modal fade" id="modalAprobarAdmision" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-card border-0 p-0 overflow-hidden shadow-lg">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #0F172A 0%, #166534 100%) !important;">
                <div>
                    <h5 class="modal-title font-heading fw-bold mb-0 text-white">
                        <i class="fas fa-check-circle me-2 text-warning"></i> Aprobar Admisión y Asignar Curso Oficial
                    </h5>
                    <small class="text-white-50">Al aprobar la solicitud, el cupo quedará autorizado y el cobro se habilitará inmediatamente en la Caja de Tesorería.</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="aprobar_admision">
                    <input type="hidden" name="solicitud_id" id="aprobar_solicitud_id" value="">

                    <!-- Resumen del Aspirante -->
                    <div class="p-3 rounded-3 mb-4" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                        <div class="row g-2 small">
                            <div class="col-md-6">
                                <span class="text-muted d-block">Aspirante:</span>
                                <strong class="fs-6 text-dark" id="aprobar_aspirante_nombre">—</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Documento:</span>
                                <strong class="text-dark" id="aprobar_aspirante_doc">—</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Sede Solicitada:</span>
                                <span class="badge bg-dark fs-6" id="aprobar_sede_solicitada">—</span>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted d-block">Grado Solicitado:</span>
                                <span class="badge bg-primary fs-6" id="aprobar_grado_solicitado">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-primary">Curso / Grupo Oficial a Asignar *</label>
                            
                            <!-- Filtrar por Sede -->
                            <div class="btn-group btn-group-sm w-100 mb-1 shadow-sm" id="btnGroupSedeApr" role="group">
                                <button type="button" class="btn btn-dark fw-bold btn-sede-filter active" data-sede="todas" onclick="filtrarCursosModalExt('aprobar_curso_select', 'todas', 'btnGroupSedeApr', 'sede')">🌐 Ambas Sedes</button>
                                <button type="button" class="btn btn-outline-primary fw-bold btn-sede-filter" data-sede="Sede Principal" onclick="filtrarCursosModalExt('aprobar_curso_select', 'Sede Principal', 'btnGroupSedeApr', 'sede')">🏢 Sede Principal</button>
                                <button type="button" class="btn btn-outline-success fw-bold btn-sede-filter" data-sede="Sede Victoria" onclick="filtrarCursosModalExt('aprobar_curso_select', 'Sede Victoria', 'btnGroupSedeApr', 'sede')">🏫 Sede Victoria</button>
                            </div>

                            <!-- Filtrar por Jornada -->
                            <div class="btn-group btn-group-sm w-100 mb-2 shadow-sm" id="btnGroupJorApr" role="group">
                                <button type="button" class="btn btn-dark fw-bold btn-jor-filter active" data-jornada="todas" onclick="filtrarCursosModalExt('aprobar_curso_select', 'todas', 'btnGroupJorApr', 'jornada')">🌐 Ambas Jornadas</button>
                                <button type="button" class="btn btn-outline-warning text-dark fw-bold btn-jor-filter" data-jornada="Mañana" onclick="filtrarCursosModalExt('aprobar_curso_select', 'Mañana', 'btnGroupJorApr', 'jornada')">☀️ Mañana (Grupo A)</button>
                                <button type="button" class="btn btn-outline-primary fw-bold btn-jor-filter" data-jornada="Tarde" onclick="filtrarCursosModalExt('aprobar_curso_select', 'Tarde', 'btnGroupJorApr', 'jornada')">⛅ Tarde (Grupo B)</button>
                            </div>

                            <select class="form-select fw-bold border-primary" name="curso_asignado_id" id="aprobar_curso_select" required>
                                <option value="">-- Seleccione el Curso Oficial --</option>
                                <?= renderCursosOptionsByJornada($cursosDB) ?>
                            </select>
                            <small class="text-muted" style="font-size: 0.75rem;">El estudiante será matriculado formalmente en este curso tras el pago en Caja.</small>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold text-dark">Monto de Matrícula Institucional ($ COP) *</label>
                            <div class="input-group">
                                <span class="input-group-text fw-bold text-success">$</span>
                                <input type="number" class="form-control fw-bold fs-5 text-success" name="monto_matricula" id="aprobar_monto" value="350000" min="0" step="1000" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Monto que se liquidará en la ventanilla de Tesorería/Caja.</small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Observaciones / Recomendaciones Académicas (Opcional)</label>
                        <textarea class="form-control" name="observaciones_revision" rows="2" placeholder="Ej: Admitido bajo validación de certificados de 9° grado; Requiere refuerzo en inglés..."></textarea>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-4">
                        <i class="fas fa-paper-plane me-1"></i>
                        Al confirmar, se enviará una notificación por correo al acudiente informándole la aprobación e indicando que puede acercarse a Caja a realizar el pago.
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">
                            <i class="fas fa-check-circle me-1"></i> Aprobar y Habilitar en Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rechazar Solicitud de Admisión -->
<div class="modal fade" id="modalRechazarAdmision" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-card border-0 p-0 overflow-hidden shadow">
            <div class="modal-header text-white p-3" style="background: #991B1B !important;">
                <h5 class="modal-title font-heading fw-bold mb-0 text-white">
                    <i class="fas fa-times-circle me-2"></i> Rechazar Solicitud de Admisión
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="rechazar_admision">
                    <input type="hidden" name="solicitud_id" id="rechazar_solicitud_id" value="">

                    <p class="small text-muted mb-3">
                        Aspirante: <strong class="text-dark" id="rechazar_aspirante_nombre">—</strong> (<span id="rechazar_aspirante_doc">—</span>)
                    </p>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-danger">Motivo o Justificación del Rechazo *</label>
                        <textarea class="form-control" name="motivo_rechazo" rows="3" placeholder="Ej: Cupos completos en el grado solicitado; No cumple con los requisitos del PEI..." required>Sin cupos disponibles en el grado solicitado para el periodo académico actual.</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger btn-sm fw-bold">
                            <i class="fas fa-ban me-1"></i> Confirmar Rechazo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ficha Detallada de Admisión -->
<div class="modal fade" id="modalDetalleAdmision" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content custom-card border-0 p-0 overflow-hidden shadow-lg">
            <div class="modal-header text-white p-4" style="background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 100%) !important;">
                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                    <div>
                        <span class="badge bg-warning text-dark mb-1 fw-bold" id="det_adm_estado">ESTADO</span>
                        <h5 class="modal-title font-heading fw-bold mb-0 text-white">Ficha Integral de Admisión</h5>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-light text-dark fs-6 font-monospace" id="det_adm_codigo">SOL-2026</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                    <i class="fas fa-user-graduate me-1"></i> Datos del Aspirante (Estudiante)
                </h6>
                <div class="row g-2 small mb-4">
                    <div class="col-md-6"><span class="text-muted d-block">Nombre Completo:</span><strong class="text-dark" id="det_adm_nombre">—</strong></div>
                    <div class="col-md-6"><span class="text-muted d-block">Documento de Identidad:</span><strong class="text-dark" id="det_adm_doc">—</strong></div>
                    <div class="col-md-3"><span class="text-muted d-block">Fecha Nacimiento:</span><span id="det_adm_fnac">—</span></div>
                    <div class="col-md-3"><span class="text-muted d-block">Género:</span><span id="det_adm_genero">—</span></div>
                    <div class="col-md-3"><span class="text-muted d-block">Teléfono / WhatsApp:</span><span id="det_adm_tel">—</span></div>
                    <div class="col-md-3"><span class="text-muted d-block">Correo Personal:</span><span id="det_adm_email">—</span></div>
                    <div class="col-12"><span class="text-muted d-block">Dirección de Residencia:</span><span id="det_adm_dir">—</span></div>
                    <div class="col-md-6 mt-2"><span class="text-muted d-block">Grado al que Aspira:</span><strong class="text-primary" id="det_adm_grado">—</strong></div>
                    <div class="col-md-6 mt-2"><span class="text-muted d-block">Colegio de Procedencia:</span><span id="det_adm_colegio">—</span></div>
                </div>

                <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                    <i class="fas fa-user-shield me-1"></i> Datos del Padre / Acudiente Responsable
                </h6>
                <div class="row g-2 small mb-3">
                    <div class="col-md-6"><span class="text-muted d-block">Nombre del Acudiente:</span><strong class="text-dark" id="det_adm_acud_nombre">—</strong></div>
                    <div class="col-md-3"><span class="text-muted d-block">Documento:</span><span id="det_adm_acud_doc">—</span></div>
                    <div class="col-md-3"><span class="text-muted d-block">Parentesco:</span><span id="det_adm_acud_parentesco">—</span></div>
                    <div class="col-md-4"><span class="text-muted d-block">Teléfono Celular:</span><strong class="text-success" id="det_adm_acud_tel">—</strong></div>
                    <div class="col-md-5"><span class="text-muted d-block">Correo Electrónico:</span><strong class="text-primary" id="det_adm_acud_email">—</strong></div>
                    <div class="col-md-3"><span class="text-muted d-block">Ocupación:</span><span id="det_adm_acud_ocup">—</span></div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 px-4 py-3">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/app.js"></script>
<script>
// Funciones JavaScript de Admisiones
function abrirModalAprobarAdmision(sol) {
    document.getElementById('aprobar_solicitud_id').value = sol.id;
    document.getElementById('aprobar_aspirante_nombre').textContent = (sol.primer_nombre + ' ' + (sol.segundo_nombre || '') + ' ' + sol.primer_apellido + ' ' + (sol.segundo_apellido || '')).trim();
    document.getElementById('aprobar_aspirante_doc').textContent = sol.tipo_documento + ' ' + sol.documento_identidad;
    document.getElementById('aprobar_grado_solicitado').textContent = sol.grado_solicitado + (sol.jornada_solicitada ? ' — Jornada ' + sol.jornada_solicitada : '');
    
    var nomSedeSol = sol.sede_solicitada || 'Sede Principal';
    var sedeBadgeEl = document.getElementById('aprobar_sede_solicitada');
    if (sedeBadgeEl) {
        sedeBadgeEl.textContent = (nomSedeSol.includes('Victoria') ? '🏫 ' : '🏢 ') + nomSedeSol;
    }
    document.getElementById('aprobar_monto').value = sol.monto_matricula || 350000;
    
    // Auto-filtrar cursos por sede y jornada solicitada del aspirante
    var jorSol = sol.jornada_solicitada || 'todas';
    filtrarCursosPorSedeYJornada('aprobar_curso_select', nomSedeSol, jorSol, 'btnGroupSedeApr', 'btnGroupJorApr');

    if (sol.curso_asignado_id) {
        document.getElementById('aprobar_curso_select').value = sol.curso_asignado_id;
    }
    new bootstrap.Modal(document.getElementById('modalAprobarAdmision')).show();
}

function abrirModalRechazarAdmision(sol) {
    document.getElementById('rechazar_solicitud_id').value = sol.id;
    document.getElementById('rechazar_aspirante_nombre').textContent = (sol.primer_nombre + ' ' + (sol.segundo_nombre || '') + ' ' + sol.primer_apellido + ' ' + (sol.segundo_apellido || '')).trim();
    document.getElementById('rechazar_aspirante_doc').textContent = sol.tipo_documento + ' ' + sol.documento_identidad;
    new bootstrap.Modal(document.getElementById('modalRechazarAdmision')).show();
}

function abrirDetalleAdmision(sol) {
    const fullName = (sol.primer_nombre + ' ' + (sol.segundo_nombre || '') + ' ' + sol.primer_apellido + ' ' + (sol.segundo_apellido || '')).trim();
    document.getElementById('det_adm_codigo').textContent = sol.codigo_solicitud;
    document.getElementById('det_adm_estado').textContent = sol.estado.toUpperCase();
    document.getElementById('det_adm_nombre').textContent = fullName;
    document.getElementById('det_adm_doc').textContent = sol.tipo_documento + ' ' + sol.documento_identidad;
    document.getElementById('det_adm_fnac').textContent = sol.fecha_nacimiento || 'No registrada';
    document.getElementById('det_adm_genero').textContent = sol.genero || 'No registrado';
    document.getElementById('det_adm_dir').textContent = (sol.direccion || '') + (sol.barrio ? ' - B/ ' + sol.barrio : '') + (sol.ciudad ? ' (' + sol.ciudad + ')' : '');
    document.getElementById('det_adm_tel').textContent = sol.telefono || 'No registrado';
    document.getElementById('det_adm_email').textContent = sol.correo || 'No registrado';
    document.getElementById('det_adm_grado').textContent = sol.grado_solicitado + ' (' + (sol.jornada_solicitada || 'Mañana') + ') — ' + (sol.sede_solicitada || 'Sede Principal');
    document.getElementById('det_adm_colegio').textContent = sol.colegio_origen || 'No registrado';

    document.getElementById('det_adm_acud_nombre').textContent = sol.acudiente_nombre;
    document.getElementById('det_adm_acud_doc').textContent = sol.acudiente_tipo_doc + ' ' + sol.acudiente_documento;
    document.getElementById('det_adm_acud_parentesco').textContent = sol.acudiente_parentesco;
    document.getElementById('det_adm_acud_tel').textContent = sol.acudiente_telefono;
    document.getElementById('det_adm_acud_email').textContent = sol.acudiente_correo;
    document.getElementById('det_adm_acud_ocup').textContent = sol.acudiente_ocupacion || 'No registrada';

    new bootstrap.Modal(document.getElementById('modalDetalleAdmision')).show();
}

function filtrarAdmisionesPorEstado(estado, btn) {
    if (btn) {
        document.querySelectorAll('#btnGroupFiltroAdmisiones .btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
    const rows = document.querySelectorAll('#tablaAdmisiones tbody tr');
    rows.forEach(r => {
        const rowEstado = r.getAttribute('data-estado');
        if (estado === 'todos' || rowEstado === estado) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function buscarEnTablaAdmisiones() {
    const q = (document.getElementById('searchAdmisionesInput')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#tablaAdmisiones tbody tr');
    rows.forEach(r => {
        const str = (r.getAttribute('data-search') || '').toLowerCase();
        if (!q || str.includes(q)) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

// Manejador de Navegación por Módulos
function switchAdminModule(moduleId) {
    document.querySelectorAll('.sidebar-link[data-module]').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.admin-module-section').forEach(sec => sec.classList.remove('active'));

    const targetLink = document.querySelector(`.sidebar-link[data-module="${moduleId}"]`);
    const targetSection = document.getElementById(`module-${moduleId}`);

    if (targetLink) targetLink.classList.add('active');
    if (targetSection) targetSection.classList.add('active');

    const titleMap = {
        'dashboard': 'Dashboard Executive Global',
        'admisiones': 'Admisiones & Nuevos Aspirantes',
        'institucion': 'Módulo Institución',
        'usuarios': 'Usuarios & Permisos RBAC',
        'academico': 'Gestión Académica & Cargas',
        'evaluacion': 'Evaluación & Boletines Institucionales',
        'pei': 'Planes de Clase & PEI',
        'reportes': 'Reportes & Estadísticas',
        'auditoria': 'Auditoría & Migración ETL',
        'configuracion': 'Configuración del Sistema'
    };
    const topbarTitle = document.getElementById('topbarModuleTitle');
    if (topbarTitle && titleMap[moduleId]) {
        topbarTitle.textContent = titleMap[moduleId];
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Filtrar Tabla CAD por Curso
function filtrarTablaCAD() {
    const cursoSel = (document.getElementById('cadCourseFilter')?.value || '').toLowerCase();
    const rows = document.querySelectorAll('#tableCAD tbody tr');

    rows.forEach(row => {
        const rowCurso = (row.getAttribute('data-curso') || '').toLowerCase();
        if (!cursoSel || rowCurso.includes(cursoSel)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Abrir boletín en pestaña nueva con el diseño oficial
function abrirBoletinAdmin(estudianteId) {
    const periodo = document.getElementById('bol_periodo')?.value || 1;
    const url = `<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiante_id=${estudianteId}&periodo=${periodo}`;
    window.open(url, '_blank');
}

// Imprimir boletines masivos seleccionados en UN SOLO DOCUMENTO PDF (Panel Consola)
function imprimirBoletinesSeleccionados() {
    const periodo = document.getElementById('bol_periodo')?.value || 'todos';
    const selectedCheckboxes = document.querySelectorAll('.chk-student-boletin:checked');
    
    if (selectedCheckboxes.length === 0) {
        alert('Por favor seleccione al menos un estudiante de la lista para generar el PDF comprimido.');
        return;
    }

    const ids = Array.from(selectedCheckboxes).map(chk => chk.value).join(',');
    const url = `<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiantes=${ids}&periodo=${periodo}`;
    window.open(url, '_blank');
}

// Funciones del Generador Masivo de Boletines PDF (#tableBoletin)
function filtrarListaBoletin() {
    const cursoFiltro = (document.getElementById('bolFiltCurso')?.value || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#tableBoletin tbody tr');
    rows.forEach(row => {
        const rowCurso = (row.getAttribute('data-curso') || '').toLowerCase().trim();
        if (!cursoFiltro || rowCurso.includes(cursoFiltro) || rowCurso === cursoFiltro) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function actualizarContadorBoletines() {
    const selCount = document.querySelectorAll('#tableBoletin .boletin-check:checked').length;
    const badge = document.getElementById('bolCountBadge');
    if (badge) {
        badge.textContent = selCount;
        badge.className = selCount > 0 ? 'badge bg-warning text-dark rounded-pill px-2' : 'badge bg-white text-dark rounded-pill px-2';
    }
}

function seleccionarTodosBoletin(check) {
    const rows = document.querySelectorAll('#tableBoletin tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const chk = row.querySelector('.boletin-check');
            if (chk) chk.checked = check;
        }
    });
    const master = document.getElementById('checkAllBoletin');
    if (master) master.checked = check;
    actualizarContadorBoletines();
}

function generarBoletinesMasivos() {
    const periodo = document.getElementById('bolPeriodo')?.value || 'todos';
    const selectedCheckboxes = document.querySelectorAll('#tableBoletin .boletin-check:checked');
    
    if (selectedCheckboxes.length === 0) {
        alert('Por favor seleccione al menos un estudiante de la lista para generar los boletines en PDF.');
        return;
    }

    const ids = Array.from(selectedCheckboxes).map(chk => chk.value).join(',');
    const url = `<?= APP_URL ?>/views/comunidad/boletin_print.php?estudiantes=${ids}&periodo=${periodo}`;
    window.open(url, '_blank');
}

// Seleccionar / Deseleccionar Todos los Estudiantes de la Lista
function toggleSelectAllBoletines(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.chk-student-boletin');
    checkboxes.forEach(chk => {
        if (chk.closest('tr').style.display !== 'none') {
            chk.checked = masterCheckbox.checked;
        }
    });
}

// Filtrar Estudiantes del Generador de Boletines por Grado, Curso o Estado
function filtrarEstudiantesBoletin() {
    const grado = (document.getElementById('bol_grado')?.value || '').toLowerCase();
    const curso = (document.getElementById('bol_curso')?.value || '').toLowerCase();
    const estado = (document.getElementById('bol_alumnos')?.value || '').toLowerCase();
    
    const rows = document.querySelectorAll('#tableEstudiantesBoletin tbody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowGrado = (row.getAttribute('data-grado') || '').toLowerCase();
        const rowCurso = (row.getAttribute('data-curso') || '').toLowerCase();
        const rowEstado = (row.getAttribute('data-estado') || '').toLowerCase();

        const matchGrado = !grado || rowGrado.includes(grado);
        const matchCurso = !curso || rowCurso.includes(curso);
        const matchEstado = (estado === 'todos') || (!estado || rowEstado === estado);

        if (matchGrado && matchCurso && matchEstado) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countSpan = document.getElementById('bol_count_filtered');
    if (countSpan) countSpan.textContent = visibleCount;
}

// Inicialización de Eventos y Filtros
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar-link[data-module]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const mod = this.getAttribute('data-module');
            switchAdminModule(mod);
            if (window.innerWidth < 992) {
                document.querySelector('.app-sidebar')?.classList.remove('show');
            }
        });
    });

    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(`module-${hash}`)) {
        switchAdminModule(hash);
    }

    // Filtrado Profesional de Usuarios
    const searchInput = document.getElementById('userSearchInput');
    const roleFilter = document.getElementById('userRoleFilter');
    const statusFilter = document.getElementById('userStatusFilter');
    const userRows = document.querySelectorAll('#usersMainTable tbody tr');

    function filterUsersTable() {
        const query = (searchInput?.value || '').toLowerCase().trim();
        const role = (roleFilter?.value || '').toLowerCase();
        const status = (statusFilter?.value || '').toLowerCase();

        userRows.forEach(row => {
            const rowSearch = row.getAttribute('data-search') || '';
            const rowRole = row.getAttribute('data-role') || '';
            const rowStatus = row.getAttribute('data-status') || '';

            const matchQuery = !query || rowSearch.includes(query);
            const matchRole = !role || rowRole === role;
            const matchStatus = !status || rowStatus === status;

            if (matchQuery && matchRole && matchStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput?.addEventListener('input', filterUsersTable);
    roleFilter?.addEventListener('change', filterUsersTable);
    statusFilter?.addEventListener('change', filterUsersTable);
});

window.cursosDataGlobal = <?= json_encode($cursosDB) ?>;
window.CURSOS_OFICIALES_CATALOGO = <?= json_encode($cursosDB, JSON_UNESCAPED_UNICODE) ?>;

// Inicializador de caché DOM para todos los selectores de curso por jornada y sede
document.addEventListener('DOMContentLoaded', function() {
    ['regCursoSelect', 'edit_curso_id', 'aprobar_curso_select'].forEach(function(sId) {
        var el = document.getElementById(sId);
        if (el) {
            el.dataset.fullHtml = el.innerHTML;
        }
    });
});

window.cursosFilterState = window.cursosFilterState || {};

function buildCursosFullHtml() {
    var full = '<option value="">-- Seleccione el Curso Oficial --</option>';
    var catalogo = (window.CURSOS_OFICIALES_CATALOGO && window.CURSOS_OFICIALES_CATALOGO.length > 0)
        ? window.CURSOS_OFICIALES_CATALOGO
        : (window.cursosDataGlobal || []);
    
    var sedesMap = {
        'Sede Principal': { icon: '🏢', 'Mañana': [], 'Tarde': [] },
        'Sede Victoria':  { icon: '🏫', 'Mañana': [], 'Tarde': [] }
    };
    catalogo.forEach(function(c) {
        var s = (c.sede && c.sede.toLowerCase().includes('victoria')) ? 'Sede Victoria' : 'Sede Principal';
        var j = (c.jornada && c.jornada.toLowerCase() === 'tarde') ? 'Tarde' : 'Mañana';
        if (!sedesMap[s]) sedesMap[s] = { icon: '🏫', 'Mañana': [], 'Tarde': [] };
        sedesMap[s][j].push(c);
    });

    for (var sName in sedesMap) {
        var sObj = sedesMap[sName];
        ['Mañana', 'Tarde'].forEach(function(jName) {
            var arr = sObj[jName];
            if (arr.length > 0) {
                var jIcon = (jName === 'Tarde') ? '⛅' : '☀️';
                full += '<optgroup label="' + sObj.icon + ' ' + sName.toUpperCase() + ' — ' + jIcon + ' ' + jName.toUpperCase() + ' (' + arr.length + ' Cursos)">';
                arr.forEach(function(c) {
                    full += '<option value="' + c.id + '" data-jornada="' + jName + '" data-sede="' + sName + '">' + jIcon + ' ' + jName + ' — ' + c.grado + ' ' + c.grupo + ' (' + sName + ' - ' + c.ano_lectivo + ')</option>';
                });
                full += '</optgroup>';
            }
        });
    }
    return full;
}

function filtrarCursosModalExt(selectId, filterVal, btnGroupId, filterType) {
    var sel = document.getElementById(selectId);
    if (!sel) return;

    if (!window.cursosFilterState[selectId]) {
        window.cursosFilterState[selectId] = { sede: 'todas', jornada: 'todas' };
    }
    
    if (filterType === 'sede') {
        window.cursosFilterState[selectId].sede = filterVal;
    } else if (filterType === 'jornada') {
        window.cursosFilterState[selectId].jornada = filterVal;
    }

    var activeSede = window.cursosFilterState[selectId].sede || 'todas';
    var activeJor = window.cursosFilterState[selectId].jornada || 'todas';

    // 1. Guardar o restaurar el catálogo completo
    if (!sel.dataset.fullHtml || !sel.dataset.fullHtml.includes('VICTORIA')) {
        sel.dataset.fullHtml = (sel.innerHTML.includes('VICTORIA')) ? sel.innerHTML : buildCursosFullHtml();
    }

    // 2. Actualizar estado visual de los botones del grupo clicado
    if (btnGroupId) {
        var group = document.getElementById(btnGroupId);
        if (group) {
            var selector = (filterType === 'sede') ? '.btn-sede-filter' : '.btn-jor-filter';
            var btns = group.querySelectorAll(selector);
            btns.forEach(function(b) {
                var attr = (filterType === 'sede') ? b.getAttribute('data-sede') : b.getAttribute('data-jornada');
                if ((attr || '').toLowerCase() === filterVal.toLowerCase()) {
                    b.classList.remove('btn-outline-primary', 'btn-outline-warning', 'btn-outline-success', 'btn-outline-secondary', 'btn-outline-dark');
                    b.classList.add('active');
                    if (filterType === 'sede') {
                        if (filterVal.includes('Victoria')) b.classList.add('btn-success', 'text-white', 'fw-bold');
                        else if (filterVal.includes('Principal')) b.classList.add('btn-primary', 'text-white', 'fw-bold');
                        else b.classList.add('btn-dark', 'text-white', 'fw-bold');
                    } else {
                        if (filterVal.toLowerCase() === 'tarde') b.classList.add('btn-primary', 'text-white', 'fw-bold');
                        else if (filterVal.toLowerCase().includes('mañana') || filterVal.toLowerCase().includes('manana')) b.classList.add('btn-warning', 'text-dark', 'fw-bold');
                        else b.classList.add('btn-dark', 'text-white', 'fw-bold');
                    }
                } else {
                    b.classList.remove('active', 'btn-primary', 'btn-warning', 'btn-success', 'btn-dark', 'text-white', 'text-dark', 'fw-bold');
                    if (filterType === 'sede') {
                        if ((attr || '').includes('Victoria')) b.classList.add('btn-outline-success');
                        else if ((attr || '').includes('Principal')) b.classList.add('btn-outline-primary');
                        else b.classList.add('btn-outline-secondary');
                    } else {
                        if ((attr || '').toLowerCase() === 'tarde') b.classList.add('btn-outline-primary');
                        else if ((attr || '').toLowerCase().includes('mañana') || (attr || '').toLowerCase().includes('manana')) b.classList.add('btn-outline-warning', 'text-dark');
                        else b.classList.add('btn-outline-secondary');
                    }
                }
            });
        }
    }

    var currentVal = sel.value;

    // 3. Crear contenedor temporal con el catálogo completo almacenado
    var temp = document.createElement('select');
    temp.innerHTML = sel.dataset.fullHtml;

    var defaultOpt = temp.querySelector('option[value=""]');
    var optgroups = temp.querySelectorAll('optgroup');

    sel.innerHTML = '';
    if (defaultOpt) sel.appendChild(defaultOpt.cloneNode(true));

    var gruposAgregados = 0;
    if (optgroups && optgroups.length > 0) {
        optgroups.forEach(function(og) {
            var lbl = (og.label || '').toLowerCase();
            
            var sedeMatch = false;
            if (activeSede === 'todas') sedeMatch = true;
            else if (activeSede.toLowerCase().includes('victoria') && lbl.includes('victoria')) sedeMatch = true;
            else if (activeSede.toLowerCase().includes('principal') && lbl.includes('principal')) sedeMatch = true;

            var jorMatch = false;
            if (activeJor === 'todas') jorMatch = true;
            else if (activeJor.toLowerCase() === 'tarde' && lbl.includes('tarde')) jorMatch = true;
            else if ((activeJor.toLowerCase().includes('mañana') || activeJor.toLowerCase().includes('manana')) && (lbl.includes('mañana') || lbl.includes('manana'))) jorMatch = true;

            if (sedeMatch && jorMatch) {
                var cloneOg = og.cloneNode(true);
                if (currentVal) {
                    var matchOpt = cloneOg.querySelector('option[value="' + currentVal + '"]');
                    if (matchOpt) matchOpt.selected = true;
                }
                sel.appendChild(cloneOg);
                gruposAgregados++;
            }
        });
    }

    // 4. Si el select usaba opciones directas sin optgroups
    if (gruposAgregados === 0) {
        var directOptions = temp.querySelectorAll('option:not([value=""])');
        directOptions.forEach(function(opt) {
            var jorAttr = (opt.getAttribute('data-jornada') || opt.textContent || '').toLowerCase();
            var sedeAttr = (opt.getAttribute('data-sede') || opt.textContent || '').toLowerCase();
            
            var sMatch = (activeSede === 'todas' || sedeAttr.includes(activeSede.toLowerCase()));
            var jMatch = (activeJor === 'todas' || jorAttr.includes(activeJor.toLowerCase()));

            if (sMatch && jMatch) {
                var cloneOpt = opt.cloneNode(true);
                if (String(cloneOpt.value) === String(currentVal)) cloneOpt.selected = true;
                sel.appendChild(cloneOpt);
            }
        });
    }
}

function filtrarCursosModal(selectId, jornada, btnGroupId) {
    filtrarCursosModalExt(selectId, jornada, btnGroupId, 'jornada');
}

function filtrarCursosPorSedeYJornada(selectId, sede, jornada, sedeGroupId, jorGroupId) {
    filtrarCursosModalExt(selectId, sede, sedeGroupId, 'sede');
    filtrarCursosModalExt(selectId, jornada, jorGroupId, 'jornada');
}

function filtrarAdmisionesPorSede(sede, btn) {
    if (btn) {
        btn.parentElement.querySelectorAll('.btn').forEach(b => b.classList.remove('active', 'btn-dark', 'text-white'));
        btn.classList.add('active', 'btn-dark', 'text-white');
    }
    const rows = document.querySelectorAll('#tablaAdmisiones tbody tr');
    rows.forEach(r => {
        const rowSede = r.getAttribute('data-sede') || 'Sede Principal';
        if (sede === 'todas' || rowSede.toLowerCase().includes(sede.toLowerCase())) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

// Muestra/oculta campos condicionales en el modal de edición
function toggleEditRolFields(rolId) {
    var fCurso = document.getElementById('editFieldCurso');
    if (fCurso) fCurso.style.display = (String(rolId) === '5') ? 'block' : 'none';
    var fCaja = document.getElementById('editFieldCajaSede');
    if (fCaja) fCaja.style.display = (String(rolId) === '2') ? 'block' : 'none';
}

// Modal Cambio de Rol Rápido
function openQuickRoleModal(user) {
    document.getElementById('quick_role_user_id').value = user.id;
    const fullname = [user.primer_nombre, user.segundo_nombre, user.primer_apellido, user.segundo_apellido].filter(Boolean).join(' ') || ('Usuario #' + user.id);
    document.getElementById('quick_role_user_name').textContent = fullname;
    document.getElementById('quick_role_user_email').textContent = (user.correo || '') + ' • Rol actual: ' + (user.nombre_rol || 'Indefinido');
    document.getElementById('quick_new_rol_id').value = user.rol_id;
    new bootstrap.Modal(document.getElementById('modalChangeUserRole')).show();
}

// Modal Editar Usuario
function openEditModal(user, focusRole = false) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_rol_id').value = user.rol_id;
    document.getElementById('edit_tipo_documento').value = user.tipo_documento || 'CC';
    document.getElementById('edit_estado_cuenta').value = user.estado_cuenta;
    document.getElementById('edit_documento_identidad').value = user.documento_identidad || '';
    document.getElementById('edit_correo').value = user.correo;
    document.getElementById('edit_primer_nombre').value = user.primer_nombre || '';
    document.getElementById('edit_segundo_nombre').value = user.segundo_nombre || '';
    document.getElementById('edit_primer_apellido').value = user.primer_apellido || '';
    document.getElementById('edit_segundo_apellido').value = user.segundo_apellido || '';
    document.getElementById('edit_telefono').value = user.telefono || '';
    document.getElementById('edit_direccion').value = user.direccion || '';

    // Sede / Caja autorizada para rol 2 (Administrativo)
    var editSede = document.getElementById('edit_sede_caja');
    if (editSede) editSede.value = user.sede_caja || 'ambas';

    // Mostrar campos condicionales según el rol
    toggleEditRolFields(user.rol_id);
    if (String(user.rol_id) === '5') {
        var sel = document.getElementById('edit_curso_id');
        if (sel && user.curso_id) sel.value = user.curso_id;
        
        var editAcudNom  = document.getElementById('edit_acudiente_nombre');
        var editAcudMail = document.getElementById('edit_acudiente_correo');
        var editAcudTel  = document.getElementById('edit_acudiente_telefono');
        if (editAcudNom)  editAcudNom.value  = user.acudiente_nombre || '';
        if (editAcudMail) editAcudMail.value = user.acudiente_correo || '';
        if (editAcudTel)  editAcudTel.value  = user.acudiente_telefono || '';
    }

    var modal = new bootstrap.Modal(document.getElementById('modalEditUser'));
    modal.show();

    if (focusRole) {
        setTimeout(function() {
            var rSel = document.getElementById('edit_rol_id');
            if (rSel) {
                rSel.focus();
                rSel.classList.add('border-warning');
                setTimeout(() => rSel.classList.remove('border-warning'), 2500);
            }
        }, 350);
    }
}

// Modal Detalle Usuario
function openUserDetailModal(user) {
    const fullName = [user.primer_nombre, user.segundo_nombre, user.primer_apellido, user.segundo_apellido].filter(Boolean).join(' ');
    
    document.getElementById('det_badge_rol').textContent = (user.nombre_rol || 'Usuario').toUpperCase();
    document.getElementById('det_nombre_completo').textContent = fullName || 'Usuario sin Nombre';
    document.getElementById('det_correo').textContent = user.correo || '—';

    document.getElementById('det_val_nombre').textContent = fullName || '—';
    document.getElementById('det_val_doc').textContent = (user.tipo_documento || 'CC') + ' ' + (user.documento_identidad || 'N/D');
    document.getElementById('det_val_correo').textContent = user.correo || '—';
    document.getElementById('det_val_telefono').textContent = user.telefono || 'No registrado';
    document.getElementById('det_val_direccion').textContent = user.direccion || 'No registrada';

    document.getElementById('det_val_rol').textContent = user.nombre_rol || '—';
    document.getElementById('det_val_estado').textContent = (user.estado_cuenta || 'activo').toUpperCase();
    document.getElementById('det_val_acceso').textContent = user.ultimo_acceso || 'Recientemente';

    // Sección Acudiente
    var rowAcud = document.getElementById('det_row_acudiente');
    if (rowAcud) {
        var isEst = (String(user.rol_id) === '5' || (user.nombre_rol && user.nombre_rol.toLowerCase() === 'estudiante'));
        rowAcud.style.display = isEst ? 'block' : 'none';
        if (isEst) {
            document.getElementById('det_val_acud_nombre').textContent   = user.acudiente_nombre || 'No registrado';
            if (user.acudiente_correo && user.acudiente_correo.trim() !== '') {
                document.getElementById('det_val_acud_correo').innerHTML = `<span class="text-success fw-bold"><i class="fas fa-envelope me-1"></i>${user.acudiente_correo}</span>`;
            } else {
                document.getElementById('det_val_acud_correo').innerHTML = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-2"><i class="fas fa-exclamation-triangle me-1"></i>No registrado</span> <small class="text-muted d-block extra-small mt-1">(Citaciones enviadas al correo institucional del estudiante)</small>`;
            }
            document.getElementById('det_val_acud_telefono').textContent = user.acudiente_telefono || 'No registrado';
        }
    }

    const badgesBox = document.getElementById('det_val_permisos_badges');
    if (badgesBox) {
        let permHTML = '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Lectura de Datos</span>';
        if (user.nombre_rol === 'administrador') {
            permHTML += '<span class="badge bg-primary me-1"><i class="fas fa-cog"></i> Control Total RBAC</span>';
            permHTML += '<span class="badge bg-warning text-dark"><i class="fas fa-database"></i> Acceso ETL</span>';
        } else if (user.nombre_rol === 'docente') {
            permHTML += '<span class="badge bg-info text-dark me-1"><i class="fas fa-pen"></i> Registro de Notas</span>';
            permHTML += '<span class="badge bg-secondary"><i class="fas fa-file-alt"></i> Carga de Planes PEI</span>';
        }
        badgesBox.innerHTML = permHTML;
    }

    var modal = new bootstrap.Modal(document.getElementById('modalUserDetail'));
    modal.show();
}

// ══════════════════════════════════════════════════════════════════
// FUNCIONES DEL MÓDULO DE EVALUACIÓN DOCENTE & GRÁFICAS DINÁMICAS
// ══════════════════════════════════════════════════════════════════
let evalRankingChartInstance = null;
let evalRadarChartInstance = null;

function switchEvalTab(tabId) {
    document.querySelectorAll('[id^="etab-"]').forEach(b => {
        b.classList.remove('active', 'bg-primary', 'text-white');
    });
    const currentBtn = document.getElementById('etab-' + tabId);
    if (currentBtn) {
        currentBtn.classList.add('active', 'bg-primary', 'text-white');
    }

    const bolPanel = document.getElementById('eval-tab-boletines');
    const docPanel = document.getElementById('eval-tab-docente');

    if (tabId === 'eval-docente') {
        if (bolPanel) bolPanel.style.display = 'none';
        if (docPanel) {
            docPanel.style.display = 'block';
            setTimeout(initEvalDocenteCharts, 100);
        }
    } else {
        if (bolPanel) bolPanel.style.display = 'block';
        if (docPanel) docPanel.style.display = 'none';
    }
}

function initEvalDocenteCharts() {
    if (typeof Chart === 'undefined') return;

    // 1. Gráfica de Ranking y Calificación por Docente (Barras Horizontales)
    const ctxRank = document.getElementById('chartDocentesRanking');
    if (ctxRank) {
        const labelsDocs = <?= json_encode(array_map(function($d) { return trim(($d['primer_nombre'] ?? '') . ' ' . ($d['primer_apellido'] ?? '')); }, $evalDocentesStats)) ?>;
        const scoresDocs = <?= json_encode(array_map(function($d) { return floatval($d['promedio_general']); }, $evalDocentesStats)) ?>;

        const bgColors = scoresDocs.map(s => {
            if (s >= 4.5) return 'rgba(40, 167, 69, 0.85)';
            if (s >= 4.0) return 'rgba(13, 110, 253, 0.85)';
            if (s >= 3.0) return 'rgba(255, 193, 7, 0.85)';
            return 'rgba(220, 53, 69, 0.85)';
        });

        if (evalRankingChartInstance) evalRankingChartInstance.destroy();
        evalRankingChartInstance = new Chart(ctxRank, {
            type: 'bar',
            data: {
                labels: labelsDocs.length ? labelsDocs : ['Docente 1', 'Docente 2'],
                datasets: [{
                    label: 'Puntaje Promedio (/5.00)',
                    data: scoresDocs.length ? scoresDocs : [4.8, 4.6],
                    backgroundColor: bgColors.length ? bgColors : ['#0d6efd', '#28a745'],
                    borderRadius: 6,
                    maxBarThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        min: 0,
                        max: 5,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ` Calificación: ${ctx.parsed.x} / 5.00`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Gráfica Radar de Criterios Institucionales
    const ctxRadar = document.getElementById('chartCriteriosRadar');
    if (ctxRadar) {
        const criteriaData = [
            <?= floatval($evalCriteriosPromedio['dominio'] ?: 4.8) ?>,
            <?= floatval($evalCriteriosPromedio['metodologia'] ?: 4.6) ?>,
            <?= floatval($evalCriteriosPromedio['respeto'] ?: 4.9) ?>,
            <?= floatval($evalCriteriosPromedio['evaluacion'] ?: 4.7) ?>,
            <?= floatval($evalCriteriosPromedio['motivacion'] ?: 4.8) ?>
        ];

        if (evalRadarChartInstance) evalRadarChartInstance.destroy();
        evalRadarChartInstance = new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: [
                    'Dominio Temático',
                    'Metodología Didáctica',
                    'Respeto & Empatía',
                    'Claridad Evaluativa',
                    'Motivación'
                ],
                datasets: [{
                    label: 'Promedio Salle',
                    data: criteriaData,
                    backgroundColor: 'rgba(15, 23, 42, 0.2)',
                    borderColor: '#0F172A',
                    pointBackgroundColor: '#f59e0b',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#f59e0b',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        min: 0,
                        max: 5,
                        ticks: { stepSize: 1, display: false },
                        pointLabels: {
                            font: { size: 10, weight: 'bold' }
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
}

// Modal Ver Comentarios de Estudiantes para un Docente
const evalComentariosData = <?= json_encode($evalComentarios) ?>;

function verComentariosDocente(docenteId, docenteNombre) {
    document.getElementById('modalDocenteNombreTitulo').textContent = docenteNombre;
    const container = document.getElementById('listaComentariosDocenteContainer');
    container.innerHTML = '';

    const comms = evalComentariosData.filter(c => String(c.docente_id) === String(docenteId));
    if (comms.length === 0) {
        container.innerHTML = '<div class="alert alert-light border text-center text-muted py-4"><i class="fas fa-comment-slash me-1"></i> Aún no se han registrado comentarios escritos de estudiantes para este docente.</div>';
    } else {
        comms.forEach(c => {
            const card = document.createElement('div');
            card.className = 'p-3 bg-light rounded-3 border mb-2 shadow-sm';
            card.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-primary text-white"><i class="fas fa-star text-warning me-1"></i> ${c.puntaje_promedio} / 5.0</span>
                    <small class="text-muted">${c.created_at || 'Reciente'}</small>
                </div>
                <p class="mb-0 text-dark small fst-italic">"${c.comentarios}"</p>
                ${c.grado ? `<div class="extra-small text-muted mt-1"><i class="fas fa-graduation-cap me-1"></i> Estudiante de ${c.grado} ${c.grupo || ''} • Jornada ${c.jornada || 'Mañana'}</div>` : ''}
            `;
            container.appendChild(card);
        });
    }

    new bootstrap.Modal(document.getElementById('modalComentariosDocente')).show();
}

// Previsualización Inteligente de Archivo de Carga Masiva
function previewBulkFile(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split(/\r\n|\r|\n/).map(l => l.trim()).filter(l => l.length > 0);
        if (lines.length === 0) return;

        const area = document.getElementById('filePreviewArea');
        const rowsBadge = document.getElementById('previewRowsCount');
        const chipsBox = document.getElementById('detectedColumnsChips');
        const thead = document.getElementById('previewTableHead');
        const tbody = document.getElementById('previewTableBody');

        if (!area || !chipsBox || !thead || !tbody) return;

        // Detectar separador
        const firstLine = lines[0];
        let sep = ',';
        if (firstLine.includes(';') && firstLine.split(';').length >= firstLine.split(',').length) sep = ';';
        else if (firstLine.includes('\t')) sep = '\t';

        const headerCols = firstLine.split(sep).map(c => c.replace(/^["']|["']$/g, '').trim());
        const hasHeaders = headerCols.some(h => /nombre|documento|correo|email|acudiente|grado|curso/i.test(h));
        const dataLines = hasHeaders ? lines.slice(1) : lines;

        rowsBadge.textContent = `${dataLines.length} filas con datos`;

        // Analizar qué columnas están presentes
        let hasAcudCorreo = false;
        let hasAcudNom = false;
        let hasAcudTel = false;
        let hasEstCorreo = false;

        chipsBox.innerHTML = '';
        headerCols.forEach((colName, idx) => {
            const norm = colName.toLowerCase();
            let isAcud = /acudiente|tutor|padre|madre/i.test(norm);
            let isMail = /correo|email|mail/i.test(norm);
            let isTel = /tel|cel|movil|whatsapp/i.test(norm);

            let chipClass = 'bg-primary-subtle text-primary border border-primary-subtle';
            let label = colName;

            if (isAcud && isMail) {
                hasAcudCorreo = true;
                chipClass = 'bg-success text-white';
                label = `✓ ${colName} (Correo Acudiente)`;
            } else if (isMail && !isAcud) {
                hasEstCorreo = true;
                chipClass = 'bg-info-subtle text-dark border';
                label = `${colName} (Correo Estudiante)`;
            } else if (isAcud && isTel) {
                hasAcudTel = true;
                chipClass = 'bg-success-subtle text-success border border-success-subtle';
            } else if (isAcud) {
                hasAcudNom = true;
                chipClass = 'bg-success-subtle text-success border border-success-subtle';
            }

            chipsBox.innerHTML += `<span class="badge ${chipClass} p-2">${label}</span>`;
        });

        // Alerta si falta Correo de Acudiente
        if (!hasAcudCorreo && hasHeaders) {
            chipsBox.innerHTML += `<span class="badge bg-warning text-dark p-2 w-100 mt-2 text-start"><i class="fas fa-exclamation-triangle me-1"></i> <strong>Atención:</strong> No se detectó columna para 'Correo Acudiente'. Los acudientes se registrarán sin correo en su perfil. Para incluirlo, use una columna llamada 'Correo Acudiente'.</span>`;
        }

        // Construir tabla de muestra (primeras 3 filas)
        let thHtml = '<tr>';
        headerCols.forEach(h => { thHtml += `<th>${h}</th>`; });
        thHtml += '</tr>';
        thead.innerHTML = thHtml;

        let tbHtml = '';
        const sampleLines = dataLines.slice(0, 3);
        sampleLines.forEach(line => {
            const cols = line.split(sep).map(c => c.replace(/^["']|["']$/g, '').trim());
            tbHtml += '<tr>';
            cols.forEach(c => { tbHtml += `<td>${c}</td>`; });
            tbHtml += '</tr>';
        });
        tbody.innerHTML = tbHtml;

        area.classList.remove('d-none');
    };

    reader.readAsText(file);
}

// =========================================================================
// MOTOR DEL CENTRO DIGITAL DE CERTIFICADOS OFICIALES LA SALLE
// =========================================================================
const CERT_STUDENTS = <?= json_encode($estudiantesCertificados, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
const CERT_TEACHERS = <?= json_encode($docentesCertificados, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
const CERT_GRADES = <?= json_encode($calificacionesPorEstudiante, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || {};

let currentCertDoc = 'estudio';
let currentCertStudentId = CERT_STUDENTS.length > 0 ? CERT_STUDENTS[0].id : null;
let currentCertTeacherId = CERT_TEACHERS.length > 0 ? CERT_TEACHERS[0].id : null;

// Inicialización de Pestañas de Documentos
document.addEventListener('DOMContentLoaded', function() {
    const tabsContainer = document.getElementById('cert_doc_tabs');
    if (tabsContainer) {
        tabsContainer.querySelectorAll('.cert-nav-pill').forEach(btn => {
            btn.addEventListener('click', function() {
                tabsContainer.querySelectorAll('.cert-nav-pill').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentCertDoc = this.getAttribute('data-doc');
                cambiarPestanaCertificado(currentCertDoc);
            });
        });
    }

    // Inicializar selectores con el primer elemento si existe
    if (currentCertStudentId) {
        const selEst = document.getElementById('cert_sel_estudiante');
        if (selEst) selEst.value = currentCertStudentId;
    }
    if (currentCertTeacherId) {
        const selDoc = document.getElementById('cert_sel_docente');
        if (selDoc) selDoc.value = currentCertTeacherId;
    }

    asegurarOpcionesDocentes();

    // Primer renderizado
    renderizarCertificadoDigital();
});

function asegurarOpcionesDocentes() {
    const selDoc = document.getElementById('cert_sel_docente');
    if (!selDoc) return;

    // Si el select no tiene opciones de docentes suficientes, poblarlo
    if (selDoc.options.length <= 1) {
        if (typeof CERT_TEACHERS !== 'undefined' && CERT_TEACHERS && CERT_TEACHERS.length > 0) {
            llenarSelectDocentes(CERT_TEACHERS);
        } else {
            fetch('<?= APP_URL ?>/views/admin/certificados_action.php?action=get_all_teachers')
                .then(r => r.json())
                .then(res => {
                    if (res && res.status === 'success' && res.data && res.data.length > 0) {
                        window.CERT_TEACHERS = res.data;
                        llenarSelectDocentes(res.data);
                    }
                })
                .catch(e => console.error('Error cargando docentes AJAX:', e));
        }
    } else {
        // Si ya tiene opciones y no hay docente seleccionado, tomar el primero
        if (!currentCertTeacherId && selDoc.options.length > 1) {
            currentCertTeacherId = selDoc.options[1].value;
            selDoc.value = currentCertTeacherId;
            actualizarCamposPersonalizacionDocente(currentCertTeacherId);
        }
    }
}

function llenarSelectDocentes(teachers) {
    const selDoc = document.getElementById('cert_sel_docente');
    if (!selDoc) return;
    selDoc.innerHTML = '<option value="">-- Seleccione un docente activo --</option>';
    teachers.forEach(d => {
        const nom = [d.primer_apellido, d.segundo_apellido, d.primer_nombre, d.segundo_nombre].filter(Boolean).join(' ').trim() || d.nombre_completo || d.correo || ('Docente ' + d.id);
        const docTxt = d.documento_identidad ? ` (${d.tipo_documento || 'CC'} ${d.documento_identidad})` : '';
        const opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = `${nom}${docTxt}`;
        selDoc.appendChild(opt);
    });

    if (!currentCertTeacherId && teachers.length > 0) {
        currentCertTeacherId = teachers[0].id;
    }
    if (currentCertTeacherId) {
        selDoc.value = currentCertTeacherId;
        actualizarCamposPersonalizacionDocente(currentCertTeacherId);
    }
}

function actualizarCamposPersonalizacionDocente(teacherId) {
    const doc = CERT_TEACHERS.find(d => String(d.id) === String(teacherId));
    if (!doc) return;

    const inpTitulo = document.getElementById('inp_cert_docente_titulo');
    const inpCargo = document.getElementById('inp_cert_docente_cargo');
    const inpContrato = document.getElementById('inp_cert_docente_contrato');
    const inpSalario = document.getElementById('inp_cert_docente_salario');
    const inpTiempo = document.getElementById('inp_cert_docente_tiempo');
    const inpLugar = document.getElementById('inp_cert_docente_lugar');

    if (inpTitulo && (!inpTitulo.value || inpTitulo.value === 'LICENCIADO EN EDUCACIÓN')) {
        inpTitulo.value = (doc.titulo_academico || doc.titulo_profesional || 'LICENCIADO EN EDUCACIÓN').toUpperCase();
    }
    if (inpCargo && !inpCargo.value) {
        inpCargo.value = 'DOCENTE DE EDUCACIÓN BÁSICA Y MEDIA';
    }
    if (inpContrato && !inpContrato.value) {
        inpContrato.value = 'PRESTACIÓN DE SERVICIOS';
    }
    if (inpSalario && !inpSalario.value) {
        inpSalario.value = '$3.500.000 MENSUALES';
    }
    if (inpTiempo && !inpTiempo.value) {
        inpTiempo.value = '01 de Febrero del 2024 a la fecha actual';
    }
    if (inpLugar && !inpLugar.value) {
        inpLugar.value = 'Ciénaga, Magdalena';
    }
}

function cambiarPestanaCertificado(docType) {
    const isDocente = (docType === 'laboral');
    const colEst = document.getElementById('cert_student_select_col');
    const colDoc = document.getElementById('cert_teacher_select_col');
    const colPeriodo = document.getElementById('cert_periodo_col');
    const camposDocente = document.querySelectorAll('.campo-docente');
    const camposCostos = document.querySelectorAll('.campo-costos');

    if (isDocente) {
        if (colEst) colEst.classList.add('d-none');
        if (colDoc) colDoc.classList.remove('d-none');
        if (colPeriodo) colPeriodo.classList.add('d-none');
        camposDocente.forEach(el => el.classList.remove('d-none'));
        camposCostos.forEach(el => el.classList.add('d-none'));

        asegurarOpcionesDocentes();

        const selDoc = document.getElementById('cert_sel_docente');
        if (selDoc) {
            if (!selDoc.value && currentCertTeacherId) {
                selDoc.value = currentCertTeacherId;
            } else if (selDoc.value) {
                currentCertTeacherId = selDoc.value;
            }
            actualizarCamposPersonalizacionDocente(currentCertTeacherId);
        }
    } else {
        if (colEst) colEst.classList.remove('d-none');
        if (colDoc) colDoc.classList.add('d-none');
        if (colPeriodo) {
            if (docType === 'estudio') {
                colPeriodo.classList.remove('d-none');
            } else {
                colPeriodo.classList.add('d-none');
            }
        }
        camposDocente.forEach(el => el.classList.add('d-none'));
        if (docType === 'costos') {
            camposCostos.forEach(el => el.classList.remove('d-none'));
        } else {
            camposCostos.forEach(el => el.classList.add('d-none'));
        }
    }

    renderizarCertificadoDigital();
}

function seleccionarPersonaCertificado(type, id) {
    if (type === 'student') {
        currentCertStudentId = id;
    } else if (type === 'teacher') {
        currentCertTeacherId = id;
        actualizarCamposPersonalizacionDocente(id);
    }
    renderizarCertificadoDigital();
}

function actualizarCertificadoEnVivo() {
    renderizarCertificadoDigital();
}

function restablecerCertificado() {
    const diaInput = document.getElementById('inp_cert_dia');
    const mesInput = document.getElementById('inp_cert_mes');
    const anoInput = document.getElementById('inp_cert_ano');
    const jorInput = document.getElementById('inp_cert_jornada');
    const matInput = document.getElementById('inp_cert_costo_mat');
    const penInput = document.getElementById('inp_cert_costo_pen');
    const titInput = document.getElementById('inp_cert_docente_titulo');
    const carInput = document.getElementById('inp_cert_docente_cargo');
    const conInput = document.getElementById('inp_cert_docente_contrato');
    const salInput = document.getElementById('inp_cert_docente_salario');
    const tieInput = document.getElementById('inp_cert_docente_tiempo');
    const lugInput = document.getElementById('inp_cert_docente_lugar');

    const hoy = new Date();
    if (diaInput) diaInput.value = hoy.getDate();
    if (anoInput) anoInput.value = hoy.getFullYear();
    if (jorInput) jorInput.value = 'Mañana';
    if (matInput) matInput.value = '$170.000 COP';
    if (penInput) penInput.value = '$170.000 COP';
    if (titInput) titInput.value = 'LICENCIADO EN EDUCACIÓN';
    if (carInput) carInput.value = 'DOCENTE DE EDUCACIÓN BÁSICA Y MEDIA';
    if (conInput) conInput.value = 'PRESTACIÓN DE SERVICIOS';
    if (salInput) salInput.value = '$3.500.000 MENSUALES';
    if (tieInput) tieInput.value = '01 de Febrero del 2024 a la fecha actual';
    if (lugInput) lugInput.value = 'Ciénaga, Magdalena';

    renderizarCertificadoDigital();
}

function renderizarCertificadoDigital() {
    const titleEl = document.getElementById('cert_render_title');
    const procEl = document.getElementById('cert_render_proclamation');
    const bodyEl = document.getElementById('cert_render_body');
    if (!titleEl || !procEl || !bodyEl) return;

    // Valores configurables del panel
    const dia = document.getElementById('inp_cert_dia')?.value || new Date().getDate();
    const mes = document.getElementById('inp_cert_mes')?.value || 'Septiembre';
    const ano = document.getElementById('inp_cert_ano')?.value || '2026';
    const jornada = document.getElementById('inp_cert_jornada')?.value || 'Mañana';

    // Obtener datos del estudiante o docente seleccionado
    let estudiante = CERT_STUDENTS.find(e => e.id == currentCertStudentId) || CERT_STUDENTS[0] || {};
    let docente = CERT_TEACHERS.find(d => d.id == currentCertTeacherId) || CERT_TEACHERS[0] || {};

    const nomEstudiante = `${estudiante.primer_nombre || ''} ${estudiante.segundo_nombre || ''} ${estudiante.primer_apellido || ''} ${estudiante.segundo_apellido || ''}`.replace(/\s+/g, ' ').trim().toUpperCase() || 'ESTUDIANTE';
    const tipoDocEst = estudiante.tipo_documento || 'T.I';
    const numDocEst = estudiante.documento_identidad || '00000000';
    const gradoEst = estudiante.grado || '11°';
    const grupoEst = estudiante.grupo || 'A';
    const numGrado = parseInt(gradoEst) || 11;
    const nivelEst = (numGrado >= 10) ? 'EDUCACIÓN MEDIA' : ((numGrado >= 6) ? 'EDUCACIÓN BÁSICA SECUNDARIA' : (gradoEst.toLowerCase().includes('trans') ? 'EDUCACIÓN PREESCOLAR' : 'EDUCACIÓN BÁSICA PRIMARIA'));
    const ihEst = (numGrado >= 10) ? 35 : 30;
    const acudienteNom = (estudiante.acudiente_nombre || '').toUpperCase();
    const acudienteDoc = estudiante.acudiente_telefono || '1.080.000.000';

    const nomDocente = `${docente.primer_nombre || ''} ${docente.segundo_nombre || ''} ${docente.primer_apellido || ''} ${docente.segundo_apellido || ''}`.replace(/\s+/g, ' ').trim().toUpperCase() || 'DOCENTE';
    const tipoDocDoc = docente.tipo_documento || 'C.C';
    const numDocDoc = docente.documento_identidad || '00000000';
    const tituloDoc = document.getElementById('inp_cert_docente_titulo')?.value || (docente.titulo_academico || docente.titulo_profesional || 'LICENCIADO EN EDUCACIÓN').toUpperCase();
    const cargoDoc = document.getElementById('inp_cert_docente_cargo')?.value || 'DOCENTE DE EDUCACIÓN BÁSICA Y MEDIA';
    const contratoDoc = document.getElementById('inp_cert_docente_contrato')?.value || 'PRESTACIÓN DE SERVICIOS';
    const salarioDoc = document.getElementById('inp_cert_docente_salario')?.value || '$3.500.000 MENSUALES';
    const tiempoDoc = document.getElementById('inp_cert_docente_tiempo')?.value || '01 de Febrero del 2024 a la fecha actual';
    const lugarDoc = document.getElementById('inp_cert_docente_lugar')?.value || 'Ciénaga, Magdalena';

    const costoMat = document.getElementById('inp_cert_costo_mat')?.value || '$170.000 COP';
    const costoPen = document.getElementById('inp_cert_costo_pen')?.value || '$170.000 COP';

    let html = '';

    // =====================================================================
    // DOCUMENTO 1: CERTIFICADO DE ESTUDIO CON NOTAS REALES SUBIDAS
    // =====================================================================
    if (currentCertDoc === 'estudio') {
        titleEl.textContent = 'CERTIFICADO DE ESTUDIO';
        procEl.textContent = 'LA RECTORA Y SECRETARIO(A) DEL INSTITUTO LA SALLE CERTIFICAN:';

        const periodoSel = document.getElementById('inp_cert_periodo')?.value || '1';
        const rawGrades = CERT_GRADES[estudiante.id] || [];
        let materias = [];

        if (periodoSel === 'todos') {
            const groups = {};
            rawGrades.forEach(g => {
                const key = g.asignatura_id || g.nombre_asignatura;
                if (!groups[key]) {
                    groups[key] = { nombre: g.nombre_asignatura, ih: g.ih || 2, notas: [] };
                }
                groups[key].notas.push(parseFloat(g.nota_final) || 0);
            });
            Object.values(groups).forEach(grp => {
                const avg = grp.notas.reduce((a, b) => a + b, 0) / (grp.notas.length || 1);
                materias.push({ nombre: grp.nombre, ih: grp.ih, nota: avg });
            });
        } else {
            materias = rawGrades.filter(g => String(g.periodo) === String(periodoSel)).map(g => ({
                nombre: g.nombre_asignatura,
                ih: g.ih || 2,
                nota: parseFloat(g.nota_final) || 0
            }));
        }

        const periodoNombre = (periodoSel === 'todos') ? 'Todos los Periodos (Acumulado)' : `Periodo ${periodoSel}`;

        html += `<p class="cert-body-p">
            Que el alumno (a) <span class="cert-highlight">${nomEstudiante}</span>, identificado (a) con <span class="cert-highlight">${tipoDocEst} No. ${numDocEst}</span> expedida en Ciénaga, Magdalena, cursó y aprobó en este Plantel el Grado <span class="cert-highlight">${gradoEst} (${grupoEst})</span> de <span class="cert-highlight">${nivelEst}</span> asistiendo puntualmente durante el año lectivo <span class="cert-highlight">${ano}</span> y obtuvo en las asignaturas evaluadas las siguientes valoraciones conceptuales correspondientes a <span class="cert-highlight">${periodoNombre}</span>, según lo establecido en la Ley 115 de 1994, Decreto 230 de 2002 y Decreto 1290 de 2009:
        </p>`;

        if (materias.length > 0) {
            let aprobadasCount = 0;
            let reprobadasCount = 0;
            let filasHtml = '';

            materias.forEach(m => {
                let nota10 = m.nota <= 5.0 ? (m.nota * 2) : m.nota;
                let finalStr = Number(nota10).toFixed(1);
                let numVal = parseFloat(finalStr);
                let desp = 'SUPERIOR';
                let badgeClass = 'text-success';

                if (numVal < 6.0) {
                    desp = 'BAJO';
                    badgeClass = 'text-danger';
                    reprobadasCount++;
                } else if (numVal < 8.0) {
                    desp = 'BÁSICO';
                    badgeClass = 'text-warning';
                    aprobadasCount++;
                } else if (numVal < 9.6) {
                    desp = 'ALTO';
                    badgeClass = 'text-primary';
                    aprobadasCount++;
                } else {
                    desp = 'SUPERIOR';
                    badgeClass = 'text-success';
                    aprobadasCount++;
                }

                filasHtml += `<tr>
                    <td>${m.nombre.toUpperCase()}</td>
                    <td class="text-center">${m.ih}</td>
                    <td class="text-center fw-bold">${finalStr}</td>
                    <td class="text-center fw-bold ${badgeClass}">${desp}</td>
                </tr>`;
            });

            html += `
            <table class="cert-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Área / Asignatura</th>
                        <th style="text-align: center; width: 12%;">I.H</th>
                        <th style="text-align: center; width: 18%;">Calificación</th>
                        <th style="text-align: center; width: 25%;">Desempeño</th>
                    </tr>
                </thead>
                <tbody>
                    ${filasHtml}
                </tbody>
            </table>
            `;

            const badgeConcepto = reprobadasCount === 0 
                ? '<span class="badge bg-success">APROBÓ</span>' 
                : '<span class="badge bg-danger">REPROBÓ ASIGNATURAS</span>';

            html += `<p class="cert-body-p mb-2">
                <strong>Convivencia:</strong> Demostró excelentes habilidades sociales de convivencia al trabajar en equipo con sus compañeros, acatando de manera ejemplar el manual de convivencia institucional.
            </p>
            <div class="p-2 bg-light border rounded text-center small mb-3">
                <strong>Aprobadas:</strong> ${aprobadasCount} &nbsp;|&nbsp; <strong>No aprobadas:</strong> ${reprobadasCount} &nbsp;|&nbsp; <strong>CONCEPTO COMISIÓN EVALUACIÓN:</strong> ${badgeConcepto}
            </div>`;
        } else {
            // El estudiante no tiene calificaciones subidas aún
            html += `
            <div class="p-4 my-3 text-center border rounded bg-light">
                <i class="fas fa-exclamation-circle text-warning fs-2 mb-2 d-block"></i>
                <h6 class="fw-bold text-dark mb-1">Sin Calificaciones Registradas en la Plataforma</h6>
                <p class="text-muted small mb-0">El estudiante <strong>${nomEstudiante}</strong> no presenta calificaciones cargadas por los docentes para el <strong>${periodoNombre}</strong>.<br>Para poder certificar con notas, primero los docentes correspondientes deben ingresar las calificaciones en su respectivo módulo académico.</p>
            </div>
            <div class="p-2 bg-light border rounded text-center small mb-3 text-muted">
                <strong>Aprobadas:</strong> 0 &nbsp;|&nbsp; <strong>No aprobadas:</strong> 0 &nbsp;|&nbsp; <strong>CONCEPTO COMISIÓN EVALUACIÓN:</strong> <span class="badge bg-secondary">PENDIENTE DE CARGA DE NOTAS</span>
            </div>
            `;
        }

        html += `
        <div class="text-center text-muted extra-small">
            CONVERSIONES: 1.0 – 5.9 = BAJO &nbsp;|&nbsp; 6.0 – 7.9 = BÁSICO &nbsp;|&nbsp; 8.0 – 9.5 = ALTO &nbsp;|&nbsp; 9.6 – 10.0 = SUPERIOR
        </div>
        <p class="cert-body-p mt-3">
            Dado en Ciénaga Magdalena a los (${dia}) días del mes de ${mes} del año ${ano}.
        </p>`;
    }

    // =====================================================================
    // DOCUMENTO 2: CONSTANCIA DE ESTUDIOS (CON ACUDIENTE)
    // =====================================================================
    else if (currentCertDoc === 'constancia') {
        titleEl.textContent = 'CONSTANCIA DE ESTUDIOS';
        procEl.textContent = 'LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA';

        html += `<div class="text-center fw-bold my-3" style="font-size: 1.15rem; letter-spacing: 1px;">HACE CONSTAR</div>`;

        let acudTxt = acudienteNom 
            ? `Que el Señor (a) <span class="cert-highlight">${acudienteNom}</span> identificado con C.C. No. <span class="cert-highlight">${acudienteDoc}</span> se encuentra registrado ante esta Institución Educativa como ACUDIENTE del (a) alumno (a) <span class="cert-highlight">${nomEstudiante}</span> identificado (a) con <span class="cert-highlight">${tipoDocEst} N.º ${numDocEst}</span> de Ciénaga, Magdalena. `
            : `Que el (a) alumno (a) <span class="cert-highlight">${nomEstudiante}</span> identificado (a) con <span class="cert-highlight">${tipoDocEst} N.º ${numDocEst}</span> de Ciénaga, Magdalena. `;

        html += `<p class="cert-body-p" style="line-height: 2;">
            ${acudTxt}Quien se encuentra debidamente MATRICULADO (A) en esta Institución cursando el <span class="cert-highlight">GRADO ${gradoEst} (${grupoEst})</span> de <span class="cert-highlight">${nivelEst}</span>, jornada <span class="cert-highlight">${jornada}</span>, para el presente año escolar <span class="cert-highlight">${ano}</span>. Con una intensidad horaria de (<span class="cert-highlight">${ihEst}</span>) horas semanales y asiste puntualmente a clases hasta la fecha.
        </p>`;

        html += `<p class="cert-body-p mt-4">
            Dado en Ciénaga Magdalena a los (${dia}) días del mes de ${mes} del año ${ano}.
        </p>`;
    }

    // =====================================================================
    // DOCUMENTO 3: CERTIFICADO DE BUENA CONDUCTA
    // =====================================================================
    else if (currentCertDoc === 'conducta') {
        titleEl.textContent = 'CERTIFICADO DE BUENA CONDUCTA';
        procEl.textContent = 'LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA';

        html += `<div class="text-center fw-bold my-3" style="font-size: 1.15rem; letter-spacing: 1px;">HACE CONSTAR</div>`;

        html += `<p class="cert-body-p" style="line-height: 2;">
            Que el (a) alumno (a) <span class="cert-highlight">${nomEstudiante}</span> Identificado (a) con <span class="cert-highlight">${tipoDocEst} N.º ${numDocEst}</span> de Ciénaga, Magdalena, cursa el grado <span class="cert-highlight">${gradoEst} (${grupoEst})</span> de <span class="cert-highlight">${nivelEst}</span> en el presente año escolar <span class="cert-highlight">${ano}</span> presentando una <span class="cert-highlight">BUENA CONDUCTA</span>, intachable disciplina y cumpliendo a cabalidad con las normas, deberes y principios requeridos por el manual de convivencia de la institución.
        </p>`;

        html += `<p class="cert-body-p mt-4">
            Dado en Ciénaga Magdalena a los (${dia}) días del mes de ${mes} del año ${ano}.
        </p>`;
    }

    // =====================================================================
    // DOCUMENTO 4: CERTIFICACIÓN DE COSTOS EDUCATIVOS
    // =====================================================================
    else if (currentCertDoc === 'costos') {
        titleEl.textContent = 'CERTIFICACIÓN DE COSTOS EDUCATIVOS';
        procEl.textContent = 'LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA';

        html += `<div class="text-center fw-bold my-3" style="font-size: 1.15rem; letter-spacing: 1px;">HACE CONSTAR</div>`;

        html += `<p class="cert-body-p">
            Los costos educativos correspondientes a la vigencia del año escolar <span class="cert-highlight">${ano}</span> del (a) Estudiante <span class="cert-highlight">${nomEstudiante}</span> identificado (a) con <span class="cert-highlight">${tipoDocEst} N.º ${numDocEst}</span> de Ciénaga, Magdalena para el <span class="cert-highlight">GRADO ${gradoEst} (${grupoEst})</span> de <span class="cert-highlight">${nivelEst}</span>, jornada <span class="cert-highlight">${jornada}</span>:
        </p>`;

        html += `
        <table class="cert-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Concepto</th>
                    <th style="text-align: right; width: 40%;">Valor Oficial</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><strong>MATRÍCULA ANUAL ${ano}</strong></td><td style="text-align: right;" class="fw-bold">${costoMat}</td></tr>
                <tr><td>PENSIÓN FEBRERO</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN MARZO</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN ABRIL</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN MAYO</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN JUNIO</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN JULIO</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN AGOSTO</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN SEPTIEMBRE</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN OCTUBRE</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr><td>PENSIÓN NOVIEMBRE</td><td style="text-align: right;">${costoPen}</td></tr>
                <tr style="background: #f1f5f9; border-top: 2px solid #0b3c68;">
                    <td><strong class="text-primary" style="font-size: 1rem;">VALOR TOTAL ANUAL VIGENCIA ${ano}</strong></td>
                    <td style="text-align: right;"><strong class="text-primary" style="font-size: 1.05rem;">$1.870.000 COP</strong></td>
                </tr>
            </tbody>
        </table>
        `;

        html += `<p class="cert-body-p mt-3">
            Dado en Ciénaga Magdalena a los (${dia}) días del mes de ${mes} del año ${ano}.
        </p>`;
    }

    // =====================================================================
    // DOCUMENTO 5: CERTIFICADO DE PAZ Y SALVO
    // =====================================================================
    else if (currentCertDoc === 'paz_salvo') {
        titleEl.textContent = 'CERTIFICADO DE PAZ Y SALVO';
        procEl.textContent = 'LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA';

        html += `<div class="text-center fw-bold my-3" style="font-size: 1.15rem; letter-spacing: 1px;">HACE CONSTAR</div>`;

        html += `<p class="cert-body-p" style="line-height: 2;">
            Que el (a) alumno (a) <span class="cert-highlight">${nomEstudiante}</span> Identificado (a) con <span class="cert-highlight">${tipoDocEst} N.º ${numDocEst}</span> expedida en Ciénaga, Magdalena, que cursó y aprobó el <span class="cert-highlight">GRADO ${gradoEst} (${grupoEst})</span> de <span class="cert-highlight">${nivelEst}</span> en el año escolar <span class="cert-highlight">${ano}</span>. <span class="cert-highlight">SE ENCUENTRA A PAZ Y SALVO CON LA INSTITUCIÓN POR TODO CONCEPTO ACADÉMICO, DISCIPLINARIO Y FINANCIERO.</span>
        </p>`;

        html += `<p class="cert-body-p mt-4">
            Dado en Ciénaga Magdalena a los (${dia}) días del mes de ${mes} del año ${ano}.
        </p>`;
    }

    // =====================================================================
    // DOCUMENTO 6: CERTIFICADO LABORAL DOCENTE
    // =====================================================================
    else if (currentCertDoc === 'laboral') {
        titleEl.textContent = 'CERTIFICADO LABORAL DOCENTE';
        procEl.textContent = 'LA SUSCRITA RECTORA DEL INSTITUTO LA SALLE DE CIÉNAGA MAGDALENA';

        html += `<div class="text-center fw-bold my-3" style="font-size: 1.15rem; letter-spacing: 1px;">CERTIFICA</div>`;

        html += `<p class="cert-body-p" style="line-height: 1.9;">
            Que el (a) señor (a) <span class="cert-highlight">${nomDocente}</span> Identificado (a) con Cédula de Ciudadanía No. <span class="cert-highlight">${numDocDoc}</span> expedida en ${lugarDoc}, presta sus servicios profesionales como Docente en esta institución educativa bajo las siguientes especificaciones laborales:
        </p>`;

        html += `
        <div class="p-3 bg-light rounded border my-3" style="line-height: 1.9; font-size: 0.98rem;">
            <div><strong>TITULO PROFESIONAL:</strong> &nbsp;<span class="cert-highlight">${tituloDoc}</span></div>
            <div><strong>CARGO:</strong> &nbsp;<span class="cert-highlight">${cargoDoc}</span></div>
            <div><strong>TIPO DE CONTRATO:</strong> &nbsp;<span class="cert-highlight">${contratoDoc}</span></div>
            <div><strong>ASIGNACIÓN SALARIAL:</strong> &nbsp;<span class="cert-highlight">${salarioDoc}</span></div>
            <div><strong>TIEMPO LABORADO:</strong> &nbsp;<span class="cert-highlight">${tiempoDoc}</span></div>
        </div>
        `;

        html += `<p class="cert-body-p">
            La persona antes referenciada se desempeña en forma eficiente, responsable, con alto sentido de pertenencia institucional y mantiene magníficas relaciones humanas con toda la comunidad educativa salle.
        </p>`;

        html += `<p class="cert-body-p mt-3">
            Se expide la presente certificación a los (${dia}) días del mes de ${mes} del año ${ano}.
        </p>`;
    }

    // =====================================================================
    // DOCUMENTO 7: OBSERVADOR DEL ESTUDIANTE (RECTORÍA)
    // =====================================================================
    else if (currentCertDoc === 'observador') {
        titleEl.textContent = 'OBSERVADOR INTEGRAL DEL ESTUDIANTE';
        procEl.textContent = 'DESPACHO DE RECTORÍA & COORDINACIÓN DE CONVIVENCIA ESCOLAR';

        html += `
        <div class="p-2 border rounded bg-light small mb-3">
            <div class="row g-1">
                <div class="col-12 col-md-6"><strong>Estudiante:</strong> <span class="cert-highlight">${nomEstudiante}</span></div>
                <div class="col-6 col-md-3"><strong>Grado:</strong> ${gradoEst} - ${grupoEst}</div>
                <div class="col-6 col-md-3"><strong>Jornada:</strong> ${jornada}</div>
                <div class="col-12 col-md-6"><strong>Enfermedades:</strong> NINGUNA</div>
                <div class="col-12 col-md-6"><strong>Medicamentos:</strong> NINGUNO</div>
            </div>
        </div>
        `;

        const aspectos = [
            '1. Participa con frecuencia en las actividades programadas en la institución.',
            '2. Es respetuoso con sus compañeros, directivos y docentes.',
            '3. Sabe valorarse y valora a los demás con empatía y tolerancia.',
            '4. Muestra excelente presentación personal y porte adecuado del uniforme.',
            '5. Evita involucrarse en notables brotes de indisciplina o alteración del orden.',
            '6. Asiste con puntualidad a clases y actividades escolares.',
            '7. Asiste con muy buena regularidad y sin ausentismo injustificado.',
            '8. Ha logrado cambios importantes en su pensar, sentir, actuar y en su responsabilidad.',
            '9. Sus padres o acudientes asisten cumplidamente a los llamados que se les hacen.',
            '10. Solicita orientación a sus docentes u otro funcionario de la institución.',
            '11. Cuida sus materiales y los enseres puestos al servicio de la institución.',
            '12. Su comportamiento se ajusta estrictamente al manual de convivencia.',
            '13. Su convivencia social y grupal es plenamente satisfactoria.',
            '14. Responde por sus trabajos, talleres y tareas cumplidamente.',
            '15. Se interesa en el estudio y dedica tiempo propicio a sus compromisos.',
            '16. Se integra con los demás y colabora solidariamente en las clases.',
            '17. Realiza trabajos y/o consultas académicas con esmero.',
            '18. Mantiene ordenado su puesto de estudio y áreas comunes.',
            '19. Cumple los compromisos pedagógicos adquiridos con el plantel.',
            '20. Asume una actitud reflexiva ante las correcciones u observaciones formativas.'
        ];

        html += `
        <table class="cert-table" style="font-size: 0.8rem;">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 75%;">Aspectos Evaluados de Convivencia y Rendimiento</th>
                    <th style="text-align: center; width: 5%;">P1</th>
                    <th style="text-align: center; width: 5%;">P2</th>
                    <th style="text-align: center; width: 5%;">P3</th>
                    <th style="text-align: center; width: 5%;">P4</th>
                </tr>
            </thead>
            <tbody>
        `;

        aspectos.forEach((asp, idx) => {
            html += `<tr>
                <td class="text-center">${idx + 1}</td>
                <td>${asp}</td>
                <td class="text-center fw-bold text-success">S</td>
                <td class="text-center fw-bold text-success">S</td>
                <td class="text-center fw-bold text-success">S</td>
                <td class="text-center fw-bold text-success">S</td>
            </tr>`;
        });

        html += `
            </tbody>
        </table>
        <div class="text-center text-muted extra-small">
            Convenciones: S = Siempre | N = No | O = Ocasionalmente
        </div>
        `;
    }

    bodyEl.innerHTML = html;
}

function imprimirCertificadoDigital() {
    window.print();
}

function descargarCertificadoWord() {
    const form = document.getElementById('form_cert_docx');
    if (!form) return;

    let estudiante = CERT_STUDENTS.find(e => e.id == currentCertStudentId) || CERT_STUDENTS[0] || {};
    let docente = CERT_TEACHERS.find(d => d.id == currentCertTeacherId) || CERT_TEACHERS[0] || {};

    const nomEstudiante = `${estudiante.primer_nombre || ''} ${estudiante.segundo_nombre || ''} ${estudiante.primer_apellido || ''} ${estudiante.segundo_apellido || ''}`.replace(/\s+/g, ' ').trim().toUpperCase() || 'ESTUDIANTE';
    const nomDocente = `${docente.primer_nombre || ''} ${docente.segundo_nombre || ''} ${docente.primer_apellido || ''} ${docente.segundo_apellido || ''}`.replace(/\s+/g, ' ').trim().toUpperCase() || 'DOCENTE';

    const numGrado = parseInt(estudiante.grado) || 11;
    const nivelEst = (numGrado >= 10) ? 'EDUCACIÓN MEDIA' : ((numGrado >= 6) ? 'EDUCACIÓN BÁSICA SECUNDARIA' : (String(estudiante.grado || '').toLowerCase().includes('trans') ? 'EDUCACIÓN PREESCOLAR' : 'EDUCACIÓN BÁSICA PRIMARIA'));
    const ihEst = (numGrado >= 10) ? 35 : 30;

    document.getElementById('docx_doc_type').value = currentCertDoc;
    document.getElementById('docx_nombre_completo').value = nomEstudiante;
    document.getElementById('docx_tipo_doc').value = estudiante.tipo_documento || 'T.I';
    document.getElementById('docx_numero_doc').value = estudiante.documento_identidad || '';
    document.getElementById('docx_grado').value = estudiante.grado || '11°';
    document.getElementById('docx_grupo').value = estudiante.grupo || 'A';
    document.getElementById('docx_nivel').value = nivelEst;
    document.getElementById('docx_ih').value = ihEst;
    document.getElementById('docx_jornada').value = document.getElementById('inp_cert_jornada')?.value || 'Mañana';
    document.getElementById('docx_acudiente_nombre').value = (estudiante.acudiente_nombre || '').toUpperCase();
    document.getElementById('docx_acudiente_doc').value = estudiante.acudiente_telefono || '';

    document.getElementById('docx_docente_nombre').value = nomDocente;
    document.getElementById('docx_docente_doc').value = docente.documento_identidad || '';
    document.getElementById('docx_docente_exp').value = document.getElementById('inp_cert_docente_lugar')?.value || 'Ciénaga, Magdalena';
    document.getElementById('docx_docente_titulo').value = document.getElementById('inp_cert_docente_titulo')?.value || (docente.titulo_academico || docente.titulo_profesional || 'LICENCIADO EN EDUCACIÓN').toUpperCase();
    document.getElementById('docx_docente_cargo').value = document.getElementById('inp_cert_docente_cargo')?.value || 'DOCENTE DE EDUCACIÓN BÁSICA Y MEDIA';
    document.getElementById('docx_docente_contrato').value = document.getElementById('inp_cert_docente_contrato')?.value || 'PRESTACIÓN DE SERVICIOS';
    document.getElementById('docx_docente_salario').value = document.getElementById('inp_cert_docente_salario')?.value || '$3.500.000 MENSUALES';
    document.getElementById('docx_docente_tiempo').value = document.getElementById('inp_cert_docente_tiempo')?.value || '01 de Febrero del 2024 a la fecha actual';

    document.getElementById('docx_costo_matricula').value = document.getElementById('inp_cert_costo_mat')?.value || '$170.000 COP';
    document.getElementById('docx_costo_pension').value = document.getElementById('inp_cert_costo_pen')?.value || '$170.000 COP';
    document.getElementById('docx_costo_total').value = '$1.870.000 COP';

    document.getElementById('docx_dia').value = document.getElementById('inp_cert_dia')?.value || new Date().getDate();
    document.getElementById('docx_mes').value = document.getElementById('inp_cert_mes')?.value || 'Septiembre';
    document.getElementById('docx_ano').value = document.getElementById('inp_cert_ano')?.value || '2026';

    // Generar materias reales para el documento de Word
    let materiasParaWord = [];
    if (currentCertDoc === 'estudio') {
        const periodoSel = document.getElementById('inp_cert_periodo')?.value || '1';
        const rawGrades = CERT_GRADES[estudiante.id] || [];
        let processed = [];

        if (periodoSel === 'todos') {
            const groups = {};
            rawGrades.forEach(g => {
                const key = g.asignatura_id || g.nombre_asignatura;
                if (!groups[key]) {
                    groups[key] = { nombre: g.nombre_asignatura, ih: g.ih || 2, notas: [] };
                }
                groups[key].notas.push(parseFloat(g.nota_final) || 0);
            });
            Object.values(groups).forEach(grp => {
                const avg = grp.notas.reduce((a, b) => a + b, 0) / (grp.notas.length || 1);
                processed.push({ nombre: grp.nombre, ih: grp.ih, nota: avg });
            });
        } else {
            processed = rawGrades.filter(g => String(g.periodo) === String(periodoSel)).map(g => ({
                nombre: g.nombre_asignatura,
                ih: g.ih || 2,
                nota: parseFloat(g.nota_final) || 0
            }));
        }

        materiasParaWord = processed.map(m => {
            let nota10 = m.nota <= 5.0 ? (m.nota * 2) : m.nota;
            let finalStr = Number(nota10).toFixed(1);
            let numVal = parseFloat(finalStr);
            let desp = 'SUPERIOR';
            if (numVal < 6.0) desp = 'BAJO';
            else if (numVal < 8.0) desp = 'BÁSICO';
            else if (numVal < 9.6) desp = 'ALTO';
            return {
                nombre: m.nombre.toUpperCase(),
                ih: m.ih,
                nota: finalStr,
                desempeno: desp
            };
        });
    }
    const matInput = document.getElementById('docx_materias_json');
    if (matInput) {
        matInput.value = JSON.stringify(materiasParaWord);
    }

    form.submit();
}
</script>
</body>
</html>
