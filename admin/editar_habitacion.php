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

$id_habitacion = Crypto::decrypt($_GET['id_habitacion']);
if (empty($id_habitacion) || !is_numeric($id_habitacion) || $id_habitacion <= 0) {
    header("Location: gestion_habitaciones.php");
    exit();
}

// Obtener datos de la habitación para editar
try {
    $sql = $conexion->prepare("SELECT numero,precio,tipo,capacidad FROM habitaciones WHERE id = :id_habitacion");
    $sql->bindParam(':id_habitacion', $id_habitacion, PDO::PARAM_INT);
    $sql->execute();
    $habitacion = $sql->fetch(PDO::FETCH_OBJ);

    if (empty($habitacion)) {
        header("Location: gestion_habitaciones.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error al obtener datos de la habitación: " . $e->getMessage());
    header("Location: gestion_habitaciones.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/editar_habitacion.css">
<div class="bg-pattern"></div>

<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-edit"></i>
            </div>
            <h2>Editar Habitación</h2>
            <p>Modifique la información de la habitación en el sistema</p>
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

            <form id="roomForm" action="../controladores/admin/editar_habitacion.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_habitacion" value="<?php echo htmlspecialchars(Crypto::encrypt($id_habitacion)); ?>">

                <!-- Número de habitación -->
                <div class="input-group">
                    <label><i class="fas fa-hashtag"></i> Número de Habitación <span class="required-star">*</span></label>
                    <input type="text" id="roomNumber" name="numero" class="input-field" placeholder="Ej: 101, 202, 305" value="<?php echo htmlspecialchars($habitacion->numero); ?>" autocomplete="off">
                    <div class="error-message" id="numberError"></div>
                </div>

                <!-- Precio -->
                <div class="input-group">
                    <label><i class="fas fa-dollar-sign"></i> Precio por Noche (USD) <span class="required-star">*</span></label>
                    <input type="number" id="roomPrice" name="precio" class="input-field" placeholder="Ej: 120" value="<?php echo htmlspecialchars((int)$habitacion->precio); ?>" step="1" min="0">
                    <div class="error-message" id="priceError"></div>
                </div>

                <!-- Tipo de habitación (Select) -->
                <div class="input-group">
                    <label><i class="fas fa-tag"></i> Tipo de Habitación <span class="required-star">*</span></label>
                    <select id="roomType" name="tipo" class="input-field">
                        <option value="simple" <?php echo $habitacion->tipo == 'simple' ? 'selected' : ''; ?>>Simple</option>
                        <option value="doble" <?php echo $habitacion->tipo == 'doble' ? 'selected' : ''; ?>>Doble</option>
                        <option value="matrimonial" <?php echo $habitacion->tipo == 'matrimonial' ? 'selected' : ''; ?>>Matrimonial</option>
                        <option value="suite" <?php echo $habitacion->tipo == 'suite' ? 'selected' : ''; ?>>Suite</option>
                        <option value="presidencial" <?php echo $habitacion->tipo == 'presidencial' ? 'selected' : ''; ?>>Presidencial</option>
                    </select>
                    <div class="error-message" id="typeError"></div>
                </div>

                <!-- Capacidad -->
                <div class="input-group">
                    <label><i class="fas fa-users"></i> Capacidad (personas) <span class="required-star">*</span></label>
                    <input type="number" id="roomCapacity" name="capacidad" class="input-field" placeholder="Ej: 2, 3, 4" value="<?php echo htmlspecialchars($habitacion->capacidad); ?>" step="1" min="1">
                    <div class="error-message" id="capacityError"></div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <a href="gestion_habitaciones.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/editar_habitacion.js"></script>
<?php include_once '../templates/footer.php'; ?>