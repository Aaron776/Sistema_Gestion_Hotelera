<?php
require_once '../autorizacion/auth.php';
require_once '../helpers/Encriptar.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/agregar_servicio.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-concierge-bell"></i>
            </div>
            <h2>Registrar Nuevo Servicio</h2>
            <p>Ingrese los datos del servicio adicional que ofrece el hotel</p>
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
            <form id="serviceForm" action="../controladores/admin/agregar_servicio.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <!-- Nombre del servicio -->
                <div class="input-group">
                    <label><i class="fas fa-tag"></i> Nombre del Servicio <span class="required-star">*</span></label>
                    <input type="text" id="serviceName" name="nombre" class="input-field" placeholder="Ej: Spa y Masajes, Restaurante, Lavandería" autocomplete="off">
                </div>

                <!-- Precio -->
                <div class="input-group">
                    <label><i class="fas fa-dollar-sign"></i> Precio (<?php echo htmlspecialchars($info_hotel->moneda); ?>) <span class="required-star">*</span></label>
                    <input type="number" step="0.01" id="servicePrice" name="precio" class="input-field" placeholder="Ej: 85.00" autocomplete="off">
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Registrar Servicio
                </button>

                <a href="gestion_servicios.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/agregar_servicio.js"></script>
<?php include_once '../templates/footer.php'; ?>