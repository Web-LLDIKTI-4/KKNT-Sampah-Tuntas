<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered table-sm">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th width="35%">Pertanyaan</th>
                    <th>Jawaban</th>
                    <th width="1">Aksi</th>
                </tr>
            </x-slot:thead>
        </x-datatable>
    </div>
</div>
<script type="text/javascript">
  $(function () {
    $('#dataTable').DataTable({
        searching: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        searchDelay: 500,
        order: [],
        ajax: "{{ route('faq.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari pertanyaan atau jawaban...",
            zeroRecords: "Belum ada FAQ. Klik \"Tambah FAQ\" untuk menambah.",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'pertanyaan', name: 'pertanyaan', className: 'fw-medium text-break'},
            {data: 'jawaban', name: 'jawaban', className: 'text-break'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ]
    });
  });
</script>
