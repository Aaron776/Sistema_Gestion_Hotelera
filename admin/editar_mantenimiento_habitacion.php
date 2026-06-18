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

$id_mantenimiento = Crypto::decrypt($_GET['id_mantenimiento']);
if (empty($id_mantenimiento) || !is_numeric($id_mantenimiento) || $id_mantenimiento <= 0) {
    header("Location: gestion_habitaciones.php");
    exit();
}

// Obtener numero de la habitacion
try {
    $sql = $conexion->prepare("SELECT numero FROM habitaciones WHERE id = :id_habitacion");
    $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
    $sql->execute();
    $numero_habitacion = $sql->fetch(PDO::FETCH_OBJ)->numero;
} catch (Exception $e) {
    error_log("Error al obtener el número de la habitación: " . $e->getMessage());
    $numero_habitacion = "";
}


// Obtener datos del mantenimeinto de la habitacion para poder editar
try {
    $sql = $conexion->prepare("SELECT motivo,fecha_inicio,fecha_fin_estimada FROM mantenimientos_habitacion WHERE id = :id_mantenimiento AND habitacion_id = :id_habitacion");
    $sql->bindParam(":id_mantenimiento", $id_mantenimiento, PDO::PARAM_INT);
    $sql->bindParam(":id_habitacion", $id_habitacion, PDO::PARAM_INT);
    $sql->execute();
    $mantenimiento = $sql->fetch(PDO::FETCH_OBJ);
    if (!$mantenimiento) {
        header("Location: gestion_habitaciones.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error al obtener datos del mantenimiento de la habitación: " . $e->getMessage());
    header("Location: gestion_habitaciones.php");
    exit();
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/editar_mantenimiento_habitacion.css">

<div class="bg-pattern"></div>
<div class="form-container">
    <div class="form-card">
        <div class="card-header">
            <div class="logo-icon"> <!-- Renombrado para consistencia -->
                <i class="fas fa-edit"></i>
            </div>
            <h2>Editar Mantenimiento</h2>
            <p>Modifique la información del registro de mantenimiento</p>
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
            <form id="maintenanceForm" action="../controladores/admin/editar_mantenimiento_habitacion.php" method="POST">
                <!-- ID oculto -->
                <input type="hidden" id="maintenanceId" name="id_habitacion" value="<?php echo htmlspecialchars(Crypto::encrypt($id_habitacion)); ?>">
                <input type="hidden" id="mantenimientoId" name="id_mantenimiento" value="<?php echo htmlspecialchars(Crypto::encrypt($id_mantenimiento)); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Información de la habitación (solo lectura) -->
                <div class="room-info">
                    <i class="fas fa-bed"></i>
                    <div class="room-info-text">
                        <h4>Habitación asignada: <?php echo htmlspecialchars($numero_habitacion); ?></h4>
                    </div>
                </div>

                <!-- Motivo -->
                <div class="input-group">
                    <label><i class="fas fa-clipboard-list"></i> Motivo del mantenimiento <span class="required-star">*</span></label>
                    <input type="text" id="motivo" name="motivo" class="input-field" placeholder="Ej: Fuga de agua, Aire acondicionado dañado, Pintura" value="<?php echo htmlspecialchars($mantenimiento->motivo); ?>">
                </div>

                <!-- Fecha de inicio -->
                <div class="input-group">
                    <label><i class="fas fa-calendar-alt"></i> Fecha de inicio <span class="required-star">*</span></label>
                    <input type="date" id="fechaInicio" name="fecha_inicio" class="input-field" placeholder="DD/MM/YYYY" value="<?php echo htmlspecialchars($mantenimiento->fecha_inicio); ?>">
                </div>

                <!-- Fecha final aproximada -->
                <div class="input-group">
                    <label><i class="fas fa-calendar-check"></i> Fecha final aproximada <span class="required-star">*</span></label>
                    <input type="date" id="fechaFinal" name="fecha_fin_estimada" class="input-field" placeholder="DD/MM/YYYY" value="<?php echo htmlspecialchars($mantenimiento->fecha_fin_estimada); ?>">
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <a href="gestion_mantenimientos_habitacion.php?id_habitacion=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_habitacion))); ?>" class="secondary-btn">
                    <i class="fas fa-arrow-left"></i> Cancelar y Volver
                </a>
            </form>
        </div>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/editar_mantenimiento_habitacion.js"></script>
<?php include_once '../templates/footer.php'; ?>