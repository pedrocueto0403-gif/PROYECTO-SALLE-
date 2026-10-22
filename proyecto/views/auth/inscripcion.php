<?php
require_once __DIR__ . '/../../config/config.php';
// Redirigir al login: Este proyecto está enfocado exclusivamente en administración de usuarios, registro de datos, subida de notas y boletines.
header("Location: " . APP_URL . "/views/auth/login.php");
exit();
