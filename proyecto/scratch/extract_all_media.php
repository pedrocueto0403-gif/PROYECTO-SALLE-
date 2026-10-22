<?php
$targetDir = __DIR__ . '/../public/assets/certificados/';
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

$files = glob(__DIR__ . '/../certificados/*.docx');
foreach ($files as $file) {
    $zip = new ZipArchive();
    if ($zip->open($file) === TRUE) {
        $prefix = strtolower(preg_replace('/[^a-z0-9]/i', '_', pathinfo($file, PATHINFO_FILENAME)));
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (strpos($stat['name'], 'media/') !== false && (preg_match('/\.(png|jpe?g|gif)$/i', $stat['name']))) {
                $ext = pathinfo($stat['name'], PATHINFO_EXTENSION);
                $destName = $prefix . '_' . basename($stat['name']);
                $content = $zip->getFromIndex($i);
                file_put_contents($targetDir . $destName, $content);
                echo "Extracted: $destName (" . strlen($content) . " bytes)\n";
            }
        }
        $zip->close();
    }
}
