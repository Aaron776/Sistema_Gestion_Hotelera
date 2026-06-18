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

// Obtener listado de servicios
try {
    $sql = $conexion->prepare("SELECT id as id_servicio,nombre,precio as precio_unitario FROM servicios");
    $sql->execute();
    $servicios = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los servicios: " . $e->getMessage());
    $servicios = [];
}


// Obtener información de la reserva
try {
    $sql = $conexion->prepare("SELECT r.id, r.estado, u.nombre as cliente_nombre FROM reservas r INNER JOIN usuarios u ON r.cliente_id = u.id WHERE r.id = :id_reserva");
    $sql->execute([':id_reserva' => $id_reserva]);
    $reserva = $sql->fetch(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener la reserva: " . $e->getMessage());
    $reserva = null;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/recepcionista/registrar_servicios_reserva.css">
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-concierge-bell"></i>
            </div>
            <h2>Registrar Servicio</h2>
            <p>Agregue un servicio adicional a la reserva</p>
        </div>

        <div class="card-body">
            <!-- Información de la reserva (se carga dinámicamente) -->
            <div class="reserva-info" id="reservaInfo">
                <h4><i class="fas fa-info-circle"></i> INFORMACIÓN DE LA RESERVA</h4>
                <div class="cliente" id="clienteNombre"><?= $reserva ? htmlspecialchars(ucfirst($reserva->cliente_nombre)) : 'Desconocido' ?></div>
                <div class="detalle" id="reservaDetalle">ID: #<?= htmlspecialchars($id_reserva) ?> - Estado: <?= $reserva ? ucfirst($reserva->estado) : 'N/A' ?></div>
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

            <form id="servicioForm" action="../controladores/recepcionista/registrar_servicios_reserva.php" method="POST">
                <!-- ID oculto de la reserva -->
                <input type="hidden" name="id_reserva" value="<?= Crypto::encrypt($id_reserva) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Selección de servicio -->
                <div class="input-group">
                    <label><i class="fas fa-tag"></i> Servicio <span class="required-star">*</span></label>
                    <select id="servicioSelect" name="id_servicio" class="select-field" required>
                        <option value="">Seleccione un servicio</option>
                        <?php foreach ($servicios as $item): ?>
                            <option value="<?= $item->id_servicio ?>" data-precio="<?= $item->precio_unitario ?>"><?= htmlspecialchars(ucfirst($item->nombre)) ?> - $<?= number_format($item->precio_unitario, 2) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cantidad -->
                <div class="input-group">
                    <label><i class="fas fa-sort-amount-up"></i> Cantidad <span class="required-star">*</span></label>
                    <input type="number" id="cantidad" name="cantidad" class="input-field" placeholder="Cantidad" value="1" min="1" step="1">
                </div>

                <!-- Resumen de precio -->
                <div class="price-preview">
                    <span><i class="fas fa-calculator"></i> Precio unitario:</span>
                    <span id="precioUnitarioDisplay">$0.00</span>
                    <span style="margin-left: auto;">Total:</span>
                    <span class="total" id="totalDisplay">$0.00</span>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Registrar Servicio
                </button>

                <a href="gestion_servicios_reserva.php?id_reserva=<?= Crypto::encrypt($id_reserva) ?>" class="secondary-btn" id="volverBtn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/recepcionista/registrar_servicios_reserva.js"></script>
<?php include_once '../templates/footer.php' ?>