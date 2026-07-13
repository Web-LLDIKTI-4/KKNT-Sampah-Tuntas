<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id kpitarget</th>
                    <th>Key performance indicator</th>
                    <th>Tahapan</th>
                    <th>Target Key performance indicator</th>
                    <th>Persen</th>
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