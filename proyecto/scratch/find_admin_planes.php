<?php
$f = file('c:/Users/SANTIAGO RODRIGUEZ/Desktop/Aula virtual/practicas antigrabiti/views/admin/index.php');
foreach ($f as $num => $line) {
    if (stripos($line, 'approve_plan') !== false || stripos($line, 'planesList') !== false || stripos($line, 'planes_clase') !== false) {
        echo ($num+1) . ": " . trim($line) . "\n";
    }
}
