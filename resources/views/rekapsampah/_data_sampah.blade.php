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
