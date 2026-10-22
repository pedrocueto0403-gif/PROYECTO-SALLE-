<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'administrador';
$_SESSION['rol_id'] = 1;

ob_start();
include __DIR__ . '/../views/admin/index.php';
$html = ob_get_clean();

if (preg_match('/window\.cursosDataGlobal\s*=\s*(.*?);/s', $html, $m)) {
    echo "FOUND window.cursosDataGlobal\n";
    echo "Length: " . strlen($m[1]) . "\n";
    echo "Substr: " . substr($m[1], 0, 150) . "...\n";
    $data = json_decode($m[1], true);
    echo "Decoded count: " . (is_array($data) ? count($data) : "NULL/FALSE (error: " . json_last_error_msg() . ")") . "\n";
    if (is_array($data)) {
        $tCount = 0;
        $mCount = 0;
        foreach ($data as $item) {
            if (isset($item['jornada'])) {
                if (strtolower($item['jornada']) === 'tarde') $tCount++;
                else $mCount++;
            }
        }
        echo "Items with Tarde: $tCount | Items with Mañana/Other: $mCount\n";
    }
} else {
    echo "window.cursosDataGlobal NOT FOUND in HTML!\n";
}
