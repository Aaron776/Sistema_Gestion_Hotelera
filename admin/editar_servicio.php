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

$id_servicio = Crypto::decrypt($_GET['id_servicio']);
if (empty($id_servicio) || !is_numeric($id_servicio) || $id_servicio <= 0) {
    header("Location: gestion_servicios.php");
    exit();
}

// Obtener datos del servicio que se va a editar
try {
    $sql = $conexion->prepare("SELECT id as id_servicio,nombre,precio FROM servicios WHERE id = :id_servicio");
    $sql->bindParam(':id_servicio', $id_servicio, PDO::PARAM_INT);
    $sql->execute();
    $servicio = $sql->fetch(PDO::FETCH_OBJ);

    if (empty($servicio)) {
        header("Location: gestion_servicios.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error al obtener datos del servicio: " . $e->getMessage());
    header("Location: gestion_servicios.php");
    exit();
}

include_once '../templates/header.php';
?>
<style>
    /* ========== ESTILOS ESPECÍFICOS: EDITAR SERVICIO ========== */

    /* Fondo decorativo */
    .bg-pattern {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 0;
        overflow: hidden;
    }

    .bg-pattern::before {
        content: "";
        position: absolute;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0) 70%);
        top: -150px;
        right: -100px;
        border-radius: 50%;
    }

    .bg-pattern::after {
        content: "";
        position: absolute;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(139, 92, 246, 0.08) 0%, rgba(139, 92, 246, 0) 70%);
        bottom: -200px;
        left: -150px;
        border-radius: 50%;
    }

    /* Contenedor principal */
    .form-container {
        position: relative;
        z-index: 10;
        width: 100%;
        max-width: 580px;
        margin: 50px auto;
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
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Encabezado */
    .form-card .card-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        padding: 45px 32px 35px;
        text-align: center;
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
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
        margin: 0;
        box-shadow: 0 12px 20px -8px rgba(59, 130, 246, 0.4);
    }

    .form-card .card-header h2 {
        font-family: 'Poppins', sans-serif;
        font-size: 1.7rem;
        font-weight: 700;
        margin: 0;
        letter-spacing: -0.8px;
    }

    .form-card .card-header p {
        font-size: 0.95rem;
        opacity: 0.75;
        margin: 0;
        line-height: 1.5;
        max-width: 420px;
        font-weight: 400;
    }

    /* Cuerpo del formulario */
    .card-body {
        padding: 40px 36px 36px;
        background: white;
    }

    .input-group {
        margin-bottom: 28px;
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

    .input-field {
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

    .input-field:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }

    .input-field.error {
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

    .submit-btn:hover:not(:disabled) {
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

    /* Responsive */
    @media (max-width: 520px) {
        .card-body {
            padding: 32px 24px;
        }

        .card-header {
            padding: 28px 24px;
        }

        .card-header h2 {
            font-size: 1.4rem;
        }
    }
</style>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon">
                <i class="fas fa-edit"></i>
            </div>
            <h2>Editar Servicio</h2>
            <p>Modifique la información del servicio</p>
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
            <form id="serviceForm" action="../controladores/admin/editar_servicio.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_servicio" value="<?= htmlspecialchars(Crypto::encrypt($servicio->id_servicio)) ?>">

                <!-- Nombre del servicio -->
                <div class="input-group">
                    <label><i class="fas fa-tag"></i> Nombre del Servicio <span class="required-star">*</span></label>
                    <input type="text" id="serviceName" name="nombre" class="input-field" placeholder="Ej: Spa y Masajes, Restaurante, Lavandería" value="<?= htmlspecialchars($servicio->nombre) ?>" autocomplete="off">
                    <div class="error-message" id="nameError"></div>
                </div>

                <!-- Precio -->
                <div class="input-group">
                    <label><i class="fas fa-dollar-sign"></i> Precio (<?php echo htmlspecialchars($info_hotel->moneda); ?>) <span class="required-star">*</span></label>
                    <input type="number" id="servicePrice" name="precio" class="input-field" placeholder="Ej: 85.00" step="0.01" min="0" value="<?= htmlspecialchars($servicio->precio) ?>">
                    <div class="error-message" id="priceError"></div>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <a href="gestion_servicios.php" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/editar_servicio.js"></script>
<?php include_once '../templates/footer.php'; ?>