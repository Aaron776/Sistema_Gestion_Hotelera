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

$id_cliente = $_SESSION['id_usuario']; // id del cliente logueado

// Obtener datos actuales del cliente
try {
    $sql = $conexion->prepare("SELECT nombre, email, telefono, direccion, cedula FROM usuarios WHERE id = :id_cliente AND rol = 'cliente'");
    $sql->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_OBJ);

    if (!$usuario) {
        header("Location: ../index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener datos del cliente: " . $e->getMessage());
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/cliente/configuracion_cuenta.css">
<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-user-edit"></i>
            </div>
            <h2>Configuración de Cuenta</h2>
            <p>Mantenga su información actualizada para garantizar una mejor experiencia en sus futuras estancias.</p>
        </div>

        <div class="card-body">
            <div class="info-note">
                <i class="fas fa-info-circle"></i>
                <span>Los cambios realizados se aplicarán inmediatamente a su perfil.</span>
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

            <form id="clienteForm" action="../controladores/cliente/configuracion_cuenta.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="input-group">
                    <label><i class="fas fa-user"></i> Nombre completo <span class="required-star">*</span></label>
                    <input type="text" id="nombre" name="nombre" class="input-field" value="<?= htmlspecialchars($usuario->nombre) ?>" placeholder="Ej: María Fernanda González López" autocomplete="name" required>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-id-card"></i> Cédula / Identificación <span class="required-star">*</span></label>
                    <input type="text" id="cedula" name="cedula" class="input-field" value="<?= htmlspecialchars($usuario->cedula) ?>" placeholder="Ej: 12345678-9" autocomplete="off" required>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-envelope"></i> Correo electrónico <span class="required-star">*</span></label>
                    <input type="email" id="email" name="email" class="input-field" value="<?= htmlspecialchars($usuario->email) ?>" placeholder="usuario@hotelhorizon.com" autocomplete="email" required>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-phone-alt"></i> Teléfono / Celular <span class="required-star">*</span></label>
                    <input type="tel" id="telefono" name="telefono" class="input-field" value="<?= htmlspecialchars($usuario->telefono) ?>" placeholder="Ej: +34 612345678" autocomplete="tel" required>
                </div>

                <div class="input-group">
                    <label><i class="fas fa-map-marker-alt"></i> Dirección completa <span class="required-star">*</span></label>
                    <input type="text" id="direccion" name="direccion" class="input-field" value="<?= htmlspecialchars($usuario->direccion) ?>" placeholder="Calle, número, ciudad, código postal" autocomplete="street-address" required>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <a href="dash_cliente.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver al Panel
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/cliente/configuracion_cuenta.js"></script>
<?php include_once '../templates/footer.php'; ?>