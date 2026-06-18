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

// Obtener estado de la habitacion
try {
    $sql = $conexion->prepare("SELECT estado FROM habitaciones WHERE id = :id_habitacion LIMIT 1");
    $sql->bindParam(':id_habitacion', $id_habitacion, PDO::PARAM_INT);
    $sql->execute();
    $habitacion = $sql->fetch(PDO::FETCH_OBJ);
    if (!$habitacion) {
        header("Location: gestion_habitaciones.php");
        exit();
    }
} catch (Exception $e) {
    error_log("Error al obtener datos de la habitación: " . $e->getMessage());
    header("Location: gestion_habitaciones.php");
    exit();
}

// Verificar si ya hay un mantenimiento en curso para esta habitacion
$mantenimiento_en_curso = false;
try {
    $sqlCheck = $conexion->prepare("SELECT id FROM mantenimientos_habitacion WHERE habitacion_id = :id_habitacion AND estado = 'en_proceso' LIMIT 1");
    $sqlCheck->bindParam(':id_habitacion', $id_habitacion, PDO::PARAM_INT);
    $sqlCheck->execute();
    $mantenimiento_en_curso = (bool)$sqlCheck->fetchColumn();
} catch (Exception $e) {
    error_log("Error al verificar mantenimiento en curso: " . $e->getMessage());
}

// Configuración del paginador
$resultados_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = 0;

try {
    // Total de registros
    $queryTotal = $conexion->prepare("SELECT COUNT(*) FROM mantenimientos_habitacion WHERE habitacion_id = :id_habitacion");
    $queryTotal->bindParam(':id_habitacion', $id_habitacion, PDO::PARAM_INT);
    $queryTotal->execute();
    $total_registros = $queryTotal->fetchColumn();

    $total_paginas = ceil($total_registros / $resultados_por_pagina);
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // Listado con paginación
    $sql = $conexion->prepare("SELECT mh.id as id_mantenimiento,mh.motivo as motivo,mh.fecha_inicio as fecha_inicio,mh.fecha_fin_estimada as fecha_fin_estimada,mh.estado as estado,u.nombre as usuario_registro FROM mantenimientos_habitacion mh INNER JOIN usuarios u ON mh.registrado_por = u.id WHERE mh.habitacion_id = :id_habitacion ORDER BY mh.id DESC LIMIT :limit OFFSET :offset");
    $sql->bindParam(':id_habitacion', $id_habitacion, PDO::PARAM_INT);
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $mantenimientos = $sql->fetchAll(PDO::FETCH_OBJ);
} catch (Exception $e) {
    error_log("Error al obtener el historial de mantenimiento: " . $e->getMessage());
    $mantenimientos = [];
    $total_registros = 0;
    $total_paginas = 0;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/gestion_mantenimientos_habitacion.css">

<div class="content-wrapper">
    <div class="section-container">
        <div class="section-header">
            <h2><i class="fas fa-tools" style="color: #3b82f6; margin-right: 10px;"></i> Historial de Mantenimiento</h2>
            <p>Monitorea y registra las intervenciones técnicas realizadas en esta habitación.</p>
        </div>
        <?php if ($habitacion->estado === 'disponible' && !$mantenimiento_en_curso): ?>
            <a href="registrar_mantenimiento_habitacion.php?id_habitacion=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_habitacion))); ?>" class="btn-add" id="btnAgregarMantenimiento" style="margin-bottom: 0;"><i class="fas fa-plus-circle"></i> Nuevo Mantenimiento</a>
        <?php endif; ?>
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
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Motivo</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Final Estimada</th>
                    <th>Estado</th>
                    <th>Registrado por</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($mantenimientos)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fas fa-tools" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">No se encontraron registros de mantenimiento</span>
                                <small style="color: #94a3b8;">Las intervenciones técnicas realizadas en esta habitación aparecerán aquí.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mantenimientos as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item->id_mantenimiento); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($item->motivo)); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($item->fecha_inicio))); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($item->fecha_fin_estimada))); ?></td>
                            <td>
                                <?php if (strtolower($item->estado) == 'en_proceso'): ?>
                                    <span class="status-badge status-enproceso"><i class="fas fa-clock"></i> En Proceso</span>
                                <?php elseif (strtolower($item->estado) == 'finalizado'): ?>
                                    <span class="status-badge status-finalizado"><i class="fas fa-check-circle"></i> Finalizado</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(ucfirst($item->usuario_registro)); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if (strtolower($item->estado) == 'en_proceso'): ?>
                                        <a href="editar_mantenimiento_habitacion.php?id_habitacion=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($id_habitacion))); ?>&id_mantenimiento=<?php echo htmlspecialchars(urlencode(Crypto::encrypt($item->id_mantenimiento))); ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                    <form id="formEliminarMant-<?= $item->id_mantenimiento ?>" action="../controladores/admin/eliminar_mantenimiento_habitacion.php" method="post" style="display:inline;">
                                        <input type="hidden" name="id_mantenimiento" value="<?php echo htmlspecialchars(Crypto::encrypt($item->id_mantenimiento)); ?>">
                                        <input type="hidden" name="id_habitacion" value="<?php echo htmlspecialchars(Crypto::encrypt($id_habitacion)); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <button type="button" class="btn-action btn-delete" title="Eliminar" onclick="confirmarEliminacionMant(<?= $item->id_mantenimiento ?>)"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                    <form id="formDisponible-<?= $item->id_mantenimiento ?>" action="../controladores/admin/cambiar_estado_mantenimiento_habitacion.php" method="post" style="display:inline;">
                                        <input type="hidden" name="id_mantenimiento" value="<?php echo htmlspecialchars(Crypto::encrypt($item->id_mantenimiento)); ?>">
                                        <input type="hidden" name="id_habitacion" value="<?php echo htmlspecialchars(Crypto::encrypt($id_habitacion)); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <button type="button" class="btn-action btn-status" title="Cambiar a disponible" onclick="confirmarDisponibleMant(<?= $item->id_mantenimiento ?>)"><i class="fas fa-door-open"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 0): ?>
            <div class="paginador-container">
                <div class="paginador-nav">
                    <a href="?id_habitacion=<?= urlencode(Crypto::encrypt($id_habitacion)) ?>&pagina=<?= max(1, $pagina - 1) ?>" class="paginador-btn <?= ($pagina <= 1) ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?id_habitacion=<?= urlencode(Crypto::encrypt($id_habitacion)) ?>&pagina=<?= $i ?>" class="paginador-btn <?= ($pagina == $i) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="?id_habitacion=<?= urlencode(Crypto::encrypt($id_habitacion)) ?>&pagina=<?= min($total_paginas, $pagina + 1) ?>" class="paginador-btn <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="paginador-info">
                    Mostrando <?= min($total_registros, $offset + 1) ?> a <?= min($total_registros, $offset + count($mantenimientos)) ?> de <?= $total_registros ?> resultados
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/gestion_mantenimientos_habitacion.js"></script>
<?php include_once '../templates/footer.php'; ?>