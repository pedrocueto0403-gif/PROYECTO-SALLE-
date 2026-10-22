<?php
$tpl = __DIR__ . '/../certificados/templates/constancia_estudios.docx';
$out = __DIR__ . '/test_out2.docx';
copy($tpl, $out);

$zip = new ZipArchive();
if ($zip->open($out) === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    // Let's see what is inside
    echo "Current XML length: " . strlen($xml) . "\n";
    $zip->close();
}
if (file_exists($out)) unlink($out);
