<?php
/**
 * DESCARGA DE PLANTILLA OFICIAL DE CARGA MASIVA (EXCEL / GOOGLE FORMS / CSV)
 * Instituto La Salle Ciénaga Magdalena
 */

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="plantilla_matricula_estudiantes_la_salle.csv"');

// Salida con BOM UTF-8 para que Excel lo abra con acentos y caracteres especiales correctamente
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Encabezados idénticos a los recolectados en Google Forms / Formularios de Matrícula
fputcsv($output, [
    'Marca temporal',
    'Tipo de Documento',
    'Número de Documento',
    'Primer Nombre',
    'Segundo Nombre',
    'Primer Apellido',
    'Segundo Apellido',
    'Correo Electrónico Estudiante',
    'Grado o Curso',
    'Nombre Completo del Acudiente',
    'Correo Electrónico del Acudiente',
    'Teléfono o WhatsApp del Acudiente',
    'Dirección de Residencia'
]);

// Filas de ejemplo realistas
fputcsv($output, [
    date('Y-m-d H:i:s'),
    'TI',
    '1082987110',
    'Andrés',
    'Felipe',
    'Mendoza',
    'Castro',
    'andres.mendoza@lasallecienaga.edu.co',
    '10° A',
    'Jorge Mendoza Blanco',
    'jorge.mendoza@gmail.com',
    '3015551234',
    'Calle 15 # 8-20, Ciénaga'
]);

fputcsv($output, [
    date('Y-m-d H:i:s'),
    'TI',
    '1082987111',
    'Valentina',
    '',
    'Herrera',
    'Pérez',
    'valentina.herrera@lasallecienaga.edu.co',
    '10° A',
    'Laura Pérez Gómez',
    'laura.perez@gmail.com',
    '3104445678',
    'Carrera 12 # 22-10, Ciénaga'
]);

fclose($output);
exit;
