<?php
$file = __DIR__ . '/../certificados/CERTIFICADO DE CONDUCTAS 2023.docx';
$zip = new ZipArchive();
if ($zip->open($file) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    $xml = preg_replace('/<\/w:p>/', "\n", $xml);
    $text = strip_tags($xml);
    $text = preg_replace("/\n\s*\n+/", "\n", $text);
    echo substr(trim($text), 0, 1500) . "\n";
    $zip->close();
}
