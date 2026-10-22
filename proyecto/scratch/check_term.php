<?php
$url = 'http://aulavirtualsalle.com/views/auth/inscripcion.php';
$postData = ['action' => 'consultar_estado', 'termino_busqueda' => '1084221199'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);

// Find where "consultar_estado" or "termino_busqueda" is in the HTML
$pos = strpos($res, 'name="termino_busqueda"');
if ($pos !== false) {
    echo substr($res, $pos - 100, 300);
} else {
    echo "Not found\n";
}
