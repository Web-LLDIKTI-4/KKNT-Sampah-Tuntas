
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered user_datatable">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>ID FREEFORM</th>
                    <th>Aksi</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Prodi.</th>
                    <th>Free Form</th>
                    <th>Nilai DPL</th>
                    <th>Nilai DPA</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('dplfreeform.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_freeform', name: 'id_freeform', visible:false},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false,visible:false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'prodi', name: 'prodi'},
            {data: 'freeform', name: 'freeform'},
            {data: 'nilai_dpl', name: 'nilai_dpl', className: 'text-center'},
            {data: 'nilai_dpa', name: 'nilai_dpa', className: 'text-center'},
        ],
        layout: {
            top1: {
                searchPanes: {
                    viewTotal: true
                }
            }
        },
        // Menambahkan opsi untuk mencegah escape HTML oleh DataTables
        decodeEntities: false
    });


  });
</script>