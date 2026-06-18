<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: ../acceso_denegado.php"); exit; }
require_once '../vendor/autoload.php';
require_once '../conexion/bd.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$sql = "SELECT mh.id, h.numero as habitacion, u.nombre as encargado, mh.fecha_inicio, mh.fecha_fin_estimada as fecha_fin, mh.estado, mh.motivo as descripcion 
        FROM mantenimientos_habitacion mh 
        JOIN habitaciones h ON mh.habitacion_id = h.id 
        LEFT JOIN usuarios u ON mh.registrado_por = u.id 
        ORDER BY mh.fecha_inicio DESC";
$stmt = $conexion->query($sql);
$mantenimientos = $stmt->fetchAll(PDO::FETCH_OBJ);

$html = '
<style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; }
    .header { text-align: center; border-bottom: 2px solid #f59e0b; padding-bottom: 15px; margin-bottom: 20px; }
    .header h1 { color: #78350f; margin: 0; font-size: 24px; }
    .header p { color: #64748b; margin: 5px 0 0 0; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
    th { background-color: #f59e0b; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) { background-color: #fffbeb; }
    .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; text-align: center; color: #94a3b8; font-size: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
<div class="header">
    <h1>Reporte de Mantenimientos</h1>
    <p>Generado el: ' . date('d/m/Y H:i:s') . ' | Hotel Horizon</p>
</div>
<table>
    <thead><tr><th>ID</th><th>Hab.</th><th>Encargado</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Descripción</th></tr></thead>
    <tbody>';

foreach($mantenimientos as $m) {
    $fin = $m->fecha_fin ? date('d/m/Y', strtotime($m->fecha_fin)) : 'N/A';
    $html .= '<tr>
        <td>MANT-' . $m->id . '</td>
        <td><strong>' . $m->habitacion . '</strong></td>
        <td>' . ($m->encargado ?? 'No asignado') . '</td>
        <td>' . date('d/m/Y', strtotime($m->fecha_inicio)) . '</td>
        <td>' . $fin . '</td>
        <td>' . strtoupper($m->estado) . '</td>
        <td>' . htmlspecialchars($m->descripcion) . '</td>
    </tr>';
}
$html .= '</tbody></table>
<div class="footer">Página <span class="pagenum"></span> - Sistema de Gestión Hotelera</div>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("Reporte_Mantenimientos_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
