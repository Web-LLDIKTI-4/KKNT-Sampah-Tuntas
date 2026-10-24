<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Lokasi Program</th>
                    <th>Kecamatan</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Keterangan</th>
                    @if ($canManage)
                        <th width="1">Aksi</th>
                    @endif
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
        searchDelay: 500,
        ajax: "{{ route('picdlh.listdataserver') }}",
        order: [],
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'lokasi_program_nama', name: 'lokasi_program_nama', defaultContent: '-', render: function (value) { return value || '-'; }},
            {data: 'kecamatan', name: 'kecamatan', defaultContent: '-', render: function (value) { return value || '-'; }},
            {data: 'nama', name: 'nama'},
            {data: 'kontak', name: 'kontak', className: 'text-center'},
            {data: 'keterangan', name: 'keterangan'},
            @if ($canManage)
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
            @endif
        ]
    });
  });
</script>
