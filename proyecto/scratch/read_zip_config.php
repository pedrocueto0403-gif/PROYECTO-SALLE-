<?php
$zip = new ZipArchive();
if ($zip->open(__DIR__ . '/../../practicas antigrabiti.zip') === TRUE) {
    echo $zip->getFromName('practicas antigrabiti/config/config.php');
    $zip->close();
} else {
    echo "Could not open zip.\n";
}
