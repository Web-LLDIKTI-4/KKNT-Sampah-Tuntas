
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id Kategori</th>
                    <th>Nama Kategori Kegiatan</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-button.export url="{{ route('kategori-kegiatan.export') }}" />
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('kategori-kegiatan.listdataserver') }}",
        order: [[2, 'asc']],
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_kategori', name: 'id_kategori', visible: false},
            {data: 'nama_kategori', name: 'nama_kategori'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
    });


  });
</script>
