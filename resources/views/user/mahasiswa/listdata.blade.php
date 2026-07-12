
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered user_datatable" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Prodi</th>
                    <th>Tahun Masuk</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Nim</th>
                    <th>Nama</th>
                    <th>Prodi</th>
                    <th>Tahun Masuk</th>
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
        ajax: "{{ route('ptmahasiswa.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'prodi', name: 'prodi'},
            {data: 'tahun_masuk', name: 'tahun_masuk'},
        ],
        // Menambahkan opsi untuk mencegah escape HTML oleh DataTables
        decodeEntities: false,
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    // Periksa apakah footer ada sebelum mencoba mengakses propertinya
                    var footer = column.footer(); // Dapatkan footer kolom

                    // Periksa apakah footer ada sebelum mencoba mengakses propertinya
                    if (footer) {
                        var title = column.footer().textContent;
        
                        // Create input element and add event listener
                        if (index !== 0) { // Skip column "No" (index 0)
                            $('<input type="text" class="form-control form-control-sm p-1" placeholder="Search ' + title + '" />')
                                .appendTo($(column.footer()).empty())
                                .on('keyup change clear', function () {
                                    if (column.search() !== this.value) {
                                        column.search(this.value).draw();
                                    }
                                });
                        }
                    }
                });
        }
    });

  });
</script>