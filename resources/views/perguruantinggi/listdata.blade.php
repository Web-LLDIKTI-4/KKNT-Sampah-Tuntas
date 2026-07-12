
<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-sm table-bordered" id="dataTable">
            <thead>
                <tr>
                    <th width="1%">No</th>
                    <th>Kodept</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Alamat</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th>No</th>
                    <th>Kodept</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Alamat</th>
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
        ajax: "{{ route('perguruantinggi.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'npsn', name: 'npsn'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'jln', name: 'jln'},
           // {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        initComplete: function () {
            var table = this;
            this.api()
                .columns()
                .every(function (index) {
                    var column = this;
                    var title = column.footer().textContent;
    
                    // Create input element and add event listener
                    if (index !== 0 && index !== 5) { // Skip column "No" (index 0)
                        $('<input type="text" class="form-control form-control-sm p-1" placeholder="Search ' + title + '" />')
                            .appendTo($(column.footer()).empty())
                            .on('keyup change clear', function () {
                                if (column.search() !== this.value) {
                                    column.search(this.value).draw();
                                }
                            });
                    }
                });
        },
    });
  });
</script>