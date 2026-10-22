<?php
$files = [
    'CERTIFICADO COSTO EDUCATIVO.docx' => 'C:/AppServ/www/certificados/CERTIFICADO COSTO EDUCATIVO.docx',
    'CERTIFICADO DE CONDUCTAS 2023.docx' => 'C:/AppServ/www/certificados/CERTIFICADO DE CONDUCTAS 2023.docx',
    'CERTIFICADO ESTUDIO 2025.docx' => 'C:/AppServ/www/certificados/CERTIFICADO ESTUDIO 2025.docx',
    'CERTIFICADO LABORAL DOCENTE 2024.docx' => 'C:/AppServ/www/certificados/CERTIFICADO LABORAL DOCENTE 2024.docx',
    'CERTIFICADO PAZ Y SALVO.docx' => 'C:/AppServ/www/certificados/CERTIFICADO PAZ Y SALVO.docx',
    'CONSTANCIA DE ESTUDIOS 2025.docx' => 'C:/AppServ/www/certificados/CONSTANCIA DE ESTUDIOS 2025.docx',
    'Observador estudiante rectoria.docx' => 'C:/AppServ/www/certificados/Observador estudiante rectoria.docx'
];

foreach ($files as $name => $path) {
    echo "\n--------------------------------------------------\n";
    echo "DOCUMENTO: $name\n";
    echo "--------------------------------------------------\n";
    if (!file_exists($path)) {
        echo "No existe en $path\n";
        continue;
    }
    $zip = new ZipArchive();
    if ($zip->open($path) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $text = strip_tags($xml);
        $text = preg_replace('/\s+/', ' ', $text);
        echo substr($text, 0, 700) . "...\n";
    }
}
