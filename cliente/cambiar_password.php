<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/cliente/cambiar_password.css">

<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-key"></i>
            </div>
            <h2>Cambiar Contraseña</h2>
            <p>Actualiza tu contraseña para mantener tu cuenta segura</p>
        </div>

        <div class="card-body">
            <div class="info-note">
                <i class="fas fa-shield-alt"></i>
                <span>Recomendamos usar una contraseña segura que no uses en otros sitios.</span>
            </div>

            <?php if (isset($_SESSION['errores'])) : ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($_SESSION['errores'] as $error) : ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errores']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['exito'])) : ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
                </div>
                <?php unset($_SESSION['exito']); ?>
            <?php endif; ?>

            <form id="passwordForm" action="../controladores/cliente/cambiar_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Contraseña actual -->
                <div class="input-group">
                    <label><i class="fas fa-lock"></i> Contraseña actual <span class="required-star">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="currentPassword" name="password_actual" class="input-field" placeholder="Ingresa tu contraseña actual">
                        <i class="far fa-eye toggle-password" data-target="currentPassword"></i>
                    </div>
                </div>

                <!-- Contraseña nueva -->
                <div class="input-group">
                    <label><i class="fas fa-lock"></i> Contraseña nueva <span class="required-star">*</span></label>
                    <div class="password-wrapper">
                        <input type="password" id="newPassword" name="password_nueva" class="input-field" placeholder="Mínimo 6 caracteres">
                        <i class="far fa-eye toggle-password" data-target="newPassword"></i>
                    </div>
                </div>

                <!-- Requisitos de contraseña -->
                <div class="password-requirements" id="passwordReqs">
                    <p><i class="fas fa-check-circle"></i> La contraseña debe cumplir:</p>
                    <ul>
                        <li id="req-length"><i class="fas fa-circle"></i> Mínimo 5 caracteres</li>
                        <li id="req-upper"><i class="fas fa-circle"></i> Al menos una letra mayúscula</li>
                        <li id="req-lower"><i class="fas fa-circle"></i> Al menos una letra minúscula</li>
                        <li id="req-number"><i class="fas fa-circle"></i> Al menos un número</li>
                    </ul>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Cambiar Contraseña
                </button>

                <a href="dash_cliente.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/cliente/cambiar_password.js"></script>
<?php include_once '../templates/footer.php'; ?>