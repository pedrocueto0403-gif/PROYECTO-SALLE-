<?php
$zipPath = __DIR__ . '/../../practicas antigrabiti.zip';
$zip = new ZipArchive();

if ($zip->open($zipPath) === TRUE) {
    $filesToUpdate = [
        'views/auth/inscripcion.php',
        'views/admin/index.php',
        'views/administrativo/index.php',
        'config/Database.php',
        'config/config.php',
        'database/database.sql'
    ];

    foreach ($filesToUpdate as $relPath) {
        $fullPath = __DIR__ . '/../' . $relPath;
        if (file_exists($fullPath)) {
            $zipEntry = 'practicas antigrabiti/' . $relPath;
            $zip->addFromString($zipEntry, file_get_contents($fullPath));
            echo "Updated in zip: $zipEntry\n";
        }
    }

    $zip->close();
    echo "Zip updated successfully!\n";
} else {
    echo "Failed to open zip file.\n";
}
