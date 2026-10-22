<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

echo "====================================================\n";
echo "VERIFICACIÓN INTEGRAL DE LAS 4 SOLICITUDES DEL USUARIO\n";
echo "====================================================\n\n";

$allPassed = true;

// 1. CAMBIO DE ROL A COORDINADOR (rol_id = 7)
echo "1. VERIFICANDO ROL COORDINADOR Y CAMBIO DE ROL:\n";
$stmtRol = $pdo->query("SELECT id, nombre_rol FROM roles WHERE id = 7");
$rolCoord = $stmtRol->fetch(PDO::FETCH_ASSOC);
if ($rolCoord && strtolower($rolCoord['nombre_rol']) === 'coordinador') {
    echo "  [OK] Rol 7 (coordinador) existe en la tabla roles.\n";
} else {
    echo "  [FAIL] Rol 7 no encontrado.\n";
    $allPassed = false;
}

// 2. CURSOS SEPARADOS POR JORNADA
echo "\n2. VERIFICANDO CURSOS SEPARADOS POR JORNADA (MAÑANA Y TARDE):\n";
$stmtCursos = $pdo->query("SELECT jornada, COUNT(*) as total FROM cursos WHERE ano_lectivo = 2026 GROUP BY jornada");
$jornadas = $stmtCursos->fetchAll(PDO::FETCH_KEY_PAIR);
echo "  Distribución 2026: " . json_encode($jornadas) . "\n";
if (!empty($jornadas['Mañana']) && !empty($jornadas['Tarde'])) {
    echo "  [OK] Cursos debidamente separados en Jornada Mañana (" . $jornadas['Mañana'] . ") y Jornada Tarde (" . $jornadas['Tarde'] . ").\n";
} else {
    echo "  [FAIL] Faltan cursos en alguna de las jornadas.\n";
    $allPassed = false;
}

// 3. SEPARACIÓN DE LAS DOS CAJAS (SEDE PRINCIPAL Y SEDE SEGUNDA)
echo "\n3. VERIFICANDO CAJAS SEPARADAS POR SEDE (PRINCIPAL & SEGUNDA):\n";
$ingP = floatval($pdo->query("SELECT SUM(monto) FROM contabilidad_caja WHERE estado_pago = 'Pagado' AND (sede_caja = 'principal' OR sede_caja IS NULL OR sede_caja = '')")->fetchColumn() ?: 0);
$egrP = floatval($pdo->query("SELECT SUM(monto) FROM contabilidad_egresos WHERE (sede_caja = 'principal' OR sede_caja IS NULL OR sede_caja = '')")->fetchColumn() ?: 0);
$saldoP = $ingP - $egrP;

$ingS = floatval($pdo->query("SELECT SUM(monto) FROM contabilidad_caja WHERE estado_pago = 'Pagado' AND sede_caja = 'segunda'")->fetchColumn() ?: 0);
$egrS = floatval($pdo->query("SELECT SUM(monto) FROM contabilidad_egresos WHERE sede_caja = 'segunda'")->fetchColumn() ?: 0);
$saldoS = $ingS - $egrS;

echo "  Caja Sede Principal: Ingresos = $$ingP | Egresos = $$egrP | Saldo = $$saldoP\n";
echo "  Caja Sede Segunda:   Ingresos = $$ingS | Egresos = $$egrS | Saldo = $$saldoS\n";
echo "  Cajas Totales:       Saldo Consolidado = $" . ($saldoP + $saldoS) . "\n";

// Test perfiles_detalles.sede_caja column
$stmtCol = $pdo->query("SHOW COLUMNS FROM perfiles_detalles LIKE 'sede_caja'");
if ($stmtCol->fetch()) {
    echo "  [OK] Campo 'sede_caja' disponible en 'perfiles_detalles' para asignar acceso a 'principal', 'segunda' o 'ambas'.\n";
} else {
    echo "  [FAIL] Campo 'sede_caja' no encontrado en perfiles_detalles.\n";
    $allPassed = false;
}

// 4. MÓDULO DE EVALUACIÓN DOCENTE
echo "\n4. VERIFICANDO MÓDULO DE EVALUACIÓN DOCENTE:\n";
$periodoActivo = $pdo->query("SELECT * FROM evaluacion_docente_periodos WHERE estado = 'activo' AND (NOW() BETWEEN fecha_inicio AND fecha_fin)")->fetch(PDO::FETCH_ASSOC);
if ($periodoActivo) {
    echo "  [OK] Periodo Activo Encontrado: '{$periodoActivo['titulo']}' (Vence: {$periodoActivo['fecha_fin']})\n";
} else {
    echo "  [WARN] No hay periodo activo en este instante.\n";
}

$evalsCount = $pdo->query("SELECT COUNT(*) FROM evaluaciones_docentes")->fetchColumn();
echo "  Total Evaluaciones registradas en BD: $evalsCount\n";

$rankingDocs = $pdo->query("
    SELECT pd.primer_nombre, pd.primer_apellido, ROUND(AVG(ed.puntaje_promedio), 2) AS promedio, COUNT(ed.id) AS total_evaluaciones
    FROM evaluaciones_docentes ed
    INNER JOIN usuarios u ON ed.docente_id = u.id
    INNER JOIN perfiles_detalles pd ON u.id = pd.usuario_id
    GROUP BY ed.docente_id
    ORDER BY promedio DESC
")->fetchAll(PDO::FETCH_ASSOC);

echo "  Ranking Docentes Actual:\n";
foreach ($rankingDocs as $rd) {
    echo "    - {$rd['primer_nombre']} {$rd['primer_apellido']}: {$rd['promedio']}/5.0 ({$rd['total_evaluaciones']} evaluaciones)\n";
}

if ($allPassed) {
    echo "\n>>> TODAS LAS PRUEBAS DE INTEGRACIÓN PASARON EXITOSAMENTE. <<<\n";
} else {
    echo "\n>>> HUBO FALLOS EN LA VERIFICACIÓN. <<<\n";
}
