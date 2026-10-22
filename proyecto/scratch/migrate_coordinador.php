<?php
$databases = ['aulavirtual_salle', 'aulavir7_aulavirtualsalle'];

foreach ($databases as $dbName) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$dbName", 'root', '123456789', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "=== Updating Database: $dbName ===\n";

        // 1. Ensure role 'coordinador' exists
        $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE id = 7 OR nombre_rol = 'coordinador'");
        $stmtRole->execute();
        $roleId = $stmtRole->fetchColumn();

        if (!$roleId) {
            $pdo->exec("INSERT INTO roles (id, nombre_rol, descripcion) VALUES (7, 'coordinador', 'Coordinador académico y de convivencia institucional')");
            echo "Role 'coordinador' (ID 7) created.\n";
        } else {
            echo "Role 'coordinador' already exists (ID: $roleId).\n";
        }

        // 2. Ensure table 'actas_convivencia' exists
        $pdo->exec("
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
        echo "Table 'actas_convivencia' verified.\n";

        // 3. Ensure coordinator user exists
        $coordEmail = 'coordinacion@lasallecienaga.edu.co';
        $stmtU = $pdo->prepare("SELECT id FROM usuarios WHERE correo = :correo");
        $stmtU->execute([':correo' => $coordEmail]);
        $coordUserId = $stmtU->fetchColumn();

        $hash = password_hash('Salle2026*', PASSWORD_BCRYPT);

        if (!$coordUserId) {
            $stmtInsU = $pdo->prepare("
                INSERT INTO usuarios (correo, password_hash, rol_id, estado_cuenta, debe_cambiar_password)
                VALUES (:correo, :hash, 7, 'activo', 0)
            ");
            $stmtInsU->execute([':correo' => $coordEmail, ':hash' => $hash]);
            $coordUserId = $pdo->lastInsertId();

            $stmtInsP = $pdo->prepare("
                INSERT INTO perfiles_detalles (usuario_id, primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, tipo_documento, documento_identidad, telefono, direccion)
                VALUES (:uid, 'Coordinación', 'Académica y', 'Convivencia', 'Escolar', 'CC', '1082987007', '3007654321', 'Calle 12 # 8-45 Ciénaga')
            ");
            $stmtInsP->execute([':uid' => $coordUserId]);
            echo "Coordinator user created (ID: $coordUserId, Email: $coordEmail).\n";
        } else {
            // Update password and role to ensure login works
            $stmtUpU = $pdo->prepare("UPDATE usuarios SET rol_id = 7, estado_cuenta = 'activo', password_hash = :hash WHERE id = :id");
            $stmtUpU->execute([':hash' => $hash, ':id' => $coordUserId]);
            echo "Coordinator user updated (ID: $coordUserId).\n";
        }

        echo "Database $dbName done!\n\n";
    } catch (Exception $e) {
        echo "Error in $dbName: " . $e->getMessage() . "\n";
    }
}
