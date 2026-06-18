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
<style>
    /* ========== ESTILOS ESPECÍFICOS: REGISTRAR RESERVA ========== */

    .content-wrapper {
        padding: 30px;
        min-height: calc(100vh - 70px);
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .form-container {
        width: 100%;
        max-width: 580px;
        animation: fadeSlideUp 0.5s ease-out;
    }

    @keyframes fadeSlideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Tarjeta del formulario */
    .form-card {
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(2px);
        border-radius: 48px;
        box-shadow: 0 35px 68px -20px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.6);
        overflow: hidden;
    }

    /* Encabezado */
    .card-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        padding: 32px 32px 28px;
        text-align: center;
        color: white;
    }

    .logo-icon {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        width: 64px;
        height: 64px;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 16px;
        box-shadow: 0 12px 20px -8px rgba(59, 130, 246, 0.4);
    }

    .card-header h2 {
        font-family: 'Poppins', sans-serif;
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .card-header p {
        font-size: 0.85rem;
        opacity: 0.8;
    }

    /* Cuerpo del formulario */
    .card-body {
        padding: 40px 36px 36px;
        background: white;
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

    .required-star {
        color: #ef4444;
        margin-left: 4px;
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

    .input-field.error,
    .select-field.error {
        border-color: #ef4444;
        background-color: #fef2f2;
    }

    .error-message {
        font-size: 0.7rem;
        color: #ef4444;
        margin-top: 6px;
        margin-left: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Select personalizado */
    .select-field {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%233b82f6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 18px center;
        background-size: 18px;
    }

    /* Campo de solo lectura */
    .readonly-field {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 600;
        cursor: default;
    }

    /* Grid para dos columnas */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    /* Botón submit */
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
        background: linear-gradient(95deg, #2563eb, #1d4ed8);
    }

    .submit-btn:disabled {
        opacity: 0.7;
        transform: none;
        cursor: not-allowed;
    }

    /* Botón secundario volver */
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

    .secondary-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    /* Resumen de la reserva */
    .summary-box {
        background: #f0f9ff;
        border-radius: 24px;
        padding: 20px;
        margin-top: 16px;
        border-left: 4px solid #3b82f6;
    }

    .summary-box h4 {
        font-size: 0.8rem;
        color: #5b6e8c;
        margin-bottom: 12px;
    }

    .summary-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
    }

    .summary-item {
        text-align: center;
        flex: 1;
    }

    .summary-item .label {
        font-size: 0.7rem;
        color: #5b6e8c;
    }

    .summary-item .value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1e293b;
    }

    /* Toast notification */
    .toast-message {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #1e293b;
        color: white;
        padding: 12px 28px;
        border-radius: 60px;
        font-size: 0.85rem;
        z-index: 1100;
        opacity: 0;
        transition: all 0.3s;
        pointer-events: none;
        font-weight: 500;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .toast-message.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }

    /* Loading overlay */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 2000;
        display: none;
        justify-content: center;
        align-items: center;
    }

    .loading-spinner {
        background: white;
        padding: 24px;
        border-radius: 32px;
        text-align: center;
    }

    .loading-spinner i {
        font-size: 2rem;
        color: #3b82f6;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Responsive */
    @media (max-width: 520px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 0;
        }

        .card-body {
            padding: 32px 24px;
        }

        .card-header {
            padding: 28px 24px;
        }

        .card-header h2 {
            font-size: 1.4rem;
        }

        .summary-details {
            flex-direction: column;
            gap: 12px;
        }
    }
</style>
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
                <form id="reservaForm" action="../controladores/recepcionista/registrar_reserva.php" method="POST">
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

                    <div class="input-group">
                        <label><i class="fas fa-bed"></i> Cliente <span class="required-star">*</span></label>
                        <select id="cliente" name="id_cliente" class="select-field" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $item) { ?>
                                <option value="<?= $item->id_cliente ?>" data-nombre="<?= $item->nombre ?>" data-cedula="<?= $item->cedula ?>"><?= $item->nombre ?> - <?= $item->cedula ?></option>
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
<script src="../app/js/recepcionista/registrar_reserva.js"></script>
<?php include_once '../templates/footer.php'; ?>