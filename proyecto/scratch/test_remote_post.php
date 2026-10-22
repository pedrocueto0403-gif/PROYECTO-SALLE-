<?php
$url = 'http://aulavirtualsalle.com/views/auth/inscripcion.php';
$postData = [
    'action' => 'registrar_solicitud',
    'primer_nombre' => 'TestAspirante',
    'primer_apellido' => 'Prueba',
    'tipo_documento' => 'TI',
    'documento_identidad' => '9999999991',
    'grado_solicitado' => '10° Media Vocacional',
    'direccion' => 'Calle 10 # 5',
    'barrio' => 'Centro',
    'ciudad' => 'Ciénaga',
    'telefono' => '3001234567',
    'acudiente_nombre' => 'Papa Prueba',
    'acudiente_tipo_doc' => 'CC',
    'acudiente_documento' => '8888888881',
    'acudiente_parentesco' => 'Padre',
    'acudiente_telefono' => '3009876543',
    'acudiente_correo' => 'acudiente.test@gmail.com'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Code: $httpCode\n";

if (strpos($response, '¡Solicitud de Admisión Radicada con Éxito!') !== false) {
    echo "SUCCESS: Found success message!\n";
    preg_match('/SOL-2026-\d+/', $response, $matches);
    if (!empty($matches)) {
        echo "Radicado: " . $matches[0] . "\n";
    }
} elseif (strpos($response, 'alert-danger') !== false) {
    echo "DANGER ALERT in response:\n";
    preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $response, $matches);
    echo strip_tags($matches[0] ?? 'No details') . "\n";
} else {
    echo "Neither success nor danger alert found. First 500 chars of response:\n";
    echo substr($response, 0, 500) . "\n";
}
