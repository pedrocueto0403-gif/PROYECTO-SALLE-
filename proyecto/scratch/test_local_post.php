<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
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

ob_start();
require __DIR__ . '/../views/auth/inscripcion.php';
$localOutput = ob_get_clean();

echo "Local output length: " . strlen($localOutput) . "\n";
foreach (['Error', 'guardando', 'Éxito', 'Radicada', 'obligatorios', 'solicitud', 'alert'] as $w) {
    echo "Local Search '$w': " . (stripos($localOutput, $w) !== false ? "FOUND" : "NOT FOUND") . "\n";
}
