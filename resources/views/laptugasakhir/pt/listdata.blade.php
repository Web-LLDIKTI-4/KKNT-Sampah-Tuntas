<div class="row">
    <div class="col-12 table-responsive">
        <x-datatable id="dataTable" tableClass="table table-bordered user_datatable">
    <x-slot:thead>
                <tr>
                    <th width="1">No</th>
                    <th>NIM</th>
                    <th>Nama</th>
                    <th>Laporan</th>
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
        ajax: "{{ route('pttugasakhir.listdataserver') }}",
        language: {
            search: "",
            searchPlaceholder: "Cari...",
            zeroRecords: "Tidak ada data yang tersedia",
            infoEmpty: "Tidak ada data yang ditemukan",
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', className: 'text-center', orderable: false, searchable: false},
            {data: 'nim', name: 'nim', className: 'text-center'},
            {data: 'nama', name: 'nama'},
            {data: 'tugas_akhir', name: 'tugas_akhir'},
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
