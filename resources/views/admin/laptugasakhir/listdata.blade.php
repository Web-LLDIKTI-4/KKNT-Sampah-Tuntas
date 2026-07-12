<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id Lap</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Laporan</th>
                    <th>Nilai</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Id Lap</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Laporan</th>
                    <th>Nilai</th>
                </tr>
            </tfoot>
            <tbody>
            </tbody>
        </table>
    </div>
    <hr>
    <a href="{{ url('laptugasakhir/export') }}"><i class="ri-file-excel-2-line"></i> Export data</a>
</div>

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('laptugasakhir.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_tugasakhir', name: 'id_tugasakhir', visible:false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'tautan', name: 'tautan'},
            {data: 'nilai_dpl', name: 'nilai_dpl'},
            {data: 'action', name: 'action', orderable: false, searchable: false,visible:false,},
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
        },
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
