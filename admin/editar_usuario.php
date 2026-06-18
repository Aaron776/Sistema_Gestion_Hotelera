<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_usuario = Crypto::decrypt($_GET['id_usuario']);
if (empty($id_usuario) || !is_numeric($id_usuario) || $id_usuario <= 0) {
    header("Location: gestion_usuarios.php");
    exit();
}

// Obtener datos del usuario para editar
try {
    $sql = $conexion->prepare("SELECT nombre,email,telefono,direccion,rol,cedula FROM usuarios WHERE id = :id_usuario");
    $sql->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
    $sql->execute();
    $usuario = $sql->fetch(PDO::FETCH_OBJ);

    if (empty($usuario)) {
        header("Location: gestion_usuarios.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error al obtener datos del usuario: " . $e->getMessage());
    header("Location: gestion_usuarios.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/editar_usuario.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-user-edit"></i>
            </div>
            <h2>Editar Usuario</h2>
            <p>Modifique la información del usuario en el sistema</p>
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
            <form id="editUserForm" action="../controladores/admin/editar_usuario.php" method="POST">
                <input type="hidden" id="userId" name="id_usuario" value="<?php echo htmlspecialchars(Crypto::encrypt($id_usuario)); ?>">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-grid">
                    <!-- Nombre Completo -->
                    <div class="input-group full-width">
                        <label><i class="fas fa-user"></i> Nombre Completo <span class="required-star">*</span></label>
                        <input type="text" id="nombre" name="nombre" class="input-field" placeholder="Ej: María Fernanda López" value="<?php echo htmlspecialchars($usuario->nombre); ?>">
                    </div>

                    <!-- Cédula -->
                    <div class="input-group">
                        <label><i class="fas fa-id-card"></i> Cédula / Identificación <span class="required-star">*</span></label>
                        <input type="text" id="cedula" name="cedula" class="input-field" placeholder="Ej: 12345678-9" value="<?php echo htmlspecialchars($usuario->cedula); ?>">
                    </div>

                    <!-- Teléfono -->
                    <div class="input-group">
                        <label><i class="fas fa-phone-alt"></i> Teléfono / Celular <span class="required-star">*</span></label>
                        <input type="tel" id="telefono" name="telefono" class="input-field" placeholder="Ej: +34 612345678" value="<?php echo htmlspecialchars($usuario->telefono); ?>">
                    </div>

                    <!-- Email (sin campo de confirmación) -->
                    <div class="input-group">
                        <label><i class="fas fa-envelope"></i> Correo electrónico <span class="required-star">*</span></label>
                        <input type="email" id="email" name="email" class="input-field" placeholder="usuario@hotelhorizon.com" value="<?php echo htmlspecialchars($usuario->email); ?>">
                    </div>

                    <!-- Dirección (ocupa 2 columnas) -->
                    <div class="input-group full-width">
                        <label><i class="fas fa-map-marker-alt"></i> Dirección completa <span class="required-star">*</span></label>
                        <input type="text" id="direccion" name="direccion" class="input-field" placeholder="Calle, número, ciudad, código postal" value="<?php echo htmlspecialchars($usuario->direccion); ?>">
                    </div>

                    <!-- Rol (Select: Administrador / Recepcionista) -->
                    <div class="input-group full-width">
                        <label><i class="fas fa-user-tag"></i> Rol <span class="required-star">*</span></label>
                        <select id="rol" name="rol" class="input-field">
                            <option value="recepcionista" <?php echo $usuario->rol == 'recepcionista' ? 'selected' : ''; ?>>Recepcionista</option>
                            <option value="admin" <?php echo $usuario->rol == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <a href="gestion_usuarios.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/editar_usuario.js"></script>
<?php include_once '../templates/footer.php'; ?>