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

// 1. Obtener información de la reserva y cliente
try {
    $sql = $conexion->prepare("SELECT r.id, r.total, r.estado, u.nombre as cliente_nombre, u.cedula as cliente_cedula 
                               FROM reservas r 
                               JOIN usuarios u ON r.cliente_id = u.id 
                               WHERE r.id = :id_reserva");
    $sql->execute([':id_reserva' => $id_reserva]);
    $reserva = $sql->fetch(PDO::FETCH_OBJ);

    if (!$reserva) {
        header("Location: gestion_reservas.php");
        exit();
    }

    // Verificar si la reserva ya tiene un pago registrado
    $sqlPago = $conexion->prepare("SELECT COUNT(*) FROM pagos WHERE reserva_id = :id_reserva AND estado = 'pagado'");
    $sqlPago->execute([':id_reserva' => $id_reserva]);
    if ($sqlPago->fetchColumn() > 0) {
        $_SESSION['errores'] = ["La reserva #{$id_reserva} ya ha sido pagada anteriormente."];
        header("Location: gestion_reservas.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    header("Location: gestion_reservas.php");
    exit();
}

// 2. Obtener detalle de habitación
$sqlHab = $conexion->prepare("SELECT h.numero, h.tipo, rh.precio 
                              FROM reserva_habitacion rh 
                              JOIN habitaciones h ON rh.habitacion_id = h.id 
                              WHERE rh.reserva_id = :id_reserva");
$sqlHab->execute([':id_reserva' => $id_reserva]);
$habitacion = $sqlHab->fetch(PDO::FETCH_OBJ);

// 3. Obtener servicios consumidos
$sqlServ = $conexion->prepare("SELECT s.nombre, rs.cantidad, rs.subtotal 
                               FROM reserva_servicio rs 
                               JOIN servicios s ON rs.servicio_id = s.id 
                               WHERE rs.reserva_id = :id_reserva");
$sqlServ->execute([':id_reserva' => $id_reserva]);
$servicios = $sqlServ->fetchAll(PDO::FETCH_OBJ);

include_once '../templates/header.php';
?>

<style>
    .content-wrapper {
        padding: 40px 20px;
        display: flex;
        justify-content: center;
    }

    .form-container {
        width: 100%;
        max-width: 750px;
        animation: fadeSlideUp 0.5s ease-out;
    }

    .form-card {
        background: white;
        border-radius: 35px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        border: 1px solid #f1f5f9;
    }

    .card-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        padding: 40px;
        text-align: center;
        color: white;
    }

    .header-icon {
        background: rgba(255, 255, 255, 0.1);
        width: 70px;
        height: 70px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 15px;
        backdrop-filter: blur(5px);
    }

    .card-body {
        padding: 40px;
    }

    /* Secciones de desglose */
    .breakdown-section {
        margin-bottom: 30px;
    }

    .breakdown-section h4 {
        font-size: 0.85rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .breakdown-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    .breakdown-table th {
        text-align: left;
        padding: 12px;
        color: #94a3b8;
        font-weight: 500;
        border-bottom: 1px solid #f1f5f9;
    }

    .breakdown-table td {
        padding: 15px 12px;
        border-bottom: 1px solid #f8fafc;
        color: #1e293b;
    }

    .total-display-box {
        background: #f8fafc;
        border-radius: 20px;
        padding: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        border: 2px solid #3b82f6;
    }

    .total-label {
        font-weight: 600;
        color: #1e293b;
        font-size: 1.1rem;
    }

    .total-amount {
        font-size: 1.8rem;
        font-weight: 800;
        color: #3b82f6;
    }

    .payment-form {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 2px dashed #e2e8f0;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .input-group {
        margin-bottom: 24px;
    }

    .input-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }

    .input-group label i {
        margin-right: 8px;
        color: #3b82f6;
        width: 20px;
    }

    .input-field,
    .select-field {
        width: 100%;
        padding: 14px 18px;
        border: 1.5px solid #e2e8f0;
        border-radius: 28px;
        font-size: 0.95rem;
        transition: all 0.2s;
        background: #fefefe;
        outline: none;
        font-family: 'Inter', sans-serif;
    }

    .input-field:focus,
    .select-field:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .readonly-field {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 600;
    }

    .submit-btn {
        width: 100%;
        background: linear-gradient(95deg, #3b82f6, #2563eb);
        border: none;
        padding: 16px;
        border-radius: 60px;
        color: white;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 16px;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(37, 99, 235, 0.35);
    }

    .secondary-btn {
        width: 100%;
        background: transparent;
        border: 1.5px solid #e2e8f0;
        padding: 14px;
        border-radius: 60px;
        color: #64748b;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-top: 16px;
        text-decoration: none;
    }

    @media (max-width: 520px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }
    }
</style>

<div class="content-wrapper">
    <div class="form-container">
        <div class="form-card">
            <div class="card-header">
                <div class="header-icon"><i class="fas fa-credit-card"></i></div>
                <h2>Liquidación de Reserva</h2>
                <p>Reserva #<?= $reserva->id ?> - <?= htmlspecialchars($reserva->cliente_nombre) ?></p>
            </div>

            <div class="card-body">
                <!-- Desglose de Habitación -->
                <div class="breakdown-section">
                    <h4><i class="fas fa-bed"></i> Detalle de Alojamiento</h4>
                    <table class="breakdown-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Habitación</th>
                                <th style="text-align: right;">Precio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Reserva de Habitación</td>
                                <td><?= htmlspecialchars($habitacion->numero) ?> (<?= htmlspecialchars($habitacion->tipo) ?>)</td>
                                <td style="text-align: right; font-weight: 600;">$<?= number_format($habitacion->precio, 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Desglose de Servicios -->
                <?php if (!empty($servicios)): ?>
                    <div class="breakdown-section">
                        <h4><i class="fas fa-concierge-bell"></i> Servicios Adicionales</h4>
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th style="text-align: center;">Cant.</th>
                                    <th style="text-align: right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicios as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s->nombre) ?></td>
                                        <td style="text-align: center;"><?= $s->cantidad ?></td>
                                        <td style="text-align: right; font-weight: 600;">$<?= number_format($s->subtotal, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="total-display-box">
                    <span class="total-label">TOTAL A LIQUIDAR</span>
                    <span class="total-amount">$<?= number_format($reserva->total, 2) ?></span>
                </div>

                <!-- Formulario de Pago -->
                <form class="payment-form" action="../controladores/recepcionista/procesar_pago.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id_reserva" value="<?= Crypto::encrypt($reserva->id) ?>">

                    <div class="form-row">
                        <div class="input-group">
                            <label><i class="fas fa-wallet"></i> Método de Pago <span class="required-star">*</span></label>
                            <select name="metodo" class="select-field" required>
                                <option value="">Seleccione el método...</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta de Crédito / Débito</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label><i class="fas fa-money-check-alt"></i> Monto a Pagar</label>
                            <input type="text" value="$<?= number_format($reserva->total, 2) ?>" class="input-field readonly-field" readonly>
                            <input type="hidden" name="monto" value="<?= $reserva->total ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label><i class="fas fa-file-upload"></i> Adjuntar Comprobante</label>
                        <input type="file" name="comprobante" class="input-field" accept="image/*,application/pdf">
                        <small style="color: #64748b; display: block; margin-top: 5px;">Obligatorio para transferencias o pagos con tarjeta.</small>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-receipt"></i> Confirmar Pago y Generar Factura
                    </button>

                    <a href="gestion_reservas.php" class="secondary-btn">
                        <i class="fas fa-arrow-left"></i> Regresar
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once '../templates/footer.php'; ?>