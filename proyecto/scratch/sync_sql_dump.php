<?php
require_once __DIR__ . '/../config/Database.php';
$pdo = Database::getInstance()->getConnection();

$targetFiles = [
    __DIR__ . '/../../aulavir7_Aulavirtualsalle.sql',
    __DIR__ . '/../database/database.sql',
    'C:/AppServ/www/practicas antigrabiti/database/database.sql'
];

// Fetch all cursos from the live database
$cursosRows = $pdo->query("SELECT id, grado, grupo, jornada, sede, ano_lectivo FROM cursos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$cursosValuesSql = [];
foreach ($cursosRows as $c) {
    $grado = addslashes($c['grado']);
    $grupo = addslashes($c['grupo']);
    $jornada = addslashes($c['jornada']);
    $sede = addslashes($c['sede']);
    $cursosValuesSql[] = "({$c['id']}, '{$grado}', '{$grupo}', '{$jornada}', '{$sede}', {$c['ano_lectivo']})";
}
$cursosInsertSql = "INSERT INTO `cursos` (`id`, `grado`, `grupo`, `jornada`, `sede`, `ano_lectivo`) VALUES\n" . implode(",\n", $cursosValuesSql) . ";";

$createTableCursos = $pdo->query("SHOW CREATE TABLE cursos")->fetch(PDO::FETCH_ASSOC)['Create Table'];

$cursosTableSql = "DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
" . $createTableCursos . ";
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
" . $cursosInsertSql . "
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;";

// Extra table definitions: evaluacion_docente_periodos and evaluaciones_docentes
$evalPeriodosCreate = $pdo->query("SHOW CREATE TABLE evaluacion_docente_periodos")->fetch(PDO::FETCH_ASSOC)['Create Table'] . ";";
$evalDocentesCreate = $pdo->query("SHOW CREATE TABLE evaluaciones_docentes")->fetch(PDO::FETCH_ASSOC)['Create Table'] . ";";

// Fetch initial data for evaluacion_docente_periodos
$evalPerData = $pdo->query("SELECT * FROM evaluacion_docente_periodos")->fetchAll(PDO::FETCH_ASSOC);
$evalPerInsert = "";
if (!empty($evalPerData)) {
    $evalPerRows = [];
    foreach ($evalPerData as $ep) {
        $tit = addslashes($ep['titulo']);
        $ind = addslashes($ep['indicaciones'] ?? '');
        $evalPerRows[] = "({$ep['id']}, '{$tit}', '{$ep['fecha_inicio']}', '{$ep['fecha_fin']}', '{$ep['estado']}', {$ep['ano_lectivo']}, {$ep['periodo_academico']}, '{$ind}', '{$ep['created_at']}')";
    }
    $evalPerInsert = "LOCK TABLES `evaluacion_docente_periodos` WRITE;\nINSERT INTO `evaluacion_docente_periodos` VALUES " . implode(",\n", $evalPerRows) . ";\nUNLOCK TABLES;\n";
}

$evalTablesSql = "
--
-- Table structure for table `evaluacion_docente_periodos`
--
DROP TABLE IF EXISTS `evaluacion_docente_periodos`;
" . $evalPeriodosCreate . "\n
" . $evalPerInsert . "
--
-- Table structure for table `evaluaciones_docentes`
--
DROP TABLE IF EXISTS `evaluaciones_docentes`;
" . $evalDocentesCreate . "\n
";

foreach ($targetFiles as $filePath) {
    if (!file_exists($filePath)) {
        echo "File does not exist: $filePath\n";
        continue;
    }

    $content = file_get_contents($filePath);
    echo "Processing $filePath (Initial length: " . strlen($content) . ")...\n";

    // 1. Replace table structure and dumping for `cursos`
    $patternCursos = '/DROP TABLE IF EXISTS `cursos`;[\s\S]*?UNLOCK TABLES;/';
    if (preg_match($patternCursos, $content)) {
        $content = preg_replace($patternCursos, $cursosTableSql, $content, 1);
        echo "  -> Replaced `cursos` table structure & data (" . count($cursosRows) . " courses) successfully!\n";
    } else {
        echo "  -> Could not match cursos table block with regex.\n";
    }

    // 2. Ensure evaluacion tables are present
    if (strpos($content, 'evaluacion_docente_periodos') === false) {
        $content .= "\n" . $evalTablesSql . "\n";
        echo "  -> Appended `evaluacion_docente_periodos` and `evaluaciones_docentes` successfully!\n";
    } else {
        echo "  -> Evaluacion tables already present.\n";
    }

    // 3. Ensure `sede_caja` in `contabilidad_caja` if present
    if (strpos($content, '`contabilidad_caja`') !== false && strpos($content, '`sede_caja`') === false) {
        $content = str_replace(
            "`recibo_numero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,",
            "`recibo_numero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,\n  `sede_caja` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'principal',",
            $content
        );
        echo "  -> Added `sede_caja` column to `contabilidad_caja` schema.\n";
    }

    // 4. Ensure `sede_caja` in `perfiles_detalles` if present
    if (strpos($content, '`perfiles_detalles`') !== false && strpos($content, '`sede_caja`') === false) {
        $content = str_replace(
            "`foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_avatar.png',",
            "`sede_caja` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ambas',\n  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_avatar.png',",
            $content
        );
        echo "  -> Added `sede_caja` column to `perfiles_detalles` schema.\n";
    }

    // 5. Ensure `jornada_solicitada` and `sede_solicitada` in `solicitudes_admision` if present
    if (strpos($content, '`solicitudes_admision`') !== false && strpos($content, '`jornada_solicitada`') === false) {
        $content = str_replace(
            "`grado_solicitado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,",
            "`grado_solicitado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,\n  `jornada_solicitada` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mañana',",
            $content
        );
        echo "  -> Added `jornada_solicitada` column to `solicitudes_admision` schema.\n";
    }
    if (strpos($content, '`solicitudes_admision`') !== false && strpos($content, '`sede_solicitada`') === false) {
        $content = str_replace(
            "`jornada_solicitada` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mañana',",
            "`jornada_solicitada` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mañana',\n  `sede_solicitada` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Sede Principal',",
            $content
        );
        echo "  -> Added `sede_solicitada` column to `solicitudes_admision` schema.\n";
    }

    file_put_contents($filePath, $content);
    echo "Saved $filePath (Final length: " . strlen($content) . ")\n\n";
}

echo "=== SYNC COMPLETED SUCCESSFULLY ===\n";
