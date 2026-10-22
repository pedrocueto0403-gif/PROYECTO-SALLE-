<?php
/**
 * FRONT CONTROLLER - AULAVIRTUAL SALLE
 * Instituto La Salle Ciénaga Magdalena
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/Router.php';

Router::initSession();

if (isset($_SESSION['user_role'])) {
    header("Location: " . Router::getHomeForRole($_SESSION['user_role']));
    exit();
}

header("Location: " . APP_URL . "/views/auth/login.php");
exit();
