<?php
require_once __DIR__ . '/../config/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "Running migrations on: " . DB_NAME . "...\n";

    // 1. Alter planes_clase: add archivo_pdf and make semana_numero nullable
    $cols = $pdo->query("DESCRIBE planes_clase")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('archivo_pdf', $cols)) {
        $pdo->exec("ALTER TABLE planes_clase ADD COLUMN archivo_pdf VARCHAR(255) NULL AFTER contenido_pei");
        echo " - Added column 'archivo_pdf' to planes_clase\n";
    }
    // Make semana_numero nullable with default null
    $pdo->exec("ALTER TABLE planes_clase MODIFY COLUMN semana_numero INT(11) NULL DEFAULT 1");
    echo " - Modified 'semana_numero' in planes_clase to be optional\n";

    // 2. Create table banco_logros if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `banco_logros` (
            `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
            `docente_id` BIGINT(20) NOT NULL,
            `curso_id` INT(11) NOT NULL,
            `asignatura_id` INT(11) NOT NULL,
            `periodo` TINYINT(4) NOT NULL DEFAULT 1,
            `escala` ENUM('Superior', 'Alto', 'Basico', 'Bajo') NOT NULL,
            `descripcion_logro` TEXT NOT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_doc_cur_asig_per_esc` (`docente_id`, `curso_id`, `asignatura_id`, `periodo`, `escala`),
            KEY `idx_busqueda_logros` (`curso_id`, `asignatura_id`, `periodo`, `escala`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo " - Created table 'banco_logros' successfully\n";

    // 3. Insert default institutional achievements if empty
    $countLogros = $pdo->query("SELECT COUNT(*) FROM banco_logros")->fetchColumn();
    echo " - Total banco_logros count: $countLogros\n";

    echo "Migrations executed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
