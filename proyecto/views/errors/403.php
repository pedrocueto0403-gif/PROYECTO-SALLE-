<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
Router::initSession();

$userRole = $_SESSION['user_role'] ?? null;
$homeUrl = $userRole ? Router::getHomeForRole($userRole) : APP_URL . '/views/auth/login.php';
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Acceso Denegado - AulaVirtual Salle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/custom.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-glass-card text-center text-dark" style="max-width: 520px;">
        <div class="mb-3">
            <span class="badge bg-danger p-3 rounded-circle fs-1 shadow"><i class="fas fa-user-slash"></i></span>
        </div>
        <h2 class="h3 fw-bold text-danger mb-2 font-heading">403 - Acceso No Autorizado</h2>
        <p class="text-secondary small mb-4">
            Su nivel de permiso actual no le autoriza a consultar esta sección del sistema institucional. La solicitud ha sido cancelada por el módulo de seguridad RBAC.
        </p>

        <div class="p-3 bg-light rounded border mb-4 text-start small">
            <div><strong>Rol Activo:</strong> <span class="text-uppercase text-primary fw-bold"><?= htmlspecialchars($userRole ?? 'No Autenticado') ?></span></div>
            <div><strong>Controlador:</strong> SecurityRBAC & SessionGuard</div>
        </div>

        <a href="<?= $homeUrl ?>" class="btn btn-lasalle-primary w-100">
            <i class="fas fa-home me-2"></i> Regresar a Mi Dashboard Autorizado
        </a>
    </div>
</body>
</html>
