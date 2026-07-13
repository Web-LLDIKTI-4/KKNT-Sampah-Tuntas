<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id desa</th>
                    <th>Kecamatan</th>
                    <th>Desa</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
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