<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Perguruan Tinggi</th>
                    <th>Jumlah</th>
                    <th width="1">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        var table = $('#dataTable').DataTable({
            searching: true,
            lengthChange: true,
            processing: true,
            serverSide: true,
            searchDelay: 500,
            ajax: "{{ route('admlogkegiatan.listdatagrouping') }}",
            language: {
                search: "",
                searchPlaceholder: "Cari...",
                zeroRecords: "Tidak ada data yang tersedia",
                infoEmpty: "Tidak ada data yang ditemukan",
            },
            columns: [
                {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
                {data: 'nim', name: 'nim', className: 'text-center', searchable: false},
                {data: 'nama_mahasiswa', name: 'nama_mahasiswa'},
                {data: 'email', name: 'email'},
                {data: 'nm_lemb', name: 'nm_lemb'},
                {data: 'count_log', name: 'count_log', className: 'text-center', orderable: false, searchable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ]
        });
    });
</script>