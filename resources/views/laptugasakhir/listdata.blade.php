<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th class="text-center" width="1">No</th>
                    <th class="text-center">Id Lap</th>
                    <th class="text-center">NIM</th>
                    <th class="text-center">Nama</th>
                    <th class="text-center">Perguruan Tinggi</th>
                    <th class="text-center">Laporan</th>
                    <th class="text-center" width="1">Nilai</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>

<x-button.export url="{{ url('laptugasakhir/export') }}" />

<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: "{{ route('laptugasakhir.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'id_tugasakhir', name: 'id_tugasakhir', visible:false},
            {data: 'nim', name: 'nim', className: 'text-center'},
            {data: 'nama', name: 'nama'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'tautan', name: 'tautan'},
            {data: 'nilai_dpl', name: 'nilai_dpl', className: 'text-center', orderable: false, searchable: false}
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
