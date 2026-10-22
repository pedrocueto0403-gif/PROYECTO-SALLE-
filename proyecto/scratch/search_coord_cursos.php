<?php
$lines = file(__DIR__ . '/../views/coordinador/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, '<select') !== false || stripos($line, 'curso') !== false && stripos($line, 'option') !== false) {
        echo ($i+1) . ': ' . trim(substr($line, 0, 100)) . PHP_EOL;
    }
}
