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

// Obtener datos de la reserva
try {
    $sql = $conexion->prepare("SELECT reservas.id as id_reserva,usuarios.nombre as cliente, reservas.estado as estado, reservas.fecha_inicio as fecha_inicio, reservas.fecha_fin as fecha_fin FROM reservas INNER JOIN usuarios ON reservas.cliente_id=usuarios.id WHERE reservas.id = :id_reserva");
    $sql->bindParam(":id_reserva", $id_reserva, PDO::PARAM_INT);
    $sql->execute();
    $reserva = $sql->fetch(PDO::FETCH_OBJ);

    if (empty($reserva)) {
        header("Location: gestion_reservas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error al obtener los datos de la reserva: " . $e->getMessage());
    header("Location: gestion_reservas.php");
    exit();
}



include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/recepcionista/cambiar_estado_reserva.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
            <h2>Cambiar Estado de Reserva</h2>
            <p>Actualice el estado de la reserva del cliente</p>
        </div>

        <div class="card-body">
            <!-- Información de la reserva (se cargará dinámicamente) -->
            <div class="reserva-info" id="reservaInfo">
                <h4><i class="fas fa-info-circle"></i> INFORMACIÓN DE LA RESERVA</h4>
                <div class="cliente" id="clienteNombre"><?= htmlspecialchars(ucfirst($reserva->cliente)) ?></div>
                <div class="detalle" id="reservaDetalle">ID: <?= htmlspecialchars($reserva->id_reserva) ?></div>
                <div class="detalle" id="fechasReserva"><?= htmlspecialchars(date("d/m/Y", strtotime($reserva->fecha_inicio))) ?> a <?= htmlspecialchars(date("d/m/Y", strtotime($reserva->fecha_fin))) ?></div>

                <div style="margin-top: 12px;">
                    <span style="font-size: 0.75rem; color: #5b6e8c; font-weight: 600;">Estado Actual: </span>
                    <?php
                    $estado = strtolower($reserva->estado);
                    if ($estado === 'pendiente') {
                        echo '<span class="status-badge status-pendiente"><i class="fas fa-clock"></i> Pendiente</span>';
                    } elseif ($estado === 'confirmada') {
                        echo '<span class="status-badge status-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>';
                    } elseif ($estado === 'cancelada') {
                        echo '<span class="status-badge status-cancelada"><i class="fas fa-times-circle"></i> Cancelada</span>';
                    } else {
                        echo '<span class="status-badge status-finalizada"><i class="fas fa-flag-checkered"></i> Finalizada</span>';
                    }
                    ?>
                </div>
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

            <form id="estadoForm" action="../controladores/recepcionista/cambiar_estado_reserva.php" method="POST">
                <input type="hidden" id="id_reserva" name="id_reserva" value="<?= Crypto::encrypt($id_reserva) ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Selector de estado -->
                <div class="input-group">
                    <label><i class="fas fa-tag"></i> Nuevo Estado <span class="required-star">*</span></label>
                    <select id="nuevoEstado" name="estado" class="select-field">
                        <option value="">Seleccione un estado</option>
                        <option value="confirmada">Confirmada</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="finalizada">Finalizada</option>
                    </select>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Cambiar Estado
                </button>

                <a href="gestion_reservas.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/recepcionista/cambiar_estado_reserva.js"></script>
<?php include_once '../templates/footer.php'; ?>