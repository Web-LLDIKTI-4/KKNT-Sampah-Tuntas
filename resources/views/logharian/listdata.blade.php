<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sms">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kode PT</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Jumlah Hari</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-btn-export url="{{ url('admlogharian/export') }}">Export Data</x-btn-export>

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogharian.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'kodept', name: 'kodept', className: 'text-center'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'nim', name: 'nim'},           
            {data: 'nama', name: 'nama'},           
            {data: 'jumlah_log', name: 'jumlah_log', className: 'text-center'},           
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false, visible:true},
        ],
    });
  });
</script>