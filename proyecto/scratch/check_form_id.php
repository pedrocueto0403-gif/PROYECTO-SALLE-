<?php
$c = file_get_contents(__DIR__ . '/../views/auth/inscripcion.php');
if (strpos($c, 'formInscripcion') !== false) {
    preg_match_all('/.*formInscripcion.*/', $c, $matches);
    print_r($matches[0]);
}
