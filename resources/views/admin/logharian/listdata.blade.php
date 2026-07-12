<div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftarsilahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sms" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kodept</th>
                    <th>Perguruan Tinggi</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Jumlah Hari</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Kodept</th>
                    <th>Perguruan Tinggi</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Jumlah Hari</th>
                    <th>Aksi</th>
                </tr>
            </tfoot>
        </table>
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
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    var title = column.footer().textContent;
    
                    // Create input element and add event listener
                    if (index !== 0 && index !== 6) { // Skip column "No" (index 0)
                        $('<input type="text" class="form-control form-control-sm p-1" placeholder="Search ' + title + '" />')
                            .appendTo($(column.footer()).empty())
                            .on('keyup change clear', function () {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    }
                });
        }
    });
  });
</script>