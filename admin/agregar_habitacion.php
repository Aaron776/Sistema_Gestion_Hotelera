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
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/agregar_habitacion.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-bed"></i>
            </div>
            <h2>Agregar Nueva Habitación</h2>
            <p>Complete el formulario para registrar una nueva habitación en el sistema</p>
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
            <form id="roomForm" action="../controladores/admin/agregar_habitacion.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="input-group">
                    <label><i class="fas fa-hashtag"></i> Número de Habitación <span class="required-star">*</span></label>
                    <input type="text" id="roomNumber" name="numero" class="input-field" placeholder="Ej: 101, 202, 305" autocomplete="off">
                </div>

                <!-- Precio -->
                <div class="input-group">
                    <label><i class="fas fa-dollar-sign"></i> Precio por Noche (USD) <span class="required-star">*</span></label>
                    <input type="number" id="roomPrice" name="precio" class="input-field" placeholder="Ej: 120" step="1" min="0">
                </div>

                <!-- Tipo de habitación (Select) -->
                <div class="input-group">
                    <label><i class="fas fa-tag"></i> Tipo de Habitación <span class="required-star">*</span></label>
                    <select id="roomType" name="tipo" class="input-field">
                        <option value="simple">Simple</option>
                        <option value="doble">Doble</option>
                        <option value="matrimonial">Matrimonial</option>
                        <option value="suite">Suite</option>
                        <option value="presidencial">Presidencial</option>
                    </select>
                </div>

                <!-- Capacidad -->
                <div class="input-group">
                    <label><i class="fas fa-users"></i> Capacidad (personas) <span class="required-star">*</span></label>
                    <input type="number" id="roomCapacity" name="capacidad" class="input-field" placeholder="Ej: 2, 3, 4" step="1" min="1">
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Registrar Habitación
                </button>

                <a href="gestion_habitaciones.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>

<script src="<?= $base_url ?>app/js/admin/agregar_habitacion.js"></script>
<?php include_once '../templates/footer.php'; ?>