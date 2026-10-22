<?php
$files = [
    'views/admin/index.php',
    'views/administrativo/index.php',
    'views/comunidad/index.php',
    'views/docente/index.php',
    'views/coordinador/index.php',
    'views/auth/inscripcion.php',
    'public/js/app.js'
];

foreach ($files as $file) {
    if (!file_exists(__DIR__ . '/../' . $file)) continue;
    $content = file_get_contents(__DIR__ . '/../' . $file);
    $lines = explode("\n", $content);
    foreach ($lines as $i => $line) {
        if (stripos($line, 'Jornada Mañana') !== false || 
            stripos($line, 'Jornada Tarde') !== false ||
            stripos($line, 'regCursoSelect') !== false ||
            stripos($line, 'editCursoSelect') !== false ||
            stripos($line, 'cursosList') !== false ||
            stripos($line, 'cursos') !== false && stripos($line, 'select') !== false) {
            echo "$file:" . ($i+1) . " -> " . trim(substr($line, 0, 100)) . PHP_EOL;
        }
    }
}
