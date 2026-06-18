document.addEventListener('DOMContentLoaded', function() {
    // Seleccionamos todos los formularios de eliminación
    const formsEliminar = document.querySelectorAll('.form-eliminar');

    formsEliminar.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Detenemos el envío inmediato

            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta reserva se eliminará permanentemente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6', // Azul principal del tema
                cancelButtonColor: '#ef4444', // Rojo de error
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit(); // Si confirma, enviamos el formulario
                }
            });
        });
    });
});