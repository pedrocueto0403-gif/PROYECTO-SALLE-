<?php
require_once __DIR__ . '/../config/config.php';
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

// Search for any alert or PHP error or message
preg_match_all('/<div class="alert[^>]*>(.*?)<\/div>/s', $response, $alerts);
echo "Alerts found:\n";
print_r($alerts[1]);

// Check if Database connection works on remote server
echo "Checking if form action was executed...\n";
if (strpos($response, 'alert-danger') !== false) {
    echo "Found alert-danger\n";
}
if (strpos($response, 'alert-success') !== false) {
    echo "Found alert-success\n";
}
