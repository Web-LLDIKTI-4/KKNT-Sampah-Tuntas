<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-sm table-bordered" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kodept</th>
                    <th>Perguruan Tinggi</th>
                    <th>Pertanyaan</th>
                    <th>Jawaban</th>
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
                    <th>Pertanyaan</th>
                    <th>Jawaban</th>
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
        ajax: "{{ route('admevaluasikegiatan.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'kodept', name: 'kodept'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'pertanyaan', name: 'pertanyaan'},           
            {data: 'jawaban', name: 'jawaban'},                
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:false},
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