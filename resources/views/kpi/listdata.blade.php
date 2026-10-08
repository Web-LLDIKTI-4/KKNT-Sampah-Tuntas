
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id Aktivitas </th>
                    <th>Nama Aktivitas</th>
                    <th>Target</th>
                    <th>Satuan</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-button.export url="{{ route('kpi.export') }}" />
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('kpi.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_kpi', name: 'id_kpi', visible: false}, 
            {data: 'nama_kpi', name: 'nama_kpi'},
            {data: 'target', name: 'target', className: 'text-center'},
            {data: 'satuan', name: 'satuan', className: 'text-center'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
    });


  });
</script>