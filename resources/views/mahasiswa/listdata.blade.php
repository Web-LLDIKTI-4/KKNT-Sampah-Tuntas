
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="user_datatable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th class="text-center" width="1">No</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Hp</th>
                    <th>Perguruan Tinggi</th>
                    <th>Lokasi Program KKN</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#user_datatable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('mahasiswa.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'location_program', name: 'location_program'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
    });
  });
</script>