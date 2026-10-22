<?php
require_once __DIR__ . '/../app/Services/ActaService.php';

$testFile = __DIR__ . '/test_acta_output.docx';
$res = ActaService::generateDocx([
    'numero_acta' => 'ACTA-2026-001',
    'tipo_acta' => 'Tipo I',
    'estudiante_nombre' => 'Matías Rafael Amaya González',
    'estudiante_doc' => 'TI 1083567310',
    'estudiante_grado' => '11° A',
    'fecha_acta' => '2026-09-24',
    'faltas_seleccionadas' => [
        'Interrumpir constantemente el desarrollo de las clases.',
        'Impuntualidad o inasistencia repetitiva a las clases.'
    ],
    'descargos' => 'El estudiante manifiesta que no se percató de la hora y reconoce la interrupción.',
    'testigos' => 'Prof. Carlos García, Gabriel De la hoz',
    'afectados_o_bienes' => 'Clima escolar en el aula de 11° A.',
    'compromisos' => 'Llegar puntual a todas las clases y mantener el orden institucional.',
    'firma_alumno' => 'Matías Rafael Amaya González',
    'firma_docente' => 'Carlos Alberto García López'
], $testFile);

if ($res && file_exists($testFile)) {
    echo "SUCCESS: Acta docx generated! Size: " . filesize($testFile) . " bytes\n";
    unlink($testFile);
} else {
    echo "FAILED: Could not generate docx\n";
}
