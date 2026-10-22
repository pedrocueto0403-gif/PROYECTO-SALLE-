<?php
$lines = file(__DIR__ . '/../views/admin/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, 'modalChangeUserRole') !== false || 
        stripos($line, 'sede_caja') !== false || 
        stripos($line, 'eval-tab-docente') !== false ||
        stripos($line, 'coordinador') !== false ||
        stripos($line, 'modalNuevoUsuario') !== false ||
        stripos($line, 'modalEditarUsuario') !== false) {
        echo ($i+1) . ': ' . trim(substr($line, 0, 100)) . PHP_EOL;
    }
}
