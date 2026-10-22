<?php
$url = 'http://aulavirtualsalle.com/views/auth/inscripcion.php';
$postData = ['action' => 'consultar_estado', 'termino_busqueda' => '1084221199'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);

// Search for any message in the whole HTML
if (preg_match('/<div class="alert[^>]*>(.*?)<\/div>/s', $res, $m)) {
    echo "ALERT FOUND: " . trim(strip_tags($m[1])) . "\n";
} else {
    echo "NO ALERT in HTML\n";
}

// Search for panel-consulta
$p = strpos($res, 'id="panel-consulta"');
echo substr($res, $p, 800);
