<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456789');
    $dbs = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases:\n";
    print_r($dbs);

    foreach (['aulavirtual_salle', 'aulavir7_aulavirtualsalle', 'aulavir7_Aulavirtualsalle'] as $db) {
        if (in_array($db, $dbs)) {
            echo "\nChecking tables in $db:\n";
            $tables = $pdo->query("SHOW TABLES FROM `$db`")->fetchAll(PDO::FETCH_COLUMN);
            echo "Count: " . count($tables) . "\n";
            if (in_array('cursos', $tables)) {
                $cursos = $pdo->query("SELECT id, grado, grupo, jornada, sede FROM `$db`.cursos ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                echo "Cursos count: " . count($cursos) . "\n";
                foreach ($cursos as $c) {
                    echo "{$c['id']}: {$c['grado']} {$c['grupo']} ({$c['jornada']} - {$c['sede']})\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
