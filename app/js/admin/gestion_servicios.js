/**
 * Alerta de confirmación para eliminar un servicio
 */
function confirmarEliminacion(id, nombre) {
    Swal.fire({
        title: '<span style="color: #1e293b; font-family: Poppins, sans-serif;">¿Eliminar servicio?</span>',
        html: `¿Estás seguro de que deseas eliminar el servicio <b>${nombre}</b>?<br><small style="color: #64748b;">Esta acción no se puede deshacer.</small>`,
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
            document.getElementById('formEliminar-' + id).submit();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log("Módulo de gestión de servicios inicializado.");
});