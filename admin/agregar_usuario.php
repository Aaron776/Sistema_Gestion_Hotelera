<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/agregar_usuario.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Agregar Nuevo Usuario</h2>
            <p>Complete el formulario para registrar un nuevo usuario en el sistema</p>
        </div>

        <div class="card-body">
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
            <form id="userForm" action="../controladores/admin/agregar_usuario.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="form-grid">
                    <!-- Nombre Completo -->
                    <div class="input-group full-width">
                        <label><i class="fas fa-user"></i> Nombre Completo <span class="required-star">*</span></label>
                        <input type="text" id="nombre" name="nombre" class="input-field" placeholder="Ej: María Fernanda López" autocomplete="name">
                    </div>

                    <!-- Cédula -->
                    <div class="input-group">
                        <label><i class="fas fa-id-card"></i> Cédula / Identificación <span class="required-star">*</span></label>
                        <input type="text" id="cedula" name="cedula" class="input-field" placeholder="Ej: 12345678-9">
                    </div>

                    <!-- Teléfono -->
                    <div class="input-group">
                        <label><i class="fas fa-phone-alt"></i> Teléfono / Celular <span class="required-star">*</span></label>
                        <input type="tel" id="telefono" name="telefono" class="input-field" placeholder="Ej: +34 612345678">
                    </div>

                    <!-- Email -->
                    <div class="input-group full-width">
                        <label><i class="fas fa-envelope"></i> Correo electrónico <span class="required-star">*</span></label>
                        <input type="email" id="email" name="email" class="input-field" placeholder="usuario@hotelhorizon.com">
                    </div>


                    <!-- Dirección -->
                    <div class="input-group full-width">
                        <label><i class="fas fa-map-marker-alt"></i> Dirección completa <span class="required-star">*</span></label>
                        <input type="text" id="direccion" name="direccion" class="input-field" placeholder="Calle, número, ciudad, código postal">
                    </div>

                    <!-- Contraseña -->
                    <div class="input-group">
                        <label><i class="fas fa-lock"></i> Contraseña <span class="required-star">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" class="input-field" placeholder="Mínimo 6 caracteres">
                            <i class="far fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="input-group">
                        <label><i class="fas fa-lock"></i> Confirmar Contraseña <span class="required-star">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" id="confirmPassword" name="confirmar_password" class="input-field" placeholder="Repite tu contraseña">
                            <i class="far fa-eye toggle-password" id="toggleConfirmPassword"></i>
                        </div>
                    </div>

                    <!-- Rol (Select: Administrador / Recepcionista) -->
                    <div class="input-group full-width">
                        <label><i class="fas fa-user-tag"></i> Rol <span class="required-star">*</span></label>
                        <select id="rol" class="input-field" name="rol">
                            <option value="recepcionista">Recepcionista</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Registrar Usuario
                </button>

                <a href="gestion_usuarios.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/agregar_usuario.js"></script>
<?php include_once '../templates/footer.php'; ?>