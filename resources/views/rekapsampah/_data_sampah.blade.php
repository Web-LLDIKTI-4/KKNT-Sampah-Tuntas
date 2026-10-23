{{-- Data Sampah: subtotal per Lokasi Program (atas) + detail per log server-side (rekapsampah.listdataserver) --}}
@php
    use App\Models\PenguranganSampah;
    $koefisien = fn ($v) => $v === null ? '-' : rtrim(rtrim(number_format((float) $v, 4, ',', '.'), '0'), ',');
    $formatNilai = [
        'angka' => fn ($v) => PenguranganSampah::formatAngka($v),
        'berat' => fn ($v) => PenguranganSampah::formatAngka($v, 2),
        'persen' => fn ($v) => PenguranganSampah::formatPersen($v === null ? null : (float) $v),
    ];

    // Satu sumber dengan export: App\Exports\DataSampahExport::kolom()
    $kolom = $kolomDataSampah;
    $kolomNilai = array_values(array_filter($kolom, fn ($k) => $k['tipe'] !== 'text'));
    $colspanSubtotal = count($kolomNilai) + 1;
    $paramFilter = array_filter([
        'bulan' => $filter['bulan'] ?? 'semua',
        'kecamatan' => $filter['id_kecamatan'],
        'kodept' => $filter['kodept'],
        'klaster' => $filter['klaster'],
        'lokasi' => $filter['lokasi'] ?? null,
    ]);
    $tableId = 'dataSampah'.uniqid();
    $barisNilai = function ($angka, string $class = '') use ($kolomNilai, $formatNilai) {
        $html = '';
        foreach ($kolomNilai as $k) {
            $html .= '<td class="text-center text-nowrap '.$class.'">'.e($formatNilai[$k['tipe']]($angka->{$k['data']} ?? null)).'</td>';
        }

        return new \Illuminate\Support\HtmlString($html);
    };
@endphp

<h6 class="mb-2">Subtotal per Lokasi Program</h6>
<div class="table-responsive scroll-box scroll-box-lg mb-4">
    <table class="table table-sm table-bordered mb-0 align-middle">
        <thead class="text-center align-middle">
            <tr>
                <th>Uraian</th>
                @foreach ($kolomNilai as $k)
                    <th>{{ $k['label'] }}</th>
                @endforeach
            </tr>
            <tr class="small text-muted">
                <th class="fw-normal"></th>
                @foreach ($kolomNilai as $k)
                    <th class="fw-normal">{{ $k['huruf'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($subtotal as $lokasi)
                <tr class="table-primary">
                    <td colspan="{{ $colspanSubtotal }}" class="fw-semibold">
                        {{ $lokasi->nama }} <span class="fw-normal text-muted small">· Koefisien Timbulan: {{ $koefisien($lokasi->koefisien) }}</span>
                    </td>
                </tr>
                @foreach ($lokasi->bulan as $bulan)
                    @foreach ($bulan->minggu as $minggu)
                        <tr>
                            <td class="ps-4 text-nowrap text-muted">{{ $minggu->label }}</td>
                            {{ $barisNilai($minggu->subtotal) }}
                        </tr>
                    @endforeach
                    <tr>
                        <td class="text-nowrap fw-medium">Subtotal {{ $bulan->nama_bulan }}</td>
                        {{ $barisNilai($bulan->subtotal, 'fw-medium') }}
                    </tr>
                @endforeach
                <tr class="table-light">
                    <td class="text-nowrap fw-semibold">Total {{ $lokasi->nama }}</td>
                    {{ $barisNilai($lokasi->total, 'fw-semibold') }}
                </tr>
            @empty
                <tr><td colspan="{{ $colspanSubtotal }}" class="text-center text-muted">Belum ada data Pendataan Sampah Penduduk</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<h6 class="mb-2">Detail per Log Pendataan</h6>
<div class="table-responsive">
    <x-datatable :id="$tableId" tableClass="table table-sm table-bordered align-middle js-data-sampah">
        <x-slot:thead>
            <tr>
                @foreach ($kolom as $k)
                    <th>{{ $k['label'] }}</th>
                @endforeach
            </tr>
            <tr class="small text-muted">
                @foreach ($kolom as $k)
                    <th class="fw-normal">{{ $k['huruf'] }}</th>
                @endforeach
            </tr>
        </x-slot:thead>
    </x-datatable>
</div>

{{-- Keterangan rumus; huruf kolom ikut $kolom (PEMDA bergeser +2). Sumber: PenguranganSampahService barisLog/angkaSubtotal --}}
@php($h = array_column($kolom, 'huruf', 'data'))
<details class="small text-muted mt-3">
    <summary class="fw-semibold text-body">Keterangan perhitungan tabel</summary>
    <div class="pt-2">
        <p class="fw-semibold text-body mb-1">Jumlah Rumah ({{ $h['jml_rumah'] }}) &amp; Rumah Memilah ({{ $h['jml_rumah_memilah'] }})</p>
        <ul class="ps-3 mb-3">
            <li>Satu rumah = alamat rumah yang sama di kelurahan yang sama dalam bulan yang sama. Alamat dibandingkan tanpa huruf besar/kecil, spasi, dan tanda baca ("Jl. Melati 1" = "jl melati 1"). Nama kepala rumah tidak dipakai.</li>
            <li>Rumah dihitung satu kali per bulan, di catatan pertamanya (urut tanggal, lalu waktu input): {{ $h['jml_rumah'] }} = 1. Catatan berikutnya untuk rumah yang sama: {{ $h['jml_rumah'] }} = 0 dan {{ $h['jml_rumah_memilah'] }} = 0, tetapi berat ({{ $h['organik'] }}, {{ $h['anorganik'] }}, {{ $h['residu'] }}) dan jiwa ({{ $h['jml_jiwa'] }}) tetap dijumlah.</li>
            <li>Di bulan berikutnya rumah yang sama dihitung lagi.</li>
            <li>{{ $h['jml_rumah_memilah'] }} = 1 bila minimal satu catatan rumah itu di bulan tersebut memilah; angkanya ditaruh di baris catatan pertama.</li>
            @if ($mingguan ?? false)
                <li>Tampilan mingguan: rumah dihitung di minggu pertama ia didata. Minggu per 7 tanggal: Minggu 1 = tgl 1–7 … Minggu 5 = tgl 29–akhir bulan.</li>
            @endif
            <li>Rumah dihitung di dalam data yang sedang difilter.</li>
        </ul>

        <p class="fw-semibold text-body mb-1">Rumus kolom</p>
        <ul class="ps-3 mb-3">
            <li>{{ $h['persen_ketaatan'] }} Ketaatan Pemilahan = {{ $h['jml_rumah_memilah'] }} ÷ {{ $h['jml_rumah'] }} × 100%.</li>
            <li>{{ $h['total_terkelola'] }} Total Terkelola = {{ $h['organik'] }} + {{ $h['anorganik'] }}. {{ $h['total_dihasilkan'] }} Total Dihasilkan = {{ $h['organik'] }} + {{ $h['anorganik'] }} + {{ $h['residu'] }}.</li>
            <li>{{ $h['persen_pengelolaan'] }} Pengelolaan Sampah = {{ $h['total_terkelola'] }} ÷ {{ $h['total_dihasilkan'] }} × 100%.</li>
            <li>{{ $h['persen_penurunan_timbulan'] }} Penurunan Timbulan = (1 − {{ $h['total_dihasilkan'] }} ÷ (koefisien × {{ $h['jml_jiwa'] }})) × 100%. Koefisien = Koefisien Timbulan lokasi program (tertulis di judul tiap lokasi), {{ $h['jml_jiwa'] }} = jumlah jiwa.</li>
        </ul>

        <p class="fw-semibold text-body mb-1">Subtotal &amp; total</p>
        <ul class="ps-3 mb-3">
            <li>Subtotal dan total menjumlahkan kolom angka dari baris-barisnya, lalu persen dihitung ulang dari jumlah itu dengan rumus di atas. Bukan rata-rata persen baris.</li>
            <li>{{ $h['persen_penurunan_timbulan'] }} subtotal = (1 − jumlah {{ $h['total_dihasilkan'] }} ÷ (koefisien × jumlah {{ $h['jml_jiwa'] }})) × 100%. {{ $h['total_dihasilkan'] }} hanya diambil dari catatan yang jumlah jiwanya diisi.</li>
        </ul>

        <p class="fw-semibold text-body mb-1">Arti tanda "-"</p>
        <ul class="ps-3 mb-3">
            <li>{{ $h['persen_ketaatan'] }} pada baris: rumah sudah dihitung di catatan sebelumnya ({{ $h['jml_rumah'] }} = 0, tidak bisa dibagi).</li>
            <li>{{ $h['persen_pengelolaan'] }}: {{ $h['total_dihasilkan'] }} = 0 (belum ada berat sampah).</li>
            <li>{{ $h['persen_penurunan_timbulan'] }}: koefisien lokasi program atau jumlah jiwa kosong/0. {{ $h['jml_jiwa'] }} "-" = jumlah jiwa tidak diisi.</li>
            <li>Pada subtotal/total: belum ada data untuk dibagi, atau koefisien/jiwa kosong.</li>
            <li>Nilai negatif ditampilkan apa adanya. {{ $h['persen_penurunan_timbulan'] }} negatif = sampah dihasilkan lebih besar dari perkiraan timbulan (koefisien × jiwa).</li>
        </ul>

        @unless ($isPt ?? false)
            <p class="fw-semibold text-body mb-1">Klaster PT</p>
            <ul class="ps-3 mb-0">
                <li>Dari Penurunan Timbulan gabungan tiap PT sesuai filter (rumus subtotal di atas): {{ collect(PenguranganSampah::KLASTER)->map(fn ($k) => $k['label'].' '.$k['ket'])->join(', ') }}. PT tanpa nilai ("-") tidak masuk klaster.</li>
            </ul>
        @endunless
    </div>
</details>

<script type="text/javascript">
  $(function () {
    // Host dimuat ulang lewat drilldown.js: hancurkan instance lama yang tabelnya sudah lepas dari DOM
    $.each($.fn.dataTable.tables(), function (i, table) {
        if ($(table).hasClass('js-data-sampah') && !document.body.contains(table)) {
            var settings = $(table).DataTable().settings()[0];
            if (settings.jqXHR) settings.jqXHR.abort();
            $(table).DataTable().destroy();
        }
    });

    var kolom = @json(array_map(fn ($k) => ['data' => $k['data'], 'tipe' => $k['tipe']], $kolom));
    var filter = @json((object) $paramFilter);
    var escape = $.fn.dataTable.render.text().display;
    var kosong = function (d) { return d === null || d === undefined || d === ''; };
    var angka = function (desimal, akhiran) {
        return function (d) {
            return kosong(d) ? '-' : Number(d).toLocaleString('id-ID', {minimumFractionDigits: desimal, maximumFractionDigits: desimal}) + (akhiran || '');
        };
    };
    var render = {
        text: function (d) { return kosong(d) ? '-' : escape(String(d)); },
        angka: angka(0),
        berat: angka(2),
        persen: angka(2, '%')
    };
    var koefisien = function (d) {
        var num = parseFloat(d);
        return isNaN(num) ? '-' : num.toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 4});
    };

    $('#{{ $tableId }}').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: true,
        lengthChange: true,
        searchDelay: 500,
        pageLength: 50,
        lengthMenu: [25, 50, 100],
        ajax: {
            url: "{{ route('rekapsampah.listdataserver') }}",
            data: function (d) { return $.extend(d, filter); }
        },
        language: {
            search: "",
            searchPlaceholder: "Cari nama KK, alamat, desa...",
            processing: "Memuat data...",
            zeroRecords: "Belum ada data Pendataan Sampah Penduduk",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: $.map(kolom, function (k) {
            return {
                data: k.data,
                className: k.tipe === 'text' ? '' : 'text-center text-nowrap',
                render: render[k.tipe]
            };
        }),
        // Judul grup Lokasi Program disisipkan tiap kali lokasi berganti dalam satu halaman
        drawCallback: function () {
            var api = this.api();
            var rows = api.rows({page: 'current'}).nodes();
            var last = null;
            api.rows({page: 'current'}).data().each(function (row, i) {
                var key = row.lokasi_id || '';
                if (key === last) return;
                last = key;
                var tr = document.createElement('tr');
                var td = document.createElement('td');
                var koef = document.createElement('span');
                tr.className = 'table-primary';
                td.colSpan = kolom.length;
                td.className = 'fw-semibold';
                td.textContent = row.nama_lokasi + ' ';
                koef.className = 'fw-normal text-muted small';
                koef.textContent = '· Koefisien Timbulan: ' + koefisien(row.koefisien);
                td.appendChild(koef);
                tr.appendChild(td);
                $(rows).eq(i).before(tr);
            });
        }
    });
    // Body tabel di-scroll dalam kotak (header sticky); search & pagination tetap di luar
    $('#{{ $tableId }}').wrap('<div class="scroll-box scroll-box-lg"></div>');
  });
</script>
