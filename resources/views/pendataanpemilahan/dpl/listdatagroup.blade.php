<div class="table-responsive">
    <table class="table table-bordered table-sm" id="dataTablePendataanGroup">
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th class="text-center">NIM</th>
                <th>Nama Mahasiswa</th>
                <th>Email</th>
                <th>Perguruan Tinggi</th>
                <th class="text-center">Jumlah Pendataan</th>
                <th class="text-center" width="1">Aksi</th>
            </tr>
        </thead>
    </table>
</div>
<script>
    $(function () {
        $('#dataTablePendataanGroup').DataTable({
            searching: true,
            lengthChange: true,
            processing: true,
            serverSide: true,
            ajax: "{{ route('pendataanpemilahan.listdatagrouping') }}",
            language: {
                search: '',
                searchPlaceholder: 'Cari...',
                zeroRecords: 'Tidak ada data yang tersedia',
                infoEmpty: 'Tidak ada data yang ditemukan',
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'nim', name: 'nim', className: 'text-center', searchable: false},
                {data: 'nama_mahasiswa', name: 'nama_mahasiswa'},
                {data: 'email', name: 'email'},
                {data: 'nm_lemb', name: 'nm_lemb'},
                {data: 'count_log', name: 'count_log', className: 'text-center', searchable: false},
                {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            ],
        });
    });
</script>
