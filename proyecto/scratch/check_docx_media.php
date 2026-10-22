<?php
$file = __DIR__ . '/../certificados/CERTIFICADO ESTUDIO 2025.docx';
$zip = new ZipArchive();
if ($zip->open($file) === TRUE) {
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (strpos($stat['name'], 'media/') !== false) {
            echo "Media: " . $stat['name'] . " (" . $stat['size'] . " bytes)\n";
        }
    }
    $zip->close();
}
