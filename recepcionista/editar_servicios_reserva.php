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

$id_servicio_reserva = Crypto::decrypt($_GET['id_servicio_reserva']);
if (empty($id_servicio_reserva) || $id_servicio_reserva <= 0 || !is_numeric($id_servicio_reserva)) {
    header("Location: gestion_reservas.php");
    exit();
}

// Obtener listado de servicios
try {
    $sql = $conexion->prepare("SELECT id as id_servicio,nombre,precio as precio_unitario FROM servicios");
    $sql->execute();
    $servicios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los servicios: " . $e->getMessage());
    $servicios = [];
}


// Obtener información del servicio de la reserva
try {
    $sql = $conexion->prepare("SELECT rs.id as id_servicio_reserva, rs.reserva_id, rs.servicio_id, s.nombre as nombre_servicio, rs.cantidad as cantidad, rs.subtotal as subtotal, s.precio as precio_unitario, u.nombre as cliente_nombre FROM reserva_servicio rs JOIN servicios s ON rs.servicio_id = s.id JOIN reservas r ON rs.reserva_id = r.id JOIN usuarios u ON r.cliente_id = u.id WHERE rs.id = :id_servicio_reserva");
    $sql->bindParam(':id_servicio_reserva', $id_servicio_reserva, PDO::PARAM_INT);
    $sql->execute();
    $servicio_reserva = $sql->fetch(PDO::FETCH_OBJ);

    if (!$servicio_reserva) {
        header("Location: gestion_reservas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener el servicio de la reserva: " . $e->getMessage());
    $servicio_reserva = null;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/recepcionista/editar_servicios_reserva.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-edit"></i>
            </div>
            <h2>Modificar Servicio</h2>
            <p>Edite los detalles del servicio seleccionado para la reserva</p>
        </div>

        <div class="card-body">
            <!-- Información de la reserva -->
            <div class="reserva-info" id="reservaInfo">
                <h4><i class="fas fa-info-circle"></i> INFORMACIÓN DE LA RESERVA</h4>
                <div class="cliente" id="clienteNombre"><?= $servicio_reserva ? htmlspecialchars(ucfirst($servicio_reserva->cliente_nombre)) : 'Desconocido' ?></div>
                <div class="detalle" id="reservaDetalle">ID Reserva: #<?= $servicio_reserva ? htmlspecialchars($servicio_reserva->reserva_id) : 'N/A' ?></div>
            </div>

            <?php if (isset($_SESSION['errores'])) : ?>
                <div class="alert alert-danger" style="margin-bottom: 20px;">
                    <ul>
                        <?php foreach ($_SESSION['errores'] as $error) : ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errores']); ?>
            <?php endif; ?>

            <form id="servicioForm" action="../controladores/recepcionista/editar_servicios_reserva.php" method="POST">
                <!-- IDs ocultos -->
                <input type="hidden" name="id_servicio_reserva" value="<?= Crypto::encrypt($servicio_reserva->id_servicio_reserva) ?>">
                <input type="hidden" name="id_reserva" value="<?= Crypto::encrypt($servicio_reserva->reserva_id) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Selección de servicio -->
                <div class="input-group">
                    <label><i class="fas fa-tag"></i> Servicio <span class="required-star">*</span></label>
                    <select id="servicioSelect" name="id_servicio" class="select-field" required>
                        <option value="">Seleccione un servicio</option>
                        <?php foreach ($servicios as $item): ?>
                            <option value="<?= $item->id_servicio ?>" data-precio="<?= $item->precio_unitario ?>" <?= ($servicio_reserva && $item->id_servicio == $servicio_reserva->servicio_id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($item->nombre)) ?> - $<?= number_format($item->precio_unitario, 2) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cantidad -->
                <div class="input-group">
                    <label><i class="fas fa-sort-amount-up"></i> Cantidad <span class="required-star">*</span></label>
                    <input type="number" id="cantidad" name="cantidad" class="input-field" placeholder="Cantidad" value="<?= $servicio_reserva ? htmlspecialchars($servicio_reserva->cantidad) : '1' ?>" min="1" step="1" required>
                </div>

                <!-- Resumen de precio -->
                <div class="price-preview">
                    <span><i class="fas fa-calculator"></i> Precio unitario:</span>
                    <span id="precioUnitarioDisplay">$<?= $servicio_reserva ? number_format($servicio_reserva->precio_unitario, 2) : '0.00' ?></span>
                    <span style="margin-left: auto;">Total:</span>
                    <span class="total" id="totalDisplay">$<?= $servicio_reserva ? number_format($servicio_reserva->subtotal, 2) : '0.00' ?></span>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <a href="gestion_servicios_reserva.php?id_reserva=<?= $servicio_reserva ? Crypto::encrypt($servicio_reserva->reserva_id) : '' ?>" class="secondary-btn" id="volverBtn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/recepcionista/editar_servicios_reserva.js"></script>

<?php include_once '../templates/footer.php'; ?>