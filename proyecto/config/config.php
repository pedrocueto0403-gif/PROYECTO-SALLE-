<?php
/**
 * ARCHIVO DE CONFIGURACIÓN GLOBAL - AULAVIRTUAL SALLE
 * Instituto La Salle Ciénaga Magdalena
 */

define('APP_NAME', 'AulaVirtual Salle');
define('INSTITUTION_NAME', 'Instituto La Salle');
define('LOCATION_NAME', 'Ciénaga – Magdalena');
define('MOTTO', 'Dios, Ciencia y Virtud');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$protocol = $isHttps ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:60';

// Detección automática de entorno (Local vs Hosting Remoto / cPanel / Vercel)
$isLocal = (!isset($_SERVER['HTTP_HOST']) || strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || strpos($host, '192.168.') !== false || strpos($host, ':60') !== false);

if ($isLocal) {
    // ENTORNO LOCAL (AppServ / Puerto 60)
    define('APP_URL',    $protocol . $host . '/practicas%20antigrabiti');
    define('ASSETS_URL', APP_URL . '/public');

    // Configuración Local MySQL
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'aulavirtual_salle');
    define('DB_USER', 'root');
    define('DB_PASS', '123456789');
    define('DB_CHARSET', 'utf8mb4');
} else {
    // ENTORNO REMOTO (cPanel / Vercel / Producción)
    define('APP_URL',    $protocol . $host);
    define('ASSETS_URL', APP_URL . '/public');

    // Credenciales Servidor Remoto cPanel / Variables de Entorno en Vercel
    if (file_exists(__DIR__ . '/db_production.php')) {
        require_once __DIR__ . '/db_production.php';
    }
    if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: '3306');
    if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'aulavir7_Aulavirtualsalle');
    if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'aulavir7_Aulavirtualsalle');
    if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: 'Aulavirtual@salle');
    if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
}

// Configuración de Seguridad & Sesión
define('SESSION_LIFETIME', 3600 * 4); // 4 horas
define('AES_KEY', 'LaSalleCienagaMagdalena2026SecretKeyCrypt'); // Para observador confidencial

// Configuración de Notificaciones por Correo (Email Gateway)
define('MAIL_FROM_ADDRESS', 'aulavirtualsalle@gmail.com');
define('MAIL_FROM_NAME', 'Instituto La Salle — Aula Virtual');
