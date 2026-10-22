<?php
$content = file_get_contents(__DIR__ . '/../views/admin/index.php');
$lines = explode("\n", $content);
foreach ($lines as $i => $line) {
    if (stripos($line, 'regCursoSelect') !== false || stripos($line, 'innerHTML') !== false && stripos($line, 'curso') !== false) {
        echo ($i+1) . ': ' . trim($line) . PHP_EOL;
    }
}
