<?php
/**
 * FRONT ROUTER PARA VERCEL - AULAVIRTUAL SALLE
 * Instituto La Salle Ciénaga Magdalena
 *
 * Vercel solo ejecuta PHP dentro de /api. Este archivo recibe TODAS las
 * peticiones (ver vercel.json) y ejecuta el script PHP correspondiente
 * dentro de /proyecto, conservando las mismas URLs que en AppServ/cPanel:
 *   /                                   -> proyecto/index.php
 *   /views/auth/login.php               -> proyecto/views/auth/login.php
 *   /app/Controllers/AuthController.php -> proyecto/app/Controllers/AuthController.php
 *   /public/css/custom.css              -> proyecto/public/css/custom.css
 */

$BASE = realpath(__DIR__ . '/../proyecto');

function vercel_not_found(): void {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>404 - Página no encontrada</title></head>'
       . '<body style="font-family:sans-serif;text-align:center;padding:60px">'
       . '<h1>404</h1><p>La página solicitada no existe.</p>'
       . '<p><a href="/views/auth/login.php">Ir al inicio de sesión</a></p></body></html>';
    exit();
}

if ($BASE === false) {
    http_response_code(500);
    echo 'No se encontró la carpeta "proyecto" en el despliegue.';
    exit();
}

// ── 1. Normalizar la ruta solicitada ─────────────────────────────────────────
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uriPath = rawurldecode($uriPath ?: '/');
$uriPath = '/' . ltrim(str_replace('\\', '/', $uriPath), '/');

if (strpos($uriPath, "\0") !== false || strpos($uriPath, '/..') !== false) {
    vercel_not_found();
}

if ($uriPath === '/api/index.php') {
    $uriPath = '/';
}

$target = realpath($BASE . $uriPath);
if ($target !== false && is_dir($target)) {
    $target = realpath($target . '/index.php');
    $uriPath = rtrim($uriPath, '/') . '/index.php';
}

// Impedir salir de /proyecto
if ($target === false || !is_file($target) || strpos($target, $BASE . DIRECTORY_SEPARATOR) !== 0) {
    vercel_not_found();
}

$relative = str_replace('\\', '/', substr($target, strlen($BASE)));

// ── 2. Carpetas privadas (credenciales, scripts internos, dumps SQL) ─────────
$blockedPrefixes = [
    '/config/',
    '/database/',
    '/scratch/',
    '/practicas antigrabiti/',
    '/app/',
];
// Única excepción dentro de /app: el controlador de login/logout
$allowedExceptions = [
    '/app/Controllers/AuthController.php',
];

if (!in_array($relative, $allowedExceptions, true)) {
    foreach ($blockedPrefixes as $prefix) {
        if (stripos($relative, $prefix) === 0) {
            vercel_not_found();
        }
    }
}

$ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));

// ── 3. Ejecutar scripts PHP ──────────────────────────────────────────────────
if ($ext === 'php') {
    $_SERVER['SCRIPT_FILENAME'] = $target;
    $_SERVER['SCRIPT_NAME']     = $uriPath;
    $_SERVER['PHP_SELF']        = $uriPath;
    $_SERVER['DOCUMENT_ROOT']   = $BASE;

    // Buffer de salida: permite que la sesión (guardada en cookies cifradas)
    // envíe sus cabeceras al final del script sin error "headers already sent".
    ob_start();

    chdir(dirname($target));
    require $target;
    return;
}

// ── 4. Archivos estáticos (respaldo si la ruta estática de vercel.json no aplica)
$mimeTypes = [
    'css'   => 'text/css; charset=utf-8',
    'js'    => 'application/javascript; charset=utf-8',
    'map'   => 'application/json',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'svg'   => 'image/svg+xml',
    'webp'  => 'image/webp',
    'ico'   => 'image/x-icon',
    'bmp'   => 'image/bmp',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
    'otf'   => 'font/otf',
    'eot'   => 'application/vnd.ms-fontobject',
    'pdf'   => 'application/pdf',
    'txt'   => 'text/plain; charset=utf-8',
];

if (!isset($mimeTypes[$ext])) {
    vercel_not_found();
}

header('Content-Type: ' . $mimeTypes[$ext]);
header('Content-Length: ' . filesize($target));
header('Cache-Control: public, max-age=86400');
readfile($target);
exit();
