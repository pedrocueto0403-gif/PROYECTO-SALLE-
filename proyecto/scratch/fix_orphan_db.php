<?php
$dataDir = 'C:/AppServ/MySQL/data';
if (file_exists("$dataDir/aulavirtual_salle") && !in_array('aulavirtual_salle', (new PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456789'))->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN))) {
    rename("$dataDir/aulavirtual_salle", "$dataDir/aulavirtual_salle_orphan_backup");
    echo "Moved orphaned aulavirtual_salle\n";
}
if (file_exists("$dataDir/aulavir7_aulavirtualsalle") && !in_array('aulavir7_aulavirtualsalle', (new PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456789'))->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN))) {
    rename("$dataDir/aulavir7_aulavirtualsalle", "$dataDir/aulavir7_orphan_backup");
    echo "Moved orphaned aulavir7_aulavirtualsalle\n";
}

$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '123456789');
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `aulavirtual_salle` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Successfully created aulavirtual_salle in MySQL!\n";
} catch (Exception $e) {
    echo "Create error: " . $e->getMessage() . "\n";
}
