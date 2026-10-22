<?php
/**
 * ESTABLECER CONTRASEÑA PERSONAL EN PRIMER INGRESO
 * Instituto La Salle Ciénaga Magdalena
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../app/Controllers/Router.php';
Router::initSession();

// Validar que el usuario esté autenticado y deba cambiar la contraseña
if (!isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "/views/auth/login.php");
    exit();
}

// Si ya no debe cambiarla, redirigir a su panel principal
if (empty($_SESSION['must_change_password'])) {
    header("Location: " . Router::getHomeForRole($_SESSION['user_role'] ?? 'estudiante'));
    exit();
}

$errorMsg = null;
$userId = $_SESSION['user_id'];
$userRole = strtolower($_SESSION['user_role'] ?? 'estudiante');
$userName = $_SESSION['user_name'] ?? ($userRole === 'estudiante' ? 'Estudiante' : 'Docente');
$userEmail = $_SESSION['user_email'] ?? '';

// Procesar formulario de cambio de clave
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevaPass = trim($_POST['nueva_password'] ?? '');
    $confirmPass = trim($_POST['confirmar_password'] ?? '');

    if (empty($nuevaPass) || empty($confirmPass)) {
        $errorMsg = "Por favor complete ambos campos de contraseña.";
    } elseif (strlen($nuevaPass) < 6) {
        $errorMsg = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($nuevaPass !== $confirmPass) {
        $errorMsg = "Las contraseñas no coinciden. Por favor verifíquelas.";
    } elseif ($nuevaPass === 'Salle2026*') {
        $errorMsg = "La nueva contraseña no puede ser la misma provisional ('Salle2026*'). Por favor elija una contraseña personal propia.";
    } else {
        // Actualizar en base de datos
        $db = Database::getInstance();
        $pdo = $db->getConnection();
        if ($pdo) {
            try {
                $hash = password_hash($nuevaPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = :hash, debe_cambiar_password = 0, actualizado_en = NOW() WHERE id = :id");
                $stmt->execute([':hash' => $hash, ':id' => $userId]);
            } catch (Exception $e) {
                error_log("Error actualizando contraseña usuario: " . $e->getMessage());
            }
        }

        // Actualizar en demo_users si aplica
        if (isset($_SESSION['demo_users']) && is_array($_SESSION['demo_users'])) {
            foreach ($_SESSION['demo_users'] as &$du) {
                if ($du['id'] == $userId) {
                    $du['password'] = $nuevaPass;
                    $du['password_hash'] = password_hash($nuevaPass, PASSWORD_BCRYPT);
                    $du['debe_cambiar_password'] = 0;
                    break;
                }
            }
        }

        // Liberar flag de cambio de clave
        unset($_SESSION['must_change_password']);
        $_SESSION['flash_success'] = ($userRole === 'estudiante') 
            ? "¡Contraseña establecida con éxito! Bienvenido(a) a tu portal estudiantil." 
            : "¡Contraseña actualizada con éxito! Bienvenido(a) a su portal institucional.";

        // Redirigir al dashboard según el rol del usuario
        $dest = Router::getHomeForRole($_SESSION['user_role'] ?? 'estudiante');
        header("Location: " . $dest);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Contraseña - Instituto La Salle Ciénaga Magdalena</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= ASSETS_URL ?>/css/custom.css" rel="stylesheet">
    <style>
        .change-pass-card {
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 1.25rem;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            max-width: 500px;
            margin: 2.5rem auto;
            padding: 2.5rem 2.25rem;
        }
        .btn-toggle-eye {
            border-left: none;
            background: #fff;
            color: #64748b;
            cursor: pointer;
        }
        .btn-toggle-eye:hover {
            color: #0f172a;
        }
    </style>
</head>
<body class="login-body" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.82), rgba(30, 58, 138, 0.75)), url('<?= ASSETS_URL ?>/assets/school_bg.jpg') no-repeat center center / cover; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">

    <div class="change-pass-card text-center">
        <!-- Logo Institucional Oficial -->
        <div class="mb-3">
            <img src="<?= ASSETS_URL ?>/assets/logo.svg" alt="Instituto La Salle Ciénaga Magdalena" class="school-logo-img" style="height: 70px;">
        </div>

        <!-- Título e Identidad Institucional -->
        <h3 class="h4 fw-bold text-dark mb-1 font-heading">Establecer Nueva Contraseña</h3>
        <p class="text-muted small mb-2">Instituto La Salle – Ciénaga Magdalena</p>
        
        <div class="mb-3">
            <span class="motto-badge">"Dios, Ciencia y Virtud"</span>
        </div>

        <!-- Mensaje Informativo de Bienvenida -->
        <div class="alert alert-info text-start py-2 px-3 small rounded-3 border-0 bg-info-subtle text-info-emphasis mb-3 shadow-sm">
            <div class="d-flex align-items-center mb-1">
                <i class="fas <?= ($userRole === 'estudiante' ? 'fa-user-graduate' : 'fa-chalkboard-teacher') ?> fs-5 me-2 text-info"></i>
                <div class="d-flex align-items-center flex-wrap gap-1">
                    <strong>¡Bienvenido(a), <?= htmlspecialchars($userName) ?>!</strong>
                    <span class="badge <?= ($userRole === 'estudiante' ? 'bg-primary' : 'bg-success') ?>" style="font-size: 0.68rem;">
                        <?= ($userRole === 'estudiante' ? 'Estudiante' : 'Docente') ?>
                    </span>
                </div>
            </div>
            <div style="font-size: 0.83rem;">
                <?php if ($userRole === 'estudiante'): ?>
                    Su cuenta fue creada con la contraseña provisional por defecto. Por favor asigne la <strong>contraseña personal que usted desee</strong> para acceder a su portal estudiantil de notas, boletines y tareas.
                <?php else: ?>
                    Su cuenta fue creada con la contraseña provisional por defecto. Por favor asigne la <strong>contraseña personal que usted desee</strong> para acceder a su portal.
                <?php endif; ?>
            </div>
        </div>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show text-start py-2 px-3 small rounded-3" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($errorMsg) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario de Cambio de Clave -->
        <form method="POST" id="formCambioClave" onsubmit="return validatePasswords(event);" class="text-start">
            <div class="mb-3">
                <label for="nueva_password" class="form-label font-heading text-secondary small fw-semibold">
                    Nueva Contraseña <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="nueva_password" name="nueva_password" placeholder="Mínimo 6 caracteres" required minlength="6" onkeyup="checkPasswordMatch()">
                    <button class="input-group-text btn-toggle-eye" type="button" onclick="togglePass('nueva_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">
                    <i class="fas fa-info-circle me-1"></i> Puede usar letras, números o símbolos que recuerde con facilidad.
                </small>
            </div>

            <div class="mb-3">
                <label for="confirmar_password" class="form-label font-heading text-secondary small fw-semibold">
                    Confirmar Nueva Contraseña <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-check-double"></i></span>
                    <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="confirmar_password" name="confirmar_password" placeholder="Repita la contraseña" required minlength="6" onkeyup="checkPasswordMatch()">
                    <button class="input-group-text btn-toggle-eye" type="button" onclick="togglePass('confirmar_password', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="matchFeedback" class="small mt-1" style="font-size: 0.78rem;"></div>
            </div>

            <button type="submit" class="btn btn-lasalle-primary w-100 py-2.5 mb-3 fw-bold shadow-sm">
                <i class="fas fa-save me-2"></i> Guardar Contraseña e Ingresar
            </button>

            <div class="text-center pt-2 border-top">
                <a href="<?= APP_URL ?>/app/Controllers/AuthController.php?action=logout" class="text-muted text-decoration-none small">
                    <i class="fas fa-arrow-left me-1"></i> Cancelar y Cerrar Sesión
                </a>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordMatch() {
            const p1 = document.getElementById('nueva_password').value;
            const p2 = document.getElementById('confirmar_password').value;
            const feedback = document.getElementById('matchFeedback');

            if (!p2) {
                feedback.innerHTML = '';
                return;
            }

            if (p1 === p2) {
                feedback.className = 'small mt-1 text-success fw-semibold';
                feedback.innerHTML = '<i class="fas fa-check-circle me-1"></i> Las contraseñas coinciden correctamente.';
            } else {
                feedback.className = 'small mt-1 text-danger fw-semibold';
                feedback.innerHTML = '<i class="fas fa-times-circle me-1"></i> Las contraseñas aún no coinciden.';
            }
        }

        function validatePasswords(e) {
            const p1 = document.getElementById('nueva_password').value.trim();
            const p2 = document.getElementById('confirmar_password').value.trim();

            if (p1.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres.');
                e.preventDefault();
                return false;
            }
            if (p1 !== p2) {
                alert('Las contraseñas no coinciden.');
                e.preventDefault();
                return false;
            }
            if (p1 === 'Salle2026*') {
                alert('Debe elegir una contraseña personal diferente a la provisional "Salle2026*".');
                e.preventDefault();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
