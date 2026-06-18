<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$id_cliente = $_SESSION['id_usuario']; // cliente logueado
try {
    // 1. Obtener total de registros
    $stmt_total = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE cliente_id = :id_cliente");
    $stmt_total->execute([':id_cliente' => $id_cliente]);
    $total_registros = $stmt_total->fetchColumn();

    $resultados_por_pagina = 10;
    $total_paginas = ceil($total_registros / $resultados_por_pagina);

    // 2. Obtener página actual
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina < 1) $pagina = 1;
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 3. Obtener reservas con paginación real
    $sql = $conexion->prepare('SELECT r.id as id_reserva, r.fecha_inicio as fecha_inicio, r.fecha_fin as fecha_fin, r.estado as estado, u.nombre as recepcionista, r.total as total FROM reservas r LEFT JOIN usuarios u ON r.empleado_id=u.id WHERE r.cliente_id = :id_cliente ORDER BY r.fecha_fin DESC LIMIT :limit OFFSET :offset');
    $sql->bindValue(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $reservas = $sql->fetchAll(PDO::FETCH_OBJ);

    // --- ESTADÍSTICAS PARA LAS TARJETAS ---
    // 1. Reservas Totales
    $stmt_total_count = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE cliente_id = :id_cliente");
    $stmt_total_count->execute([':id_cliente' => $id_cliente]);
    $total_reservas = $stmt_total_count->fetchColumn();

    // 2. Reservas Activas (Confirmadas)
    $stmt_activas = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE cliente_id = :id_cliente AND estado = 'confirmada'");
    $stmt_activas->execute([':id_cliente' => $id_cliente]);
    $reservas_activas = $stmt_activas->fetchColumn();

    // 3. Total Gastado (Excluyendo canceladas)
    $stmt_gasto = $conexion->prepare("SELECT SUM(total) FROM reservas WHERE cliente_id = :id_cliente AND estado != 'cancelada'");
    $stmt_gasto->execute([':id_cliente' => $id_cliente]);
    $total_gastado = (float)$stmt_gasto->fetchColumn();

    // 4. Reservas Finalizadas
    $stmt_fin = $conexion->prepare("SELECT COUNT(*) FROM reservas WHERE cliente_id = :id_cliente AND estado = 'finalizada'");
    $stmt_fin->execute([':id_cliente' => $id_cliente]);
    $reservas_finalizadas = $stmt_fin->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener las reservas: " . $e->getMessage());
    $reservas = [];
    $total_registros = 0;
    $total_paginas = 0;
}

include_once '../templates/header.php';
?>

<link rel="stylesheet" href="../app/css/cliente/gestion_reservas.css">

<div class="main-container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-calendar-alt" style="margin-right: 12px; color:#3b82f6;"></i> Mis Reservas</h1>
            <p>Gestiona tus reservas, edita fechas o cancela según necesites</p>
        </div>
        <a href="registrar_reserva.php" class="btn-add" id="btnNuevaReserva"><i class="fas fa-plus-circle"></i> Nueva Reserva</a>
    </div>

    <!-- Tarjetas de Estadísticas de Reservas -->
    <div class="dashboard-cards" style="padding: 0 0 30px 0;">
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-title">TOTAL RESERVAS</div>
            <div class="stat-value"><?= $total_reservas ?></div>
            <div class="stat-trend">Historial completo</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-title">RESERVAS ACTIVAS</div>
            <div class="stat-value"><?= $reservas_activas ?></div>
            <div class="stat-trend">Próximas estancias</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fffbeb; color: #d97706;"><i class="fas fa-wallet"></i></div>
            <div class="stat-title">INVERSIÓN TOTAL</div>
            <div class="stat-value">$<?= number_format($total_gastado, 2) ?></div>
            <div class="stat-trend">Gastos en alojamiento</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fdf2f8; color: #db2777;"><i class="fas fa-flag-checkered"></i></div>
            <div class="stat-title">ESTANCIAS FINALIZADAS</div>
            <div class="stat-value"><?= $reservas_finalizadas ?></div>
            <div class="stat-trend">Visitas realizadas</div>
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
                    <th>Recepcionista</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Total (<?php echo htmlspecialchars($info_hotel->moneda); ?>)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($reservas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fas fa-calendar-times" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">No se encontraron reservas</span>
                                <small style="color: #94a3b8;">Aún no has realizado ninguna reserva en nuestro sistema.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservas as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->id_reserva); ?></td>
                            <td><?php echo htmlspecialchars($item->recepcionista ?? 'Web / Online'); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($item->fecha_inicio))); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($item->fecha_fin))); ?></td>
                            <td>
                                <?php
                                $estado = strtolower($item->estado);
                                if ($estado == 'pendiente') {
                                    echo '<span class="status-badge status-pendiente"><i class="fas fa-clock"></i> Pendiente</span>';
                                } elseif ($estado == 'confirmada') {
                                    echo '<span class="status-badge status-confirmada"><i class="fas fa-check-circle"></i> Confirmada</span>';
                                } elseif ($estado == 'cancelada') {
                                    echo '<span class="status-badge status-cancelada"><i class="fas fa-times-circle"></i> Cancelada</span>';
                                } else {
                                    echo '<span class="status-badge status-finalizada"><i class="fas fa-flag-checkered"></i> Finalizada</span>';
                                }
                                ?>
                            </td>
                            <td>$<?php echo htmlspecialchars(number_format($item->total, 2)); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($item->estado == 'pendiente'): ?>
                                        <a class="btn-action btn-edit" href="editar_reserva.php?id_reserva=<?php echo Crypto::encrypt($item->id_reserva); ?>" title="Editar"><i class="fas fa-edit"></i></a>
                                        <form action="../controladores/cliente/eliminar_reserva.php" method="POST" class="form-eliminar">
                                            <input type="hidden" name="id_reserva" value="<?php echo Crypto::encrypt($item->id_reserva); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button class="btn-action btn-delete" type="submit" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($item->estado !== 'cancelada'): ?>
                                        <a href="gestion_servicios_reserva.php?id_reserva=<?php echo Crypto::encrypt($item->id_reserva); ?>" class="btn-services-text" title="Ver Servicios">
                                            <i class="fas fa-concierge-bell"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($item->estado == 'finalizada'): ?>
                                        <a href="../factura/factura_pdf.php?id_reserva=<?php echo Crypto::encrypt($item->id_reserva); ?>" class="btn-factura" target="_blank" title="Descargar Factura">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_registros > 0): ?>
            <!-- Paginador UI -->
            <div class="paginador-container">
                <div class="paginador-info">
                    Mostrando <?= count($reservas) ?> resultados de <?= $total_registros ?> totales
                </div>
                <div class="paginador-nav">
                    <?php if ($pagina > 1): ?>
                        <a href="?pagina=<?= $pagina - 1 ?>" class="paginador-btn"><i class="fas fa-chevron-left"></i> </a>
                    <?php else: ?>
                        <span class="paginador-btn disabled"><i class="fas fa-chevron-left"></i> </span>
                    <?php endif; ?>

                    <?php
                    $rango = 2;
                    $inicio = max(1, $pagina - $rango);
                    $fin = min($total_paginas, $pagina + $rango);

                    if ($inicio > 1): ?>
                        <a href="?pagina=1" class="paginador-btn">1</a>
                        <?php if ($inicio > 2): ?>
                            <span class="paginador-dots">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                        <?php if ($i == $pagina): ?>
                            <span class="paginador-btn active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?pagina=<?= $i ?>" class="paginador-btn"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($fin < $total_paginas): ?>
                        <?php if ($fin < $total_paginas - 1): ?>
                            <span class="paginador-dots">...</span>
                        <?php endif; ?>
                        <a href="?pagina=<?= $total_paginas ?>" class="paginador-btn"><?= $total_paginas ?></a>
                    <?php endif; ?>

                    <?php if ($pagina < $total_paginas): ?>
                        <a href="?pagina=<?= $pagina + 1 ?>" class="paginador-btn"><i class="fas fa-chevron-right"></i> </a>
                    <?php else: ?>
                        <span class="paginador-btn disabled"><i class="fas fa-chevron-right"></i> </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>


    <script src="<?= $base_url ?>app/js/cliente/gestion_reservas.js"></script>
    <?php include_once '../templates/footer.php'; ?>