<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

$pdo = Database::getInstance()->getConnection();
if ($pdo) {
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `aulavir7_Aulavirtualsalle` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        echo "Database `aulavir7_Aulavirtualsalle` ensured locally.\n";

        $sql = "
            CREATE TABLE IF NOT EXISTS `aulavir7_Aulavirtualsalle`.`solicitudes_admision` (
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
        ";
        $pdo->exec($sql);
        echo "Table `solicitudes_admision` ensured in `aulavir7_Aulavirtualsalle`.\n";

    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
}
