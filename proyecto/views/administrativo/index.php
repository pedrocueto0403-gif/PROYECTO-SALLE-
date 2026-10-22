<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
Router::initSession();

// Redirigir al inicio del rol activo o al login (módulo tesorería/administrativo desactivado)
if (isset($_SESSION['user_role'])) {
    header("Location: " . Router::getHomeForRole($_SESSION['user_role']));
} else {
    header("Location: " . APP_URL . "/views/auth/login.php");
}
exit();
