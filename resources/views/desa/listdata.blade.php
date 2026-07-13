<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id desa</th>
                    <th>Kecamatan</th>
                    <th>Desa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Id desa</th>
                    <th>Kecamatan</th>
                    <th>Desa</th>
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
        ajax: "{{ route('desa.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_desa', name: 'id_desa', visible:false},
            {data: 'kecamatan', name: 'kecamatan'},
            {data: 'desa', name: 'desa'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    return "<div class='text-wrap'>" + data + "</div>";
                },
                targets: 3
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
                    if (index !== 0 && index !== 4) { // Skip column "No" (index 0)
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