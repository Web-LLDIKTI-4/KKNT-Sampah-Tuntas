<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id kpitarget</th>
                    <th>Key performance indicator</th>
                    <th>Tahapan</th>
                    <th>Target Key performance indicator</th>
                    <th>Persen</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Id kpitarget</th>
                    <th>Key performance indicator</th>
                    <th>Tahapan</th>
                    <th>Target Key performance indicator</th>
                    <th>Persen</th>
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
        ajax: "{{ route('kpitarget.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_target', name: 'id_target', visible:false},
            {data: 'nama_kpi', name: 'nama_kpi'},
            {data: 'tahapan', name: 'tahapan'},               
            {data: 'nama_kpitarget', name: 'nama_kpitarget'},
            {data: 'persen', name: 'persen', width:'10%'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    return "<div class='text-wrap'>" + data + "</div>";
                },
                targets: 4
            }
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