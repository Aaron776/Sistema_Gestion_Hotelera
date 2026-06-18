<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: ../acceso_denegado.php"); exit; }
require_once '../vendor/autoload.php';
require_once '../conexion/bd.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$sql = "SELECT r.id, u.nombre as cliente, h.numero as habitacion, r.fecha_inicio, r.fecha_fin, r.total, r.estado 
        FROM reservas r 
        JOIN usuarios u ON r.cliente_id = u.id 
        LEFT JOIN reserva_habitacion rh ON r.id = rh.reserva_id
        LEFT JOIN habitaciones h ON rh.habitacion_id = h.id
        ORDER BY r.fecha_inicio DESC LIMIT 100";
$stmt = $conexion->query($sql);
$reservas = $stmt->fetchAll(PDO::FETCH_OBJ);

$html = '
<style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; }
    .header { text-align: center; border-bottom: 2px solid #06b6d4; padding-bottom: 15px; margin-bottom: 20px; }
    .header h1 { color: #164e63; margin: 0; font-size: 24px; }
    .header p { color: #64748b; margin: 5px 0 0 0; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
    th { background-color: #06b6d4; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) { background-color: #ecfeff; }
    .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; text-align: center; color: #94a3b8; font-size: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
<div class="header">
    <h1>Reporte de Reservas (Últimas 100)</h1>
    <p>Generado el: ' . date('d/m/Y H:i:s') . ' | Hotel Horizon</p>
</div>
<table>
    <thead><tr><th>Reserva ID</th><th>Cliente</th><th>Habitación</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Estado</th></tr></thead>
    <tbody>';

foreach($reservas as $r) {
    $html .= '<tr>
        <td>RES-' . $r->id . '</td>
        <td><strong>' . htmlspecialchars($r->cliente) . '</strong></td>
        <td>' . ($r->habitacion ?? 'Sin asignar') . '</td>
        <td>' . date('d/m/Y', strtotime($r->fecha_inicio)) . '</td>
        <td>' . date('d/m/Y', strtotime($r->fecha_fin)) . '</td>
        <td>$' . number_format($r->total, 2) . '</td>
        <td>' . strtoupper($r->estado) . '</td>
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
$dompdf->stream("Reporte_Reservas_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
