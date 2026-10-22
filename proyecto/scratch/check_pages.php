<?php
$files = glob(__DIR__ . '/../certificados/*.docx');
foreach ($files as $file) {
    echo "========================================\n";
    echo "FILE: " . basename($file) . "\n";
    $zip = new ZipArchive();
    if ($zip->open($file) === TRUE) {
        $xml = $zip->getFromName('word/document.xml');
        $text = strip_tags(preg_replace('/<\/w:p>/', "\n", $xml));
        // count occurrences of "LA SUSCRITA RECTORA" or "CERTIFICA" or "HACEN CONSTAR"
        preg_match_all('/(HACEN CONSTAR|CERTIFICAN|CERTIFICA)/i', $text, $matches);
        echo "Headings found: " . count($matches[0]) . "\n";
        $zip->close();
    }
}
