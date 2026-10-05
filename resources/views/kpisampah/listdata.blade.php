<div class="row">
    <div class="col-12">
        <x-datatable id="dataTableSampah" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                {{-- Header bertingkat mengikuti format Excel --}}
                <tr>
                    <th rowspan="3" width="1">No</th>
                    <th rowspan="3">Bulan</th>
                    <th rowspan="3">Kecamatan</th>
                    <th rowspan="3">Desa/Kelurahan</th>
                    <th rowspan="3">Jumlah RW</th>
                    <th rowspan="3">Jumlah Penduduk (Jiwa)</th>
                    <th rowspan="3">Jumlah Rumah Keseluruhan</th>
                    <th rowspan="3">Jumlah Rumah yang Memilah</th>
                    <th rowspan="3">Persentase Ketaatan Pemilahan</th>
                    <th rowspan="3">Timbulan Sampah (Kg/Bulan)</th>
                    <th colspan="12" class="text-center">Jenis Sampah yang Diolah</th>
                    <th rowspan="3">Persentase Penurunan Sampah</th>
                    <th rowspan="3">Keterangan</th>
                </tr>
                <tr>
                    <th colspan="6" class="text-center">Organik</th>
                    <th colspan="4" class="text-center">Anorganik</th>
                    <th rowspan="2">Total Pengolahan (Kg/Bulan)</th>
                    <th rowspan="2">Sampah Belum Terkelola (Kg/Bulan)</th>
                </tr>
                <tr>
                    <th>Diolah di Sumber (Kg/Bulan)</th>
                    <th>Nama Metode Pengolahan</th>
                    <th>Jumlah Metode (unit)</th>
                    <th>Diolah oleh DLH (Kg/Bulan)</th>
                    <th>Nama Fasilitas DLH</th>
                    <th>Lokasi Fasilitas</th>
                    <th>Diolah di Sumber (Kg/Bulan)</th>
                    <th>Nama Metode Pengolahan</th>
                    <th>Lokasi Metode</th>
                    <th>Jumlah Metode (unit)</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    $('#dataTableSampah').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: "{{ route('kpisampah.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'bulan', name: 'bulan'},
            {data: 'kecamatan', name: 'kecamatan'},
            {data: 'kelurahan', name: 'kelurahan'},
            {data: 'jml_rw', name: 'jml_rw', className: 'text-end', searchable: false},
            {data: 'jml_penduduk', name: 'jml_penduduk', className: 'text-end', searchable: false},
            {data: 'jml_rumah', name: 'jml_rumah', className: 'text-end', searchable: false},
            {data: 'jml_rumah_memilah', name: 'jml_rumah_memilah', className: 'text-end', searchable: false},
            {data: 'persen_ketaatan', name: 'persen_ketaatan', className: 'text-end', searchable: false},
            {data: 'timbulan', name: 'timbulan', className: 'text-end', searchable: false},
            {data: 'organik_sumber', name: 'organik_sumber', className: 'text-end', searchable: false},
            {data: 'organik_metode', name: 'organik_metode', defaultContent: '-', searchable: false},
            {data: 'organik_metode_unit', name: 'organik_metode_unit', className: 'text-end', searchable: false},
            {data: 'organik_dlh', name: 'organik_dlh', className: 'text-end', searchable: false},
            {data: 'organik_dlh_fasilitas', name: 'organik_dlh_fasilitas', defaultContent: '-', searchable: false},
            {data: 'organik_dlh_lokasi', name: 'organik_dlh_lokasi', defaultContent: '-', searchable: false},
            {data: 'anorganik_sumber', name: 'anorganik_sumber', className: 'text-end', searchable: false},
            {data: 'anorganik_metode', name: 'anorganik_metode', defaultContent: '-', searchable: false},
            {data: 'anorganik_metode_lokasi', name: 'anorganik_metode_lokasi', defaultContent: '-', searchable: false},
            {data: 'anorganik_metode_unit', name: 'anorganik_metode_unit', className: 'text-end', searchable: false},
            {data: 'pengurangan', name: 'pengurangan', className: 'text-end', searchable: false},
            {data: 'belum_terkelola', name: 'belum_terkelola', className: 'text-end', searchable: false},
            {data: 'persen_pengurangan', name: 'persen_pengurangan', className: 'text-end', searchable: false},
            {data: 'keterangan', name: 'keterangan', defaultContent: '-', searchable: false},
        ]
    });
  });
</script>
