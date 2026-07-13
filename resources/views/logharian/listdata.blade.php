<div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftarsilahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sms">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kodept</th>
                    <th>Perguruan Tinggi</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Jumlah Hari</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
</div>
<a href="{{ url('admlogharian/export') }}">export excel</a>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogharian.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'kodept', name: 'kodept'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'nim', name: 'nim'},           
            {data: 'nama', name: 'nama'},           
            {data: 'jumlah_log', name: 'jumlah_log'},           
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:true},
        ],
    });
  });
</script>