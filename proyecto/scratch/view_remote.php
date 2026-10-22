<?php
$remote = file_get_contents('http://aulavirtualsalle.com/views/auth/inscripcion.php');
echo substr($remote, 0, 1500);
