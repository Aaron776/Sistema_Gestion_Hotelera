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

$id_recepcionista = $_SESSION['id_usuario'];

try {
    // 1. Obtener total de registros
    $stmt_total = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE empleado_id IS NULL OR empleado_id = :id_recepcionista");
    $stmt_total->execute([':id_recepcionista' => $id_recepcionista]);
    $total_registros = $stmt_total->fetchColumn();

    $resultados_por_pagina = 10;
    $total_paginas = ceil($total_registros / $resultados_por_pagina);

    // 2. Obtener página actual
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina < 1) $pagina = 1;
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 3. Obtener reservas con paginación real
    $sql = $conexion->prepare('SELECT r.id as id_reserva, r.fecha_inicio as fecha_inicio, r.fecha_fin as fecha_fin, r.estado as estado, u.nombre as recepcionista, c.nombre as cliente_nombre, r.total as total, h.numero as habitacion FROM reservas r LEFT JOIN usuarios u ON r.empleado_id=u.id LEFT JOIN usuarios c ON r.cliente_id=c.id LEFT JOIN reserva_habitacion rh ON r.id = rh.reserva_id LEFT JOIN habitaciones h ON rh.habitacion_id = h.id WHERE r.empleado_id IS NULL OR r.empleado_id = :id_recepcionista ORDER BY r.fecha_fin DESC LIMIT :limit OFFSET :offset');
    $sql->bindValue(':id_recepcionista', $id_recepcionista, PDO::PARAM_INT);
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $reservas = $sql->fetchAll(PDO::FETCH_OBJ);

    // --- ESTADÍSTICAS PARA LAS TARJETAS ---
    $qTotal = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE empleado_id IS NULL OR empleado_id = :id_recepcionista");
    $qTotal->execute([':id_recepcionista' => $id_recepcionista]);
    $stat_total = $qTotal->fetchColumn();

    $qPendiente = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE estado='pendiente' AND (empleado_id IS NULL OR empleado_id = :id_recepcionista)");
    $qPendiente->execute([':id_recepcionista' => $id_recepcionista]);
    $stat_pendientes = $qPendiente->fetchColumn();

    $qConfirmada = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE estado='confirmada' AND (empleado_id IS NULL OR empleado_id = :id_recepcionista)");
    $qConfirmada->execute([':id_recepcionista' => $id_recepcionista]);
    $stat_confirmadas = $qConfirmada->fetchColumn();

    $qFinalizada = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE estado='finalizada' AND (empleado_id IS NULL OR empleado_id = :id_recepcionista)");
    $qFinalizada->execute([':id_recepcionista' => $id_recepcionista]);
    $stat_finalizadas = $qFinalizada->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener las reservas: " . $e->getMessage());
    $reservas = [];
    $total_registros = 0;
    $total_paginas = 0;
    $stat_total = 0;
    $stat_pendientes = 0;
    $stat_confirmadas = 0;
    $stat_finalizadas = 0;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="../app/css/recepcionista/gestion_reservas.css">
<div class="content-wrapper">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0;"><i class="fas fa-calendar-check" style="color: #3b82f6; margin-right: 10px;"></i> Gestión de Reservas</h2>
        <a href="registrar_reserva.php" class="btn-add" id="btnAgregarReserva" style="margin-bottom: 0;"><i class="fas fa-plus-circle"></i> Nueva Reserva</a>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="dashboard-cards" style="padding: 0 0 30px 0;">
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-list"></i></div>
            <div class="stat-title">TOTAL RESERVAS</div>
            <div class="stat-value"><?= $stat_total ?></div>
            <div class="stat-trend">Registradas por ti/online</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fffbeb; color: #d97706;"><i class="fas fa-clock"></i></div>
            <div class="stat-title">PENDIENTES</div>
            <div class="stat-value"><?= $stat_pendientes ?></div>
            <div class="stat-trend">Requieren confirmación</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-check-circle"></i></div>
            <div class="stat-title">CONFIRMADAS</div>
            <div class="stat-value"><?= $stat_confirmadas ?></div>
            <div class="stat-trend">Huéspedes esperados</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fdf2f8; color: #db2777;"><i class="fas fa-flag-checkered"></i></div>
            <div class="stat-title">FINALIZADAS</div>
            <div class="stat-value"><?= $stat_finalizadas ?></div>
            <div class="stat-trend">Reservas completadas</div>
        </div>
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
        <table class="reservas-table" id="reservasTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Registrado Por</th>
                    <th>Habitación</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Total (<?php echo $info_hotel->moneda ?? 'USD' ?>)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($reservas)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">No se encontraron reservas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservas as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item->id_reserva) ?></td>
                            <td><?= htmlspecialchars($item->cliente_nombre ?? 'Web/Online') ?></td>
                            <td><?= htmlspecialchars($item->recepcionista ?? 'Cliente Online') ?></td>
                            <td><span style="font-weight: 600; color: #475569;">Hab. <?= htmlspecialchars($item->habitacion ?? 'N/A') ?></span></td>
                            <td><?= date('d/m/Y', strtotime($item->fecha_inicio)) ?></td>
                            <td><?= date('d/m/Y', strtotime($item->fecha_fin)) ?></td>
                            <td>
                                <?php
                                $estado = strtolower($item->estado);
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
                            </td>
                            <td>$<?= number_format($item->total, 2) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($item->estado === 'confirmada' && $item->recepcionista != ''): ?>
                                        <a href="editar_reserva.php?id_reserva=<?= Crypto::encrypt($item->id_reserva) ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                        <form action="../controladores/recepcionista/eliminar_reserva.php" method="POST" class="form-eliminar">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="id_reserva" value="<?= Crypto::encrypt($item->id_reserva) ?>">
                                            <button type="button" class="btn-action btn-delete btn-confirmar-eliminar" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="gestion_servicios_reserva.php?id_reserva=<?= Crypto::encrypt($item->id_reserva) ?>" class="btn-action btn-services" title="Servicios"><i class="fas fa-concierge-bell"></i></a>
                                    <?php if ($item->estado === 'finalizada'): ?>
                                        <a href="pagar_reserva.php?id_reserva=<?= Crypto::encrypt($item->id_reserva) ?>" class="btn-action btn-pay" title="Pagar"><i class="fas fa-credit-card"></i></a>
                                    <?php endif; ?>
                                    <?php if ($item->estado !== 'finalizada'): ?>
                                        <a href="cambiar_estado_reserva.php?id_reserva=<?= Crypto::encrypt($item->id_reserva) ?>" class="btn-action btn-status" title="Cambiar Estado"><i class="fas fa-sync-alt"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Controles de Paginación -->
        <?php if ($total_paginas > 0): ?>
            <div class="paginator-container">
                <!-- Botones a la Izquierda -->
                <div class="paginator-buttons">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?= $pagina - 1 ?>" class="paginator-btn" title="Anterior"><i class="fas fa-chevron-left"></i> Anterior</a>
                    <?php else: ?>
                        <span class="paginator-btn disabled" title="Anterior"><i class="fas fa-chevron-left"></i> Anterior</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?pagina=<?= $i ?>" class="paginator-btn <?= ($i === $pagina) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?pagina=<?= $pagina + 1 ?>" class="paginator-btn" title="Siguiente">Siguiente <i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="paginator-btn disabled" title="Siguiente">Siguiente <i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>

                <!-- Info a la Derecha -->
                <div class="paginator-info">
                    <?php
                    $inicio_mostrado = ($total_registros > 0) ? $offset + 1 : 0;
                    $fin_mostrado = min($offset + $resultados_por_pagina, $total_registros);
                    ?>
                    Mostrando <?= $inicio_mostrado ?> a <?= $fin_mostrado ?> de <?= $total_registros ?> resultados
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
    <script src="<?= $base_url ?>app/js/recepcionista/gestion_reservas.js"></script>
<?php include_once '../templates/footer.php'; ?>