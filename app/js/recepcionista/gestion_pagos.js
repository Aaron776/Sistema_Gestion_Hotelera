function formatCurrency(amount) {
    return new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' }).format(amount);
}

function verDetalle(idPago) {
    // La variable pagosData debe estar definida globalmente antes de este script
    const pago = pagosData.find(p => p.id_pago == idPago);
    if (!pago) return;

    // Poblar Info Reserva
    document.getElementById('modalReserva').textContent = 'RES-' + pago.id_reserva;
    document.getElementById('modalCheckin').textContent = pago.fecha_inicio;
    document.getElementById('modalCheckout').textContent = pago.fecha_fin;
    document.getElementById('modalReservaTotal').textContent = formatCurrency(pago.reserva_total);
    
    // Status Badge para Reserva
    let resEstadoClass = 'status-pendiente';
    if (pago.reserva_estado === 'confirmada') resEstadoClass = 'status-confirmada';
    if (pago.reserva_estado === 'cancelada') resEstadoClass = 'status-cancelada';
    if (pago.reserva_estado === 'finalizada') resEstadoClass = 'status-finalizada';
    document.getElementById('modalReservaEstado').innerHTML = `<span class="status-badge ${resEstadoClass}">${pago.reserva_estado.toUpperCase()}</span>`;

    // Poblar Info Cliente
    document.getElementById('modalCliente').textContent = pago.cliente;
    document.getElementById('modalCedula').textContent = pago.cliente_cedula || 'N/A';
    document.getElementById('modalTelefono').textContent = pago.cliente_telefono || 'N/A';

    // Poblar Info Pago
    document.getElementById('modalIdPago').textContent = pago.id_pago;
    document.getElementById('modalRecepcionista').textContent = pago.recepcionista;
    document.getElementById('modalFecha').textContent = pago.fecha_pago;
    document.getElementById('modalMonto').textContent = formatCurrency(pago.monto);
    
    // Método
    let metodoLabel = pago.metodo.charAt(0).toUpperCase() + pago.metodo.slice(1);
    document.getElementById('modalMetodo').innerHTML = `<span class="method-badge method-${pago.metodo}">${metodoLabel}</span>`;
    
    // Estado Pago
    let estadoClass = pago.estado.toLowerCase() === 'pagado' ? 'status-pagado' : 'status-pendiente';
    document.getElementById('modalEstado').innerHTML = `<span class="status-badge ${estadoClass}">${pago.estado.toUpperCase()}</span>`;
    
    // Comprobante
    if (pago.comprobante && pago.comprobante.trim() !== '') {
        document.getElementById('modalComprobante').innerHTML = `<a href="../app/comprobantes_pagos/${pago.comprobante}" target="_blank" style="color:#3b82f6; text-decoration:none;"><i class="fas fa-file-invoice"></i> Ver Archivo</a>`;
    } else {
        document.getElementById('modalComprobante').innerHTML = `<span style="color: #94a3b8; font-style: italic;">Sin comprobante</span>`;
    }
    
    // Mostrar modal
    document.getElementById('pagoModal').style.display = 'flex';
}

function cerrarModal() {
    document.getElementById('pagoModal').style.display = 'none';
}

// Cerrar al hacer clic fuera del contenido
window.onclick = function(event) {
    const modal = document.getElementById('pagoModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
};