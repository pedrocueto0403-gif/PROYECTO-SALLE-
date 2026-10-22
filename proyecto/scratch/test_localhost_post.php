<?php
$url = 'http://localhost:60/practicas%20antigrabiti/views/auth/inscripcion.php';
$postData = [
    'action' => 'registrar_solicitud',
    'primer_nombre' => 'TestLocal',
    'primer_apellido' => 'Estudiante',
    'tipo_documento' => 'TI',
    'documento_identidad' => '7788990011',
    'grado_solicitado' => '10° Media Vocacional',
    'direccion' => 'Calle 10 # 5',
    'barrio' => 'Centro',
    'ciudad' => 'Ciénaga',
    'telefono' => '3001234567',
    'acudiente_nombre' => 'Acudiente Local',
    'acudiente_tipo_doc' => 'CC',
    'acudiente_documento' => '6655443322',
    'acudiente_parentesco' => 'Madre',
    'acudiente_telefono' => '3009876543',
    'acudiente_correo' => 'acudiente.local@gmail.com'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code on localhost:60: $httpCode\n";
if (strpos($res, '¡Solicitud de Admisión Radicada con Éxito!') !== false) {
    echo "SUCCESS on localhost:60! Solicitud radicada!\n";
    preg_match('/SOL-2026-\d+/', $res, $m);
    if (!empty($m)) echo "Radicado: " . $m[0] . "\n";
} else {
    echo "FAILED on localhost:60.\n";
    if (preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $res, $m)) {
        echo "Alert: " . trim(strip_tags($m[1])) . "\n";
    }
}
