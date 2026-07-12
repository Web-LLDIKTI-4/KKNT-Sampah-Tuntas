<div class="alert alert-info"> (Info DPL) Jika mahasiswa belum masuk ke daftarsilahkan kelola melalui menu "<a href="{{ url('dplmentoring') }}">Kelola Data Mentoring Mahasiswa</a>"</div>

<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Pulang</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Waktu Masuk</th>
                    <th>Waktu Pulang</th>
                    <th>Aksi</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admlogkehadiran.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'tanggal', name: 'tanggal'},
            {data: 'nama_mahasiswa', name: 'nama_mahasiswa'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'waktu_masuk', name: 'waktu_masuk'},
            {data: 'waktu_pulang', name: 'waktu_pulang'},
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:false},
        ],
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    var footer = column.footer();
                    var title = footer ? footer.textContent : '';

                    // Create input element and add event listener
                    if (index !== 0) { // Skip column "No" (index 0)
                        $('<input type="text" class="form-control form-control-sm" placeholder="Search ' + title + '" />')
                            .appendTo($(footer).empty())
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