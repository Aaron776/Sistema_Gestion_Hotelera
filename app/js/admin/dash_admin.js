/**
 * Lógica del Dashboard Administrativo
 */
document.addEventListener('DOMContentLoaded', function() {
    // 1. Gráfico de Ocupación Semanal
    const ocupChartEl = document.getElementById('ocupacionChart');
    if (ocupChartEl) {
        const ctxOcup = ocupChartEl.getContext('2d');
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
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.raw}% ocupado` } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#eef2ff' },
                        title: { display: true, text: 'Porcentaje (%)' }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Ingresos Diarios
    const ingresosChartEl = document.getElementById('ingresosChart');
    if (ingresosChartEl) {
        const ctxIngresos = ingresosChartEl.getContext('2d');
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
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: (ctx) => `$${ctx.raw}K USD` } }
                },
                scales: {
                    y: {
                        title: { display: true, text: 'Miles de USD' },
                        grid: { color: '#eef2f6' }
                    }
                }
            }
        });
    }

    // 3. Simulación de dinamismo en tiempo real
    let count = 0;
    const simulationInterval = setInterval(() => {
        // Cambiar sutilmente el número de reservas de hoy para dar sensación de viveza
        const statValues = document.querySelectorAll('.stat-card .stat-value');
        if (statValues.length > 1 && count % 6 === 0) {
            const reservasElement = statValues[1];
            const base = parseInt(reservasElement.innerText);
            if (!isNaN(base)) {
                const variation = Math.floor(Math.random() * 3) - 1; // -1, 0, 1
                const nuevo = base + variation;
                if (nuevo >= 0) {
                    reservasElement.innerText = nuevo;
                    setTimeout(() => { reservasElement.innerText = base; }, 1800);
                }
            }
        }
        // Cambiar color del badge de notificaciones
        const badge = document.querySelector('.badge-dot');
        if (badge && count % 8 === 0) {
            badge.style.backgroundColor = "#f97316";
            setTimeout(() => { badge.style.backgroundColor = "#ef4444"; }, 1000);
        }
        count++;
    }, 4000);

    // Tooltip Avatar
    const avatarDiv = document.querySelector('.avatar');
    if (avatarDiv) {
        avatarDiv.addEventListener('mouseenter', () => {
            avatarDiv.setAttribute('title', 'Administrador del Hotel');
        });
    }
});