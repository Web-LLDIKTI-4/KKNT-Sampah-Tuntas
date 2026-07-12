<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered user_datatable" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Laporan</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Laporan</th>
                </tr>
            </tfoot>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('pttugasakhir.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'tugas_akhir', name: 'tugas_akhir'},
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
                    if (index !== 0 && index !== 4) { // Skip column "No" (index 0)
                        $('<input type="text" placeholder="Search ' + title + '" />')
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
