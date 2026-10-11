<div class="table-responsive">
    <table class="table table-bordered table-sm" id="dataTablePendataan">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Kepala Keluarga</th>
                <th>Alamat Rumah</th>
                <th>RT</th>
                <th>RW</th>
                <th>Memilah</th>
                <th>Organik (kg)</th>
                <th>Anorganik (kg)</th>
                <th>Residu (kg)</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<script>
    $(function () {
        $('#dataTablePendataan').DataTable({
            searching: true,
            lengthChange: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('pendataanpemilahan.listdataserver') }}",
            language: {
                search: '',
                searchPlaceholder: 'Cari...',
                zeroRecords: 'Tidak ada data yang tersedia',
                infoEmpty: 'Tidak ada data yang ditemukan',
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {data: 'tanggal', name: 'tanggal'},
                {data: 'nama_kepala_keluarga', name: 'nama_kepala_keluarga'},
                {data: 'alamat_rumah', name: 'alamat_rumah'},
                {data: 'rt', name: 'rt'},
                {data: 'rw', name: 'rw'},
                {data: 'memilah', name: 'memilah'},
                {data: 'organik_kg', name: 'organik_kg'},
                {data: 'anorganik_kg', name: 'anorganik_kg'},
                {data: 'residu_kg', name: 'residu_kg'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],
        });
    });
</script>
