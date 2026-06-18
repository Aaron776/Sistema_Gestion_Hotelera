document.addEventListener('DOMContentLoaded', function() {
    /**
     * Alerta de confirmación para eliminar una habitación
     */
    window.confirmarEliminacion = function(id, numero) {
        Swal.fire({
            title: '<span style="color: #1e293b; font-family: Poppins, sans-serif;">¿Eliminar habitación?</span>',
            html: `¿Estás seguro de que deseas eliminar la habitación <b>${numero}</b>?<br><small style="color: #64748b;">Esta acción es irreversible y solo se permitirá si no hay reservas confirmadas.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444', // Rojo del sistema
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
    };

    /**
     * Alerta de confirmación para poner una habitación en mantenimiento
     */
    window.confirmarMantenimiento = function(id, numero) {
        Swal.fire({
            title: '<span style="color: #1e293b; font-family: Poppins, sans-serif;">¿Poner en mantenimiento?</span>',
            html: `¿Estás seguro de que deseas poner la habitación <b>${numero}</b> en mantenimiento?<br><small style="color: #64748b;">Esto indicará que la habitación no está disponible para reserva temporalmente.</small>`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#ea580c', // Naranja
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-tools"></i> Sí, mantenimiento',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            borderRadius: '24px',
            customClass: {
                popup: 'swal2-rounded-30'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formMantenimiento-' + id).submit();
            }
        });
    };
});