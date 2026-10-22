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
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);

// Check if any error or success is anywhere
echo "Length of response: " . strlen($response) . "\n";
// Let's search for words
foreach (['Error', 'guardando', 'Éxito', 'Radicada', 'obligatorios', 'solicitud', 'alert'] as $w) {
    echo "Search '$w': " . (stripos($response, $w) !== false ? "FOUND" : "NOT FOUND") . "\n";
}

// Check where in the response 'alert' or error might be
preg_match_all('/class="[^"]*alert[^"]*"/', $response, $alerts);
print_r($alerts[0]);
