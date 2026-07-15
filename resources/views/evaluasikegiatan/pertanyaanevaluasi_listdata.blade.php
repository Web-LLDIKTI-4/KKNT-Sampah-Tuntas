<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-sm table-bordered">
            <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>Pertanyaan</th>
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
        ajax: "{{ route('admevaluasikegiatan.pertanyaanevaluasiserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {
                data: 'pertanyaan',
                name: 'pertanyaan',
                render: function(data, type, row) {
                    // Create a temporary div element to strip HTML tags
                    var div = document.createElement("div");
                    div.innerHTML = data;
                    var text = div.textContent || div.innerText || "";
                    return text;
                }
            },                
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
    });
  });
</script>