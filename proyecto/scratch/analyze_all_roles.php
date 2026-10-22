<?php
$files = [
    'views/auth/inscripcion.php',
    'views/admin/index.php',
    'views/administrativo/index.php',
    'views/coordinador/index.php',
    'database/database.sql',
    '../aulavir7_Aulavirtualsalle.sql'
];

foreach ($files as $f) {
    $path = __DIR__ . '/../' . $f;
    if (!file_exists($path)) {
        echo "Missing $f at $path\n";
        continue;
    }
    $c = file_get_contents($path);
    echo "=== $f (Size: " . strlen($c) . ") ===\n";
    if (preg_match_all('/(sede[a-zA-Z0-9_]*)/i', $c, $m)) {
        $unique = array_unique(array_map('strtolower', $m[1]));
        echo "Sede terms: " . implode(', ', array_slice($unique, 0, 15)) . "\n";
    }
}
