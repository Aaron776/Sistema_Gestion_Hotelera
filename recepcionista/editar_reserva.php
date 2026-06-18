<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_reserva = Crypto::decrypt($_GET['id_reserva']);
if (empty($id_reserva) || $id_reserva <= 0 || !is_numeric($id_reserva)) {
    header("Location: gestion_reservas.php");
    exit();
}

include_once '../templates/header.php';

$id_recepcionista = $_SESSION['id_usuario']; // id del recepcionista logueado

// Obtener datos de la reserva que esta en estado confirmada y que le pertenezca a ese recepcionista
try {
    $sql = $conexion->prepare("SELECT r.id, r.fecha_inicio, r.fecha_fin, r.total, rh.habitacion_id as habitacion_id,r.cliente_id as cliente_id FROM reservas r INNER JOIN reserva_habitacion rh ON r.id=rh.reserva_id WHERE r.id = :id_reserva AND r.empleado_id = :id_recepcionista AND r.estado='confirmada'");
    $sql->bindParam(':id_recepcionista', $id_recepcionista, PDO::PARAM_INT);
    $sql->bindParam(':id_reserva', $id_reserva, PDO::PARAM_INT);
    $sql->execute();
    $reserva = $sql->fetch(PDO::FETCH_OBJ);
    if (!$reserva) {
        header("Location: gestion_reservas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener la reserva: " . $e->getMessage());
    $reserva = [];
    header("Location: gestion_reservas.php");
    exit();
}

// Obtener listado de habitaciones disponibles (incluyendo la actual de la reserva)
try {
    $sql = $conexion->prepare("SELECT id as id_habitacion, numero, tipo, precio, capacidad FROM habitaciones WHERE estado='disponible' OR id = :id_hab_actual");
    $sql->bindParam(':id_hab_actual', $reserva->habitacion_id, PDO::PARAM_INT);
    $sql->execute();
    $habitaciones = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener las habitaciones: " . $e->getMessage());
    $habitaciones = [];
}

// Obtener listado de usuario con rol cliente en estado activo
try {
    $sql = $conexion->prepare("SELECT id as id_cliente, nombre,cedula FROM usuarios WHERE rol='cliente' AND estado='activo'");
    $sql->execute();
    $clientes = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los clientes: " . $e->getMessage());
    $clientes = [];
}
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/recepcionista/editar_reserva.css">

<div class="content-wrapper">
    <div class="form-container">
        <div class="form-card">
            <div class="card-header">
                <div class="logo-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h2>Modificar Reserva</h2>
                <p>Reserva #<?= $id_reserva ?> - Solo puede editar reservas pendientes</p>
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

                <form id="reservaForm" action="../controladores/recepcionista/editar_reserva.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id_reserva" value="<?= Crypto::encrypt($id_reserva) ?>">

                    <div class="input-group">
                        <label><i class="fas fa-bed"></i> Cliente <span class="required-star">*</span></label>
                        <select id="cliente" name="id_cliente" class="select-field" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $item) { ?>
                                <option value="<?= $item->id_cliente ?>" data-nombre="<?= $item->nombre ?>" data-cedula="<?= $item->cedula ?>" <?= $reserva->cliente_id == $item->id_cliente ? 'selected' : '' ?>><?= $item->nombre ?> - <?= $item->cedula ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-bed"></i> Habitación <span class="required-star">*</span></label>
                        <select id="habitacion" name="id_habitacion" class="select-field" required>
                            <option value="">Seleccione una habitación</option>
                            <?php foreach ($habitaciones as $item) { ?>
                                <option value="<?= $item->id_habitacion ?>"
                                    data-precio="<?= $item->precio ?>"
                                    <?= ($item->id_habitacion == $reserva->habitacion_id) ? 'selected' : '' ?>>
                                    <?= $item->numero ?> - <?= $item->tipo ?> - $<?= $item->precio ?>/noche
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label><i class="fas fa-calendar-alt"></i> Fecha de inicio <span class="required-star">*</span></label>
                            <input type="text" id="fechaInicio" name="fecha_inicio" value="<?= $reserva->fecha_inicio ?>" class="input-field" placeholder="YYYY-MM-DD">
                        </div>

                        <div class="input-group">
                            <label><i class="fas fa-calendar-check"></i> Fecha de fin <span class="required-star">*</span></label>
                            <input type="text" id="fechaFin" name="fecha_fin" value="<?= $reserva->fecha_fin ?>" class="input-field" placeholder="YYYY-MM-DD">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group">
                            <label><i class="fas fa-moon"></i> Cantidad de noches</label>
                            <input type="number" id="noches" name="noches" class="input-field readonly-field" readonly>
                        </div>

                        <div class="input-group">
                            <label><i class="fas fa-dollar-sign"></i> Precio total (<?= htmlspecialchars($info_hotel->moneda); ?>)</label>
                            <input type="text" id="precioTotal" name="total" value="<?= number_format($reserva->total, 2) ?>" class="input-field readonly-field" readonly>
                        </div>
                    </div>

                    <div class="summary-box" id="summaryBox">
                        <h4><i class="fas fa-receipt"></i> Resumen actualizado</h4>
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
                        <i class="fas fa-sync-alt"></i> Actualizar Reserva
                    </button>

                    <a href="gestion_reservas.php" class="secondary-btn">
                        <i class="fas fa-arrow-left"></i> Volver sin cambios
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/recepcionista/editar_reserva.js"></script>
<?php include_once '../templates/footer.php'; ?>