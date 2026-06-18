document.addEventListener('DOMContentLoaded', () => {
    const btnNotificaciones = document.getElementById('btnNotificaciones');
    const dropdownNotificaciones = document.getElementById('dropdownNotificaciones');
    const listaNotificaciones = document.getElementById('listaNotificaciones');
    const badgeNotificaciones = document.getElementById('badgeNotificaciones');

    if (!btnNotificaciones) return;

    // Obtener notificaciones al cargar
    fetchNotificaciones();

    // Actualizar cada 30 segundos
    setInterval(fetchNotificaciones, 30000);

    // Toggle dropdown
    btnNotificaciones.addEventListener('click', (e) => {
        // Evitar propagación para no cerrar inmediatamente por el click global
        e.stopPropagation();
        
        const isVisible = dropdownNotificaciones.style.display === 'block';
        dropdownNotificaciones.style.display = isVisible ? 'none' : 'block';

        if (!isVisible) {
            marcarLeidas();
        }
    });

    // Cerrar si se hace click fuera
    document.addEventListener('click', (e) => {
        if (!btnNotificaciones.contains(e.target)) {
            dropdownNotificaciones.style.display = 'none';
        }
    });

    // Evitar que al hacer click dentro del dropdown se cierre
    dropdownNotificaciones.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    function fetchNotificaciones() {
        fetch('../controladores/notificaciones/obtener.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Actualizar badge
                    if (data.unleidas > 0) {
                        badgeNotificaciones.style.display = 'flex';
                        badgeNotificaciones.textContent = data.unleidas > 9 ? '9+' : data.unleidas;
                        badgeNotificaciones.style.backgroundColor = '#ef4444';
                    } else {
                        badgeNotificaciones.style.display = 'none';
                    }

                    // Renderizar lista
                    if (data.listado.length === 0) {
                        listaNotificaciones.innerHTML = '<div style="padding: 12px 16px; font-size: 0.8rem; color: #64748b; text-align: center;">No tienes notificaciones recientes.</div>';
                    } else {
                        let html = '';
                        data.listado.forEach(notif => {
                            const unreadStyle = notif.leida ? '' : 'background-color: #f0fdf4; border-left: 3px solid #10b981;';
                            html += `
                                <div style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; ${unreadStyle}">
                                    <div style="font-size: 0.8rem; color: #334155;">${notif.mensaje}</div>
                                    <div style="font-size: 0.65rem; color: #94a3b8; margin-top: 4px;">${new Date(notif.fecha).toLocaleString()}</div>
                                </div>
                            `;
                        });
                        listaNotificaciones.innerHTML = html;
                    }
                }
            })
            .catch(err => console.error("Error obteniendo notificaciones:", err));
    }

    function marcarLeidas() {
        if (badgeNotificaciones.style.display === 'none') return; // Ya están leídas
        
        fetch('../controladores/notificaciones/marcar_leidas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                badgeNotificaciones.style.display = 'none';
                badgeNotificaciones.textContent = '';
                // Se actualizará visualmente como leídas en el próximo fetch
            }
        })
        .catch(err => console.error("Error al marcar como leídas:", err));
    }
});
