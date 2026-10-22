<?php
$lines = file(__DIR__ . '/../views/administrativo/index.php');
foreach ($lines as $i => $line) {
    if (stripos($line, '<select') !== false && stripos($line, 'curso') !== false ||
        stripos($line, 'name="curso') !== false ||
        stripos($line, 'name="grado') !== false ||
        stripos($line, 'modalCobroMasivo') !== false) {
        echo ($i+1) . ': ' . trim($line) . PHP_EOL;
    }
}
