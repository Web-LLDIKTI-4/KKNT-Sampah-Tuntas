<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Judul</th>
                    <th>Status</th>
                    <th>File</th>
                    <th>Diunggah</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        order: [[4, 'desc']],
        ajax: "{{ route('panduan.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari judul atau nama file...",
            zeroRecords: "Belum ada panduan. Klik \"Tambah Panduan\" untuk mengunggah.",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'judul', name: 'judul'},
            {data: 'is_aktif', name: 'is_aktif', searchable: false},
            {data: 'file', name: 'nama_file'},
            {data: 'created_at', name: 'created_at', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
  });
</script>
