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
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'tanggal', name: 'tanggal', className: 'text-center'},
                {data: 'nama_kepala_keluarga', name: 'nama_kepala_keluarga'},
                {data: 'alamat_rumah', name: 'alamat_rumah'},
                {data: 'rt', name: 'rt', className: 'text-center'},
                {data: 'rw', name: 'rw', className: 'text-center'},
                {data: 'memilah', name: 'memilah', className: 'text-center'},
                {data: 'organik_kg', name: 'organik_kg', className: 'text-end'},
                {data: 'anorganik_kg', name: 'anorganik_kg', className: 'text-end'},
                {data: 'residu_kg', name: 'residu_kg', className: 'text-end'},
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            ],
        });
    });
</script>
