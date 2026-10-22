<?php
$lines = file(__DIR__ . '/../views/administrativo/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'id="caja"') !== false || 
        stripos($line, 'id="modalNuevoRecibo"') !== false || 
        stripos($line, 'id="modalNuevoEgreso"') !== false ||
        stripos($line, 'modalCobrarMatricula') !== false ||
        stripos($line, 'sede_caja') !== false) {
        echo ($i+1) . ': ' . trim(substr($line, 0, 100)) . PHP_EOL;
    }
}
