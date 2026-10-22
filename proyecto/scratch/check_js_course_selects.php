<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'regCursoSelect') !== false || 
        stripos($line, 'aprobar_curso_select') !== false ||
        stripos($line, 'edit_curso_id') !== false ||
        stripos($line, 'default_curso_id') !== false) {
        echo ($i+1) . ': ' . trim(substr($line, 0, 100)) . PHP_EOL;
    }
}
