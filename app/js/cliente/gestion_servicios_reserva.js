document.addEventListener('DOMContentLoaded', function() {
    // Seleccionar todos los botones de eliminar (si existen en la tabla)
    const deleteButtons = document.querySelectorAll('.btn-confirmar-eliminar');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Obtener el formulario más cercano
            const form = this.closest('.form-eliminar');

            // Configuración de SweetAlert2
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Este servicio será retirado de la reserva y el total será recalculado.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Sí, eliminar ahora',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});