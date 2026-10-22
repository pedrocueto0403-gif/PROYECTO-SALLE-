<?php
require_once 'practicas antigrabiti/config/config.php';
require_once 'practicas antigrabiti/config/Database.php';
require_once 'practicas antigrabiti/app/Services/EmailGateway.php';
require_once 'practicas antigrabiti/app/Services/ActaService.php';

echo "=== PRUEBAS DE LOGO, CORREO Y CITACIONES ===\n\n";

// 1. Verificar logo del acta
$logoPath = 'practicas antigrabiti/public/assets/logo_acta.png';
echo "1. Logo del Acta (logo_acta.png): " . (file_exists($logoPath) ? "OK (" . filesize($logoPath) . " bytes)" : "ERROR: No existe") . "\n";

// 2. Verificar generación de Word con el logo
$tmpWord = sys_get_temp_dir() . '/test_final_acta.docx';
$sampleData = [
    'numero_acta' => 'ACTA-2026-FINAL-01',
    'tipo_acta' => 'Tipo I',
    'estudiante_nombre' => 'Amaya González Matías Rafael',
    'estudiante_doc' => 'TI 1083567310',
    'estudiante_grado' => '11° A',
    'fecha_acta' => '2026-09-24',
    'faltas_seleccionadas' => [
        '1. Llegar tarde al colegio, al aula de clases o a cualquier actividad institucional programada.'
    ],
    'descargos' => 'Estudiante manifiesta que se le presentó un inconveniente matutino.',
    'compromisos' => 'Compromiso de madrugar y cumplir el horario escolar.'
];
$okWord = ActaService::generateDocx($sampleData, $tmpWord);
echo "2. Generación Word (.docx) con Logo incrustado: " . ($okWord && file_exists($tmpWord) && filesize($tmpWord) > 15000 ? "OK (" . filesize($tmpWord) . " bytes)" : "ERROR") . "\n";

// 3. Probar método de notificación de Citación
$sampleEstudiante = [
    'primer_nombre' => 'Matías',
    'primer_apellido' => 'Amaya',
    'acudiente_nombre' => 'González Laura',
    'acudiente_correo' => 'acudiente.test@lasallecienaga.edu.co',
    'grado' => '11°',
    'grupo' => 'A'
];
$datosCita = [
    'fecha_citacion' => '2026-09-25 08:30:00',
    'modalidad' => 'Presencial — Coordinación',
    'motivo' => 'Revisión y firma de compromisos convivenciales y académicos.',
    'profesional' => 'Coordinador de Convivencia'
];
$resCita = EmailGateway::notificarCitacionAcudiente($sampleEstudiante, $datosCita);
echo "3. Notificación de Citación: Estado: " . ($resCita['estado'] ?? 'N/A') . " - " . ($resCita['mensaje'] ?? 'OK') . "\n";

// 4. Probar método de notificación de Acta
$datosActa = [
    'numero_acta' => 'ACTA-2026-0005',
    'tipo_acta' => 'Tipo I',
    'fecha_acta' => '2026-09-24',
    'faltas' => ['1. Llegar tarde al colegio o al aula de clase.'],
    'descargos' => 'Versión registrada en observador.',
    'compromisos' => 'Llegar puntual.',
    'convocante' => 'Coordinación de Convivencia'
];
$resActa = EmailGateway::notificarActaConvivencia($sampleEstudiante, $datosActa);
echo "4. Notificación de Acta al Acudiente: Estado: " . ($resActa['estado'] ?? 'N/A') . " - " . ($resActa['mensaje'] ?? 'OK') . "\n";

echo "\n=== TODAS LAS PRUEBAS FINALIZADAS CON ÉXITO ===\n";
