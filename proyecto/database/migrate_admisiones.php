<?php
/**
 * MIGRACIÓN: Tabla solicitudes_admision para proceso de admisión y matrícula
 * Instituto La Salle Ciénaga Magdalena
 */
require_once __DIR__ . '/../config/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        die("❌ Error: No se pudo conectar a la base de datos.\n");
    }

    echo "Iniciando migración de Admisiones...\n";

    // Crear tabla solicitudes_admision
    $sql = "
    CREATE TABLE IF NOT EXISTS `solicitudes_admision` (
        `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
        `codigo_solicitud` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Ej: SOL-2026-0001',
        
        -- Datos del Estudiante / Aspirante
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
        
        -- Datos Académicos
        `grado_solicitado` VARCHAR(30) NOT NULL COMMENT 'Ej: 10°, 11°, 6°, etc.',
        `curso_asignado_id` INT NULL COMMENT 'FK a cursos cuando se aprueba',
        `ano_lectivo` INT NOT NULL DEFAULT 2026,
        `colegio_origen` VARCHAR(200) NULL,
        
        -- Datos del Acudiente / Tutor Legal
        `acudiente_nombre` VARCHAR(180) NOT NULL,
        `acudiente_tipo_doc` ENUM('CC','CE','Pasaporte') NOT NULL DEFAULT 'CC',
        `acudiente_documento` VARCHAR(30) NOT NULL,
        `acudiente_parentesco` VARCHAR(50) NOT NULL DEFAULT 'Padre/Madre',
        `acudiente_telefono` VARCHAR(30) NOT NULL,
        `acudiente_correo` VARCHAR(150) NOT NULL,
        `acudiente_direccion` VARCHAR(255) NULL,
        `acudiente_ocupacion` VARCHAR(100) NULL,
        
        -- Estado del Flujo de Admisión
        `estado` ENUM('Pendiente','Aprobada','Rechazada','Matriculado') NOT NULL DEFAULT 'Pendiente',
        `monto_matricula` DECIMAL(12,2) NOT NULL DEFAULT 350000.00,
        `observaciones_revision` TEXT NULL,
        `motivo_rechazo` TEXT NULL,
        
        -- Vínculos tras Aprobación y Pago en Caja
        `recibo_caja_id` BIGINT NULL COMMENT 'FK contabilidad_caja tras el pago',
        `usuario_estudiante_id` BIGINT NULL COMMENT 'FK usuarios tras crear cuenta',
        `usuario_acudiente_id` BIGINT NULL COMMENT 'FK usuarios acudiente si aplica',
        `matricula_id` BIGINT NULL COMMENT 'FK matriculas tras crear registro formal',
        
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX `idx_estado` (`estado`),
        INDEX `idx_documento_est` (`documento_identidad`),
        INDEX `idx_documento_acud` (`acudiente_documento`),
        INDEX `idx_codigo` (`codigo_solicitud`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de solicitudes de inscripción y admisión de nuevos estudiantes';
    ";

    $pdo->exec($sql);
    echo "✅ Tabla `solicitudes_admision` creada o verificada exitosamente.\n";

    // Insertar registros de demostración si la tabla está vacía
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM solicitudes_admision");
    $total = $stmtCount->fetchColumn();

    if ($total == 0) {
        echo "Insertando solicitudes de aspirantes demo...\n";
        
        $demos = [
            [
                'codigo' => 'SOL-2026-0001',
                'p_nom' => 'Juan', 's_nom' => 'David', 'p_ape' => 'Gómez', 's_ape' => 'Castro',
                'tipo_doc' => 'TI', 'doc' => '1084221199', 'f_nac' => '2010-05-14', 'gen' => 'Masculino',
                'dir' => 'Calle 15 # 22-10 Barrio Kennedy', 'barrio' => 'Kennedy', 'ciudad' => 'Ciénaga',
                'tel' => '3015551234', 'correo' => 'juandavid.gomez@gmail.com',
                'grado' => '10°', 'col_origen' => 'Colegio San Juan de Dios',
                'acud_nom' => 'María Del Carmen Castro', 'acud_tdoc' => 'CC', 'acud_doc' => '57418902',
                'acud_parent' => 'Madre', 'acud_tel' => '3007788991', 'acud_email' => 'mariacastro.cienaga@gmail.com',
                'acud_dir' => 'Calle 15 # 22-10 Barrio Kennedy', 'acud_ocup' => 'Comerciante',
                'estado' => 'Pendiente', 'monto' => 350000.00
            ],
            [
                'codigo' => 'SOL-2026-0002',
                'p_nom' => 'Valentina', 's_nom' => 'Sofía', 'p_ape' => 'Morales', 's_ape' => 'Navarro',
                'tipo_doc' => 'TI', 'doc' => '1083994411', 'f_nac' => '2009-08-20', 'gen' => 'Femenino',
                'dir' => 'Carrera 20 # 11-45 Centro', 'barrio' => 'Centro', 'ciudad' => 'Ciénaga',
                'tel' => '3024445678', 'correo' => 'valen.morales@hotmail.com',
                'grado' => '11°', 'col_origen' => 'Instituto Moderno del Norte',
                'acud_nom' => 'Roberto Morales Blanco', 'acud_tdoc' => 'CC', 'acud_doc' => '85412987',
                'acud_parent' => 'Padre', 'acud_tel' => '3119988776', 'acud_email' => 'roberto.morales@construcciones.com',
                'acud_dir' => 'Carrera 20 # 11-45 Centro', 'acud_ocup' => 'Ingeniero',
                'estado' => 'Aprobada', 'monto' => 350000.00,
                'curso_asignado_id' => 3 // Grado 11° Grupo A
            ]
        ];

        $stmtIns = $pdo->prepare("
            INSERT INTO solicitudes_admision 
            (codigo_solicitud, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, documento_identidad, fecha_nacimiento, genero, direccion, barrio, ciudad, telefono, correo, grado_solicitado, curso_asignado_id, colegio_origen, acudiente_nombre, acudiente_tipo_doc, acudiente_documento, acudiente_parentesco, acudiente_telefono, acudiente_correo, acudiente_direccion, acudiente_ocupacion, estado, monto_matricula, created_at)
            VALUES 
            (:codigo, :p_nom, :s_nom, :p_ape, :s_ape, :tipo_doc, :doc, :f_nac, :gen, :dir, :barrio, :ciudad, :tel, :correo, :grado, :curso_id, :col_origen, :acud_nom, :acud_tdoc, :acud_doc, :acud_parent, :acud_tel, :acud_email, :acud_dir, :acud_ocup, :estado, :monto, NOW())
        ");

        foreach ($demos as $d) {
            $stmtIns->execute([
                ':codigo' => $d['codigo'],
                ':p_nom' => $d['p_nom'],
                ':s_nom' => $d['s_nom'],
                ':p_ape' => $d['p_ape'],
                ':s_ape' => $d['s_ape'],
                ':tipo_doc' => $d['tipo_doc'],
                ':doc' => $d['doc'],
                ':f_nac' => $d['f_nac'],
                ':gen' => $d['gen'],
                ':dir' => $d['dir'],
                ':barrio' => $d['barrio'],
                ':ciudad' => $d['ciudad'],
                ':tel' => $d['tel'],
                ':correo' => $d['correo'],
                ':grado' => $d['grado'],
                ':curso_id' => $d['curso_asignado_id'] ?? null,
                ':col_origen' => $d['col_origen'],
                ':acud_nom' => $d['acud_nom'],
                ':acud_tdoc' => $d['acud_tdoc'],
                ':acud_doc' => $d['acud_doc'],
                ':acud_parent' => $d['acud_parent'],
                ':acud_tel' => $d['acud_tel'],
                ':acud_email' => $d['acud_email'],
                ':acud_dir' => $d['acud_dir'],
                ':acud_ocup' => $d['acud_ocup'],
                ':estado' => $d['estado'],
                ':monto' => $d['monto']
            ]);
        }
        echo "✅ Solicitudes de demostración insertadas (1 Pendiente, 1 Aprobada lista para caja).\n";
    }

    echo "🎉 Migración de admisiones completada con éxito.\n";

} catch (Exception $e) {
    echo "❌ Error en migración: " . $e->getMessage() . "\n";
}
