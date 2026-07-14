
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="user_datatable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th>No</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Hp</th>
                    <th>Perguruan Tinggi</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#user_datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('mahasiswa.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'email', name: 'email'},
            {data: 'phone', name: 'phone'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
    });
  });
</script>