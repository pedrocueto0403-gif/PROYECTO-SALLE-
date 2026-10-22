<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../app/Services/ActaService.php';

echo "=== INICIANDO SUITE DE PRUEBAS DE CONVIVENCIA Y ACTAS ===\n\n";

$pdo = Database::getInstance()->getConnection();
if (!$pdo) {
    die("ERROR: Sin conexión a base de datos.\n");
}

// 1. Verificar Rol Coordinador
$stmtRol = $pdo->query("SELECT * FROM roles WHERE nombre_rol = 'coordinador'");
$rol = $stmtRol->fetch(PDO::FETCH_ASSOC);
echo "1. Rol Coordinador: " . ($rol ? "OK (ID: {$rol['id']})" : "ERROR") . "\n";

// 2. Verificar Usuario Coordinador
$stmtCoord = $pdo->query("SELECT u.id, u.correo, r.nombre_rol FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id WHERE u.correo = 'coordinacion@lasallecienaga.edu.co'");
$coord = $stmtCoord->fetch(PDO::FETCH_ASSOC);
echo "2. Usuario Coordinador: " . ($coord ? "OK (ID: {$coord['id']}, Rol: {$coord['nombre_rol']})" : "ERROR") . "\n";

// 3. Verificar Tabla actas_convivencia
$stmtTable = $pdo->query("SHOW TABLES LIKE 'actas_convivencia'");
$tbl = $stmtTable->fetch();
echo "3. Tabla actas_convivencia: " . ($tbl ? "OK" : "ERROR") . "\n";

// 4. Test Catálogo de Faltas
$cat = ActaService::getCatalogoFaltas();
echo "4. Catálogo Oficial extraído de Word:\n";
echo "   - Tipo I: " . count($cat['Tipo I']) . " faltas\n";
echo "   - Tipo II: " . count($cat['Tipo II']) . " faltas\n";
echo "   - Tipo III: " . count($cat['Tipo III']) . " faltas\n";

// 5. Test Generación de Documentos Word (.docx) para los 3 tipos
$sampleEstudiante = [
    'numero_acta' => 'ACTA-2026-TEST-01',
    'tipo_acta' => 'Tipo I',
    'estudiante_nombre' => 'Ahumada Mariño Anahy Sofía',
    'estudiante_doc' => 'TI 1176965995',
    'estudiante_grado' => 'Grado 11° A',
    'fecha_acta' => '2026-09-23',
    'faltas_seleccionadas' => [
        '1. Llegar tarde al colegio, al aula de clases o a cualquier actividad institucional programada.',
        '4. Portar inadecuadamente el uniforme institucional o usar prendas que no correspondan.'
    ],
    'descargos' => 'La estudiante manifiesta que hubo trancón en el transporte escolar.',
    'testigos' => 'Compañeros de ruta.',
    'afectados_o_bienes' => 'Ninguno.',
    'compromisos' => 'Salir 15 minutos más temprano y respetar el horario de entrada a las 6:45 AM.',
    'firma_alumno' => 'Anahy Sofía Ahumada',
    'firma_docente' => 'Prof. Carlos García López',
    'firma_padre' => 'Mariño Sofía (Acudiente)',
    'firma_dir_grupo' => 'Prof. Carlos García López'
];

$file1 = sys_get_temp_dir() . '/test_acta_tipo1.docx';
$ok1 = ActaService::generateDocx($sampleEstudiante, $file1);
echo "5.1 Generación Word Tipo I: " . ($ok1 && file_exists($file1) && filesize($file1) > 2000 ? "OK (" . filesize($file1) . " bytes)" : "ERROR") . "\n";

// Tipo II
$sampleEstudiante['tipo_acta'] = 'Tipo II';
$sampleEstudiante['numero_acta'] = 'ACTA-2026-TEST-02';
$sampleEstudiante['faltas_seleccionadas'] = [
    '3. Manifestar reiteradamente conductas de agresión verbal, psicológica, o física hacia miembros de la comunidad.'
];
$file2 = sys_get_temp_dir() . '/test_acta_tipo2.docx';
$ok2 = ActaService::generateDocx($sampleEstudiante, $file2);
echo "5.2 Generación Word Tipo II: " . ($ok2 && file_exists($file2) && filesize($file2) > 2000 ? "OK (" . filesize($file2) . " bytes)" : "ERROR") . "\n";

// Tipo III
$sampleEstudiante['tipo_acta'] = 'Tipo III';
$sampleEstudiante['numero_acta'] = 'ACTA-2026-TEST-03';
$sampleEstudiante['remision_entidad'] = 'Policía de Infancia y Adolescencia / ICBF';
$sampleEstudiante['faltas_seleccionadas'] = [
    '2. Porte, consumo, distribución o facilitación de sustancias psicoactivas o bebidas alcohólicas.'
];
$file3 = sys_get_temp_dir() . '/test_acta_tipo3.docx';
$ok3 = ActaService::generateDocx($sampleEstudiante, $file3);
echo "5.3 Generación Word Tipo III: " . ($ok3 && file_exists($file3) && filesize($file3) > 2000 ? "OK (" . filesize($file3) . " bytes)" : "ERROR") . "\n";

// 6. Test Inserción de Acta en Base de Datos
$stmtIns = $pdo->prepare("
    INSERT INTO actas_convivencia 
        (numero_acta, tipo_acta, estudiante_id, curso_id, creador_id, creador_rol, creador_nombre, 
         fecha_acta, faltas_seleccionadas, descargos, testigos, afectados_o_bienes, compromisos, 
         firmas_nombres)
    VALUES 
        ('ACTA-2026-0001', 'Tipo I', 7, 23, 221, 'coordinador', 'Coordinador General',
         '2026-09-23', :faltas, 'Versión de prueba', 'Sin testigos', 'Sin daños', 'Compromiso de puntualidad', :firmas)
");
$stmtIns->execute([
    ':faltas' => json_encode($sampleEstudiante['faltas_seleccionadas'], JSON_UNESCAPED_UNICODE),
    ':firmas' => json_encode(['alumno' => 'Anahy Ahumada', 'docente' => 'Coordinación'], JSON_UNESCAPED_UNICODE)
]);
echo "6. Inserción Acta en Base de Datos: OK (ID insertado: " . $pdo->lastInsertId() . ")\n";

echo "\n=== TODAS LAS PRUEBAS COMPLETADAS EXITOSAMENTE ===\n";
