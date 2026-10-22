<?php
$zip = new ZipArchive();
if ($zip->open(__DIR__ . '/../../practicas antigrabiti.zip') === TRUE) {
    $content = $zip->getFromName('practicas antigrabiti/views/auth/inscripcion.php');
    $lines = explode("\n", $content);
    for ($i = 95; $i <= 120; $i++) {
        echo ($i + 1) . ': ' . ($lines[$i] ?? '') . "\n";
    }
    $zip->close();
}
