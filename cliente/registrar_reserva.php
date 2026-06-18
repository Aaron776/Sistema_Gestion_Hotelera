<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_cliente = $_SESSION['id_usuario']; // cliente logueado


include_once '../templates/header.php';

// Obtener listado de habitacion en estado disponible
try {
    $sql = $conexion->prepare("SELECT id as id_habitacion,numero,tipo,precio,capacidad FROM habitaciones WHERE estado='disponible'");
    $sql->execute();
    $habitaciones = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las habitaciones: " . $e->getMessage());
    $habitaciones = [];
}
?>
<link rel="stylesheet" href="../app/css/cliente/registrar_reserva.css">
<div class="content-wrapper">
    <div class="form-container">
        <div class="form-card">
            <div class="card-header">
                <div class="logo-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h2>Nueva Reserva</h2>
                <p>Complete los datos para realizar su reserva</p>
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
                <form id="reservaForm" action="../controladores/cliente/registrar_reserva.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="input-group">
                        <label><i class="fas fa-bed"></i> Habitación <span class="required-star">*</span></label>
                        <select id="habitacion" name="id_habitacion" class="select-field" required>
                            <option value="">Seleccione una habitación</option>
                            <?php foreach ($habitaciones as $item) { ?>
                                <option value="<?= $item->id_habitacion ?>" data-numero="<?= $item->numero ?>" data-tipo="<?= $item->tipo ?>" data-capacidad="<?= $item->capacidad ?>" data-precio="<?= $item->precio ?>"><?= $item->numero ?> - <?= $item->tipo ?> - $<?= $item->precio ?>/noche</option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <!-- Fecha de inicio -->
                        <div class="input-group">
                            <label><i class="fas fa-calendar-alt"></i> Fecha de inicio <span class="required-star">*</span></label>
                            <input type="text" id="fechaInicio" name="fecha_inicio" value="<?php echo date('Y-m-d'); ?>" class="input-field" placeholder="DD/MM/YYYY">
                        </div>

                        <!-- Fecha de fin -->
                        <div class="input-group">
                            <label><i class="fas fa-calendar-check"></i> Fecha de fin <span class="required-star">*</span></label>
                            <input type="text" id="fechaFin" name="fecha_fin" class="input-field" placeholder="DD/MM/YYYY">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label><i class="fas fa-moon"></i> Cantidad de noches</label>
                            <input type="number" id="noches" name="noches" class="input-field">
                        </div>

                        <!-- Precio total (calculado automáticamente) -->
                        <div class="input-group">
                            <label><i class="fas fa-dollar-sign"></i> Precio total (<?php echo htmlspecialchars($info_hotel->moneda); ?>)</label>
                            <input type="text" id="precioTotal" name="total" class="input-field readonly-field" readonly placeholder="Se calculará automáticamente">
                        </div>
                    </div>

                    <!-- Resumen visual -->
                    <div class="summary-box" id="summaryBox" style="display: none;">
                        <h4><i class="fas fa-receipt"></i> Resumen de la reserva</h4>
                        <div class="summary-details">
                            <div class="summary-item">
                                <div class="label">Precio por noche</div>
                                <div class="value" id="precioNocheDisplay">$0</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Noches</div>
                                <div class="value" id="nochesDisplay">0</div>
                            </div>
                            <div class="summary-item">
                                <div class="label">Total</div>
                                <div class="value" id="totalDisplay">$0</div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">
                        <i class="fas fa-save"></i> Confirmar Reserva
                    </button>

                    <a href="gestion_reservas.php" class="secondary-btn">
                        <i class="fas fa-arrow-left"></i> Cancelar y Volver
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/cliente/registrar_reserva.js"></script>
<?php include_once '../templates/footer.php'; ?>