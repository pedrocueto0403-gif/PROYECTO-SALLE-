<?php
require_once __DIR__ . '/../config/Database.php';

try {
    $pdo = Database::getInstance()->getConnection();
    if (!$pdo) {
        die("No DB connection\n");
    }

    // Check if acudiente_correo exists
    $cols = $pdo->query("SHOW COLUMNS FROM perfiles_detalles LIKE 'acudiente_correo'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE perfiles_detalles ADD COLUMN acudiente_correo VARCHAR(150) NULL AFTER acudiente_telefono");
        echo "Added acudiente_correo to perfiles_detalles.\n";
    } else {
        echo "acudiente_correo already exists.\n";
    }

    // Check if firma_digital exists
    $colsFirma = $pdo->query("SHOW COLUMNS FROM perfiles_detalles LIKE 'firma_digital'")->fetchAll();
    if (empty($colsFirma)) {
        $pdo->exec("ALTER TABLE perfiles_detalles ADD COLUMN firma_digital VARCHAR(255) NULL AFTER foto_perfil");
        echo "Added firma_digital to perfiles_detalles.\n";
    } else {
        echo "firma_digital already exists.\n";
    }

    // Check if notificaciones_enviadas or logs exists, or add a table for email notification audit
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notificaciones_correo` (
            `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
            `destinatario_correo` VARCHAR(150) NOT NULL,
            `destinatario_nombre` VARCHAR(180) NULL,
            `tipo_notificacion` ENUM('psicologia_citacion','cartera_cobro','general') NOT NULL,
            `asunto` VARCHAR(255) NOT NULL,
            `cuerpo_html` LONGTEXT NOT NULL,
            `referencia_id` BIGINT NULL COMMENT 'ID de citacion o de recibo/deuda',
            `estado_envio` ENUM('enviado','fallido','simulado') DEFAULT 'enviado',
            `fecha_envio` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table notificaciones_correo ensured.\n";

    // Set sample acudiente_correo for existing student padilla viviana (id 8) and martinez (id 5)
    $pdo->exec("UPDATE perfiles_detalles SET acudiente_correo = 'acudiente.martinez@lasallecienaga.edu.co' WHERE usuario_id = 5 AND (acudiente_correo IS NULL OR acudiente_correo = '')");
    $pdo->exec("UPDATE perfiles_detalles SET acudiente_correo = 'acudiente.padilla@gmail.com' WHERE usuario_id = 8 AND (acudiente_correo IS NULL OR acudiente_correo = '')");
    echo "Sample guardian emails updated.\n";

    // Check if debe_cambiar_password exists in usuarios
    $colsPass = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'debe_cambiar_password'")->fetchAll();
    if (empty($colsPass)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER estado_cuenta");
        echo "Added debe_cambiar_password to usuarios.\n";
    } else {
        echo "debe_cambiar_password already exists.\n";
    }

    // Set debe_cambiar_password = 1 for all teachers (rol_id = 3) and students (rol_id = 5)
    $pdo->exec("UPDATE usuarios SET debe_cambiar_password = 1 WHERE rol_id IN (3, 5)");
    echo "Set debe_cambiar_password = 1 for all registered teachers and students.\n";

} catch (Exception $e) {
    echo "Error in migration: " . $e->getMessage() . "\n";
}
