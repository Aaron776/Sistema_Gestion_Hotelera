<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'recepcionista') {
    header("Location: ../acceso_denegado.php");
    exit();
}

// Inicialización para prevenir errores de variables no definidas
$total_servicios = 0;
$total_cantidad = 0;
$servicios_reserva = [];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_reserva = Crypto::decrypt($_GET['id_reserva']);
if (empty($id_reserva) || $id_reserva <= 0 || !is_numeric($id_reserva)) {
    header("Location: gestion_reservas.php");
    exit();
}

// Configuración del paginador
$resultados_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = 0;

try {
    // 1. Obtener total de registros
    $queryTotal = $conexion->prepare("SELECT COUNT(*) FROM reserva_servicio WHERE reserva_id = :id_reserva");
    $queryTotal->execute([':id_reserva' => $id_reserva]);
    $total_registros = $queryTotal->fetchColumn();

    $total_paginas = ceil($total_registros / $resultados_por_pagina);
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 2. Obtener servicios con paginación
    $sql = $conexion->prepare("SELECT rs.id as id_servicio_reserva, s.nombre as nombre_servicio, rs.cantidad as cantidad, rs.subtotal as subtotal, s.precio as precio_unitario FROM reserva_servicio rs JOIN servicios s ON rs.servicio_id = s.id WHERE rs.reserva_id = :id_reserva ORDER BY rs.id DESC LIMIT :limit OFFSET :offset");
    $sql->bindValue(':id_reserva', $id_reserva, PDO::PARAM_INT);
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $servicios_reserva = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    error_log("Error al obtener los servicios de la reserva: " . $e->getMessage());
    $servicios_reserva = [];
    $total_registros = 0;
    $total_paginas = 0;
}

// Obtener información de la reserva y total general de servicios
try {
    $sql = $conexion->prepare("SELECT r.id, r.estado, u.nombre as cliente_nombre FROM reservas r INNER JOIN usuarios u ON r.cliente_id = u.id WHERE r.id = :id_reserva");
    $sql->execute([':id_reserva' => $id_reserva]);
    $reserva = $sql->fetch(PDO::FETCH_OBJ);

    // Calculamos el total real sumando la columna subtotal de la tabla reserva_servicio
    $sqlTotal = $conexion->prepare("SELECT SUM(subtotal) FROM reserva_servicio WHERE reserva_id = :id_reserva");
    $sqlTotal->execute([':id_reserva' => $id_reserva]);
    $total_servicios = (float)$sqlTotal->fetchColumn() ?: 0;

    // Calculamos la cantidad total de items
    $sqlCant = $conexion->prepare("SELECT SUM(cantidad) FROM reserva_servicio WHERE reserva_id = :id_reserva");
    $sqlCant->execute([':id_reserva' => $id_reserva]);
    $total_cantidad = (int)$sqlCant->fetchColumn() ?: 0;
} catch (PDOException $e) {
    error_log("Error al obtener la reserva: " . $e->getMessage());
    $reserva = null;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/recepcionista/gestion_servicios_reserva.css">

<div class="content-wrapper">
    <!-- Información de la reserva -->
    <div class="reserva-header" id="reservaHeader">
        <div class="reserva-info">
            <h3 id="reservaTitulo"><i class="fas fa-user-circle"></i> <?= $reserva ? htmlspecialchars($reserva->cliente_nombre) : 'Reserva' ?></h3>
            <p id="reservaDetalle"><i class="fas fa-hashtag"></i> ID de Reserva: <?= htmlspecialchars($id_reserva) ?></p>
        </div>
        <div class="total-servicios">
            <span>Total servicios adicionales:</span>
            <span class="total" id="totalServicios">$<?= number_format($total_servicios, 2) ?></span>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas de la Reserva -->
    <div class="dashboard-cards" style="padding: 0 0 30px 0;">
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-concierge-bell"></i></div>
            <div class="stat-title">SERVICIOS REGISTRADOS</div>
            <div class="stat-value"><?= $total_registros ?></div>
            <div class="stat-trend">Tipos de servicios</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-title">MONTO TOTAL</div>
            <div class="stat-value">$<?= number_format($total_servicios, 2) ?></div>
            <div class="stat-trend">Cargos extra</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fdf2f8; color: #db2777;"><i class="fas fa-layer-group"></i></div>
            <div class="stat-title">CANTIDAD TOTAL</div>
            <div class="stat-value"><?= $total_cantidad ?></div>
            <div class="stat-trend">Unidades totales</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fffbeb; color: #d97706;"><i class="fas fa-info-circle"></i></div>
            <div class="stat-title">ESTADO RESERVA</div>
            <div class="stat-value" style="font-size: 1.2rem; padding-top: 10px;">
                <?php
                if ($reserva) {
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
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
            <div class="stat-trend">Situación actual</div>
        </div>
    </div>

    <!-- Botón Agregar alineado a la derecha -->
    <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
        <?php if ($reserva && $reserva->estado == 'confirmada') { ?>
            <a href="registrar_servicios_reserva.php?id_reserva=<?= Crypto::encrypt($id_reserva) ?>" class="btn-add" id="btnAgregarServicio" style="margin-bottom: 0;"><i class="fas fa-plus-circle"></i> Agregar Servicio</a>
        <?php } ?>
    </div>

    <div class="table-container">
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
        <table class="servicios-table" id="serviciosTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Servicio</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>SubTotal</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($servicios_reserva)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fas fa-concierge-bell" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">No se encontraron servicios agregados a esta reserva</span>
                                <small style="color: #94a3b8;">Haz clic en "Agregar Servicio" para añadir uno.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($servicios_reserva as $item) { ?>
                        <tr>
                            <td><?= htmlspecialchars($item->id_servicio_reserva) ?></td>
                            <td><?= htmlspecialchars(ucfirst($item->nombre_servicio)) ?></td>
                            <td><?= htmlspecialchars($item->cantidad) ?></td>
                            <td>$<?= htmlspecialchars(number_format($item->precio_unitario, 2)) ?></td>
                            <td><strong>$<?= htmlspecialchars(number_format($item->subtotal, 2)) ?></strong></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($reserva->estado === 'confirmada'): ?>
                                        <a href="editar_servicios_reserva.php?id_servicio_reserva=<?= htmlspecialchars(Crypto::encrypt($item->id_servicio_reserva)) ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                        <form action="../controladores/recepcionista/eliminar_servicios_reserva.php" method="POST" class="form-eliminar">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="id_servicio_reserva" value="<?= htmlspecialchars(Crypto::encrypt($item->id_servicio_reserva)) ?>">
                                            <button type="button" class="btn-action btn-delete btn-confirmar-eliminar" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 0): ?>
            <div class="paginador-container">
                <div class="paginador-nav">
                    <a href="?id_reserva=<?= htmlspecialchars(urlencode($_GET['id_reserva'])) ?>&pagina=<?= max(1, $pagina - 1) ?>" class="paginador-btn <?= ($pagina <= 1) ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?id_reserva=<?= htmlspecialchars(urlencode($_GET['id_reserva'])) ?>&pagina=<?= $i ?>" class="paginador-btn <?= ($pagina == $i) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?id_reserva=<?= htmlspecialchars(urlencode($_GET['id_reserva'])) ?>&pagina=<?= min($total_paginas, $pagina + 1) ?>" class="paginador-btn <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="paginador-info">
                    Mostrando <?= min($total_registros, $offset + 1) ?> a <?= min($total_registros, $offset + count($servicios_reserva)) ?> de <?= $total_registros ?> resultados
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="<?= $base_url ?>app/js/recepcionista/gestion_servicios_reserva.js"></script>
<?php include_once '../templates/footer.php'; ?>