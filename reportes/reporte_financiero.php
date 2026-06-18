<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') { header("Location: ../acceso_denegado.php"); exit; }
require_once '../vendor/autoload.php';
require_once '../conexion/bd.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$sql = "SELECT p.id, u.nombre as cliente, p.metodo, p.monto, p.fecha 
        FROM pagos p 
        JOIN reservas r ON p.reserva_id = r.id 
        JOIN usuarios u ON r.cliente_id = u.id 
        WHERE p.estado = 'pagado'
        ORDER BY p.fecha DESC";
$stmt = $conexion->query($sql);
$pagos = $stmt->fetchAll(PDO::FETCH_OBJ);

$html = '
<style>
    body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #333; }
    .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 15px; margin-bottom: 20px; }
    .header h1 { color: #064e3b; margin: 0; font-size: 24px; }
    .header p { color: #64748b; margin: 5px 0 0 0; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
    th { background-color: #10b981; color: white; padding: 12px; text-align: left; }
    td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
    tr:nth-child(even) { background-color: #f8fafc; }
    .footer { position: fixed; bottom: -30px; left: 0px; right: 0px; height: 30px; text-align: center; color: #94a3b8; font-size: 10px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
</style>
<div class="header">
    <h1>Reporte Financiero (Pagos Recibidos)</h1>
    <p>Generado el: ' . date('d/m/Y H:i:s') . ' | Hotel Horizon</p>
</div>
<table>
    <thead><tr><th>ID Pago</th><th>Cliente</th><th>Método</th><th>Fecha</th><th>Monto</th></tr></thead>
    <tbody>';

$totalIngresos = 0;
foreach($pagos as $p) {
    $totalIngresos += $p->monto;
    $html .= '<tr>
        <td>PAG-' . str_pad($p->id, 4, '0', STR_PAD_LEFT) . '</td>
        <td>' . htmlspecialchars($p->cliente) . '</td>
        <td>' . ucfirst($p->metodo) . '</td>
        <td>' . date('d/m/Y H:i', strtotime($p->fecha)) . '</td>
        <td><strong>$' . number_format($p->monto, 2) . '</strong></td>
    </tr>';
}
$html .= '</tbody></table>
<div style="margin-top: 20px; text-align: right; font-size: 16px; background:#ecfdf5; padding: 10px; border:1px solid #a7f3d0; border-radius:8px;">
    <strong>Ingresos Totales Recaudados: </strong> <span style="color:#059669; font-weight:900;">$' . number_format($totalIngresos, 2) . '</span>
</div>
<div class="footer">Página <span class="pagenum"></span> - Sistema de Gestión Hotelera</div>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Financiero_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
