<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';
require_once '../helpers/Formatos.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../acceso_denegado.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$id_usuario = $_SESSION['id_usuario']; // ID DE USuario logueado

// Configuración del paginador
$resultados_por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina < 1) $pagina = 1;
$offset = 0;

try {
    // 1. Obtener total de registros
    $queryTotal = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE estado='activo' AND rol!='cliente'");
    $queryTotal->execute();
    $total_registros = $queryTotal->fetchColumn();

    $total_paginas = ceil($total_registros / $resultados_por_pagina);
    if ($pagina > $total_paginas && $total_paginas > 0) $pagina = $total_paginas;

    $offset = ($pagina - 1) * $resultados_por_pagina;

    // 2. Obtener datos con paginación real
    $sql = $conexion->prepare("SELECT id as id_usuario,nombre,email,rol,telefono,direccion,ultimo_acceso,cedula FROM usuarios WHERE estado='activo' AND rol!='cliente' ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $sql->bindValue(':limit', $resultados_por_pagina, PDO::PARAM_INT);
    $sql->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sql->execute();
    $usuarios = $sql->fetchAll(PDO::FETCH_OBJ);

    // --- ESTADÍSTICAS PARA LAS TARJETAS ---
    // Administradores
    $qAdmins = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE estado='activo' AND rol='admin'");
    $qAdmins->execute();
    $total_admins = $qAdmins->fetchColumn();

    // Recepcionistas
    $qRecep = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE estado='activo' AND rol='recepcionista'");
    $qRecep->execute();
    $total_recepcionistas = $qRecep->fetchColumn();

    // Activos hoy (acceso en el día actual)
    $qHoy = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE estado='activo' AND rol!='cliente' AND ultimo_acceso >= CURRENT_DATE");
    $qHoy->execute();
    $total_activos_hoy = $qHoy->fetchColumn();
} catch (PDOException $e) {
    error_log("Error al obtener los usuarios: " . $e->getMessage());
    $usuarios = [];
    $total_registros = 0;
    $total_paginas = 0;
    $total_admins = 0;
    $total_recepcionistas = 0;
    $total_activos_hoy = 0;
}

include_once '../templates/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>app/css/admin/gestion_usuarios.css">

<div class="content-wrapper">
    <div class="section-container">
        <div class="section-header">
            <h2><i class="fas fa-users"></i> Gestión de Usuarios</h2>
            <p>Administra los usuarios del sistema: roles, accesos y permisos de cada miembro.</p>
        </div>
        <a href="agregar_usuario.php" class="btn-add" id="btnAgregarUsuario"><i class="fas fa-user-plus"></i> Agregar Usuario</a>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="dashboard-cards" style="padding: 0 0 30px 0;">
        <div class="stat-card">
            <div class="card-icon" style="background: #eef2ff; color: #3b82f6;"><i class="fas fa-users"></i></div>
            <div class="stat-title">PERSONAL TOTAL</div>
            <div class="stat-value"><?= $total_registros ?></div>
            <div class="stat-trend">Registrados activos</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-user-shield"></i></div>
            <div class="stat-title">ADMINISTRADORES</div>
            <div class="stat-value"><?= $total_admins ?></div>
            <div class="stat-trend">Control de gestión</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #fffaf5; color: #ea580c;"><i class="fas fa-user-tie"></i></div>
            <div class="stat-title">RECEPCIONISTAS</div>
            <div class="stat-value"><?= $total_recepcionistas ?></div>
            <div class="stat-trend">Operaciones diarias</div>
        </div>
        <div class="stat-card">
            <div class="card-icon" style="background: #ecfdf5; color: #10b981;"><i class="fas fa-history"></i></div>
            <div class="stat-title">ACTIVOS HOY</div>
            <div class="stat-value"><?= $total_activos_hoy ?></div>
            <div class="stat-trend">Sesiones recientes</div>
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
        <table id="usersTable" style="width:100%">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Nombre Completo</th>
                    <th style="width: 120px;">Cédula</th>
                    <th>Email</th>
                    <th style="width: 130px;">Teléfono</th>
                    <th style="max-width: 150px;">Dirección</th>
                    <th>Rol</th>
                    <th>Último Acceso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 12px;">
                                <i class="fas fa-user-slash" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <span style="font-weight: 500; font-size: 0.95rem;">No se encontraron usuarios activos en el sistema</span>
                                <small style="color: #94a3b8;">Haz clic en "Agregar Usuario" para registrar personal administrativo.</small>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $item) : ?>
                        <tr>
                            <td><span style="color:#94a3b8; font-weight:700;">#<?= htmlspecialchars($item->id_usuario) ?></span></td>
                            <td><?= htmlspecialchars($item->nombre) ?></td>
                            <td><?= htmlspecialchars($item->cedula) ?></td>
                            <td><?= htmlspecialchars($item->email) ?></td>
                            <td><?= htmlspecialchars($item->telefono) ?></td>
                            <td><?= htmlspecialchars($item->direccion) ?></td>
                            <td>
                                <?php if ($item->rol === 'admin') : ?>
                                    <span class="role-badge role-admin"><i class="fas fa-user-shield"></i> Administrador</span>
                                <?php elseif ($item->rol === 'recepcionista') : ?>
                                    <span class="role-badge role-recepcionista"><i class="fas fa-user-tie"></i> Recepcionista</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $tiempo_str = Formatos::tiempoAgo($item->ultimo_acceso);
                                $is_recent = ($tiempo_str === 'Justo ahora' || stripos($tiempo_str, 'minuto') !== false);
                                ?>
                                <span class="time-badge <?= $is_recent ? 'recent' : '' ?>">
                                    <i class="far fa-clock"></i> <?= htmlspecialchars($tiempo_str) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="editar_usuario.php?id_usuario=<?= htmlspecialchars(urlencode(Crypto::encrypt($item->id_usuario))) ?>" class="btn-action btn-edit" title="Editar"><i class="fas fa-edit"></i></a>
                                    <?php if ($item->id_usuario != $id_usuario): ?>
                                        <form id="formEliminar-<?= $item->id_usuario ?>" action="../controladores/admin/eliminar_usuario.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="id_usuario" value="<?= htmlspecialchars(Crypto::encrypt($item->id_usuario)) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="button" class="btn-action btn-delete" title="Eliminar" onclick="confirmarEliminacion(<?= $item->id_usuario ?>, '<?= htmlspecialchars($item->nombre, ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form id="formReset-<?= $item->id_usuario ?>" action="../controladores/admin/editar_password_usuario.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?= htmlspecialchars(Crypto::encrypt($item->id_usuario)) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="button" class="btn-action btn-password" title="Restablecer contraseña" onclick="confirmarResetPassword(<?= $item->id_usuario ?>, '<?= htmlspecialchars($item->nombre, ENT_QUOTES) ?>')"><i class="fas fa-key"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Renderización de paginador -->
        <?php if ($total_paginas > 0): ?>
            <div class="paginador-container">
                <div class="paginador-nav">
                    <!-- Anterior -->
                    <a href="?pagina=<?= max(1, $pagina - 1) ?>" class="paginador-btn <?= ($pagina <= 1) ? 'disabled' : '' ?>"><i class="fas fa-chevron-left"></i></a>

                    <!-- Números -->
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?pagina=<?= $i ?>" class="paginador-btn <?= ($pagina == $i) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <!-- Siguiente -->
                    <a href="?pagina=<?= min($total_paginas, $pagina + 1) ?>" class="paginador-btn <?= ($pagina >= $total_paginas) ? 'disabled' : '' ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="paginador-info">
                    Mostrando <?= min($total_registros, $offset + 1) ?> a <?= min($total_registros, $offset + count($usuarios)) ?> de <?= $total_registros ?> resultados
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="<?= $base_url ?>app/js/admin/gestion_usuarios.js"></script>
<?php include_once '../templates/footer.php'; ?>