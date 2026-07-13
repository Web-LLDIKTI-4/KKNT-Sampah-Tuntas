
<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm table-bordered">
    <x-slot:thead>
                <tr>
                    <th width="1%">No</th>
                    <th>Kodept</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Alamat</th>
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
        ajax: "{{ route('perguruantinggi.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'npsn', name: 'npsn'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'jln', name: 'jln'},
           // {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
    });
  });
</script>