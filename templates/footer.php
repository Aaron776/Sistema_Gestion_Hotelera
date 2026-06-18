<footer>
    <i class="far fa-copyright"></i> 2026 Hotel Horizon - Sistema de gestión hotelera avanzado. Datos en tiempo real.
</footer>
</div>
</div>

<script>
    // gráfico ocupación semanal (ocupación porcentual)
    const ctxOcup = document.getElementById('ocupacionChart').getContext('2d');
    new Chart(ctxOcup, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Ocupación (%)',
                data: [62, 68, 72, 74, 79, 85, 82],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.05)',
                borderWidth: 2.5,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: 'white',
                pointRadius: 4,
                pointHoverRadius: 7,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: {
                            size: 11
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.raw}% ocupado`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        color: '#eef2ff'
                    },
                    title: {
                        display: true,
                        text: 'Porcentaje (%)'
                    }
                }
            }
        }
    });

    // gráfico ingresos diarios (en miles de dólares)
    const ctxIngresos = document.getElementById('ingresosChart').getContext('2d');
    new Chart(ctxIngresos, {
        type: 'bar',
        data: {
            labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
            datasets: [{
                label: 'Ingresos (USD $K)',
                data: [12.4, 14.2, 15.8, 18.1, 24.3, 28.6, 26.4],
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderRadius: 12,
                borderSkipped: false,
                barPercentage: 0.65,
                categoryPercentage: 0.8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `$${ctx.raw}K USD`
                    }
                }
            },
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Miles de USD'
                    },
                    grid: {
                        color: '#eef2f6'
                    }
                }
            }
        }
    });

    // Animación de cambios ficticios (efecto visual - dashboard atractivo)
    // Podemos simular actualización leve de un valor en tarjeta cada cierto tiempo (solo por dinamismo)
    let count = 0;
    setInterval(() => {
        // Solo efecto visual: cambia sutilmente el número de reservas de hoy o un detalle
        const reservasElement = document.querySelector('.stat-card:nth-child(2) .stat-value');
        if (reservasElement && count % 6 === 0) {
            const base = 38;
            const variacion = Math.floor(Math.random() * 3) - 1; // -1,0,1
            const nuevo = base + variacion;
            if (nuevo >= 0) reservasElement.innerText = nuevo;
            setTimeout(() => {
                reservasElement.innerText = "38";
            }, 1800);
        }
        // Cambiar tiny badge de notificaciones (solo por estilo)
        const badge = document.querySelector('.badge-dot');
        if (badge && count % 8 === 0) {
            badge.style.backgroundColor = "#f97316";
            setTimeout(() => badge.style.backgroundColor = "#ef4444", 1000);
        }
        count++;
    }, 4000);

    // efecto de hover en tarjetas y opción de botón interactivo extra (solo estilo)
    // El backend de PHP ahora se encarga dinámicamente de marcar la navegación activa y el título.

    function getIconFromText(menu) {
        if (menu.includes("Habitaciones")) return "bed";
        if (menu.includes("Reservas")) return "calendar-check";
        if (menu.includes("Huéspedes")) return "users";
        if (menu.includes("Reportes")) return "chart-line";
        if (menu.includes("Configuración")) return "cog";
        return "chart-pie";
    }

    // Tooltip placeholder: adaptar tamaño responsive
    window.addEventListener('resize', () => {
        // Los gráficos se redimensionan automáticamente con Chart.js responsive
    });

    // Simulación: hover en avatar muestra nombre Admin
    const avatarDiv = document.querySelector('.avatar');
    if (avatarDiv) {
        avatarDiv.addEventListener('mouseenter', () => {
            avatarDiv.setAttribute('title', 'Administrador Hotel');
        });
    }
</script>
<script src="../app/js/notificaciones.js"></script>
</body>

</html>