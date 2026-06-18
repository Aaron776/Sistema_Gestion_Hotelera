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

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/registrar_mantenimiento_habitacion.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-tools"></i>
            </div>
            <h2>Registrar Mantenimiento</h2>
            <p>Ingrese los datos del mantenimiento para una habitación</p>
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
            <form id="maintenanceForm" action="../controladores/admin/registrar_mantenimiento_habitacion.php" method="POST">
                <input type="hidden" name="id_habitacion" id="id_habitacion" value="<?php echo htmlspecialchars(Crypto::encrypt($id_habitacion)); ?>">
                <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <!-- Motivo -->
                <div class="input-group">
                    <label><i class="fas fa-clipboard-list"></i> Motivo del mantenimiento <span class="required-star">*</span></label>
                    <input type="text" id="motivo" name="motivo" class="input-field" placeholder="Ej: Fuga de agua, Aire acondicionado dañado, Pintura">
                </div>

                <!-- Fecha de inicio -->
                <div class="input-group">
                    <label><i class="fas fa-calendar-alt"></i> Fecha de inicio <span class="required-star">*</span></label>
                    <input type="date" id="fechaInicio" name="fecha_inicio" class="input-field" placeholder="DD/MM/YYYY" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Fecha final aproximada -->
                <div class="input-group">
                    <label><i class="fas fa-calendar-check"></i> Fecha final aproximada <span class="required-star">*</span></label>
                    <input type="date" id="fechaFinal" name="fecha_fin_estimada" class="input-field" placeholder="DD/MM/YYYY">
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Registrar Mantenimiento
                </button>

                <a href="gestion_mantenimientos_habitacion.php?id_habitacion=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_habitacion))); ?>" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/registrar_mantenimiento_habitacion.js"></script>
<?php include_once '../templates/footer.php'; ?>