<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=aulavirtual_salle;charset=utf8mb4', 'root', '123456789');

// Grados oficiales
$grados = [
    'Párvulo',
    'Prejardín',
    'Jardín',
    'Transición',
    '1°', '2°', '3°', '4°', '5°',
    '6°', '7°', '8°', '9°', '10°', '11°'
];

$sedes = ['Sede Principal', 'Sede Victoria'];

echo "Grados: " . count($grados) . "\n";
echo "Sedes: " . count($sedes) . "\n";
echo "Total expected courses: " . (count($grados) * 2 * count($sedes)) . "\n";
