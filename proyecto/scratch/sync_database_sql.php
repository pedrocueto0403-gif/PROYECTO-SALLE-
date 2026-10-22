<?php
$files = [
    __DIR__ . '/../database/database.sql',
    'C:/AppServ/www/practicas antigrabiti/database/database.sql'
];

$sqlDumpContent = file_get_contents(__DIR__ . '/../../aulavir7_Aulavirtualsalle.sql');

// Extract cursos block from aulavir7_Aulavirtualsalle.sql
preg_match('/DROP TABLE IF EXISTS `cursos`;[\s\S]*?UNLOCK TABLES;/', $sqlDumpContent, $matches);
$cursosBlock = $matches[0] ?? null;

if (!$cursosBlock) {
    echo "Could not extract cursosBlock from aulavir7_Aulavirtualsalle.sql!\n";
    exit(1);
}

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    // In database.sql, pattern is DROP TABLE IF EXISTS `cursos`; up to next comment -- ---
    $pat = '/DROP TABLE IF EXISTS `cursos`;[\s\S]*?(?=-- --------------------------------------------------------)/';
    if (preg_match($pat, $c)) {
        $c = preg_replace($pat, $cursosBlock . "\n\n", $c, 1);
        file_put_contents($f, $c);
        echo "Updated cursos in $f\n";
    } else {
        echo "Pattern not matched in $f\n";
    }
}
