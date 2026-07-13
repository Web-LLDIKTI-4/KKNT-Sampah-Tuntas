<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id Lap</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Perguruan Tinggi</th>
                    <th>Laporan</th>
                    <th>Nilai</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
    <hr>
    <x-btn-export url="{{ url('laptugasakhir/export') }}" />
</div>

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('laptugasakhir.listdataserver') }}",
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_tugasakhir', name: 'id_tugasakhir', visible:false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'tautan', name: 'tautan'},
            {data: 'nilai_dpl', name: 'nilai_dpl'},
            {data: 'action', name: 'action', orderable: false, searchable: false,visible:false,},
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
