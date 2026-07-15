<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Id Lap</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Nama Perguruan Tinggi</th>
                    <th>Laporan</th>
                    <th>Nilai</th>                    
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-btn-export url="{{ url('dpllaptugasakhir/export') }}" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('dpllaptugasakhir.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
            {data: 'id_tugasakhir', name: 'id_tugasakhir', visible:false},
            {data: 'nim', name: 'nim'},
            {data: 'nama', name: 'nama'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'tautan', name: 'tautan'},
            {data: 'action', name: 'action', className: 'text-center', orderable: false, searchable: false},
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
