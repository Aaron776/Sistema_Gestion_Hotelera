/**
 * Confirmación para eliminar un registro de mantenimiento
 */
function confirmarEliminacionMant(id) {
    Swal.fire({
        title: '<span style="color: #1e293b; font-family: Poppins, sans-serif;">¿Eliminar mantenimiento?</span>',
        html: '<span style="color: #64748b;">Este registro de mantenimiento se eliminará permanentemente.</span>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        borderRadius: '24px',
        customClass: {
            popup: 'swal2-rounded-30'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formEliminarMant-' + id).submit();
        }
    });
}

/**
 * Confirmación para finalizar un mantenimiento y liberar la habitación
 */
function confirmarDisponibleMant(id) {
    Swal.fire({
        title: '<span style="color: #1e293b; font-family: Poppins, sans-serif;">¿Finalizar mantenimiento?</span>',
        html: '<span style="color: #64748b;">La habitación volverá a estar disponible para reservas.</span>',
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-door-open"></i> Sí, disponible',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        borderRadius: '24px',
        customClass: {
            popup: 'swal2-rounded-30'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formDisponible-' + id).submit();
        }
    });
}