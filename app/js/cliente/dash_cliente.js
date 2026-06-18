document.addEventListener('DOMContentLoaded', function() {
    // Gráfica de gastos mensuales
    const ctx = document.getElementById('expensesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
            datasets: [{
                label: 'Gastos en servicios (USD)',
                data: [120, 230, 180, 310, 245, 160],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.05)',
                borderWidth: 2.5,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: 'white',
                pointRadius: 4,
                pointHoverRadius: 6,
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
                        label: (ctx) => `$${ctx.raw} USD`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#eef2ff'
                    },
                    title: {
                        display: true,
                        text: 'Monto (USD)'
                    }
                }
            }
        }
    });

    // Simulación de notificaciones
    console.log("Dashboard cliente - Hotel Horizon");
});