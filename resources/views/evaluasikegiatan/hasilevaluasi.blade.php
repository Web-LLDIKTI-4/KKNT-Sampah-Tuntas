<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm table-bordered">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Kodept</th>
                    <th>Perguruan Tinggi</th>
                    <th>Pertanyaan</th>
                    <th>Jawaban</th>
                    <th>Aksi</th>
                </tr>
            </x-slot:thead>
</x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    var table = $('#dataTable').DataTable({
        searching: true,
        lengthChange: false,
        processing: true,
        serverSide: true,
        ajax: "{{ route('admevaluasikegiatan.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'kodept', name: 'kodept'},
            {data: 'nm_lemb', name: 'nm_lemb'},
            {data: 'pertanyaan', name: 'pertanyaan'},           
            {data: 'jawaban', name: 'jawaban'},                
            {data: 'action', name: 'action', orderable: false, searchable: false, visible:false},
        ],
    });
  });
</script>