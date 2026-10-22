<?php
$files = [
    'views/auth/inscripcion.php',
    'views/admin/index.php',
    'views/administrativo/index.php',
    'views/coordinador/index.php',
    'config/Database.php'
];

foreach ($files as $f) {
    $path = __DIR__ . '/../' . $f;
    if (!file_exists($path)) continue;
    $c = file_get_contents($path);
    if (preg_match_all('/\$grados[a-zA-Z0-9_]*\s*=\s*\[[^\]]+\]/s', $c, $m)) {
        echo "=== $f ===\n";
        foreach ($m[0] as $match) {
            echo substr($match, 0, 300) . "\n---\n";
        }
    }
}
