<?php
$files = [
    'CERTIFICADO DE CONDUCTAS 2023.docx',
    'CERTIFICADO COSTO EDUCATIVO.docx'
];
foreach ($files as $fn) {
    $file = __DIR__ . '/../certificados/' . $fn;
    echo "========================================\n";
    echo "FILE: $fn\n";
    echo "========================================\n";
    $zip = new ZipArchive();
    if ($zip->open($file) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $text = strip_tags($xml);
        $text = preg_replace("/\n\s*\n+/", "\n", $text);
        echo trim($text) . "\n\n";
        $zip->close();
    }
}
