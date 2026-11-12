import Chart from 'chart.js/auto';

const programBarPalette = ['#14b8a6', '#2563eb', '#9333ea', '#db2777', '#d97706', '#16a34a', '#0284c7', '#e11d48'];

const centerTextPlugin = {
    id: 'dashboardDonutCenter',
    beforeDraw(chart) {
        if (chart.config.type !== 'doughnut') {
            return;
        }

        const total = chart.canvas.dataset.totalWaran ?? '';
        const { ctx, chartArea } = chart;

        if (!chartArea) {
            return;
        }

        const centerX = (chartArea.left + chartArea.right) / 2;
        const centerY = (chartArea.top + chartArea.bottom) / 2;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        ctx.fillStyle = '#6b7280';
        ctx.font = '12px sans-serif';
        ctx.fillText('Jumlah', centerX, centerY - 12);

        ctx.fillStyle = '#32475c';
        ctx.font = '700 24px sans-serif';
        ctx.fillText(String(total), centerX, centerY + 12);
        ctx.restore();
    },
};

function parseJson(value, fallback) {
    try {
        return JSON.parse(value);
    } catch {
        return fallback;
    }
}

function initDashboardCharts() {
    const statusEl = document.getElementById('statusChart');
    const programEl = document.getElementById('programChart');

    if (!statusEl || !programEl) {
        return;
    }

    Chart.getChart(statusEl)?.destroy();
    Chart.getChart(programEl)?.destroy();

    const pengisian = Number(statusEl.dataset.pengisian ?? 0);
    const kekosongan = Number(statusEl.dataset.kekosongan ?? 0);

    new Chart(statusEl, {
        type: 'doughnut',
        data: {
            labels: ['Pengisian Semasa', 'Kekosongan'],
            datasets: [{
                data: [pengisian, kekosongan],
                backgroundColor: ['#10b981', '#f43f5e'],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.parsed;
                            const pct = total > 0 ? Math.round((value / total) * 100) : 0;

                            return `${context.label}: ${value} (${pct}%)`;
                        },
                    },
                },
            },
        },
        plugins: [centerTextPlugin],
    });

    const programLabels = parseJson(programEl.dataset.labels ?? '[]', []);
    const programValues = parseJson(programEl.dataset.values ?? '[]', []);

    new Chart(programEl, {
        type: 'bar',
        data: {
            labels: programLabels,
            datasets: [{
                label: 'Jumlah',
                data: programValues,
                backgroundColor: programLabels.map((_, i) => programBarPalette[i % programBarPalette.length]),
                borderRadius: 6,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.parsed.y;
                            const pct = total > 0 ? Math.round((value / total) * 100) : 0;

                            return `Jumlah: ${value} (${pct}%)`;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        autoSkip: true,
                        maxRotation: 45,
                        minRotation: 0,
                    },
                },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', initDashboardCharts);
document.addEventListener('livewire:navigated', initDashboardCharts);
