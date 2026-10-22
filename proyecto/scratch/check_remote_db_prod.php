<?php
// Check if db_production.php can be accessed directly (should be 403 or 200 blank)
$ch = curl_init('http://aulavirtualsalle.com/config/db_production.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "db_production.php HTTP Code: $code\n";
