document.addEventListener('DOMContentLoaded', function() {
    /**
     * Alerta de confirmación para eliminar un usuario
     */
    window.confirmarEliminacion = function(id, nombre) {
        Swal.fire({
            title: '<span style="color: #1e293b; font-family: Poppins, sans-serif;">¿Eliminar usuario?</span>',
            html: `¿Estás seguro de que deseas eliminar a <b>${nombre}</b>?<br><small style="color: #64748b;">Esta acción desactivará al usuario del sistema.</small>`,
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
     * Alerta de confirmación para restablecer contraseña
     */
    window.confirmarResetPassword = function(id, nombre) {
        Swal.fire({
            title: '<span style="color: #1e293b; font-family: Poppins, sans-serif;">Restablecer Contraseña</span>',
            html: `Se generará una nueva clave temporal para <b>${nombre}</b> y se enviará automáticamente a su correo electrónico registrado.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6', // Azul del sistema
            cancelButtonColor: '#64748b',
            confirmButtonText: '<i class="fas fa-key"></i> Sí, restablecer',
            cancelButtonText: 'Cancelar',
            borderRadius: '24px',
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formReset-' + id).submit();
            }
        });
    };

    /**
     * Muestra un mensaje toast en la parte inferior de la pantalla.
     * @param {string} message - El mensaje a mostrar.
     * @param {string} type - El tipo de mensaje ('success', 'error', 'info').
     */
    window.showToast = function(message, type = "info") {
        let toast = document.createElement("div");
        toast.className = "toast-message";
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
        toast.style.position = "fixed";
        toast.style.bottom = "30px";
        toast.style.left = "50%";
        toast.style.transform = "translateX(-50%)";
        toast.style.background = type === "success" ? "#0f172a" : "#b91c1c";
        toast.style.color = "white";
        toast.style.padding = "12px 28px";
        toast.style.borderRadius = "60px";
        toast.style.zIndex = "3000";
        toast.style.fontSize = "0.85rem";
        toast.style.fontWeight = "500";
        toast.style.boxShadow = "0 10px 25px rgba(0,0,0,0.15)";
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    };
});