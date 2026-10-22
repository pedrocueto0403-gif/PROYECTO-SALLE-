<?php
session_start();
$_SESSION['user_role'] = 'administrador';
$_SESSION['rol_id'] = 1;
$_REQUEST['action'] = 'get_person';
$_GET['action'] = 'get_person';
$_GET['type'] = 'student';
$_GET['id'] = 193;

ob_start();
include __DIR__ . '/../views/admin/certificados_action.php';
$output = ob_get_clean();

echo "Response from get_person:\n" . $output . "\n";

// Now test docx generation directly
$outDocx = __DIR__ . '/test_download.docx';
generateCertificateDocxFile('estudio', [
    'nombre_completo' => 'ALEJANDRA BRITO ÁVILA',
    'tipo_doc' => 'T.I',
    'numero_doc' => '1.121.971.552',
    'grado' => '10°',
    'grupo' => 'A',
    'nivel' => 'EDUCACIÓN MEDIA',
    'ano_lectivo' => '2026',
    'dia' => '16',
    'mes' => 'Septiembre',
    'ano' => '2026'
], $outDocx);

if (file_exists($outDocx)) {
    echo "Docx generated successfully! Size: " . filesize($outDocx) . " bytes\n";
    unlink($outDocx);
} else {
    echo "Docx generation failed!\n";
}
