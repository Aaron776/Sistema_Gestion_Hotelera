<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: ../acceso_denegado.php"); exit; }
require_once '../vendor/autoload.php';
require_once '../conexion/bd.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$sql = "SELECT id, nombre, email, cedula, telefono, rol, estado, ultimo_acceso FROM usuarios ORDER BY rol ASC, nombre ASC";
$stmt = $conexion->query($sql);
$usuarios = $stmt->fetchAll(PDO::FETCH_OBJ);

$html = '
<style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; }
    .header { text-align: center; border-bottom: 2px solid #ef4444; padding-bottom: 15px; margin-bottom: 20px; }
    .header h1 { color: #7f1d1d; margin: 0; font-size: 24px; }
    .header p { color: #64748b; margin: 5px 0 0 0; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
    th { background-color: #ef4444; color: white; padding: 10px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) { background-color: #fef2f2; }
    .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; text-align: center; color: #94a3b8; font-size: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
<div class="header">
    <h1>Reporte de Usuarios del Sistema</h1>
    <p>Generado el: ' . date('d/m/Y H:i:s') . ' | Hotel Horizon</p>
</div>
<table>
    <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Último Acceso</th></tr></thead>
    <tbody>';

foreach($usuarios as $u) {
    $ultimo = $u->ultimo_acceso ? date('d/m/Y H:i', strtotime($u->ultimo_acceso)) : 'Nunca';
    $html .= '<tr>
        <td>' . $u->id . '</td>
        <td><strong>' . htmlspecialchars($u->nombre) . '</strong></td>
        <td>' . htmlspecialchars($u->email) . '</td>
        <td>' . strtoupper($u->rol) . '</td>
        <td>' . strtoupper($u->estado) . '</td>
        <td>' . $ultimo . '</td>
    </tr>';
}
$html .= '</tbody></table>
<div class="footer">Página <span class="pagenum"></span> - Sistema de Gestión Hotelera</div>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Usuarios_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
