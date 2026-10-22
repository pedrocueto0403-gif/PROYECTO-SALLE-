<?php
$remote = file_get_contents('http://aulavirtualsalle.com/views/auth/inscripcion.php');
echo "Remote length: " . strlen($remote) . " bytes\n";
$local = file_get_contents(__DIR__ . '/../views/auth/inscripcion.php');
echo "Local length: " . strlen($local) . " bytes\n";

if (strpos($remote, 'registrar_solicitud') !== false) {
    echo "Remote HAS 'registrar_solicitud'\n";
} else {
    echo "Remote DOES NOT HAVE 'registrar_solicitud'!\n";
}

if (strpos($remote, 'solicitudes_admision') !== false) {
    echo "Remote HAS 'solicitudes_admision'\n";
} else {
    echo "Remote DOES NOT HAVE 'solicitudes_admision'!\n";
}
