async function fetchData() {
    const res = await fetch('/data');
    return await res.json();
}

function renderChart(ctx, labels, datasets, title) {
    new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets },
        options: { plugins: { title: { display: true, text: title } } }
    });
}

function createDataset(label, data, color) {
    return { label, data, backgroundColor: color };
}

function downloadCsv() {
    window.location.href = '/export';
}

function initMap(points) {
    if (!points.length) return;
    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 8,
        center: { lat: parseFloat(points[0].lat), lng: parseFloat(points[0].lng) }
    });
    points.forEach(p => {
        const marker = new google.maps.Marker({
            position: { lat: parseFloat(p.lat), lng: parseFloat(p.lng) },
            map: map
        });
    });
}

async function init() {
    const data = await fetchData();
    document.getElementById('status').textContent =
        `電圧:${data.status.voltage}V 温度:${data.status.temperature}℃ 混雑:${data.status.crowd}`;

    const dailyLabels = Object.keys(data.daily);
    const dailyEnter = dailyLabels.map(l => data.daily[l]['\u5165\u5c71']);
    const dailyLeave = dailyLabels.map(l => data.daily[l]['\u4e0b\u5c71']);
    renderChart(document.getElementById('dailyChart'), dailyLabels, [
        createDataset('入山', dailyEnter, 'rgba(75,192,192,0.6)'),
        createDataset('下山', dailyLeave, 'rgba(192,75,75,0.6)')
    ], '日別カウント');

    const monthlyLabels = Object.keys(data.monthly);
    const monthlyEnter = monthlyLabels.map(l => data.monthly[l]['\u5165\u5c71']);
    const monthlyLeave = monthlyLabels.map(l => data.monthly[l]['\u4e0b\u5c71']);
    renderChart(document.getElementById('monthlyChart'), monthlyLabels, [
        createDataset('入山', monthlyEnter, 'rgba(75,192,192,0.6)'),
        createDataset('下山', monthlyLeave, 'rgba(192,75,75,0.6)')
    ], '月別カウント');

    const yearlyLabels = Object.keys(data.yearly);
    const yearlyEnter = yearlyLabels.map(l => data.yearly[l]['\u5165\u5c71']);
    const yearlyLeave = yearlyLabels.map(l => data.yearly[l]['\u4e0b\u5c71']);
    renderChart(document.getElementById('yearlyChart'), yearlyLabels, [
        createDataset('入山', yearlyEnter, 'rgba(75,192,192,0.6)'),
        createDataset('下山', yearlyLeave, 'rgba(192,75,75,0.6)')
    ], '年別カウント');

    initMap(data.points);
}

window.addEventListener('DOMContentLoaded', init);
