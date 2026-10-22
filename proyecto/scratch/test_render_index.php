<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Herman Rodríguez';
$_SESSION['user_role'] = 'administrador';
$_SESSION['rol_id'] = 1;

ob_start();
include 'C:/AppServ/www/practicas antigrabiti/views/admin/index.php';
$html = ob_get_clean();

echo "HTML length: " . strlen($html) . " bytes\n";
if (strpos($html, 'Centro Oficial de Emisión de Certificados Digitales') !== false) {
    echo "SUCCESS: Centro Oficial de Emisión de Certificados Digitales found in HTML!\n";
} else {
    echo "ERROR: Certificate section NOT found!\n";
}

if (strpos($html, 'cert_sel_estudiante') !== false) {
    echo "SUCCESS: cert_sel_estudiante found in HTML!\n";
} else {
    echo "ERROR: cert_sel_estudiante NOT found!\n";
}

if (strpos($html, 'cert_sel_docente') !== false) {
    echo "SUCCESS: cert_sel_docente found in HTML!\n";
} else {
    echo "ERROR: cert_sel_docente NOT found!\n";
}
