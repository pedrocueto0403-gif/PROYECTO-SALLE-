<?php
/**
 * CONEXIÓN A BASE DE DATOS MEDIANTE PATRÓN SINGLETON (PDO)
 * Instituto La Salle Ciénaga Magdalena
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo = null;
    private $isConnected = false;
    private $lastError = null;

    private function __construct() {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        // Intentos en orden: Configuración principal y fallbacks locales comunes
        $hostsToTry = [DB_HOST];
        if (DB_HOST === '127.0.0.1') {
            $hostsToTry[] = 'localhost';
        } elseif (DB_HOST === 'localhost') {
            $hostsToTry[] = '127.0.0.1';
        }

        $dbsToTry = [DB_NAME];
        if (DB_NAME === 'aulavirtual_salle') {
            $dbsToTry[] = 'aulavir7_aulavirtualsalle';
        } elseif (DB_NAME === 'aulavir7_aulavirtualsalle' || DB_NAME === 'aulavir7_Aulavirtualsalle') {
            $dbsToTry[] = 'aulavirtual_salle';
        }

        foreach ($hostsToTry as $host) {
            foreach ($dbsToTry as $dbname) {
                try {
                    $dsn = "mysql:host={$host};port=" . DB_PORT . ";dbname={$dbname};charset=" . DB_CHARSET;
                    $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                    $this->isConnected = true;
                    $this->lastError = null;
                    $this->verificarTablasCriticas();
                    return;
                } catch (PDOException $e) {
                    $this->lastError = $e->getMessage();
                }
            }
        }

        // Si falló la conexión directa, intentar auto-creación si MySQL responde
        try {
            $dsnBase = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
            $pdoBase = new PDO($dsnBase, DB_USER, DB_PASS, $options);
            $pdoBase->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdoBase->exec("USE `" . DB_NAME . "`;");
            
            $schemaFile = __DIR__ . '/../database/database.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $pdoBase->exec($sql);
            }
            
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $this->isConnected = true;
            $this->lastError = null;
            $this->verificarTablasCriticas();
        } catch (Exception $ex) {
            $this->isConnected = false;
            $this->lastError = $ex->getMessage();
            error_log("Error de conexión a la BD: " . $ex->getMessage());
        }
    }

    /**
     * Auto-verifica y asegura la existencia de tablas críticas del sistema
     */
    private function verificarTablasCriticas(): void {
        if (!$this->pdo) return;
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `solicitudes_admision` (
                    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `codigo_solicitud` VARCHAR(30) NOT NULL UNIQUE,
                    `primer_nombre` VARCHAR(60) NOT NULL,
                    `segundo_nombre` VARCHAR(60) NULL,
                    `primer_apellido` VARCHAR(60) NOT NULL,
                    `segundo_apellido` VARCHAR(60) NULL,
                    `tipo_documento` ENUM('RC','TI','CC','CE','NES','Pasaporte') NOT NULL DEFAULT 'TI',
                    `documento_identidad` VARCHAR(30) NOT NULL,
                    `fecha_nacimiento` DATE NULL,
                    `genero` ENUM('Masculino','Femenino','Otro') DEFAULT 'Masculino',
                    `direccion` VARCHAR(255) NULL,
                    `barrio` VARCHAR(100) NULL,
                    `ciudad` VARCHAR(100) DEFAULT 'Ciénaga',
                    `telefono` VARCHAR(30) NULL,
                    `correo` VARCHAR(150) NULL,
                    `grado_solicitado` VARCHAR(30) NOT NULL,
                    `jornada_solicitada` VARCHAR(30) NOT NULL DEFAULT 'Mañana',
                    `sede_solicitada` VARCHAR(60) NOT NULL DEFAULT 'Sede Principal',
                    `curso_asignado_id` INT NULL,
                    `ano_lectivo` INT NOT NULL DEFAULT 2026,
                    `colegio_origen` VARCHAR(200) NULL,
                    `acudiente_nombre` VARCHAR(180) NOT NULL,
                    `acudiente_tipo_doc` ENUM('CC','CE','Pasaporte') NOT NULL DEFAULT 'CC',
                    `acudiente_documento` VARCHAR(30) NOT NULL,
                    `acudiente_parentesco` VARCHAR(50) NOT NULL DEFAULT 'Padre/Madre',
                    `acudiente_telefono` VARCHAR(30) NOT NULL,
                    `acudiente_correo` VARCHAR(150) NOT NULL,
                    `acudiente_direccion` VARCHAR(255) NULL,
                    `acudiente_ocupacion` VARCHAR(100) NULL,
                    `estado` ENUM('Pendiente','Aprobada','Rechazada','Matriculado') NOT NULL DEFAULT 'Pendiente',
                    `monto_matricula` DECIMAL(12,2) NOT NULL DEFAULT 350000.00,
                    `observaciones_revision` TEXT NULL,
                    `motivo_rechazo` TEXT NULL,
                    `recibo_caja_id` BIGINT NULL,
                    `usuario_estudiante_id` BIGINT NULL,
                    `usuario_acudiente_id` BIGINT NULL,
                    `matricula_id` BIGINT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX `idx_estado` (`estado`),
                    INDEX `idx_documento_est` (`documento_identidad`),
                    INDEX `idx_codigo` (`codigo_solicitud`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Asegurar columna sede_solicitada si la tabla ya existía
            $colsSol = $this->pdo->query("SHOW COLUMNS FROM solicitudes_admision")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('sede_solicitada', $colsSol)) {
                $this->pdo->exec("ALTER TABLE solicitudes_admision ADD COLUMN `sede_solicitada` VARCHAR(60) NOT NULL DEFAULT 'Sede Principal' AFTER `jornada_solicitada`");
            }

            // Tabla oficial de Actas de Convivencia Escolar (Tipos I, II y III)
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `actas_convivencia` (
                    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `numero_acta` VARCHAR(50) NOT NULL,
                    `tipo_acta` ENUM('Tipo I', 'Tipo II', 'Tipo III') NOT NULL DEFAULT 'Tipo I',
                    `estudiante_id` BIGINT NOT NULL,
                    `curso_id` INT NULL,
                    `creador_id` BIGINT NOT NULL,
                    `creador_rol` VARCHAR(30) NOT NULL,
                    `creador_nombre` VARCHAR(150) NOT NULL,
                    `fecha_acta` DATE NOT NULL,
                    `faltas_seleccionadas` TEXT NOT NULL,
                    `descargos` TEXT NULL,
                    `testigos` TEXT NULL,
                    `afectados_o_bienes` TEXT NULL,
                    `compromisos` TEXT NULL,
                    `remision_entidad` VARCHAR(150) NULL,
                    `otros` TEXT NULL,
                    `firmas_nombres` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_acta_est` (`estudiante_id`),
                    INDEX `idx_acta_tipo` (`tipo_acta`),
                    INDEX `idx_acta_fecha` (`fecha_acta`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Asegurar rol coordinador (ID 7)
            $this->pdo->exec("INSERT IGNORE INTO roles (id, nombre_rol, descripcion) VALUES (7, 'coordinador', 'Coordinador académico y de convivencia institucional')");

            // Asegurar usuario coordinador
            $coordExists = $this->pdo->query("SELECT id FROM usuarios WHERE correo = 'coordinacion@lasallecienaga.edu.co' LIMIT 1")->fetchColumn();
            if (!$coordExists) {
                $hash = password_hash('Salle2026*', PASSWORD_BCRYPT);
                $this->pdo->prepare("INSERT INTO usuarios (correo, password_hash, rol_id, estado_cuenta, debe_cambiar_password) VALUES ('coordinacion@lasallecienaga.edu.co', :h, 7, 'activo', 0)")->execute([':h' => $hash]);
                $cId = $this->pdo->lastInsertId();
                $this->pdo->prepare("INSERT INTO perfiles_detalles (usuario_id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, documento_identidad, telefono, direccion) VALUES (:uid, 'Coordinación', 'Académica y', 'Convivencia', 'Escolar', 'CC', '1082987007', '3007654321', 'Calle 12 # 8-45 Ciénaga')")->execute([':uid' => $cId]);
            }

            $this->asegurarCursosYEstudiantes11A();
            $this->asegurarJornadasCajasYEvaluacion();
        } catch (Exception $e) {
            error_log('Error auto-creando tablas criticas: ' . $e->getMessage());
        }
    }

    /**
     * Auto-asegura cursos por Jornada (Mañana y Tarde), sedes de cajas y módulo de evaluación docente
     */
    private function asegurarJornadasCajasYEvaluacion(): void {
        if (!$this->pdo) return;
        try {
            // 1. Columnas en cursos
            $cursosCols = $this->pdo->query("SHOW COLUMNS FROM cursos")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('jornada', $cursosCols)) {
                $this->pdo->exec("ALTER TABLE cursos ADD COLUMN `jornada` VARCHAR(30) NOT NULL DEFAULT 'Mañana' AFTER `grupo`");
            }
            if (!in_array('sede', $cursosCols)) {
                $this->pdo->exec("ALTER TABLE cursos ADD COLUMN `sede` VARCHAR(60) NOT NULL DEFAULT 'Sede Principal' AFTER `jornada`");
            }

            // 2. Columna sede_caja en perfiles_detalles
            $perfilesCols = $this->pdo->query("SHOW COLUMNS FROM perfiles_detalles")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('sede_caja', $perfilesCols)) {
                $this->pdo->exec("ALTER TABLE perfiles_detalles ADD COLUMN `sede_caja` VARCHAR(50) NOT NULL DEFAULT 'ambas' AFTER `direccion`");
            }

            // 3. Columna sede_caja en contabilidad_caja y contabilidad_egresos
            $cajaCols = $this->pdo->query("SHOW COLUMNS FROM contabilidad_caja")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('sede_caja', $cajaCols)) {
                $this->pdo->exec("ALTER TABLE contabilidad_caja ADD COLUMN `sede_caja` VARCHAR(50) NOT NULL DEFAULT 'principal' AFTER `recibo_numero`");
            }
            $egresosCols = $this->pdo->query("SHOW COLUMNS FROM contabilidad_egresos")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('sede_caja', $egresosCols)) {
                $this->pdo->exec("ALTER TABLE contabilidad_egresos ADD COLUMN `sede_caja` VARCHAR(50) NOT NULL DEFAULT 'principal' AFTER `soporte_factura`");
            }

            // 4. Tablas de Evaluación Docente
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `evaluacion_docente_periodos` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `titulo` VARCHAR(150) NOT NULL DEFAULT 'Evaluación Docente 2026 - Período 1',
                    `fecha_inicio` DATETIME NOT NULL,
                    `fecha_fin` DATETIME NOT NULL,
                    `estado` ENUM('activo', 'inactivo', 'cerrado') NOT NULL DEFAULT 'activo',
                    `ano_lectivo` INT NOT NULL DEFAULT 2026,
                    `periodo_academico` INT NOT NULL DEFAULT 1,
                    `indicaciones` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `evaluaciones_docentes` (
                    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                    `periodo_id` INT NOT NULL,
                    `estudiante_id` BIGINT NOT NULL,
                    `docente_id` BIGINT NOT NULL,
                    `curso_id` INT NOT NULL,
                    `asignatura_id` INT NULL,
                    `criterio_dominio` TINYINT NOT NULL DEFAULT 5,
                    `criterio_metodologia` TINYINT NOT NULL DEFAULT 5,
                    `criterio_respeto` TINYINT NOT NULL DEFAULT 5,
                    `criterio_evaluacion` TINYINT NOT NULL DEFAULT 5,
                    `criterio_motivacion` TINYINT NOT NULL DEFAULT 5,
                    `puntaje_promedio` DECIMAL(3,2) NOT NULL DEFAULT 5.00,
                    `comentarios` TEXT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY `uk_eval_est_doc_per` (`periodo_id`, `estudiante_id`, `docente_id`),
                    INDEX `idx_eval_doc` (`docente_id`),
                    INDEX `idx_eval_per` (`periodo_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Asegurar compatibilidad de columnas creado_en y created_at
            $colsEval = $this->pdo->query("SHOW COLUMNS FROM `evaluaciones_docentes`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('creado_en', $colsEval)) {
                $this->pdo->exec("ALTER TABLE `evaluaciones_docentes` ADD COLUMN `creado_en` DATETIME DEFAULT CURRENT_TIMESTAMP;");
            }
            if (!in_array('created_at', $colsEval)) {
                $this->pdo->exec("ALTER TABLE `evaluaciones_docentes` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP;");
            }
        } catch (Exception $e) {
            error_log('Error asegurando jornadas, cajas y evaluación docente: ' . $e->getMessage());
        }
    }

    /**
     * Auto-asegura la existencia de cursos de Transición a 11° y asigna todos los estudiantes a 11° A
     */
    private function asegurarCursosYEstudiantes11A(): void {
        if (!$this->pdo) return;
        try {
            // 1. Asegurar cursos oficiales: 1 curso por jornada (A en Mañana, B en Tarde)
            // Grados: Párvulo, Prejardín, Jardín, Transición, 1° a 11° en Sede Principal y Sede Victoria
            $grados = ['Párvulo', 'Prejardín', 'Jardín', 'Transición', '1°', '2°', '3°', '4°', '5°', '6°', '7°', '8°', '9°', '10°', '11°'];
            $sedes = ['Sede Principal', 'Sede Victoria'];

            try {
                $keys = $this->pdo->query("SHOW INDEX FROM cursos")->fetchAll(PDO::FETCH_ASSOC);
                $keyNames = array_unique(array_column($keys, 'Key_name'));
                if (in_array('uk_curso_ano_jornada', $keyNames)) {
                    $this->pdo->exec("ALTER TABLE cursos DROP INDEX `uk_curso_ano_jornada`");
                }
                if (!in_array('uk_curso_ano_jornada_sede', $keyNames)) {
                    $this->pdo->exec("ALTER TABLE cursos ADD UNIQUE KEY `uk_curso_ano_jornada_sede` (`grado`, `grupo`, `ano_lectivo`, `jornada`, `sede`)");
                }
            } catch (Exception $eKey) {}

            $chk = $this->pdo->prepare("SELECT id FROM cursos WHERE grado = :gr AND grupo = :gp AND ano_lectivo = 2026 AND jornada = :jor AND sede = :sede LIMIT 1");
            $ins = $this->pdo->prepare("INSERT INTO cursos (grado, grupo, jornada, sede, ano_lectivo) VALUES (:gr, :gp, :jor, :sede, 2026)");

            foreach ($sedes as $sede) {
                foreach ($grados as $gr) {
                    // Mañana -> Grupo A (Ej: 1A)
                    $chk->execute([':gr' => $gr, ':gp' => 'A', ':jor' => 'Mañana', ':sede' => $sede]);
                    if (!$chk->fetchColumn()) {
                        $ins->execute([':gr' => $gr, ':gp' => 'A', ':jor' => 'Mañana', ':sede' => $sede]);
                    }
                    // Tarde -> Grupo B (Ej: 1B)
                    $chk->execute([':gr' => $gr, ':gp' => 'B', ':jor' => 'Tarde', ':sede' => $sede]);
                    if (!$chk->fetchColumn()) {
                        $ins->execute([':gr' => $gr, ':gp' => 'B', ':jor' => 'Tarde', ':sede' => $sede]);
                    }
                }
            }

            // 2. Obtener el ID del curso 11° A Mañana
            $stmt11A = $this->pdo->prepare("SELECT id FROM cursos WHERE grado = '11°' AND grupo = 'A' AND ano_lectivo = 2026 AND jornada = 'Mañana' LIMIT 1");
            $stmt11A->execute();
            $curso11AId = $stmt11A->fetchColumn();
            if (!$curso11AId) {
                $stmt11A = $this->pdo->prepare("SELECT id FROM cursos WHERE grado = '11°' AND grupo = 'A' AND ano_lectivo = 2026 LIMIT 1");
                $stmt11A->execute();
                $curso11AId = $stmt11A->fetchColumn();
            }

            if ($curso11AId) {
                // Actualizar todas las matrículas activas de 2026 hacia el curso 11° A
                $upMat = $this->pdo->prepare("UPDATE matriculas SET curso_id = :cid WHERE ano_lectivo = 2026 AND (curso_id != :cid OR curso_id IS NULL)");
                $upMat->execute([':cid' => $curso11AId]);

                // Matricular automáticamente a cualquier estudiante (rol_id = 5) que aún no tenga matrícula en 2026
                $sinMat = $this->pdo->query("
                    SELECT u.id 
                    FROM usuarios u 
                    LEFT JOIN matriculas m ON u.id = m.estudiante_id AND m.ano_lectivo = 2026 
                    WHERE u.rol_id = 5 AND m.id IS NULL
                ")->fetchAll(PDO::FETCH_COLUMN);

                foreach ($sinMat as $estId) {
                    $codigo = "EST-2026-" . str_pad($estId, 5, '0', STR_PAD_LEFT);
                    $insMat = $this->pdo->prepare("
                        INSERT INTO matriculas (estudiante_id, curso_id, ano_lectivo, estado_matricula, codigo_matricula)
                        VALUES (:eid, :cid, 2026, 'activa', :cod)
                    ");
                    $insMat->execute([
                        ':eid' => $estId,
                        ':cid' => $curso11AId,
                        ':cod' => $codigo
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log('Error asegurando cursos y estudiantes en 11° A: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene la instancia única de la clase Database (Singleton)
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    /**
     * Retorna el objeto PDO activo
     */
    public function getConnection(): ?PDO {
        return $this->pdo;
    }

    /**
     * Indica si la conexión física con MySQL está activa
     */
    public function isConnected(): bool {
        return $this->isConnected;
    }

    /**
     * Retorna el último mensaje de error si falló la conexión
     */
    public function getLastError(): ?string {
        return $this->lastError;
    }

    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("No se permite deserializar una instancia Singleton.");
    }
}
