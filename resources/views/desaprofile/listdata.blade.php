<div class="row">
    <div class="col-12 table-responsive">
        <table class="table table-bordered table-sm" id="dataTable">
            <thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id profile</th>
                    <th>Tahun</th>
                    <th>Desa</th>
                    <th>Potensi</th>
                    <th>Masalah</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <th width="1">No</th>
                    <th>Id profile</th>
                    <th>Tahun</th>
                    <th>Desa</th>
                    <th>Potensi</th>
                    <th>Masalah</th>
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
        ajax: "{{ route('desaprofile.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_profile', name: 'id_profile', visible:false},
            {data: 'tahun', name: 'tahun'},
            {data: 'desa', name: 'desa'},
            {data: 'potensi', name: 'potensi'},
            {data: 'masalah', name: 'masalah'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        columnDefs: [
            {
                render: function (data, type, full, meta) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" + strippedText + "</div>";
                },
                targets: 4
            },
            {
                render: function (data, type, full, meta) {
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    // Mengambil teks dari div tersebut yang sudah bersih dari tag HTML
                    var strippedText = tempDiv.textContent || tempDiv.innerText || '';
                    return "<div class='text-wrap'>" + strippedText + "</div>";
                },
                targets: 5
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