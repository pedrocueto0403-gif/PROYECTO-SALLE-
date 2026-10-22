<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'action' => 'registrar_solicitud',
    'primer_nombre' => 'Estudiante',
    'primer_apellido' => 'FinalTest',
    'tipo_documento' => 'TI',
    'documento_identidad' => '1122334455',
    'fecha_nacimiento' => '2010-03-25',
    'genero' => 'Masculino',
    'direccion' => 'Calle 15 # 8-20',
    'barrio' => 'Centro',
    'ciudad' => 'Ciénaga',
    'telefono' => '3005554433',
    'correo' => 'estudiante.final@test.com',
    'grado_solicitado' => '10° Media Vocacional',
    'colegio_origen' => 'Colegio Las Flores',
    'acudiente_nombre' => 'Acudiente Final',
    'acudiente_tipo_doc' => 'CC',
    'acudiente_documento' => '9988112233',
    'acudiente_parentesco' => 'Padre',
    'acudiente_telefono' => '3007778899',
    'acudiente_correo' => 'acudiente.final@test.com'
];

ob_start();
require __DIR__ . '/../views/auth/inscripcion.php';
$html = ob_get_clean();

echo "HTML Length: " . strlen($html) . "\n";
if (strpos($html, '¡Solicitud de Admisión Radicada con Éxito!') !== false) {
    echo "SUCCESS: Page rendered Radicado con Éxito!\n";
    preg_match('/SOL-2026-\d+/', $html, $m);
    if (!empty($m)) echo "Radicado generated: " . $m[0] . "\n";
} else {
    echo "FAIL: Success message not found.\n";
    if (preg_match('/<div class="alert alert-danger[^>]*>(.*?)<\/div>/s', $html, $m)) {
        echo "Alert: " . strip_tags($m[1]) . "\n";
    }
}
