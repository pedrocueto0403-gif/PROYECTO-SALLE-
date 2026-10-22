<?php
// Test generating a standalone docx from template
$tplPath = __DIR__ . '/../certificados/templates/constancia_estudios.docx';
if (!file_exists($tplPath)) {
    // let's check one of the original ones
    $tplPath = __DIR__ . '/../certificados/CONSTANCIA DE ESTUDIOS 2025.docx';
}

echo "Testing template: $tplPath\n";
$zip = new ZipArchive();
$tempFile = __DIR__ . '/test_output.docx';
if (file_exists($tempFile)) unlink($tempFile);

copy($tplPath, $tempFile);

if ($zip->open($tempFile) === TRUE) {
    echo "Zip opened successfully. Num files: " . $zip->numFiles . "\n";
    $zip->close();
    echo "Success! Size: " . filesize($tempFile) . " bytes\n";
} else {
    echo "Failed to open zip\n";
}
if (file_exists($tempFile)) unlink($tempFile);
