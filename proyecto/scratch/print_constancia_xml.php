<?php
$tpl = __DIR__ . '/../certificados/templates/constancia_estudios.docx';
$zip = new ZipArchive();
if ($zip->open($tpl) === TRUE) {
    echo $zip->getFromName('word/document.xml');
    $zip->close();
}
