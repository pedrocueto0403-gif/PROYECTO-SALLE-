<?php
$ch = curl_init('http://aulavirtualsalle.com/config/Database.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Database.php HTTP Code: $code\n";
