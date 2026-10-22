<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
Router::initSession();

// Redirigir si ya existe sesión activa
if (isset($_SESSION['user_role'])) {
    header("Location: " . Router::getHomeForRole($_SESSION['user_role']));
    exit();
}

$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aula Virtual - Instituto La Salle Ciénaga Magdalena</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= ASSETS_URL ?>/css/custom.css" rel="stylesheet">
</head>
<body class="login-body" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.72), rgba(30, 58, 138, 0.60)), url('<?= ASSETS_URL ?>/assets/school_bg.jpg') no-repeat center center / cover;">

    <div class="login-glass-card text-center" style="max-width: 480px; margin: 2rem auto;">
        <!-- Logo Institucional Oficial -->
        <div class="mb-3">
            <img src="<?= ASSETS_URL ?>/assets/logo.svg" alt="Instituto La Salle Ciénaga Magdalena" class="school-logo-img">
        </div>

        <!-- Título e Identidad Institucional -->
        <h2 class="h3 fw-bold text-dark mb-1">Plataforma Académica La Salle</h2>
        <p class="text-muted small mb-2">Administración de Usuarios • Registro de Notas • Boletines</p>
        
        <div class="mb-4">
            <span class="motto-badge">"Dios, Ciencia y Virtud"</span>
        </div>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show text-start py-2 px-3 small" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de Inicio de Sesión -->
        <form action="<?= APP_URL ?>/app/Controllers/AuthController.php?action=login" method="POST" class="text-start">
            <div class="mb-3">
                <label for="correo" class="form-label font-heading text-secondary small fw-semibold">Correo Institucional</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control border-start-0 ps-0" id="correo" name="correo" placeholder="ejemplo@lasallecienaga.edu.co" value="" required>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password" class="form-label font-heading text-secondary small fw-semibold">Contraseña</label>
                    <a href="#" class="small text-decoration-none text-primary fw-medium" onclick="alert('Consulte con la secretaría o administración del colegio para restablecer sus credenciales.'); return false;">¿Olvidó su clave?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="••••••••" value="" required>
                </div>
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="remember" checked>
                <label class="form-check-input-label text-muted small" for="remember">Mantener sesión iniciada</label>
            </div>

            <button type="submit" class="btn btn-lasalle-primary w-100 py-2.5 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i> Ingresar a la Plataforma
            </button>

            <!-- Cargar Credenciales Demo por Rol -->
            <div class="border-top pt-3 mt-3">
                <p class="text-muted text-center extra-small mb-2 fw-semibold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                    <i class="fas fa-bolt text-warning me-1"></i> Seleccionar Rol para Cargar Credenciales
                </p>
                <div class="d-flex flex-wrap gap-2 justify-content-center mb-2">
                    <button type="button" onclick="fillCreds('admin@lasallecienaga.edu.co', 'Salle2026*')" class="btn btn-sm btn-outline-primary py-1 px-3" style="font-size: 0.8rem;">
                        <i class="fas fa-user-shield me-1"></i> Administrador
                    </button>
                    <button type="button" onclick="fillCreds('docente.garcia@lasallecienaga.edu.co', 'Salle2026*')" class="btn btn-sm btn-outline-success py-1 px-3" style="font-size: 0.8rem;">
                        <i class="fas fa-chalkboard-teacher me-1"></i> Docente (Calificar)
                    </button>
                    <button type="button" onclick="fillCreds('estudiante.martinez@lasallecienaga.edu.co', 'Salle2026*')" class="btn btn-sm btn-outline-secondary py-1 px-3" style="font-size: 0.8rem;">
                        <i class="fas fa-user-graduate me-1"></i> Estudiante (Boletines)
                    </button>
                </div>
            </div>
        </form>
        
        <footer class="mt-4 pt-2 text-center border-top border-light">
            <span class="text-muted extra-small" style="font-size: 0.7rem;">&copy; <?= date('Y') ?> Instituto La Salle Ciénaga Magdalena • Todos los derechos reservados.</span>
        </footer>
    </div>

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= ASSETS_URL ?>/js/app.js"></script>
    <script>
    function fillCreds(email, pass) {
        document.getElementById('correo').value = email;
        document.getElementById('password').value = pass;
    }
    </script>
</body>
</html>