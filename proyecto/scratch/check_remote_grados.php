<?php
$html = file_get_contents('http://aulavirtualsalle.com/views/auth/inscripcion.php');
$p1 = strpos($html, 'name="grado_solicitado"');
if ($p1 !== false) {
    $p2 = strpos($html, '</select>', $p1);
    echo substr($html, $p1, $p2 - $p1 + 9);
} else {
    echo "grado_solicitado select not found!\n";
}
