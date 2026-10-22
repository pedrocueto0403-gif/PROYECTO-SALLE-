<?php
$html = file_get_contents('http://aulavirtualsalle.com/views/auth/inscripcion.php');
// Search for formInscripcion in HTML
$pos = strpos($html, 'id="formInscripcion"');
if ($pos !== false) {
    echo substr($html, $pos - 50, 400);
} else {
    echo "formInscripcion not found in remote HTML!\n";
}
