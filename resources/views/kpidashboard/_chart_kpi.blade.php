@if ($chartKpi['detail']->isNotEmpty())
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-start gap-4">
                <div>
                    <h5 class="mb-1">Ringkasan Capaian KPI per Daerah</h5>
                    <p class="mb-0 card-subtitle">Rata-rata capaian kegiatan dalam KPI per lokasi program; maksimal 100%</p>
                </div>
                <x-button variant="outline-primary" icon="ri-download-2-line" class="text-nowrap" data-unduh-chart="ringkasan">Unduh PNG</x-button>
            </div>
            <div class="card-body">
                <div id="chart-kpi-ringkasan"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-start gap-4">
                <div>
                    <h5 class="mb-1">Capaian per Kegiatan</h5>
                    <p class="mb-0 card-subtitle">Pilih KPI untuk melihat capaian tiap kegiatan per daerah</p>
                </div>
                <div class="d-flex gap-2">
                    <select id="pilih-kpi-chart" class="form-select form-select-sm" aria-label="Pilih KPI">
                        @foreach ($chartKpi['detail'] as $i => $kpi)
                            <option value="{{ $i }}">{{ $kpi['nama_kpi'] }}</option>
                        @endforeach
                    </select>
                    <x-button variant="outline-primary" icon="ri-download-2-line" class="text-nowrap" data-unduh-chart="detail">Unduh PNG</x-button>
                </div>
            </div>
            <div class="card-body">
                <div id="chart-kpi-detail"></div>
            </div>
        </div>
    </div>

<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script>
$(function () {
    const data = {{ Js::from($chartKpi) }};
    const isDark = document.documentElement.classList.contains('dark-style');
    // Latar mengikuti warna card agar PNG hasil unduhan tidak transparan
    const background = getComputedStyle(document.querySelector('#chart-kpi-ringkasan').closest('.card')).backgroundColor;
    const charts = {};

    // Label dipecah per ±24 karakter agar tidak tumpang tindih
    const wrap = (text) => text.split(' ').reduce((lines, word) => {
        const last = lines[lines.length - 1];
        if (last && (last + ' ' + word).length <= 24) {
            lines[lines.length - 1] = last + ' ' + word;
        } else {
            lines.push(word);
        }
        return lines;
    }, []);

    // Horizontal bila kategori banyak; tinggi menyesuaikan jumlah kategori
    const render = (key, categories, series, horizontal) => {
        charts[key]?.destroy();
        const height = horizontal ? Math.max(260, 80 + categories.length * (series.length * 16 + 24)) : 340;
        charts[key] = new ApexCharts(document.querySelector('#chart-kpi-' + key), {
            chart: { type: 'bar', height: height, toolbar: { show: false }, background: background, fontFamily: 'inherit' },
            theme: { mode: isDark ? 'dark' : 'light' },
            colors: ['#666cff', '#26c6f9', '#fdb528', '#72e128', '#ff4d49'],
            series: series,
            plotOptions: { bar: { horizontal: horizontal, columnWidth: '60%', barHeight: '70%', borderRadius: 4 } },
            dataLabels: { enabled: false },
            xaxis: horizontal
                ? { categories: categories, min: 0, max: 100, tickAmount: 5, labels: { formatter: (v) => Math.round(v) + '%' } }
                : { categories: categories.map(wrap) },
            yaxis: horizontal
                ? { labels: { maxWidth: 260 } }
                : { min: 0, max: 100, tickAmount: 5, labels: { formatter: (v) => Math.round(v) + '%' } },
            legend: { position: 'top' },
            tooltip: { y: { formatter: (v) => v === null ? 'Belum ada data' : v.toLocaleString('id-ID') + '%' } },
            noData: { text: 'Belum ada data' },
        });
        charts[key].render();
    };

    const renderDetail = (i) => {
        const kpi = data.detail[i];
        render('detail', kpi.kegiatan, kpi.series, true);
        charts.detail.namaFile = 'capaian-kegiatan-' + kpi.nama_kpi.toLowerCase().replace(/[^a-z0-9]+/g, '-');
    };

    render('ringkasan', data.ringkasan.kpi, data.ringkasan.series, data.ringkasan.kpi.length > 6);
    charts.ringkasan.namaFile = 'ringkasan-capaian-kpi-per-daerah';
    renderDetail(0);

    $('#pilih-kpi-chart').on('change', function () {
        renderDetail(this.value);
    });

    $('[data-unduh-chart]').on('click', function () {
        const chart = charts[$(this).data('unduh-chart')];
        chart.dataURI({ scale: 2 }).then(({ imgURI }) => {
            const link = document.createElement('a');
            link.href = imgURI;
            link.download = chart.namaFile + '.png';
            link.click();
        });
    });
});
</script>
@endif
