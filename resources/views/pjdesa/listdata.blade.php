<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id pjdesa</th>
                    <th>Nama Kecamatan</th>
                    <th>Nama Desa / Kelurahan</th>
                    <th>Ketua Kelompok</th>
                    <th>Instansi</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('pjdesa.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_pjdesa', name: 'id_pjdesa', visible:false},
            {data: 'kecamatan', name: 'kecamatan'},
            {data: 'desa', name: 'desa'},
            {data: 'pjdesa', name: 'pjdesa'},
            {data: 'instansi', name: 'instansi'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    return "<div class='text-wrap'>" + data + "</div>";
                },
                targets: 3
            }
        ],
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        }
    });
  });
</script>