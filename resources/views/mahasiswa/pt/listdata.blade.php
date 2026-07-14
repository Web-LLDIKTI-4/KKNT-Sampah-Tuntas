
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered user_datatable">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Prodi</th>
                    <th>Tahun Masuk</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('ptmahasiswa.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'prodi', name: 'prodi'},
            {data: 'tahun_masuk', name: 'tahun_masuk'},
        ],
        // Menambahkan opsi untuk mencegah escape HTML oleh DataTables
        decodeEntities: false,
    });

  });
</script>