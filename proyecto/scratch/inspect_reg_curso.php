<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'administrador';
$_SESSION['rol_id'] = 1;

ob_start();
include __DIR__ . '/../views/admin/index.php';
$html = ob_get_clean();

$pos = strpos($html, 'id="regCursoSelect"');
if ($pos !== false) {
    echo "Found regCursoSelect!\n";
    $snippet = substr($html, $pos - 50, 1500);
    echo $snippet . "\n";
} else {
    echo "regCursoSelect not found!\n";
}
