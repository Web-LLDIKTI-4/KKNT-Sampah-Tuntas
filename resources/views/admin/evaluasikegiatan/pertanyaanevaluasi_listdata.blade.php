<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-sm table-bordered" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Pertanyaan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Pertanyaan</th>
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
        ajax: "{{ route('admevaluasikegiatan.pertanyaanevaluasiserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {
                data: 'pertanyaan',
                name: 'pertanyaan',
                render: function(data, type, row) {
                    // Create a temporary div element to strip HTML tags
                    var div = document.createElement("div");
                    div.innerHTML = data;
                    var text = div.textContent || div.innerText || "";
                    return text;
                }
            },                
            {data: 'action', name: 'action', orderable: false, searchable: false},
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
        }
    });
  });
</script>